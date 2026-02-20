<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Syllabus - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Course Syllabus</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Academics</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Syllabus</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Syllabus Content -->
    <?php
    $syllabus_query = "SELECT * FROM syllabus WHERE display_order >= 0 ORDER BY display_order ASC";
    $syllabus_result = $conn->query($syllabus_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title text-start">Course Syllabus Resources</h2>
                    <p class="lead text-center">Access detailed syllabus for all diploma programs</p>
                </div>
            </div>

            <?php if ($syllabus_result && $syllabus_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($syllabus = $syllabus_result->fetch_assoc()): ?>
                        <div class="col-lg-6">
                            <div class="syllabus-card">
                                <div class="syllabus-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="syllabus-content">
                                    <h4><?php echo $syllabus['title']; ?></h4>
                                    <p class="syllabus-desc">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Access comprehensive syllabus details including course structure, subjects, and examination patterns
                                    </p>
                                    <a href="<?php echo $syllabus['url']; ?>" 
                                       class="btn btn-primary btn-lg w-100" 
                                       target="_blank">
                                        <i class="fas fa-external-link-alt me-2"></i>View Syllabus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Additional Info -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="info-box">
                            <h4><i class="fas fa-lightbulb text-warning me-2"></i>Important Information</h4>
                            <ul class="info-list">
                                <li>All programs follow GTU approved curriculum</li>
                                <li>Syllabus is updated regularly to meet industry requirements</li>
                                <li>Practical and theoretical components are balanced for optimal learning</li>
                                <li>Industry-relevant electives are available in higher semesters</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Syllabus information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>