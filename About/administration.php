<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Administration - K.D. Polytechnic"; ?>
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
                <h1 class="page-title">Administration</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="../index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Administration
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<?php
$admin_query = "SELECT * FROM administration ORDER BY id DESC";
$admin_result = $conn->query($admin_query);
?>

<!-- Administration Content -->
<section class="py-5 bg-white">
<div class="container">

<?php if ($admin_result && $admin_result->num_rows > 0): ?>
<?php while ($admin = $admin_result->fetch_assoc()): ?>

<div class="row mb-5">
    <div class="col-lg-12">
        <div class="content-box">

            <h2 class="section-title text-start">
                <?php echo htmlspecialchars($admin['title']); ?>
            </h2>

            <div class="content-text">
                <?php echo $admin['description']; ?>
            </div>

        </div>
    </div>
</div>

<?php endwhile; ?>
<?php else: ?>

<div class="alert alert-info text-center">
    <i class="fas fa-info-circle me-2"></i>
    Administration information will be available soon.
</div>

<?php endif; ?>

</div>
</section>

<!-- Footer -->
<?php include '../assets/preload/footer.php'; ?>

</body>
</html>
