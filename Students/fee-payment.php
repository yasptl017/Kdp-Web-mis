<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Fee Payment - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Tuition Fee Payment</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">Fee Payment</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <?php
    $tfp_query = "SELECT * FROM tfp WHERE display_order >= 0 ORDER BY display_order ASC";
    $tfp_result = $conn->query($tfp_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($tfp_result && $tfp_result->num_rows > 0): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <?php while($tfp = $tfp_result->fetch_assoc()): ?>
                            <div class="content-box mb-4">
                                <h3 class="section-title text-start"><?php echo $tfp['title']; ?></h3>
                                <?php if (!empty($tfp['description'])): ?>
                                    <div class="content-text"><?php echo $tfp['description']; ?></div>
                                <?php endif; ?>
                                <?php if (!empty($tfp['file'])): ?>
                                    <div class="text-center mt-4">
                                        <img src="../Admin/<?php echo $tfp['file']; ?>" class="img-fluid" alt="Fee Payment" style="max-width: 600px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Fee payment information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>