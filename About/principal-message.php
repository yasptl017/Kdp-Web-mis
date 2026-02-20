<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Principal's Message - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Principal's Message</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Principal's Message</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Principal's Message Content -->
    <?php
    $college_query = "SELECT * FROM college_details LIMIT 1";
    $college_result = $conn->query($college_query);
    $college = $college_result->fetch_assoc();
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="principal-photo-card">
                        <?php if (!empty($college['principal_photo'])): ?>
                            <img src="../Admin/<?php echo $college['principal_photo']; ?>" 
                                 alt="<?php echo $college['principal_name']; ?>" 
                                 class="img-fluid principal-photo">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x500/1e3a8a/FFFFFF?text=Principal" 
                                 alt="Principal" 
                                 class="img-fluid principal-photo">
                        <?php endif; ?>
                        <div class="principal-info">
                            <h4><?php echo $college['principal_name']; ?></h4>
                            <p class="text-muted">Principal</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="message-card">
                        <h2 class="section-title text-start">Message from Principal</h2>
                        <div class="message-content">
                            <?php echo $college['principal_message']; ?>
                        </div>
                        <div class="signature mt-4">
                            <p class="mb-0"><strong><?php echo $college['principal_name']; ?></strong></p>
                            <p class="text-muted">Principal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
