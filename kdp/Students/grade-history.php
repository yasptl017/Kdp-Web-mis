<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Student Grade History - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Student Grade History</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">Grade History</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <?php
    $sgh_query = "SELECT * FROM sgh WHERE display_order >= 0 ORDER BY display_order ASC";
    $sgh_result = $conn->query($sgh_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($sgh_result && $sgh_result->num_rows > 0): ?>
                        <?php while ($item = $sgh_result->fetch_assoc()): ?>
                            <div class="portal-card">
                                <div class="portal-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3><?php echo $item['title']; ?></h3>
                                <p>Access your complete grade history and academic performance records</p>
                                <a href="<?php echo $item['url']; ?>" class="btn btn-primary btn-lg w-100" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>View Grade History
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>Grade history portal link will be available soon.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>