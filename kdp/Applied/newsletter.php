<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

$page_title = "Newsletter - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
?>
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
                    <h1 class="page-title">Department Newsletter</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="index.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Newsletter</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
<?php include 'dptnavigation.php'; ?>

    <?php
    $newsletter_query = "SELECT * FROM dept_newsletter WHERE department = '$DEPARTMENT_NAME' AND display_order >= 0 ORDER BY display_order ASC";
    $newsletter_result = $conn->query($newsletter_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($newsletter_result && $newsletter_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($newsletter = $newsletter_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="newsletter-card">
                                <div class="newsletter-icon">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                                <h5 class="newsletter-title"><?php echo $newsletter['title']; ?></h5>
                                <?php if (!empty($newsletter['remark'])): ?>
                                    <p class="newsletter-remark"><?php echo $newsletter['remark']; ?></p>
                                <?php endif; ?>
                                <p class="newsletter-date">
                                    <i class="far fa-calendar me-2"></i>
                                    <?php echo date("F d, Y", strtotime($newsletter['created_at'])); ?>
                                </p>
                                <div class="newsletter-actions">
                                    <?php if (!empty($newsletter['file'])): ?>
                                        <a href="../Admin/<?php echo $newsletter['file']; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="../Admin/<?php echo $newsletter['file']; ?>" class="btn btn-accent btn-sm" download>
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Newsletters will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>