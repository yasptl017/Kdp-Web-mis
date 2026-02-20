<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Academic Calendar - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Academic Calendar</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Academics</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Academic Calendar</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- Academic Calendar Content -->
    <?php
    $calendar_query = "SELECT * FROM academic_calendar WHERE display_order >= 0 ORDER BY display_order ASC";
    $calendar_result = $conn->query($calendar_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="section-title text-start">Academic Calendar Downloads</h2>
                    <p class="lead text-center">Download academic calendars for various terms and semesters</p>
                </div>
            </div>
            <?php if ($calendar_result && $calendar_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($calendar = $calendar_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="document-card">
                                <div class="document-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="document-content">
                                    <h5 class="document-title"><?php echo $calendar['title']; ?></h5>
                                    <p class="document-course">
                                        <i class="fas fa-book me-2"></i><?php echo $calendar['course']; ?>
                                    </p>
                                    <?php if (!empty($calendar['remark'])): ?>
                                        <p class="document-remark"><?php echo $calendar['remark']; ?></p>
                                    <?php endif; ?>
                                    <a href="../Admin/<?php echo $calendar['file_path']; ?>" 
                                       class="btn btn-accent btn-sm w-100" 
                                       target="_blank">
                                        <i class="fas fa-download me-2"></i>Download Calendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Academic calendar will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>