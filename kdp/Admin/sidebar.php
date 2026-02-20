<?php include 'session_check.php'; ?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a href="index.php" class="sidebar-brand">
        <i class="fas fa-shield-alt"></i>
        <span>Admin Panel</span>
    </a>

    <ul class="sidebar-menu">

        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <!-- About -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-toggle-item" data-target="aboutMenu" role="button" aria-expanded="false" aria-controls="aboutMenu">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
                <i class="fas fa-chevron-down menu-arrow ms-auto"></i>
            </a>
            <ul class="submenu" id="aboutMenu" aria-hidden="true">
                <li><a href="college_details.php">College Details</a></li>
                <li><a href="slider.php">Slider</a></li>
                <li><a href="manage_adminstration.php">Administration</a></li>
                <li><a href="manage_users.php">Create Users</a></li>
                <li><a href="manage_facilities.php">Facilities</a></li>
                <li><a href="manage_newsletter.php">Newsletter</a></li>
                <li><a href="manage_md.php">Mandatory Disclosure</a></li>
                <li><a href="manage_committees.php">Institute Committees</a></li>
                <li><a href="manage_mou.php">MOU</a></li>
                <li><a href="manage_feedback.php">Student Feedback</a></li>
                <li><a href="manage_stats.php">Stats</a></li>
            </ul>
        </li>

        <!-- Academics -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-toggle-item" data-target="academicsMenu" role="button" aria-expanded="false" aria-controls="academicsMenu">
                <i class="fas fa-university"></i>
                <span>Academics</span>
                <i class="fas fa-chevron-down menu-arrow ms-auto"></i>
            </a>
            <ul class="submenu" id="academicsMenu" aria-hidden="true">
                <li><a href="manage_intake.php">Departments/Intake</a></li>
                <li><a href="manage_accreditation.php">Manage Accreditation</a></li>
                <li><a href="manage_acal.php">Academic Calendar</a></li>
                <li><a href="manage_admission.php">Admission Details</a></li>
                <li><a href="manage_AICTEAf.php">AICTE EOA</a></li>
                <li><a href="manage_GTUAf.php">GTU Affiliation</a></li>
                <li><a href="manage_syllabus.php">Syllabus</a></li>
                <li><a href="add_dept.php">Add Department</a></li>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Departments -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-toggle-item" data-target="usersMenu" role="button" aria-expanded="false" aria-controls="usersMenu">
                <i class="fas fa-users"></i>
                <span>Departments</span>
                <i class="fas fa-chevron-down menu-arrow ms-auto"></i>
            </a>
            <ul class="submenu" id="usersMenu" aria-hidden="true">
                <li><a href="about_dept.php">About Department</a></li>
                <li><a href="manage_dept_nb.php">Notice Board</a></li>
                <li><a href="manage_faculty.php">Add Faculty</a></li>
                <li><a href="manage_activities.php">Add Activities</a></li>
                <li><a href="manage_dept_facilities.php">Add Facilities</a></li>
                <li><a href="manage_dept_newsletter.php">Add Newsletter</a></li>
                <li><a href="manage_dept_syllabus.php">Add Syllabus</a></li>
            </ul>
        </li>

        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <!-- Student Corner -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-toggle-item" data-target="productMenu" role="button" aria-expanded="false" aria-controls="productMenu">
                <i class="fas fa-box"></i>
                <span>Student Corner</span>
                <i class="fas fa-chevron-down menu-arrow ms-auto"></i>
            </a>
            <ul class="submenu" id="productMenu" aria-hidden="true">
                <li><a href="manage_mhwb.php">Mental Health & Well-being of Students</a></li>
                <li><a href="manage_tfp.php">Tution Fees Payment</a></li>
                <li><a href="manage_iste.php">ISTE Student Chapter</a></li>
                <li><a href="manage_ss.php">Student Section</a></li>
                <li><a href="manage_sgh.php">Student Grade History</a></li>
                <li><a href="manage_gsp.php">GTU Student Portal</a></li>
            </ul>
        </li>

        <!-- Campus Life -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-toggle-item" data-target="settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
                <i class="fas fa-cog"></i>
                <span>Campus Life</span>
                <i class="fas fa-chevron-down menu-arrow ms-auto"></i>
            </a>
            <ul class="submenu" id="settingsMenu" aria-hidden="true">
                <li><a href="manage_event_activities.php">Events / Activities</a></li>
                <li><a href="manage_ncc.php">NCC</a></li>
                <li><a href="manage_nss.php">NSS</a></li>
                <li><a href="manage_alumni.php">Alumni Association</a></li>
                <li><a href="manage_hostel.php">Hostel</a></li>
                <li><a href="manage_canteen.php">Canteen</a></li>
                <li><a href="manage_gallery.php">Manage Gallery Images</a></li>
                <li><a href="manage_lib.php">Library</a></li>
                <li><a href="manage_sports.php">Sports</a></li>
                <li><a href="manage_ard.php">Anti Ragging Disclaimer</a></li>
            </ul>
        </li>

        <!-- Circulars -->
        <li class="menu-item">
            <a href="manage_circulars.php">
                <i class="fas fa-chart-line"></i>
                <span>Circulars</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="manage_dynamic_menu.php">
                <i class="fas fa-chart-line"></i>
                <span>Dynamic Menu Items</span>
            </a>
        </li>
        
        <?php endif; ?>

        <!-- Logout -->
        <li class="menu-item">
            <a href="logout.php">
                <i class="fas fa-lock"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>