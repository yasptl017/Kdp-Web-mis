<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Mandatory Disclosure - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Mandatory Disclosure</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Mandatory Disclosure</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Mandatory Disclosure Content -->
    <?php
    $md_query = "SELECT * FROM md_files WHERE display_order >= 0 ORDER BY display_order ASC";
    $md_result = $conn->query($md_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>AICTE Mandatory Disclosure:</strong> As per AICTE regulations, all institutions are required to disclose important information for public access.
                    </div>
                </div>
            </div>

            <?php if ($md_result->num_rows > 0): ?>
                <div class="row g-4 mt-3">
                    <?php while ($md = $md_result->fetch_assoc()): ?>
                        <div class="col-lg-6 col-md-12">
                            <div class="document-card large">
                                <div class="document-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="document-content">
                                    <h4 class="document-title"><?php echo $md['title']; ?></h4>
                                    <?php if (!empty($md['remark'])): ?>
                                        <p class="document-remark"><?php echo $md['remark']; ?></p>
                                    <?php endif; ?>
                                    <p class="document-date">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        Updated: <?php echo date("F d, Y", strtotime($md['created_at'])); ?>
                                    </p>
                                    <div class="document-actions">
                                        <a href="../Admin/<?php echo $md['file']; ?>" 
                                           class="btn btn-primary" 
                                           target="_blank">
                                            <i class="fas fa-eye me-2"></i>View Document
                                        </a>
                                        <a href="../Admin/<?php echo $md['file']; ?>" 
                                           class="btn btn-accent" 
                                           download>
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center mt-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>Mandatory disclosure documents will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>