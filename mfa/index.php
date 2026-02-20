<?php
// Function to read CSV file
function readCSV($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ",");
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}

// Function to convert percentage string to float
function percentageToFloat($percentage) {
    if ($percentage === '' || $percentage === '-' || $percentage === null) {
        return null;
    }
    return floatval(str_replace('%', '', $percentage));
}

// Function to extract and sort month columns chronologically
function getMonthColumns($data) {
    if (empty($data)) return [];
    
    $monthColumns = [];
    foreach ($data[0] as $key => $value) {
        // Match month-year pattern (e.g., "Jul-24", "Jan 2025", "Jul 2025")
        if (preg_match('/^[A-Z][a-z]{2}[-\s]\d{2,4}$/', $key) || 
            preg_match('/^[A-Z][a-z]{2}\s\d{4}$/', $key)) {
            $monthColumns[] = $key;
        }
    }
    
    // Sort months chronologically (oldest to newest)
    usort($monthColumns, function($a, $b) {
        $dateA = convertToDate($a);
        $dateB = convertToDate($b);
        return $dateA <=> $dateB;
    });
    
    return $monthColumns;
}

// Helper function to convert month string to comparable date
function convertToDate($monthStr) {
    // Handle different formats: "Jul-24", "Jul 2024", "Jul 2025"
    $monthStr = str_replace(['-', ' '], '-', $monthStr);
    $parts = explode('-', $monthStr);
    
    if (count($parts) === 2) {
        $month = $parts[0];
        $year = $parts[1];
        
        // Convert 2-digit year to 4-digit
        if (strlen($year) === 2) {
            $year = (intval($year) > 50) ? "19" . $year : "20" . $year;
        }
        
        return DateTime::createFromFormat('M-Y', $month . '-' . $year);
    }
    
    return new DateTime('1900-01-01'); // Fallback date
}

// Function to get all schemes for a specific category
function getSchemesByCategory($data, $category) {
    $schemes = [];
    foreach ($data as $row) {
        $rowCategory = $row['Category'] ?? 'Unknown';
        if ($category === 'ALL' || $rowCategory === $category) {
            $schemeName = $row['Scheme_name'] ?? $row['Scheme_name'] ?? 'Unknown Scheme';
            if (!in_array($schemeName, $schemes)) {
                $schemes[] = $schemeName;
            }
        }
    }
    sort($schemes);
    return $schemes;
}

// Function to get stock details with scheme-wise breakdown
function getStockDetails($data, $stock, $category, $selectedSchemes = null) {
    $stockData = [];
    $monthColumns = getMonthColumns($data);
    
    foreach ($data as $row) {
        $rowStock = $row['Stock'] ?? $row['stock'] ?? '';
        $rowCategory = $row['Category'] ?? 'Unknown';
        $schemeName = $row['scheme_name'] ?? $row['scheme_name'] ?? 'Unknown Scheme';
        $threeYReturn = $row['3Y Return (%)'] ?? null;
        $schemeUrl = $row['scheme_pr_url'] ?? null;
        
        // Filter by category
        if ($category !== 'ALL' && $rowCategory !== $category) {
            continue;
        }
        
        // Filter by selected schemes if provided
        if ($selectedSchemes !== null && !in_array($schemeName, $selectedSchemes)) {
            continue;
        }
        
        // Check if this row contains the target stock
        if ($rowStock === $stock) {
            $stockData[] = [
                'scheme' => $schemeName,
                'category' => $rowCategory,
                'threeYReturn' => $threeYReturn,  // ✅ Added
                'schemeUrl' => $schemeUrl,        // ✅ Added
                'data' => $row
            ];
        }
    }
    
    return $stockData;
}


