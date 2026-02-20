<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Vision & Mission - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="about-pages.css">
<body>
    <?php include_once "../Admin/dbconfig.php"; ?>
    
    <!-- Top Info Bar -->
    <?php include '../assets/preload/topbar.php'; ?>

    <!-- Header -->
    <?php include '../assets/preload/header.php'; ?>

    <!-- Navigation -->
    <?php include '../assets/preload/navigation.php'; ?>

    <!-- Mobile Navigation -->
    <?php include '../assets/preload/mobilenav.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Vision & Mission</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Vision & Mission</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision & Mission Content -->
    <?php
    $college_query = "SELECT * FROM college_details LIMIT 1";
    $college_result = $conn->query($college_query);
    $college = $college_result->fetch_assoc();
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <!-- Vision -->
                <div class="col-lg-6">
                    <div class="vm-card">
                        <div class="vm-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h2 class="vm-title">Our Vision</h2>
                        <div class="vm-content">
                            <?php echo $college['vision']; ?>
                        </div>
                    </div>
                </div>

                <!-- Mission -->
                <div class="col-lg-6">
                    <div class="vm-card">
                        <div class="vm-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h2 class="vm-title">Our Mission</h2>
                        <div class="vm-content">
                            <?php echo $college['mission']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
