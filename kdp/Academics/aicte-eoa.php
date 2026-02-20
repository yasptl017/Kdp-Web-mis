<!DOCTYPE html>
<html lang="en">
<?php $page_title = "AICTE EOA - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">AICTE Extension of Approval</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Academics</a></li>
                            <li class="breadcrumb-item active" aria-current="page">AICTE EOA</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- AICTE EOA Content -->
    <?php
    $aicte_query = "SELECT * FROM aicte_affiliation WHERE display_order >= 0 ORDER BY display_order ASC";
    $aicte_result = $conn->query($aicte_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>AICTE Approval:</strong> All India Council for Technical Education Extension of Approval letters
                    </div>
                </div>
            </div>

            <?php if ($aicte_result && $aicte_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($aicte = $aicte_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="document-card">
                                <div class="document-icon">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <div class="document-content">
                                    <h5 class="document-title"><?php echo $aicte['title']; ?></h5>
                                    <?php if (!empty($aicte['remark'])): ?>
                                        <p class="document-remark"><?php echo $aicte['remark']; ?></p>
                                    <?php endif; ?>
                                    <div class="document-actions mt-3">
                                        <a href="../Admin/<?php echo $aicte['file_path']; ?>" 
                                           class="btn btn-primary" 
                                           target="_blank">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="../Admin/<?php echo $aicte['file_path']; ?>" 
                                           class="btn btn-accent btn-sm" 
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
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>AICTE EOA documents will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>