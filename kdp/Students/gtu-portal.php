<!DOCTYPE html>
<html lang="en">
<?php $page_title = "GTU Student Portal - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">GTU Student Portal</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">GTU Portal</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $gsp_query = "SELECT * FROM gsp ORDER BY created_at DESC";
    $gsp_result = $conn->query($gsp_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($gsp_result && $gsp_result->num_rows > 0): ?>
                        <?php while ($item = $gsp_result->fetch_assoc()): ?>
                            <div class="portal-card">
                                <div class="portal-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <h3><?php echo $item['title']; ?></h3>
                                <p>Access GTU student portal for exam results, hall tickets, and academic information</p>
                                <a href="<?php echo $item['url']; ?>" class="btn btn-primary btn-lg w-100" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Go to GTU Portal
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>GTU portal link will be available soon.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
