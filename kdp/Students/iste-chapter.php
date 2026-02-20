<!DOCTYPE html>
<html lang="en">
<?php $page_title = "ISTE Student Chapter - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">ISTE Student Chapter</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">ISTE Chapter</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <?php
    $iste_query = "SELECT * FROM iste WHERE display_order >= 0 ORDER BY display_order ASC";
    $iste_result = $conn->query($iste_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <?php if ($iste_result && $iste_result->num_rows > 0): ?>
                <?php while ($item = $iste_result->fetch_assoc()): ?>
                    <div class="content-box mb-4">
                        <h3 class="section-title text-start"><?php echo $item['title']; ?></h3>
                        <?php if (!empty($item['description'])): ?>
                            <div class="content-text"><?php echo $item['description']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['file'])): ?>
                            <a href="../Admin/<?php echo $item['file']; ?>" class="btn btn-primary mt-3" target="_blank">
                                <i class="fas fa-file-pdf me-2"></i>View Document
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>ISTE Chapter information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>