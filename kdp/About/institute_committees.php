<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Institute Committees - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Institute Committees</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Institute Committees</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Institute Committees Content -->
    <?php
    $committees_query = "SELECT * FROM committees WHERE display_order >= 0 ORDER BY display_order ASC";
    $committees_result = $conn->query($committees_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="lead text-center mb-5">Various committees formed for smooth functioning and governance of the institute</p>
                </div>
            </div>

            <?php if ($committees_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($committee = $committees_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="committee-card">
                                <div class="committee-header">
                                    <i class="fas fa-users"></i>
                                    <h5><?php echo $committee['title']; ?></h5>
                                </div>
                                <div class="committee-body">
                                    <?php if (!empty($committee['remark'])): ?>
                                        <p class="committee-remark"><?php echo $committee['remark']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($committee['file'])): ?>
                                        <div class="committee-actions">
                                            <a href="../Admin/<?php echo $committee['file']; ?>" 
                                               class="btn btn-accent btn-sm w-100" 
                                               target="_blank">
                                                <i class="fas fa-file-pdf me-2"></i>View Committee Details
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="committee-date mt-3">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?php echo date("F d, Y", strtotime($committee['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Committee information will be updated soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>