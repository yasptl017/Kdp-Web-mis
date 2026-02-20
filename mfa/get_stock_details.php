<?php
header('Content-Type: application/json');

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

// Function to get stock details with scheme-wise breakdown
function getStockDetails($data, $stock, $category, $selectedSchemes = null) {
    $stockData = [];
    $monthColumns = getMonthColumns($data);
    
    foreach ($data as $row) {
        $rowStock = $row['Stock'] ?? $row['stock'] ?? '';
        $rowCategory = $row['Category'] ?? 'Unknown';
        $schemeName = $row['Scheme_name'] ?? 'Unknown Scheme';
        $schemeUrl = $row['scheme_pr_url'] ?? null;
        $threeYReturn = $row['3Y Return (%)'] ?? null;
        
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
            $schemeData = [];
            foreach ($monthColumns as $month) {
                $value = $row[$month] ?? '';
                if ($value === '' || $value === '-' || $value === null) {
                    $schemeData[$month] = null;
                } else {
                    $cleanValue = str_replace('%', '', $value);
                    if (is_numeric($cleanValue)) {
                        $schemeData[$month] = floatval($cleanValue);
                    } else {
                        $schemeData[$month] = $value;
                    }
                }
            }

            // Clean 3Y Return if numeric
            if ($threeYReturn !== null) {
                $threeYReturn = str_replace('%', '', $threeYReturn);
                $threeYReturn = is_numeric($threeYReturn) ? floatval($threeYReturn) : $threeYReturn;
            }

            $stockData[] = [
                'name' => $schemeName,
                'category' => $rowCategory,
                'threeYReturn' => $threeYReturn,   // ✅ Added
                'schemeUrl' => $schemeUrl,        // ✅ Added
                'data' => $schemeData
            ];
        }
    }
    
    // Sort by scheme name
    usort($stockData, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return [
        'schemes' => $stockData,
        'months' => $monthColumns
    ];
}

// Main execution
try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get parameters
    $stock = $_POST['stock'] ?? '';
    $category = $_POST['category'] ?? 'ALL';
    $schemesParam = $_POST['schemes'] ?? '';
    
    if (empty($stock)) {
        throw new Exception('Stock parameter is required');
    }
    
    // Parse selected schemes
    $selectedSchemes = null;
    if (!empty($schemesParam)) {
        $selectedSchemes = explode(',', $schemesParam);
        $selectedSchemes = array_map('trim', $selectedSchemes);
        $selectedSchemes = array_filter($selectedSchemes); // Remove empty values
    }
    
    // Read CSV file
    $csvFile = 'combined_holdings.csv';
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file '$csvFile' not found");
    }
    
    $data = readCSV($csvFile);
    if (empty($data)) {
        throw new Exception('No data found in CSV file');
    }
    
    // Get stock details
    $stockDetails = getStockDetails($data, $stock, $category, $selectedSchemes);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $stockDetails,
        'message' => 'Stock details retrieved successfully'
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => $e->getMessage()
    ]);
}
