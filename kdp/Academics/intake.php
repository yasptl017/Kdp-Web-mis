<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Departments & Intake - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
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
                <h1 class="page-title">Departments & Intake</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="../index.php">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="#">Academics</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Departments & Intake
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>
<?php
$intake_query  = "SELECT * FROM intake WHERE display_order >= 0 ORDER BY display_order ASC";
$intake_result = $conn->query($intake_query);
?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title text-start">Approved Intake Capacity</h2>
                <p class="lead text-center">Sanctioned intake capacity for various diploma programs</p>
            </div>
        </div>
        <?php if ($intake_result && $intake_result->num_rows > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table academic-table">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Course Name</th>
                                <th>Intake Capacity</th>
                                <th>Duration</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sr = 1; ?>
                        <?php while ($intake = $intake_result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Sr. No."><?php echo $sr++; ?></td>
                                <td data-label="Course Name">
                                    <?php echo $intake['course_name']; ?>
                                </td>
                                <td data-label="Intake Capacity">
                                    <?php echo $intake['intek']; ?> Students
                                </td>
                                <td data-label="Duration">
                                    <?php echo $intake['duration']; ?>
                                </td>
                                <td data-label="Remarks">
                                    <?php echo !empty($intake['remark']) ? $intake['remark'] : '-'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center">
            Intake information will be updated soon.
        </div>
        <?php endif; ?>
    </div>
</section>
<!-- Footer -->
<?php include '../assets/preload/footer.php'; ?>
</body>
</html>