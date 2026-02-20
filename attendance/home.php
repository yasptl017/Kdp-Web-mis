<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="app-page-title mb-0">Dashboard</h1>
                    <p class="text-muted mb-0" style="font-size:0.875rem;">
                        Welcome back, <strong><?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : 'User'; ?></strong>
                    </p>
                </div>
                <button type="button" class="btn app-btn-secondary pwa-install-btn d-none">
                    Install App
                </button>
            </div>

            <!-- Quick Access Cards -->
            <div class="row g-3 g-md-4 mb-4">

                <div class="col-6 col-lg-3">
                    <div class="app-card app-card-stat shadow-sm h-100 text-center" style="cursor:pointer;">
                        <div class="app-card-body p-3 p-md-4">
                            <div class="stats-icon mb-2" style="font-size:2.25rem;"><i class="bi bi-calendar2-check text-primary"></i></div>
                            <h4 class="stats-type mb-0">My Attendance</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:0.78rem;">View &amp; take attendance</p>
                        </div>
                        <a class="app-card-link-mask" href="myAttendance.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="app-card app-card-stat shadow-sm h-100 text-center" style="cursor:pointer;">
                        <div class="app-card-body p-3 p-md-4">
                            <div class="stats-icon mb-2" style="font-size:2.25rem;"><i class="bi bi-pencil-square text-warning"></i></div>
                            <h4 class="stats-type mb-0">Edit Attendance</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:0.78rem;">Update marked records</p>
                        </div>
                        <a class="app-card-link-mask" href="editAttendance.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="app-card app-card-stat shadow-sm h-100 text-center" style="cursor:pointer;">
                        <div class="app-card-body p-3 p-md-4">
                            <div class="stats-icon mb-2" style="font-size:2.25rem;"><i class="bi bi-calendar-week text-success"></i></div>
                            <h4 class="stats-type mb-0">Add Mapping</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:0.78rem;">Schedule lectures &amp; labs</p>
                        </div>
                        <a class="app-card-link-mask" href="addMapping.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="app-card app-card-stat shadow-sm h-100 text-center" style="cursor:pointer;">
                        <div class="app-card-body p-3 p-md-4">
                            <div class="stats-icon mb-2" style="font-size:2.25rem;">📊</div>
                            <h4 class="stats-type mb-0">Muster Report</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:0.78rem;">Export to Excel</p>
                        </div>
                        <a class="app-card-link-mask" href="lecmuster.php"></a>
                    </div>
                </div>

            </div>

            <!-- Quick Manage Links -->
            <div class="row g-3">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4 class="mb-3">Quick Links</h4>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="managefaculty.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-person-badge me-1"></i>Faculty
                                </a>
                                <a href="managestudents.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-people me-1"></i>Students
                                </a>
                                <a href="managesubjects.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-journal-bookmark me-1"></i>Subjects
                                </a>
                                <a href="managesemester.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-calendar3 me-1"></i>Semester
                                </a>
                                <a href="manageslot.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-clock me-1"></i>Slots
                                </a>
                                <a href="managelabs.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-building me-1"></i>Labs
                                </a>
                                <a href="bulkupload.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-upload me-1"></i>Bulk Upload
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
