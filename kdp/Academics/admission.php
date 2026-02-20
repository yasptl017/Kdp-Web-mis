<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Admission Details - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<body>

    <?php include_once "../Admin/dbconfig.php"; ?>

    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Admission Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="../index.php">Home</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="#">Academics</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Admission Details
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Admission Content -->
    <?php
        $admission_query = "
            SELECT * 
            FROM admissions 
            WHERE display_order >= 0
            ORDER BY display_order ASC, id DESC
        ";
        $admission_result = $conn->query($admission_query);
    ?>

    <section class="py-5 bg-white">
        <div class="container">

            <?php if ($admission_result && $admission_result->num_rows > 0): ?>

                <?php while ($admission = $admission_result->fetch_assoc()): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="section-title text-start">
                                <?php echo htmlspecialchars($admission['title']); ?>
                            </h2>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="content-box">
                                <div class="admission-content">
                                    <?php echo $admission['description']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Admission details will be available soon.
                </div>
            <?php endif; ?>

            <!-- Quick Info Cards -->
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h5>Eligibility</h5>
                        <p>SSC / 10th Standard pass with minimum 35% marks</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h5>Application Process</h5>
                        <p>Online application through ACPC Gujarat</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5>Program Duration</h5>
                        <p>3 Years Diploma Program</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

</body>
</html>