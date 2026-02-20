<!-- Main Navigation -->
<?php
// Detect current directory for proper link paths
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
$is_in_about = ($current_dir === 'About');
$is_in_academics = ($current_dir === 'Academics');
$is_in_students = ($current_dir === 'Students');
$is_in_campus = ($current_dir === 'Campus');
$is_in_dept = in_array($current_dir, ['Computer', 'Mechanical', 'Civil', 'Electrical', 'Hns', 'Applied', 'Ec']);
$base_path = ($is_in_about || $is_in_academics || $is_in_students || $is_in_campus || $is_in_dept) ? '../' : '';
$about_path = $is_in_about ? '' : (($is_in_academics || $is_in_students || $is_in_campus || $is_in_dept) ? '../About/' : 'About/');
$academics_path = $is_in_academics ? '' : (($is_in_about || $is_in_students || $is_in_campus || $is_in_dept) ? '../Academics/' : 'Academics/');
$students_path = $is_in_students ? '' : (($is_in_about || $is_in_academics || $is_in_campus || $is_in_dept) ? '../Students/' : 'Students/');
$campus_path = $is_in_campus ? '' : (($is_in_about || $is_in_academics || $is_in_students || $is_in_dept) ? '../Campus/' : 'Campus/');

// Fetch dynamic menu data
include_once($base_path . 'Admin/dbconfig.php');
$menu_query = "SELECT DISTINCT menu FROM dynamic_menu ORDER BY menu ASC";
$menu_result = $conn->query($menu_query);
?>
<nav class="navbar navbar-expand-lg main-navigation sticky-top">
    <div class="container">
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Desktop Menu -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>index.php">Home</a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">About</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>about.php">About Institute</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>vision-mission.php">Vision & Mission</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>principal-message.php">Principal's Message</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>administration.php">administration</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>facilities.php">Facilities</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>newsletter.php">Newsletter</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>mandatory_disclosure.php">Mandatory Disclosure</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>institute_committees.php">Institute Committees</a></li>
                        <li><a class="dropdown-item" href="<?php echo $about_path; ?>organization.php">Organization</a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">Academics</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>intake.php">Departments & Intake</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>accreditation.php">Accreditation</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>academic-calendar.php">Academic Calendar</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>admission.php">Admission Details</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>aicte-eoa.php">AICTE EOA</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>gtu-affiliation.php">GTU Affiliation</a></li>
                        <li><a class="dropdown-item" href="<?php echo $academics_path; ?>syllabus.php">Syllabus</a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">Departments</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Computer/aboutdpt.php">Computer Engineering</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Electrical/aboutdpt.php">Electrical Engineering</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Mechanical/aboutdpt.php">Mechanical Engineering</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Civil/aboutdpt.php">Civil Engineering</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Applied/aboutdpt.php">Applied Science</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Ec/aboutdpt.php">Electronics & Communication</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Hns/aboutdpt.php">Science & Humanities</a></li>
                    </ul>
                </li>
                
                <?php if ($menu_result && $menu_result->num_rows > 0): ?>
                    <?php while ($menu = $menu_result->fetch_assoc()): ?>
                        <?php
                        $menu_name = $menu['menu'];
                        $heading_query = "SELECT DISTINCT heading FROM dynamic_menu WHERE menu = ? ORDER BY heading ASC";
                        $stmt = $conn->prepare($heading_query);
                        $stmt->bind_param("s", $menu_name);
                        $stmt->execute();
                        $heading_result = $stmt->get_result();
                        
                        $headings = [];
                        while ($heading_row = $heading_result->fetch_assoc()) {
                            $headings[] = $heading_row['heading'];
                        }
                        $stmt->close();
                        
                        // Split headings into chunks of 4
                        $heading_chunks = array_chunk($headings, 4);
                        ?>
                        <li class="nav-item dropdown mega-dropdown">
                            <a class="nav-link" href="#"><?php echo htmlspecialchars($menu_name); ?></a>
                            <div class="dropdown-menu mega-menu">
                                <div class="container">
                                    <?php foreach ($heading_chunks as $chunk): ?>
                                        <div class="row">
                                            <?php foreach ($chunk as $heading): ?>
                                                <?php
                                                $items_query = "SELECT id, sub_heading FROM dynamic_menu WHERE menu = ? AND heading = ? ORDER BY id ASC";
                                                $stmt2 = $conn->prepare($items_query);
                                                $stmt2->bind_param("ss", $menu_name, $heading);
                                                $stmt2->execute();
                                                $items_result = $stmt2->get_result();
                                                ?>
                                                <div class="col-md-3">
                                                    <h6 class="mega-menu-title"><?php echo htmlspecialchars($heading); ?></h6>
                                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                                        <a class="dropdown-item" href="<?php echo $base_path; ?>dynamic-page.php?id=<?php echo $item['id']; ?>">
                                                            <?php echo htmlspecialchars($item['sub_heading']); ?>
                                                        </a>
                                                    <?php endwhile; ?>
                                                </div>
                                                <?php $stmt2->close(); ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php endif; ?>
                
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">Students</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>notice-board.php">Notice Board</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>mental-health.php">Mental Health & Well-being</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>fee-payment.php">Fee Payment</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>iste-chapter.php">ISTE Student Chapter</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>student-section.php">Student Section</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>grade-history.php">Grade History</a></li>
                        <li><a class="dropdown-item" href="<?php echo $students_path; ?>gtu-portal.php">GTU Student Portal</a></li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#">Campus</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>events.php">Events & Activities</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>ncc.php">NCC</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>nss.php">NSS</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>alumni.php">Alumni Association</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>hostel.php">Hostel</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>canteen.php">Canteen</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>gallery.php">Gallery</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>library.php">Library</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>sports.php">Sports</a></li>
                        <li><a class="dropdown-item" href="<?php echo $campus_path; ?>anti-ragging.php">Anti-Ragging</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>circulars.php">Circulars</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_path; ?>contact.php">Contact</a>
                </li>
            </ul>
        </div>
    </div>
</nav>