// Function to calculate consolidated month differences for all stocks by category
function calculateConsolidatedDifferencesByCategory($data, $maxPeriods = 10, $selectedSchemes = null) {
    // Extract and sort month columns
    $monthColumns = getMonthColumns($data);
    
    // Get recent periods only (reverse to show newest first)
    $recentMonthColumns = array_slice($monthColumns, -($maxPeriods + 1));
    
    // Group data by category and then by stock
    $categoryData = [];
    $allStocksData = []; // For ALL category
    
    foreach ($data as $row) {
        $category = $row['Category'] ?? 'Unknown';
        $stock = $row['Stock'] ?? $row['stock'] ?? 'Unknown Stock';
        $schemeName = $row['Scheme_name'] ?? $row['Scheme_name'] ?? 'Unknown Scheme';
        
        // Filter by selected schemes if provided
        if ($selectedSchemes !== null && !in_array($schemeName, $selectedSchemes)) {
            continue;
        }
        
        // Add to specific category
        if (!isset($categoryData[$category])) {
            $categoryData[$category] = [];
        }
        if (!isset($categoryData[$category][$stock])) {
            $categoryData[$category][$stock] = [];
        }
        $categoryData[$category][$stock][] = $row;
        
        // Add to ALL category
        if (!isset($allStocksData[$stock])) {
            $allStocksData[$stock] = [];
        }
        $allStocksData[$stock][] = $row;
    }
    
    // Add ALL category to the beginning
    $finalCategoryData = ['ALL' => $allStocksData] + $categoryData;
    
    $results = [];
    $periodHeaders = [];
    
    // Generate period headers (newest to oldest for display)
    $periods = [];
    for ($i = 1; $i < count($recentMonthColumns); $i++) {
        $prevMonth = $recentMonthColumns[$i-1];
        $currentMonth = $recentMonthColumns[$i];
        $periods[] = $prevMonth . ' to ' . $currentMonth;
    }
    
    // Reverse to show newest first
    $periodHeaders = array_reverse($periods);
    
    // Calculate differences for each category (including ALL)
    foreach ($finalCategoryData as $category => $stockData) {
        $results[$category] = [];
        
        // Sort stocks alphabetically within category
        ksort($stockData);
        
        foreach ($stockData as $stock => $schemes) {
            $results[$category][$stock] = [];
            
            // Calculate for each period (newest to oldest)
            foreach ($periodHeaders as $period) {
                $allDifferences = [];
                
                // Extract months from period string
                $periodParts = explode(' to ', $period);
                $prevMonth = $periodParts[0];
                $currentMonth = $periodParts[1];
                
                // Calculate difference for each scheme that holds this stock
                foreach ($schemes as $scheme) {
                    $prevValue = percentageToFloat($scheme[$prevMonth] ?? '');
                    $currentValue = percentageToFloat($scheme[$currentMonth] ?? '');
                    
                    if ($prevValue !== null && $currentValue !== null) {
                        $difference = $currentValue - $prevValue;
                        $allDifferences[] = $difference;
                    }
                }
                
                // Calculate average difference for this period
                if (count($allDifferences) > 0) {
                    $avgDifference = array_sum($allDifferences) / count($allDifferences);
                    $results[$category][$stock][$period] = round($avgDifference, 2);
                } else {
                    $results[$category][$stock][$period] = null;
                }
            }
        }
    }
    
    return ['results' => $results, 'periods' => $periodHeaders, 'categories' => array_keys($finalCategoryData)];
}

// Function to filter stocks based on positive/negative conditions for a specific category
function filterStocksByCondition($categoryStocks, $totalPeriods, $targetCount, $condition) {
    $filteredResults = [];
    
    foreach ($categoryStocks as $stock => $periods) {
        // Get the most recent periods
        $recentPeriods = array_slice($periods, 0, $totalPeriods, true);
        
        $positiveCount = 0;
        $negativeCount = 0;
        $validPeriods = 0;
        
        foreach ($recentPeriods as $period => $value) {
            if ($value !== null) {
                $validPeriods++;
                if ($value > 0) {
                    $positiveCount++;
                } elseif ($value < 0) {
                    $negativeCount++;
                }
            }
        }
        
        // Only consider stocks that have data for the requested number of periods
        if ($validPeriods >= $totalPeriods) {
            if ($condition === 'positive' && $positiveCount >= $targetCount) {
                $filteredResults[$stock] = $periods;
            } elseif ($condition === 'negative' && $negativeCount >= $targetCount) {
                $filteredResults[$stock] = $periods;
            }
        }
    }
    
    return $filteredResults;
}

// Main execution
$csvFile = 'combined_holdings.csv';

if (!file_exists($csvFile)) {
    echo "<p style='color: red;'>Error: CSV file '$csvFile' not found!</p>\n";
    echo "<p>Please make sure the CSV file exists in the same directory as this PHP script.</p>\n";
    exit;
}

