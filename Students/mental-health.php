<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Mental Health & Well-being - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Mental Health & Well-being</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">Mental Health</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <?php
    $mhwb_query = "SELECT * FROM mhwb WHERE display_order >= 0 ORDER BY display_order ASC";
    $mhwb_result = $conn->query($mhwb_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <?php if ($mhwb_result && $mhwb_result->num_rows > 0): ?>
                <?php while ($item = $mhwb_result->fetch_assoc()): ?>
                    <div class="content-box mb-4">
                        <h3 class="section-title text-start"><?php echo $item['title']; ?></h3>
                        <?php if (!empty($item['description'])): ?>
                            <div class="content-text"><?php echo $item['description']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['file'])): ?>
                            <a href="../Admin/<?php echo $item['file']; ?>" class="btn btn-primary mt-3" target="_blank">
                                <i class="fas fa-download me-2"></i>Download Resources
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>