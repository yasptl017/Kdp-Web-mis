<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

$page_title = "Syllabus - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
?>
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
                    <h1 class="page-title">Syllabus</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="index.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Syllabus</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
<?php include 'dptnavigation.php'; ?>
    <?php
    $syllabus_query = "SELECT * FROM dept_syllabus WHERE department = '$DEPARTMENT_NAME' ORDER BY created_at DESC";
    $syllabus_result = $conn->query($syllabus_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($syllabus_result && $syllabus_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($syllabus = $syllabus_result->fetch_assoc()): ?>
                        <div class="col-lg-6">
                            <div class="syllabus-card">
                                <div class="syllabus-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="syllabus-content">
                                    <h5 class="syllabus-title"><?php echo $syllabus['title']; ?></h5>
                                    <a href="<?php echo $syllabus['url']; ?>" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-external-link-alt me-2"></i>View Syllabus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Syllabus information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