$data = readCSV($csvFile);

if (empty($data)) {
    echo "<p style='color: red;'>Error: No data found in CSV file or file is empty!</p>\n";
    exit;
}

// Get all month columns for later use
$allMonthColumns = getMonthColumns($data);

// Get parameters from URL
$selectedCategory = $_GET['category'] ?? 'ALL'; // Default to ALL
$filterType = $_GET['filter'] ?? '';
$totalPeriods = intval($_GET['total_periods'] ?? 5);
$targetCount = intval($_GET['target_count'] ?? 4);

// Handle scheme filtering
$selectedSchemes = null;
if (!empty($_GET['schemes'])) {
    $selectedSchemes = explode(',', $_GET['schemes']);
}

// Get all schemes for the selected category
$allSchemes = getSchemesByCategory($data, $selectedCategory);

$analysis = calculateConsolidatedDifferencesByCategory($data, 50, $selectedSchemes);
$results = $analysis['results'];
$periods = $analysis['periods'];
$categories = $analysis['categories'];

// Apply filters if requested
$selectedResults = $results[$selectedCategory] ?? [];
$displayTitle = htmlspecialchars($selectedCategory);

if ($filterType === 'positive') {
    $selectedResults = filterStocksByCondition($selectedResults, $totalPeriods, $targetCount, 'positive');
    $displayTitle = htmlspecialchars($selectedCategory) . " - Stocks with $targetCount+ positive periods out of recent $totalPeriods periods";
} elseif ($filterType === 'negative') {
    $selectedResults = filterStocksByCondition($selectedResults, $totalPeriods, $targetCount, 'negative');
    $displayTitle = htmlspecialchars($selectedCategory) . " - Stocks with $targetCount+ negative periods out of recent $totalPeriods periods";
}

