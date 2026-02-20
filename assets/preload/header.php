<?php
// ===============================
// Fetch college details
// ===============================
$sql = "SELECT * FROM college_details LIMIT 1";
$result = $conn->query($sql);
$college = $result->fetch_assoc();

$college_name = $college['college_name'] ?? '';
$alt_name     = $college['college_alt_name'] ?? '';
$logo1        = $college['logo_1'] ?? '';
$logo2        = $college['logo_2'] ?? '';

// ===============================
// Ensure BASE_URL exists
// ===============================
if (!defined('BASE_URL')) {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $basePath = rtrim(str_replace(basename($scriptName), '', $scriptName), '/');
    define('BASE_URL', $basePath !== '' ? $basePath . '/' : '/');
}

// ===============================
// Build absolute image paths
// ===============================
$logo1_path = !empty($logo1)
    ? BASE_URL . 'Admin/' . ltrim($logo1, '/')
    : 'https://via.placeholder.com/100/1e3a8a/FFFFFF?text=Logo1';

$logo2_path = !empty($logo2)
    ? BASE_URL . 'Admin/' . ltrim($logo2, '/')
    : 'https://via.placeholder.com/100/f97316/FFFFFF?text=Logo2';
?>

<!-- ===============================
     Header Section
================================ -->
<header class="site-header">
    <div class="container">

        <!-- Desktop Header -->
        <div class="row align-items-center d-none d-lg-flex py-4">

            <!-- Left Logo -->
            <div class="col-lg-2">
                <div class="logo-wrapper">
                    <img src="<?= $logo1_path ?>"
                         alt="College Logo"
                         class="header-logo">
                </div>
            </div>

            <!-- College Name -->
            <div class="col-lg-8 text-center">
                <h1 class="college-name-main"><?= htmlspecialchars($college_name) ?></h1>
              
            </div>

            <!-- Right Logo -->
            <div class="col-lg-2">
                <div class="logo-wrapper">
                    <img src="<?= $logo2_path ?>"
                         alt="Affiliation Logo"
                         class="header-logo">
                </div>
            </div>

        </div>

        <!-- Mobile Header -->
        <div class="row d-lg-none py-3">
            <div class="col-12 text-center">

                <div class="logo-wrapper mb-3">
                    <img src="<?= $logo1_path ?>"
                         alt="College Logo"
                         class="header-logo-mobile">
                </div>

                <h1 class="college-name-mobile"><?= htmlspecialchars($alt_name) ?></h1>
                <p class="college-tagline-mobile">Excellence in Technical Education</p>

            </div>
        </div>

    </div>
</header>
