<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Newsletter - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Newsletter</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Newsletter</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <!-- Newsletter Content -->
    <?php
    $newsletters_query = "SELECT * FROM newsletters WHERE display_order >= 0 ORDER BY display_order ASC";
    $newsletters_result = $conn->query($newsletters_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="lead text-center mb-5">Download our latest newsletters and stay updated with institute activities</p>
                </div>
            </div>
            <?php if ($newsletters_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($newsletter = $newsletters_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="document-card">
                                <div class="document-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="document-content">
                                    <h5 class="document-title"><?php echo $newsletter['title']; ?></h5>
                                    <?php if (!empty($newsletter['remark'])): ?>
                                        <p class="document-remark"><?php echo $newsletter['remark']; ?></p>
                                    <?php endif; ?>
                                    <p class="document-date">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?php echo date("F d, Y", strtotime($newsletter['created_at'])); ?>
                                    </p>
                                    <a href="../Admin/<?php echo $newsletter['file']; ?>" 
                                       class="btn btn-accent btn-sm" 
                                       target="_blank">
                                        <i class="fas fa-download me-2"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No newsletters available at the moment.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>