// Add scheme filter info to title
if ($selectedSchemes !== null) {
    $schemeCount = count($selectedSchemes);
    $displayTitle .= " (Filtered by $schemeCount schemes)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutual Fund Portfolio Analysis</title>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    
    <!-- Select2 for multi-select -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: white;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        /* Category Selection */
        .category-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .category-section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .category-links a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 5px 10px;
            background: white;
            color: #007bff;
            text-decoration: none;
            border: 1px solid #007bff;
            font-size: 13px;
        }
        
        .category-links a:hover,
        .category-links a.active {
            background: #007bff;
            color: white;
        }
        
        /* Special styling for ALL category */
        .category-links a.all-category {
            background: #28a745;
            border-color: #28a745;
            color: white;
            font-weight: bold;
        }
        
        .category-links a.all-category:hover {
            background: #1e7e34;
        }
        
        .category-links a.all-category.active {
            background: #1e7e34;
            border-color: #1e7e34;
        }
        
        .stock-count {
            background: #666;
            color: white;
            padding: 1px 5px;
            margin-left: 3px;
            font-size: 11px;
        }
        
        .category-links a.all-category .stock-count {
            background: rgba(0,0,0,0.3);
        }
        
        /* Scheme Filter Section */
        .scheme-filter-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background: #f0f8ff;
        }
        
        .scheme-filter-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        
        .scheme-filter-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .scheme-select-container {
            flex: 1;
            min-width: 300px;
        }
        
        .scheme-filter-controls button {
            padding: 5px 15px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        
        .scheme-filter-controls button:hover {
            background: #0056b3;
        }
        
        .scheme-filter-controls .clear-btn {
            background: #6c757d;
        }
        
        .scheme-filter-controls .clear-btn:hover {
            background: #545b62;
        }
        
        /* Select2 customization */
        .select2-container {
            font-size: 13px;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ccc;
            border-radius: 4px;
            min-height: 32px;
        }
        
        /* Filter Section */
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .filter-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-form label {
            font-weight: bold;
            font-size: 13px;
        }
        
        .filter-form input[type="number"] {
            width: 60px;
            padding: 5px;
            border: 1px solid #ccc;
            font-size: 13px;
        }
        
        .filter-form button {
            padding: 5px 15px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        
        .filter-form button:hover {
            background: #0056b3;
        }
        
        .filter-form .reset-btn {
            background: #6c757d;
        }
        
        .filter-form .reset-btn:hover {
            background: #545b62;
        }
        
        /* DataTables - Remove default styling */
        .dataTables_wrapper {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate {
            margin: 10px 0;
        }
        
        .dataTables_filter input {
            padding: 5px;
            border: 1px solid #ccc;
            margin-left: 5px;
        }
        
        .dataTables_length select {
            padding: 2px;
            border: 1px solid #ccc;
        }
        
        /* Simple table styling */
        table.dataTable {
            border-collapse: collapse;
            width: 100%;
            margin: 0;
            border: 1px solid #ddd;
        }
        
        table.dataTable thead th {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        
        table.dataTable tbody td {
            border: 1px solid #ddd;
            padding: 5px 3px;
            text-align: center;
            font-size: 11px;
        }
        
        /* Stock name column */
        .stock-name {
            text-align: left !important;
            font-weight: bold;
            background: #f9f9f9 !important;
            min-width: 150px;
            cursor: pointer;
        }
        
        .stock-name:hover {
            background: #e6f2ff !important;
            color: #007bff;
        }
        
        /* Color scheme - Blue and Red only */
        .positive {
            color: #0066cc !important;
            background: #e6f2ff !important;
            font-weight: bold;
        }
        
        .negative {
            color: #cc0000 !important;
            background: #ffe6e6 !important;
            font-weight: bold;
        }
        
        .no-data {
            color: #999 !important;
            background: #f5f5f5 !important;
        }
        
        /* Remove DataTables default styling */
        table.dataTable thead th,
        table.dataTable tbody td {
            box-sizing: border-box;
        }
        
        table.dataTable.no-footer {
            border-bottom: 1px solid #ddd;
        }
        
        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc {
            cursor: pointer;
        }
        
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:after {
            opacity: 0.5;
            font-size: 8px;
        }
        
        /* Pagination */
        .dataTables_paginate .paginate_button {
            padding: 5px 10px;
            margin: 0 2px;
            border: 1px solid #ddd;
            background: white;
            color: #007bff !important;
            text-decoration: none;
        }
        
        .dataTables_paginate .paginate_button:hover {
            background: #007bff;
            color: white !important;
        }
        
        .dataTables_paginate .paginate_button.current {
            background: #007bff;
            color: white !important;
        }
        
        /* Info section */
        .info {
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f8ff;
            border: 1px solid #ccc;
            font-size: 13px;
        }
        
        .legend {
            margin-top: 10px;
        }
        
        .legend span {
            display: inline-block;
            margin-right: 20px;
        }
        
        .legend .color-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-right: 5px;
            border: 1px solid #999;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 95%;
            height: 90%;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-bottom: none;
            position: relative;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .modal-header .stock-info {
            margin-top: 5px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .close {
            color: white;
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .close:hover {
            background-color: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 20px;
            height: calc(100% - 100px);
            overflow: auto;
        }
        
        /* Modal table styles */
        .modal-body table.dataTable {
            font-size: 11px;
        }
        
        .modal-body table.dataTable thead th {
            padding: 6px 3px;
            font-size: 10px;
        }
        
        .modal-body table.dataTable tbody td {
            padding: 4px 2px;
            font-size: 10px;
        }
        
        .scheme-name-col {
            text-align: left !important;
            font-weight: bold;
            background: #f9f9f9 !important;
            min-width: 200px;
        }
        
        .category-col {
            text-align: left !important;
            background: #f0f8ff !important;
            font-weight: normal;
            min-width: 100px;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .scheme-filter-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .scheme-select-container {
                width: 100%;
            }
            
            table.dataTable {
                font-size: 10px;
            }
            
            table.dataTable thead th,
            table.dataTable tbody td {
                padding: 3px 2px;
            }
            
            .modal-content {
                width: 98%;
                height: 95%;
                margin: 1% auto;
            }
            
            .modal-body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mutual Fund Portfolio Analysis</h1>
        
        <!-- Category Selection -->
        <div class="category-section">
            <h3>Select Category:</h3>
            <div class="category-links">
                <?php foreach ($categories as $category): ?>
                    <?php 
                    $stockCount = count($results[$category] ?? []);
                    $isActive = ($category === $selectedCategory && empty($filterType)) ? 'active' : '';
                    $isAllCategory = ($category === 'ALL');
                    $categoryClass = $isAllCategory ? 'all-category' : '';
                    ?>
                    <a href="?category=<?php echo urlencode($category); ?>" class="<?php echo $isActive . ' ' . $categoryClass; ?>">
                        <?php if ($isAllCategory): ?>
                            🌟 <?php echo htmlspecialchars($category); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($category); ?>
                        <?php endif; ?>
                        <span class="stock-count"><?php echo $stockCount; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Scheme Filter Section -->
        <div class="scheme-filter-section">
            <h3>Filter by Schemes (<?php echo count($allSchemes); ?> schemes available):</h3>
            <div class="scheme-filter-controls">
                <div class="scheme-select-container">
                    <select id="schemeSelect" multiple="multiple" style="width: 100%;">
                        <?php foreach ($allSchemes as $scheme): ?>
                            <?php $isSelected = ($selectedSchemes !== null && in_array($scheme, $selectedSchemes)) ? 'selected' : ''; ?>
                            <option value="<?php echo htmlspecialchars($scheme); ?>" <?php echo $isSelected; ?>>
                                <?php echo htmlspecialchars($scheme); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="applySchemeFilter()">Apply Filter</button>
                <button class="clear-btn" onclick="clearSchemeFilter()">Clear Filter</button>
            </div>
            <?php if ($selectedSchemes !== null): ?>
            <div style="margin-top: 10px; font-size: 13px; color: #666;">
                <strong>Active Filter:</strong> <?php echo count($selectedSchemes); ?> scheme(s) selected
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3>Smart Filters for <?php echo htmlspecialchars($selectedCategory); ?>:</h3>
            <div class="filter-form">
                <label for="total_periods">Total Periods:</label>
                <input type="number" id="total_periods" name="total_periods" value="<?php echo $totalPeriods; ?>" min="1" max="20">
                
                <label for="target_count">Target Count:</label>
                <input type="number" id="target_count" name="target_count" value="<?php echo $targetCount; ?>" min="1" max="20">
                
                <button onclick="applyFilter('positive')">Show Positive Stocks</button>
                <button onclick="applyFilter('negative')">Show Negative Stocks</button>
                <button class="reset-btn" onclick="resetFilters()">Reset Filters</button>
            </div>
        </div>

        <?php if (!empty($selectedResults)): ?>
        <!-- Info -->
        <div class="info">
            <strong>Showing:</strong> <?php echo $displayTitle; ?> | 
            <strong>Stocks:</strong> <?php echo count($selectedResults); ?> | 
            <strong>Periods:</strong> <?php echo count($periods); ?>
            <?php if ($selectedCategory === 'ALL'): ?>
            | <strong>📊 All Categories Combined</strong>
            <?php endif; ?>
            
            <div class="legend">
                <span><span class="color-box" style="background: #e6f2ff; border-color: #0066cc;"></span>Positive (Blue)</span>
                <span><span class="color-box" style="background: #ffe6e6; border-color: #cc0000;"></span>Negative (Red)</span>
                <span><span class="color-box" style="background: #f5f5f5;"></span>No Data</span>
                <span style="margin-left: 20px; color: #007bff; font-weight: bold;">💡 Click on any stock name to view detailed breakdown</span>
            </div>
        </div>

        <!-- Data Table -->
        <table id="stockTable" class="display">
            <thead>
                <tr>
                    <th>Stock Name</th>
                    <?php foreach ($periods as $period): ?>
                        <th title="<?php echo htmlspecialchars($period); ?>">
                            <?php 
                            $shortPeriod = str_replace([' to ', '-20'], [' → ', '-'], $period);
                            echo htmlspecialchars($shortPeriod); 
                            ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($selectedResults as $stock => $stockData): ?>
                    <tr>
                        <td class="stock-name" onclick="showStockDetails('<?php echo htmlspecialchars($stock, ENT_QUOTES); ?>')" title="Click to view detailed breakdown">
                            <?php echo htmlspecialchars($stock); ?>
                        </td>
                        <?php foreach ($periods as $period): ?>
                            <?php
                            $value = $stockData[$period] ?? null;
                            
                            if ($value === null) {
                                echo '<td class="no-data" data-order="-999">-</td>';
                            } else {
                                $class = '';
                                $displayValue = '';
                                
                                if ($value > 0) {
                                    $class = 'positive';
                                    $displayValue = '+' . $value . '%';
                                } elseif ($value < 0) {
                                    $class = 'negative';
                                    $displayValue = $value . '%';
                                } else {
                                    $class = 'no-data';
                                    $displayValue = '0.00%';
                                }
                                
                                echo '<td class="' . $class . '" data-order="' . $value . '">' . $displayValue . '</td>';
                            }
                            ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
        <div style="text-align: center; padding: 30px;">
            <h3>No Data Available</h3>
            <p>No stock data found<?php echo $filterType ? ' matching the filter criteria' : ' for category: <strong>' . htmlspecialchars($selectedCategory) . '</strong>'; ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stock Details Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeStockModal()">&times;</span>
                <h2 id="modalStockName">Stock Details</h2>
                <div class="stock-info" id="modalStockInfo">Loading...</div>
            </div>
            <div class="modal-body">
                <div class="loading-spinner" id="modalLoading">
                    <div class="spinner"></div>
                </div>
                <div id="modalContent" style="display: none;">
                    <table id="stockDetailTable" class="display" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Scheme Name</th>
                                <th>Category</th>
                                <!-- Month columns will be dynamically added here -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let stockDetailTable = null;
        
        $(document).ready(function() {
            // Initialize main stock table
            $('#stockTable').DataTable({
                "pageLength": 50,
                "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
                "order": [[0, "asc"]],
                "scrollX": true,
                "autoWidth": false,
                "columnDefs": [
                    {
                        "targets": 0,
                        "type": "string",
                        "className": "stock-name"
                    },
                    {
                        "targets": "_all",
                        "type": "num",
                        "render": function(data, type, row) {
                            if (type === 'sort') {
                                if (data === '-') return -999;
                                var numericValue = parseFloat(data.replace(/[+%]/g, ''));
                                return isNaN(numericValue) ? -999 : numericValue;
                            }
                            return data;
                        }
                    }
                ],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });

            // Initialize scheme selector
            $('#schemeSelect').select2({
                placeholder: "Select schemes to filter (leave empty for all schemes)",
                allowClear: true,
                closeOnSelect: false
            });
        });

        function showStockDetails(stockName) {
            $('#stockModal').show();
            $('#modalStockName').text(stockName);
            $('#modalStockInfo').text('Loading scheme details...');
            $('#modalLoading').show();
            $('#modalContent').hide();
            
            // Destroy existing DataTable if it exists
            if (stockDetailTable !== null) {
                stockDetailTable.destroy();
                stockDetailTable = null;
            }
            
            // Get current filter parameters
            const category = '<?php echo $selectedCategory; ?>';
            const schemes = getSelectedSchemes();
            
            // Fetch stock details via AJAX
            $.ajax({
                url: 'get_stock_details.php',
                method: 'POST',
                data: {
                    stock: stockName,
                    category: category,
                    schemes: schemes
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayStockDetails(response.data, stockName);
                    } else {
                        $('#modalLoading').hide();
                        $('#modalContent').html('<div style="text-align: center; padding: 50px;">Error: ' + response.message + '</div>').show();
                    }
                },
                error: function() {
                    $('#modalLoading').hide();
                    $('#modalContent').html('<div style="text-align: center; padding: 50px;">Error loading stock details. Please try again.</div>').show();
                }
            });
        }

    function displayStockDetails(data, stockName) {
    if (data.schemes.length === 0) {
        $('#modalLoading').hide();
        $('#modalContent').html('<div style="text-align: center; padding: 50px;">No scheme data found for this stock.</div>').show();
        return;
    }

    // Update modal header info
    $('#modalStockInfo').text(`${data.schemes.length} scheme(s) holding this stock`);

    // Clear existing table content
    const table = $('#stockDetailTable');
    const thead = table.find('thead tr');
    const tbody = table.find('tbody');
    
    // Clear existing month columns (keep first two columns)
    thead.find('th:gt(1)').remove();
    tbody.empty();

    // ✅ Add new header only for 3Y Return
    thead.append('<th>3Y Return (%)</th>');

    // Add month columns to header (recent first)
    data.months.slice().reverse().forEach(function(month) {
        const shortMonth = month.replace(/[-\s]20/, '-').replace(/[-\s]19/, '-');
        thead.append(`<th title="${month}">${shortMonth}</th>`);
    });

    // Add data rows
    data.schemes.forEach(function(scheme) {
        let row = `<tr>
            <td class="scheme-name-col">${scheme.name}</td>
            <td class="category-col">${scheme.category}</td>
            <td>${scheme.threeYReturn !== null ? scheme.threeYReturn : '-'}</td>`;
        
        // ✅ Reverse months for data too
        data.months.slice().reverse().forEach(function(month) {
            const value = scheme.data[month];
            if (value === null || value === undefined || value === '' || value === '-') {
                row += '<td class="no-data">-</td>';
            } else {
                const numValue = parseFloat(value.toString().replace('%', ''));
                if (!isNaN(numValue)) {
                    if (numValue > 0) {
                        row += `<td class="positive">${numValue}%</td>`;
                    } else if (numValue < 0) {
                        row += `<td class="negative">${numValue}%</td>`;
                    } else {
                        row += `<td class="no-data">0.00%</td>`;
                    }
                } else {
                    row += `<td class="no-data">${value}</td>`;
                }
            }
        });
        
        row += '</tr>';
        tbody.append(row);
    });

    // Hide loading and show content
    $('#modalLoading').hide();
    $('#modalContent').show();

    // Initialize DataTable for stock details
    stockDetailTable = $('#stockDetailTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"]],
        "scrollX": true,
        "autoWidth": false,
        "columnDefs": [
            {
                "targets": [0, 1],
                "type": "string"
            },
            {
                "targets": "_all",
                "type": "num",
                "render": function(data, type, row) {
                    if (type === 'sort') {
                        if (data === '-') return -999;
                        var numericValue = parseFloat(data.replace(/[+%]/g, ''));
                        return isNaN(numericValue) ? -999 : numericValue;
                    }
                    return data;
                }
            }
        ],
        "language": {
            "search": "Search schemes:",
            "lengthMenu": "Show _MENU_ schemes",
            "info": "Showing _START_ to _END_ of _TOTAL_ schemes",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
}


        function closeStockModal() {
            $('#stockModal').hide();
            if (stockDetailTable !== null) {
                stockDetailTable.destroy();
                stockDetailTable = null;
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('stockModal');
            if (event.target === modal) {
                closeStockModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeStockModal();
            }
        });

        function getSelectedSchemes() {
            const selected = $('#schemeSelect').val();
            return selected ? selected.join(',') : '';
        }

        function applySchemeFilter() {
            const selectedSchemes = getSelectedSchemes();
            const currentUrl = new URL(window.location);
            
            if (selectedSchemes) {
                currentUrl.searchParams.set('schemes', selectedSchemes);
            } else {
                currentUrl.searchParams.delete('schemes');
            }
            
            window.location.href = currentUrl.toString();
        }

        function clearSchemeFilter() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.delete('schemes');
            window.location.href = currentUrl.toString();
        }

        function applyFilter(filterType) {
            const totalPeriods = document.getElementById('total_periods').value;
            const targetCount = document.getElementById('target_count').value;
            const currentCategory = '<?php echo urlencode($selectedCategory); ?>';
            const selectedSchemes = getSelectedSchemes();
            
            if (parseInt(targetCount) > parseInt(totalPeriods)) {
                alert('Target count cannot be greater than total periods!');
                return;
            }
            
            let url = `?category=${currentCategory}&filter=${filterType}&total_periods=${totalPeriods}&target_count=${targetCount}`;
            
            if (selectedSchemes) {
                url += `&schemes=${encodeURIComponent(selectedSchemes)}`;
            }
            
            window.location.href = url;
        }

        function resetFilters() {
            const currentCategory = '<?php echo urlencode($selectedCategory); ?>';
            const selectedSchemes = getSelectedSchemes();
            
            let url = `?category=${currentCategory}`;
            
            if (selectedSchemes) {
                url += `&schemes=${encodeURIComponent(selectedSchemes)}`;
            }
            
            window.location.href = url;
        }

        // Update target count max value when total periods changes
        document.getElementById('total_periods').addEventListener('input', function() {
            const totalPeriods = this.value;
            const targetCountInput = document.getElementById('target_count');
            targetCountInput.max = totalPeriods;
            
            if (parseInt(targetCountInput.value) > parseInt(totalPeriods)) {
                targetCountInput.value = totalPeriods;
            }
        });
    </script>
</body>
</html>