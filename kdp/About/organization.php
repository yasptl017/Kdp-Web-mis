<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Organization - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Organization</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Organization</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Organization Content -->
    <?php
    $college_query = "SELECT * FROM college_details LIMIT 1";
    $college_result = $conn->query($college_query);
    $college = $college_result->fetch_assoc();
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <!-- Organization Structure -->
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="section-title text-start">Organizational Structure</h2>
                    <p class="lead text-center">Our institute operates under a well-defined organizational structure ensuring efficient administration and academic excellence.</p>
                </div>
            </div>

            <!-- Principal -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-6 col-md-8">
                    <div class="org-card principal-card">
                        <div class="org-photo">
                            <?php if (!empty($college['principal_photo'])): ?>
                                <img src="../Admin/<?php echo $college['principal_photo']; ?>" 
                                     alt="<?php echo $college['principal_name']; ?>" 
                                     class="img-fluid">
                            <?php else: ?>
                                <div class="placeholder-photo">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="org-info">
                            <h4><?php echo $college['principal_name']; ?></h4>
                            <p class="designation">Principal</p>
                            <p class="contact-info">
                                <i class="fas fa-envelope me-2"></i><?php echo $college['email']; ?><br>
                                <i class="fas fa-phone me-2"></i><?php echo $college['contact_no']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Departments Section -->
            <?php
            $departments_query = "SELECT * FROM departments ORDER BY department ASC";
            $departments_result = $conn->query($departments_query);
            ?>
            <?php if ($departments_result && $departments_result->num_rows > 0): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="section-subtitle text-start mb-4">Academic Departments</h3>
                    </div>
                </div>
                <div class="row g-4">
                    <?php while ($dept = $departments_result->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="dept-card">
                                <div class="dept-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h5><?php echo $dept['department']; ?></h5>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <!-- Administrative Structure -->
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="section-subtitle text-start mb-4">Administrative Structure</h3>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5>Academic Section</h5>
                        <p>Manages academic activities, curriculum, examinations, and student records</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h5>Administration</h5>
                        <p>Handles administrative functions, HR, finance, and general operations</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h5>Training & Placement</h5>
                        <p>Coordinates industrial training, internships, and placement activities</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5>Library</h5>
                        <p>Manages library resources, digital content, and reading facilities</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Maintenance</h5>
                        <p>Responsible for infrastructure maintenance and facility management</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5>Student Affairs</h5>
                        <p>Handles student welfare, grievances, and extracurricular activities</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
