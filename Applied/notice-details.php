<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

include_once "../Admin/dbconfig.php";

// Get notice ID from URL
$notice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch notice details
$notice_query = "SELECT * FROM nb WHERE id = $notice_id AND department = '$DEPARTMENT_NAME'";
$notice_result = $conn->query($notice_query);
$notice = $notice_result->fetch_assoc();

if (!$notice) {
    header("Location: notice-board.php");
    exit();
}

$page_title = $notice['title'] . " - " . $DEPARTMENT_NAME;
$notice_date_value = !empty($notice['date']) ? $notice['date'] : $notice['created_at'];
?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Notice Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="index.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item"><a href="notice-board.php">Notice Board</a></li>
                            <li class="breadcrumb-item active">Details</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
<?php include 'dptnavigation.php'; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="notice-detail-card">
                        <!-- Notice Header -->
                        <div class="notice-detail-header">
                            <div class="notice-meta">
                                <span class="notice-department">
                                    <i class="fas fa-building me-2"></i>
                                    <?php echo $notice['department']; ?>
                                </span>
                                <span class="notice-date">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?php echo date("d F Y", strtotime($notice_date_value)); ?>
                                </span>
                            </div>
                            <h2 class="notice-detail-title"><?php echo $notice['title']; ?></h2>
                        </div>

                        <!-- Notice Description -->
                        <?php if (!empty($notice['description'])): ?>
                            <div class="notice-detail-description">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </h5>
                                <div class="description-content">
                                    <?php echo $notice['description']; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Notice File -->
                        <?php if (!empty($notice['file'])): ?>
                            <div class="notice-detail-file">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-file-pdf me-2"></i>Attached Document
                                </h5>
                                <div class="file-preview-box">
                                    <div class="file-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="file-info">
                                        <p class="file-name">Notice Document</p>
                                        <p class="file-size">PDF File</p>
                                    </div>
                                    <div class="file-actions">
                                        <a href="../Admin/<?php echo $notice['file']; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="../Admin/<?php echo $notice['file']; ?>" class="btn btn-accent" download>
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Back Button -->
                        <div class="notice-detail-footer">
                            <a href="notice-board.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Notice Board
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>

