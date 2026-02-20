<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Accreditation - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
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
                    <h1 class="page-title">Accreditation Status</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Academics</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Accreditation</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- Accreditation Content -->
    <?php
    $accreditation_query = "SELECT * FROM accreditation WHERE display_order >= 0 ORDER BY display_order ASC";
    $accreditation_result = $conn->query($accreditation_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title text-start">NBA Accreditation Status</h2>
                    <p class="lead text-center">Our programs are accredited by National Board of Accreditation (NBA)</p>
                </div>
            </div>
            <?php if ($accreditation_result && $accreditation_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($accr = $accreditation_result->fetch_assoc()): ?>
                        <div class="col-lg-6">
                            <div class="accreditation-card">
                                <div class="accr-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <h4><?php echo $accr['course_name']; ?></h4>
                                <div class="accr-status">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo $accr['accreditation_status']; ?>
                                </div>
                                <?php if (!empty($accr['remark'])): ?>
                                    <p class="accr-remark"><?php echo $accr['remark']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Accreditation information will be updated soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>