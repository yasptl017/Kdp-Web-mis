<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

$page_title = "Faculty - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
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
                    <h1 class="page-title">Faculty Members</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="index.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Faculty</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
<?php include 'dptnavigation.php'; ?>

    <?php
    $faculty_query = "SELECT * FROM faculty WHERE department = '$DEPARTMENT_NAME' ORDER BY idx ASC, name ASC";
    $faculty_result = $conn->query($faculty_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Faculty Team</h2>
                <p class="section-subtitle"><?php echo $DEPARTMENT_NAME; ?> Department</p>
            </div>
            
            <?php if ($faculty_result && $faculty_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($faculty = $faculty_result->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="faculty-card">
                                <div class="faculty-photo">
                                    <?php if (!empty($faculty['photo'])): ?>
                                        <img src="../Admin/<?php echo $faculty['photo']; ?>" alt="<?php echo $faculty['name']; ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/default-faculty.png" alt="<?php echo $faculty['name']; ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="faculty-info">
                                    <h5 class="faculty-name"><?php echo $faculty['name']; ?></h5>
                                    <p class="faculty-designation"><?php echo $faculty['designation']; ?></p>
                                    <a href="faculty-profile.php?id=<?php echo $faculty['id']; ?>" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-user me-2"></i>View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Faculty information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
