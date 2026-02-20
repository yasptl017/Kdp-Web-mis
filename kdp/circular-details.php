<!DOCTYPE html>
<html lang="en">
<?php 
include_once "Admin/dbconfig.php";

// Get circular ID from URL
$circular_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch circular details
$circular_query = "SELECT * FROM circulars WHERE id = $circular_id";
$circular_result = $conn->query($circular_query);
$circular = $circular_result->fetch_assoc();

if (!$circular) {
    header("Location: circulars.php");
    exit();
}

$page_title = $circular['title'] . " - K.D. Polytechnic";
?>
<?php include 'assets/preload/head.php'; ?>
<body>
    <?php include 'assets/preload/topbar.php'; ?>
    <?php include 'assets/preload/header.php'; ?>
    <?php include 'assets/preload/navigation.php'; ?>
    <?php include 'assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Circular Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="circulars.php">Circulars</a></li>
                            <li class="breadcrumb-item active">Circular Details</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="circular-detail-card">
                        <!-- Circular Header -->
                        <div class="circular-detail-header">
                            <div class="circular-meta">
                                <span class="circular-date">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?php echo date("d F Y", strtotime($circular['date'])); ?>
                                </span>
                                <?php if (!empty($circular['remark'])): ?>
                                    <span class="circular-remark">
                                        <i class="fas fa-tag me-2"></i>
                                        <?php echo $circular['remark']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="circular-detail-title"><?php echo $circular['title']; ?></h2>
                        </div>

                        <!-- Circular Description -->
                        <?php if (!empty($circular['description'])): ?>
                            <div class="circular-detail-description">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </h5>
                                <div class="description-content">
                                    <?php echo $circular['description']; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Circular File -->
                        <?php if (!empty($circular['file'])): ?>
                            <div class="circular-detail-file">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-file-pdf me-2"></i>Attached Document
                                </h5>
                                <div class="file-preview-box">
                                    <div class="file-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="file-info">
                                        <p class="file-name">Circular Document</p>
                                        <p class="file-size">PDF File</p>
                                    </div>
                                    <div class="file-actions">
                                        <a href="Admin/<?php echo $circular['file']; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="Admin/<?php echo $circular['file']; ?>" class="btn btn-accent" download>
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Back Button -->
                        <div class="circular-detail-footer">
                            <a href="circulars.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Circulars
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>
</body>
</html>