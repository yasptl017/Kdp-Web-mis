<?php
// Turn off timeouts for long scraping
set_time_limit(0);
ob_implicit_flush(true);
ob_end_flush();

// Input and output file paths
$inputFile  = "Schemes.csv";
$outputFile = "combined_holdings.csv";

// Check if we should start full scraping
$startScraping = isset($_GET['start_scraping']) && $_GET['start_scraping'] == '1';

// Bootstrap CSS for progress bar
echo '<!DOCTYPE html><html><head>
<title>Scheme Scraper</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
body { padding: 20px; }
.log-box { max-height: 300px; overflow-y: auto; border:1px solid #ccc; padding:10px; margin-top:10px; }
.preview-table { max-height: 400px; overflow: auto; }
.btn-start-scraping { 
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
</head><body>';

echo "<h2>Mutual Fund Scheme Scraper</h2>";

// Read input CSV
$schemes = [];
if (($handle = fopen($inputFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ","); // First row as headers
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $schemes[] = array_combine($headers, $row);
    }
    fclose($handle);
}

$totalSchemes = count($schemes);

if (!$startScraping) {
    // PREVIEW MODE - Show first scheme data
    echo "<div class='alert alert-info'>";
    echo "<h4>Preview Mode</h4>";
    echo "<p>Total schemes to process: <strong>$totalSchemes</strong></p>";
    echo "<p>Scraping first scheme to show preview...</p>";
    echo "</div>";

    if (empty($schemes)) {
        echo "<div class='alert alert-danger'>No schemes found in $inputFile</div>";
        echo "</body></html>";
        exit;
    }

    // Get first scheme
    $firstScheme = $schemes[0];
    $category = $firstScheme['Category'];
    $scheme_name = $firstScheme['scheme_name'];
    $scheme_pr_url = $firstScheme['scheme_pr_url'];

    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Preview: $scheme_name</h5></div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Category:</strong> $category</p>";
    echo "<p><strong>URL:</strong> <a href='$scheme_pr_url' target='_blank'>$scheme_pr_url</a></p>";
    
    // Fetch HTML using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $scheme_pr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
    $html = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "<div class='alert alert-danger'>❌ Network error: " . curl_error($ch) . "</div>";
        curl_close($ch);
        echo "</div></div></body></html>";
        exit;
    }
    curl_close($ch);

    // Parse HTML with DOMDocument
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Check if "Equity (0%)" exists
    $equityZero = $xpath->query("//li[contains(@class,'assettype') and contains(@class,'disabled')]");
    if ($equityZero->length > 0 && strpos($equityZero->item(0)->textContent, "Equity (0%)") !== false) {
        echo "<div class='alert alert-warning'>⚠️ This scheme has Equity (0%) - will be skipped</div>";
    } else {
        // Find equity portfolio div
        $equityDiv = $xpath->query("//div[@id='equity_tab5']//table");
        if ($equityDiv->length == 0) {
            echo "<div class='alert alert-warning'>⚠️ No equity table found</div>";
        } else {
            $table = $equityDiv->item(0);

            // Extract table headers
            $headersRow = [];
            foreach ($xpath->query(".//thead/tr/th", $table) as $th) {
                $headersRow[] = trim($th->textContent);
            }

            if (count($headersRow) < 2) {
                echo "<div class='alert alert-warning'>⚠️ Insufficient columns found</div>";
            } else {
                $months = array_slice($headersRow, 1);
                
                echo "<div class='alert alert-success'>✅ Found equity table with " . count($months) . " months of data</div>";
                
                // Display preview table
                echo "<div class='preview-table'>";
                echo "<table class='table table-striped table-sm'>";
                echo "<thead class='table-dark'>";
                echo "<tr>";
                foreach ($headersRow as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                $previewCount = 0;
                foreach ($xpath->query(".//tbody/tr", $table) as $tr) {
                    if ($previewCount >= 10) break; // Show only first 10 rows
                    
                    $tds = $xpath->query(".//td", $tr);
                    if ($tds->length < 2) continue;

                    $stock = trim($tds->item(0)->textContent);
                    if ($stock == "" || $stock == "-" || strtoupper($stock) == "N/A") continue;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($stock) . "</td>";
                    
                    for ($i = 1; $i < $tds->length; $i++) {
                        $value = trim($tds->item($i)->textContent);
                        if ($value == "" || $value == "-" || strtoupper($value) == "N/A") {
                            $value = "0";
                        }
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                    $previewCount++;
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
                
                if ($previewCount > 0) {
                    echo "<p class='text-muted'>Showing first $previewCount rows. Total rows will be extracted during full scraping.</p>";
                }
            }
        }
    }
    
    echo "</div></div>";

    // Show start scraping button
    echo "<div class='text-center mt-4'>";
    echo "<a href='?start_scraping=1' class='btn btn-start-scraping text-white'>";
    echo "🚀 Start Full Scraping ($totalSchemes schemes)";
    echo "</a>";
    echo "</div>";

} else {
    // FULL SCRAPING MODE
    echo '<button onclick="window.location.href=\'?\'" class="btn btn-secondary mb-3">🔄 Back to Preview</button>';
    echo "<div class='alert alert-info'><h4>Full Scraping Mode</h4><p>Processing all $totalSchemes schemes...</p></div>";

    // Progress bar container
    echo '<div class="progress">
      <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
    </div>';

    echo '<div class="log-box" id="log"></div>';

    echo "<script>
    function updateProgress(percent) {
        let bar = document.getElementById('progress-bar');
        bar.style.width = percent + '%';
        bar.innerText = percent + '%';
    }
    function logMessage(msg) {
        let logBox = document.getElementById('log');
        logBox.innerHTML += msg + '<br>';
        logBox.scrollTop = logBox.scrollHeight;
    }
    </script>";

    flush();

    // Output data
    $data = [];

    // Loop through each scheme
    foreach ($schemes as $index => $row) {
        $category      = $row['Category'];
        $scheme_name   = $row['scheme_name'];
        $scheme_pr_url = $row['scheme_pr_url'];

        echo "<script>logMessage('Processing " . ($index + 1) . "/$totalSchemes: $scheme_name');</script>";
        flush();

        // Fetch HTML using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scheme_pr_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
        $html = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "<script>logMessage('❌ Network error: " . addslashes(curl_error($ch)) . "');</script>";
            curl_close($ch);
            flush();
            continue;
        }
        curl_close($ch);

        // Parse HTML with DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // Check if "Equity (0%)" exists
        $equityZero = $xpath->query("//li[contains(@class,'assettype') and contains(@class,'disabled')]");
        if ($equityZero->length > 0 && strpos($equityZero->item(0)->textContent, "Equity (0%)") !== false) {
            echo "<script>logMessage('⚠️ Skipping $scheme_name (Equity 0%)');</script>";
            flush();
            continue;
        }

        // Find equity portfolio div
        $equityDiv = $xpath->query("//div[@id='equity_tab5']//table");
        if ($equityDiv->length == 0) {
            echo "<script>logMessage('⚠️ No equity table for $scheme_name');</script>";
            flush();
            continue;
        }

        $table = $equityDiv->item(0);

        // Extract table headers
        $headersRow = [];
        foreach ($xpath->query(".//thead/tr/th", $table) as $th) {
            $headersRow[] = trim($th->textContent);
        }

        if (count($headersRow) < 2) {
            echo "<script>logMessage('⚠️ Insufficient columns for $scheme_name');</script>";
            flush();
            continue;
        }
        $months = array_slice($headersRow, 1);

        // Extract stock holdings data
        $stock_count = 0;
        foreach ($xpath->query(".//tbody/tr", $table) as $tr) {
            $tds = $xpath->query(".//td", $tr);
            if ($tds->length < 2) continue;

            $stock = trim($tds->item(0)->textContent);
            if ($stock == "" || $stock == "-" || strtoupper($stock) == "N/A") continue;

            // Extract values
            $values = [];
            for ($i = 1; $i < $tds->length; $i++) {
                $value = trim($tds->item($i)->textContent);
                if ($value == "" || $value == "-" || strtoupper($value) == "N/A") {
                    $value = "0";
                } else {
                    $value = str_replace(["%", ","], "", $value);
                }
                $values[] = $value;
            }

            // Create record
            $record = [
                "Category"      => $category,
                "Scheme_name"   => $scheme_name,
                "scheme_pr_url" => $scheme_pr_url,
                "Stock"         => $stock
            ];

            foreach ($months as $i => $month) {
                $record[$month] = $values[$i] ?? "0";
            }

            $data[] = $record;
            $stock_count++;
        }

        echo "<script>logMessage('✅ Extracted $stock_count stocks from $scheme_name');</script>";

        // Update progress bar
        $percent = round((($index + 1) / $totalSchemes) * 100);
        echo "<script>updateProgress($percent);</script>";
        flush();

        sleep(1); // polite delay
    }

    // Save results to CSV
    if (!empty($data)) {
        $fp = fopen($outputFile, "w");
        fputcsv($fp, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        echo "<script>logMessage('<b>🎉 Total records extracted: " . count($data) . "</b>');</script>";
        echo "<script>logMessage('📂 Saved to $outputFile');</script>";
        echo "<div class='alert alert-success mt-3'><h4>Scraping Complete!</h4>";
        echo "<p>Successfully processed $totalSchemes schemes and extracted " . count($data) . " stock records.</p>";
        echo "<p>Results saved to: <strong>$outputFile</strong></p>";
        echo "<a href='$outputFile' class='btn btn-success' download>📥 Download CSV</a>";
        echo "</div>";
    } else {
        echo "<script>logMessage('<b>⚠️ No data was extracted.</b>');</script>";
        echo "<div class='alert alert-warning mt-3'><p>No data was extracted from any schemes.</p></div>";
    }
}

echo "</body></html>";
?>