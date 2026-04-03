<?php
require_once __DIR__ . '/auth.php';
require_login();
$current_page = basename((string)($_SERVER['PHP_SELF'] ?? ''));
$mapping_pages = [
    'addLectureMapping.php',
    'addLabMapping.php',
    'addTutMapping.php',
];
$alternate_pages = [
    'lecAttendance.php',
    'labAttendance.php',
    'tutAttendance.php',
];
$manage_pages = [
    'managefaculty.php',
    'managesubjects.php',
    'managestudents.php',
    'managesemester.php',
    'manageslot.php',
    'managelabs.php',
    'bulkupload.php',
    'editfaculty.php',
    'editsubjects.php',
    'editstudent.php',
    'editsemester.php',
    'editslot.php',
];
$is_mapping_page = in_array($current_page, $mapping_pages, true);
$is_alternate_page = in_array($current_page, $alternate_pages, true);
$is_manage_page = in_array($current_page, $manage_pages, true);
$header_enrollment_search = htmlspecialchars(trim((string)($_GET['enrollment'] ?? '')));
?>
<header class="app-header fixed-top">
    <div class="app-header-inner">
        <div class="container-fluid py-2">
            <div class="app-header-content">
                <div class="row justify-content-between align-items-center">

                    <div class="col-auto d-flex align-items-center gap-2">
                        <a id="sidepanel-toggler" class="sidepanel-toggler d-inline-block d-xl-none" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" role="img"><title>Menu</title><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M4 7h22M4 15h22M4 23h22"></path></svg>
                        </a>
                        <a id="sidepanel-toggler-desktop" class="sidepanel-toggler-desktop d-none d-xl-inline-flex" href="#" title="Toggle Sidebar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                        </a>
                    </div>

                    <div class="search-mobile-trigger d-sm-none col">
                        <i class="search-mobile-trigger-icon fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div class="app-search-box col">
                        <form class="app-search-form" method="GET" action="studentAttendance.php" style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="flex: 1; position: relative;">
                                <input type="text" placeholder="Search Enrollment No..." name="enrollment" value="<?= $header_enrollment_search; ?>" class="form-control search-input" style="padding-right: 2.75rem; padding-left: 0.9rem; height: 40px; border-radius: 0.35rem; border: 1px solid #ddd;">
                                <button type="submit" class="btn search-btn btn-primary" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); padding: 0.4rem 0.6rem; border-radius: 0.25rem; border: none; background: transparent; color: #667eea; cursor: pointer; font-size: 0.95rem;" value="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                            </div>
                        </form>
                    </div>

                    <div class="app-utilities col-auto">
                        <div class="app-utility-item app-user-dropdown dropdown">
                            <a class="dropdown-toggle d-flex align-items-center gap-2" id="user-dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                <img src="assets/images/user.png" alt="user profile" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                <span class="d-none d-md-inline text-truncate" style="max-width:120px;font-size:0.875rem;font-weight:500;">
                                    <?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : 'User'; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="user-dropdown-toggle">
                                <li><span class="dropdown-item-text text-muted small px-3 py-1">
                                    <?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : ''; ?>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="app-sidepanel" class="app-sidepanel">
        <div id="sidepanel-drop" class="sidepanel-drop"></div>
        <div class="sidepanel-inner d-flex flex-column">
            <a href="#" id="sidepanel-close" class="sidepanel-close d-xl-none">&times;</a>

            <div class="app-branding">
                <a class="app-logo" href="home.php">
                    <img class="logo-icon me-2" src="assets/images/app-logo.svg" alt="KDP-MIS Logo">
                    <span class="logo-text">KDP-MIS</span>
                </a>
            </div>

            <nav id="app-nav-main" class="app-nav app-nav-main flex-grow-1">
                <ul class="app-menu list-unstyled accordion" id="menu-accordion">

                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <span class="nav-icon"><i class="bi bi-house-door"></i></span>
                            <span class="nav-link-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="myAttendanceSelect.php">
                            <span class="nav-icon"><i class="bi bi-calendar2-check"></i></span>
                            <span class="nav-link-text">My Attendance</span>
                        </a>
                    </li>

                    <li class="nav-item has-submenu">
                        <a class="nav-link submenu-toggle <?= $is_mapping_page ? 'active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#submenu-add-mapping" aria-expanded="<?= $is_mapping_page ? 'true' : 'false' ?>" aria-controls="submenu-add-mapping">
                            <span class="nav-icon"><i class="bi bi-calendar-week"></i></span>
                            <span class="nav-link-text">Add Mapping</span>
                            <span class="submenu-arrow">
                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-chevron-down" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </span>
                        </a>
                        <div id="submenu-add-mapping" class="collapse submenu <?= $is_mapping_page ? 'show' : '' ?>" data-bs-parent="#menu-accordion">
                            <ul class="submenu-list list-unstyled">
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'addLectureMapping.php' ? 'active' : '' ?>" href="addLectureMapping.php"><i class="bi bi-calendar-week me-1"></i>Lecture Mapping</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'addLabMapping.php' ? 'active' : '' ?>" href="addLabMapping.php"><i class="bi bi-diagram-3 me-1"></i>Lab Mapping</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'addTutMapping.php' ? 'active' : '' ?>" href="addTutMapping.php"><i class="bi bi-journal-check me-1"></i>Tutorial Mapping</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="editAttendance.php">
                            <span class="nav-icon"><i class="bi bi-pencil-square"></i></span>
                            <span class="nav-link-text">Edit Attendance</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="lecmuster.php">
                            <span class="nav-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                            <span class="nav-link-text">Muster Report</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="attendanceAnalysis.php">
                            <span class="nav-icon"><i class="bi bi-bar-chart-line"></i></span>
                            <span class="nav-link-text">Attendance Analysis</span>
                        </a>
                    </li>
                    <li class="nav-item has-submenu">
                        <a class="nav-link submenu-toggle <?= $is_alternate_page ? 'active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#submenu-2" aria-expanded="<?= $is_alternate_page ? 'true' : 'false' ?>" aria-controls="submenu-2">
                            <span class="nav-icon"><i class="bi bi-diagram-3"></i></span>
                            <span class="nav-link-text">Alternate</span>
                            <span class="submenu-arrow">
                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-chevron-down" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </span>
                        </a>
                        <div id="submenu-2" class="collapse submenu submenu-2 <?= $is_alternate_page ? 'show' : '' ?>" data-bs-parent="#menu-accordion">
                            <ul class="submenu-list list-unstyled">
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'lecAttendance.php' ? 'active' : '' ?>" href="lecAttendance.php"><i class="bi bi-journal-text me-1"></i>Lecture Attendance</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'labAttendance.php' ? 'active' : '' ?>" href="labAttendance.php"><i class="bi bi-camera-video me-1"></i>Lab Attendance</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'tutAttendance.php' ? 'active' : '' ?>" href="tutAttendance.php"><i class="bi bi-book me-1"></i>Tutorial Attendance</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item has-submenu">
                        <a class="nav-link submenu-toggle <?= $is_manage_page ? 'active' : '' ?>" href="#" data-bs-toggle="collapse" data-bs-target="#submenu-1" aria-expanded="<?= $is_manage_page ? 'true' : 'false' ?>" aria-controls="submenu-1">
                            <span class="nav-icon"><i class="bi bi-gear"></i></span>
                            <span class="nav-link-text">Manage</span>
                            <span class="submenu-arrow">
                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-chevron-down" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </span>
                        </a>
                        <div id="submenu-1" class="collapse submenu submenu-1 <?= $is_manage_page ? 'show' : '' ?>" data-bs-parent="#menu-accordion">
                            <ul class="submenu-list list-unstyled">
                                <li class="submenu-item"><a class="submenu-link <?= in_array($current_page, ['managefaculty.php', 'editfaculty.php'], true) ? 'active' : '' ?>" href="managefaculty.php"><i class="bi bi-person-badge me-1"></i>Faculty</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= in_array($current_page, ['managesubjects.php', 'editsubjects.php'], true) ? 'active' : '' ?>" href="managesubjects.php"><i class="bi bi-journal-bookmark me-1"></i>Subjects</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= in_array($current_page, ['managestudents.php', 'editstudent.php'], true) ? 'active' : '' ?>" href="managestudents.php"><i class="bi bi-people me-1"></i>Students</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= in_array($current_page, ['managesemester.php', 'editsemester.php'], true) ? 'active' : '' ?>" href="managesemester.php"><i class="bi bi-calendar3 me-1"></i>Semester</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= in_array($current_page, ['manageslot.php', 'editslot.php'], true) ? 'active' : '' ?>" href="manageslot.php"><i class="bi bi-clock me-1"></i>Slots</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'managelabs.php' ? 'active' : '' ?>" href="managelabs.php"><i class="bi bi-building me-1"></i>Labs</a></li>
                                <li class="submenu-item"><a class="submenu-link <?= $current_page === 'bulkupload.php' ? 'active' : '' ?>" href="bulkupload.php"><i class="bi bi-upload me-1"></i>Bulk Upload</a></li>
                            </ul>
                        </div>
                    </li>

                </ul>
            </nav>

            <div class="app-sidepanel-footer">
                <nav class="app-nav app-nav-footer">
                    <ul class="app-menu footer-menu list-unstyled">
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
                                <span class="nav-link-text"><?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : ''; ?></span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</header>
