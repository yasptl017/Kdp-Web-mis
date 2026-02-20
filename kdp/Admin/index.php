<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

// Fetch college data
$data = $conn->query("SELECT * FROM college_details LIMIT 1")->fetch_assoc();
?>
<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <div class="container-fluid">

        <!-- PAGE TITLE -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">
                <i class="fas fa-home me-2"></i>Welcome to Dashboard
            </h2>
        </div>

        <!-- CARD -->
        <div class="card shadow-lg border-0">
            <div class="card-body text-center p-5">

                <!-- LOGO -->
                <?php if (!empty($data['logo_1']) && file_exists($data['logo_1'])): ?>
                    <img src="<?php echo $data['logo_1']; ?>" 
                         alt="College Logo" 
                         style="height:110px;" 
                         class="mb-3">
                <?php else: ?>
                    <div class="mb-3">
                        <i class="fas fa-university fa-4x text-primary"></i>
                    </div>
                <?php endif; ?>

                <!-- COLLEGE NAME -->
                <h1 class="fw-bold text-primary">
                    <?php echo $data['college_name']; ?>
                </h1>

                <?php if (!empty($data['college_alt_name'])): ?>
                    <h4 class="text-muted mb-4"><?php echo $data['college_alt_name']; ?></h4>
                <?php endif; ?>

                <hr class="my-4">

                <!-- CONTACT INFO -->
                <div class="row justify-content-center mb-4">

                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-light rounded shadow-sm">
                            <i class="fas fa-phone fa-lg text-primary"></i>
                            <h6 class="mt-2 mb-0"><?php echo $data['contact_no']; ?></h6>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-light rounded shadow-sm">
                            <i class="fas fa-envelope fa-lg text-danger"></i>
                            <h6 class="mt-2 mb-0"><?php echo $data['email']; ?></h6>
                        </div>
                    </div>

                    <div class="col-md-8 mt-3">
                        <div class="p-3 bg-light rounded shadow-sm">
                            <i class="fas fa-map-marker-alt fa-lg text-success"></i>
                            <h6 class="mt-2 mb-0"><?php echo nl2br($data['address']); ?></h6>
                        </div>
                    </div>

                </div>

                <hr class="my-4">

                <!-- SOCIAL MEDIA -->
                <h5 class="mb-3">Follow Us</h5>
                <div class="d-flex justify-content-center gap-3">

                    <?php if (!empty($data['facebook_link'])): ?>
                        <a href="<?php echo $data['facebook_link']; ?>" target="_blank" class="btn btn-primary">
                            <i class="fab fa-facebook"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($data['instagram_link'])): ?>
                        <a href="<?php echo $data['instagram_link']; ?>" target="_blank" class="btn btn-danger">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($data['twitter_link'])): ?>
                        <a href="<?php echo $data['twitter_link']; ?>" target="_blank" class="btn btn-info">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($data['linkedin_link'])): ?>
                        <a href="<?php echo $data['linkedin_link']; ?>" target="_blank" class="btn btn-secondary">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>

</main>

<?php include "footer.php"; ?>

</body>
</html>
