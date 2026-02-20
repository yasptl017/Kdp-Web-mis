<!-- Mobile Offcanvas Menu -->
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
if (!isset($conn)) {
    include_once($base_path . 'Admin/dbconfig.php');
}
$mobile_menu_query = "SELECT DISTINCT menu FROM dynamic_menu ORDER BY menu ASC";
$mobile_menu_result = $conn->query($mobile_menu_query);
?>
<div class="offcanvas offcanvas-start mobile-menu" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Navigation Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="mobile-nav-list">
            <li>
                <a href="<?php echo $base_path; ?>index.php" class="mobile-menu-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            
            <li class="mobile-nav-item">
                <button type="button" class="mobile-nav-toggle" data-target="mobileAbout">
                    <span class="nav-text"><i class="fas fa-info-circle"></i> About</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="mobile-submenu" id="mobileAbout">
                    <a href="<?php echo $about_path; ?>about.php" class="mobile-menu-link">About Institute</a>
                    <a href="<?php echo $about_path; ?>vision-mission.php" class="mobile-menu-link">Vision & Mission</a>
                    <a href="<?php echo $about_path; ?>principal-message.php" class="mobile-menu-link">Principal's Message</a>
                    <a href="<?php echo $about_path; ?>administration.php" class="mobile-menu-link">Administration</a>
                    <a href="<?php echo $about_path; ?>facilities.php" class="mobile-menu-link">Facilities</a>
                    <a href="<?php echo $about_path; ?>newsletter.php" class="mobile-menu-link">Newsletter</a>
                    <a href="<?php echo $about_path; ?>mandatory_disclosure.php" class="mobile-menu-link">Mandatory Disclosure</a>
                    <a href="<?php echo $about_path; ?>institute_committees.php" class="mobile-menu-link">Institute Committees</a>
                    <a href="<?php echo $about_path; ?>organization.php" class="mobile-menu-link">Organization</a>
                </div>
            </li>
            
            <li class="mobile-nav-item">
                <button type="button" class="mobile-nav-toggle" data-target="mobileAcademics">
                    <span class="nav-text"><i class="fas fa-graduation-cap"></i> Academics</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="mobile-submenu" id="mobileAcademics">
                    <a href="<?php echo $academics_path; ?>intake.php" class="mobile-menu-link">Departments & Intake</a>
                    <a href="<?php echo $academics_path; ?>accreditation.php" class="mobile-menu-link">Accreditation</a>
                    <a href="<?php echo $academics_path; ?>academic-calendar.php" class="mobile-menu-link">Academic Calendar</a>
                    <a href="<?php echo $academics_path; ?>admission.php" class="mobile-menu-link">Admission Details</a>
                    <a href="<?php echo $academics_path; ?>aicte-eoa.php" class="mobile-menu-link">AICTE EOA</a>
                    <a href="<?php echo $academics_path; ?>gtu-affiliation.php" class="mobile-menu-link">GTU Affiliation</a>
                    <a href="<?php echo $academics_path; ?>syllabus.php" class="mobile-menu-link">Syllabus</a>
                </div>
            </li>
            
            <li class="mobile-nav-item">
                <button type="button" class="mobile-nav-toggle" data-target="mobileDepts">
                    <span class="nav-text"><i class="fas fa-building"></i> Departments</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="mobile-submenu" id="mobileDepts">
                    <a href="<?php echo $base_path; ?>Computer/aboutdpt.php" class="mobile-menu-link">Computer Engineering</a>
                    <a href="<?php echo $base_path; ?>Electrical/aboutdpt.php" class="mobile-menu-link">Electrical Engineering</a>
                    <a href="<?php echo $base_path; ?>Mechanical/aboutdpt.php" class="mobile-menu-link">Mechanical Engineering</a>
                    <a href="<?php echo $base_path; ?>Civil/aboutdpt.php" class="mobile-menu-link">Civil Engineering</a>
                    <a href="<?php echo $base_path; ?>Applied/aboutdpt.php" class="mobile-menu-link">Applied Mechanics</a>
                    <a href="<?php echo $base_path; ?>Hns/aboutdpt.php" class="mobile-menu-link">Science & Humanities</a>
                </div>
            </li>
            
            <?php if ($mobile_menu_result && $mobile_menu_result->num_rows > 0): ?>
                <?php 
                $mobile_menu_counter = 0;
                while ($mobile_menu = $mobile_menu_result->fetch_assoc()): 
                    $mobile_menu_counter++;
                    $mobile_menu_name = $mobile_menu['menu'];
                    $mobile_heading_query = "SELECT DISTINCT heading FROM dynamic_menu WHERE menu = ? ORDER BY heading ASC";
                    $mobile_stmt = $conn->prepare($mobile_heading_query);
                    $mobile_stmt->bind_param("s", $mobile_menu_name);
                    $mobile_stmt->execute();
                    $mobile_heading_result = $mobile_stmt->get_result();
                ?>
                    <li class="mobile-nav-item">
                        <button type="button" class="mobile-nav-toggle" data-target="mobileDynamic<?php echo $mobile_menu_counter; ?>">
                            <span class="nav-text"><i class="fas fa-th-large"></i> <?php echo htmlspecialchars($mobile_menu_name); ?></span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </button>
                        <div class="mobile-submenu" id="mobileDynamic<?php echo $mobile_menu_counter; ?>">
                            <?php while ($mobile_heading = $mobile_heading_result->fetch_assoc()): ?>
                                <?php
                                $mobile_heading_name = $mobile_heading['heading'];
                                $mobile_items_query = "SELECT id, sub_heading FROM dynamic_menu WHERE menu = ? AND heading = ? ORDER BY id ASC";
                                $mobile_stmt2 = $conn->prepare($mobile_items_query);
                                $mobile_stmt2->bind_param("ss", $mobile_menu_name, $mobile_heading_name);
                                $mobile_stmt2->execute();
                                $mobile_items_result = $mobile_stmt2->get_result();
                                ?>
                                <div class="mobile-mega-category"><?php echo htmlspecialchars($mobile_heading_name); ?></div>
                                <?php while ($mobile_item = $mobile_items_result->fetch_assoc()): ?>
                                    <a href="<?php echo $base_path; ?>dynamic-page.php?id=<?php echo $mobile_item['id']; ?>" class="mobile-menu-link"><?php echo htmlspecialchars($mobile_item['sub_heading']); ?></a>
                                <?php endwhile; ?>
                                <?php $mobile_stmt2->close(); ?>
                            <?php endwhile; ?>
                        </div>
                    </li>
                <?php 
                    $mobile_stmt->close();
                endwhile; ?>
            <?php endif; ?>
            
            <li class="mobile-nav-item">
                <button type="button" class="mobile-nav-toggle" data-target="mobileStudents">
                    <span class="nav-text"><i class="fas fa-user-graduate"></i> Students</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="mobile-submenu" id="mobileStudents">
                    <a href="<?php echo $students_path; ?>notice-board.php" class="mobile-menu-link">Notice Board</a>
                    <a href="<?php echo $students_path; ?>mental-health.php" class="mobile-menu-link">Mental Health & Well-being</a>
                    <a href="<?php echo $students_path; ?>fee-payment.php" class="mobile-menu-link">Fee Payment</a>
                    <a href="<?php echo $students_path; ?>iste-chapter.php" class="mobile-menu-link">ISTE Student Chapter</a>
                    <a href="<?php echo $students_path; ?>student-section.php" class="mobile-menu-link">Student Section</a>
                    <a href="<?php echo $students_path; ?>grade-history.php" class="mobile-menu-link">Grade History</a>
                    <a href="<?php echo $students_path; ?>gtu-portal.php" class="mobile-menu-link">GTU Student Portal</a>
                </div>
            </li>
            
            <li class="mobile-nav-item">
                <button type="button" class="mobile-nav-toggle" data-target="mobileCampus">
                    <span class="nav-text"><i class="fas fa-university"></i> Campus</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
                <div class="mobile-submenu" id="mobileCampus">
                    <a href="<?php echo $campus_path; ?>events.php" class="mobile-menu-link">Events & Activities</a>
                    <!-- <a href="<?php echo $campus_path; ?>ncc.php" class="mobile-menu-link">NCC</a> -->
                    <a href="<?php echo $campus_path; ?>nss.php" class="mobile-menu-link">NSS</a>
                    <a href="<?php echo $campus_path; ?>alumni.php" class="mobile-menu-link">Alumni Association</a>
                    <a href="<?php echo $campus_path; ?>hostel.php" class="mobile-menu-link">Hostel</a>
                    <a href="<?php echo $campus_path; ?>canteen.php" class="mobile-menu-link">Canteen</a>
                    <a href="<?php echo $campus_path; ?>gallery.php" class="mobile-menu-link">Gallery</a>
                    <a href="<?php echo $campus_path; ?>library.php" class="mobile-menu-link">Library</a>
                    <a href="<?php echo $campus_path; ?>sports.php" class="mobile-menu-link">Sports</a>
                    <a href="<?php echo $campus_path; ?>anti-ragging.php" class="mobile-menu-link">Anti-Ragging</a>
                </div>
            </li>
            
            <!--
            <li>
                <a href="<?php echo $base_path; ?>circulars.php" class="mobile-menu-link">
                    <i class="fas fa-bullhorn"></i> Circulars
                </a>
            </li>
            -->
            
            <li>
                <a href="<?php echo $base_path; ?>contact.php" class="mobile-menu-link">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Mobile Navigation Styles */
.mobile-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-nav-list > li {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.mobile-nav-toggle {
    width: 100%;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    color: #333;
    font-size: 1rem;
    transition: background-color 0.3s ease;
}

.mobile-nav-toggle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.mobile-nav-toggle .nav-text {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobile-nav-toggle .toggle-icon {
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}

.mobile-nav-toggle.active .toggle-icon {
    transform: rotate(180deg);
}

.mobile-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: #f8f9fa;
}

.mobile-submenu.open {
    max-height: 2000px;
}

.mobile-menu-link {
    display: block;
    padding: 10px 15px 10px 45px;
    text-decoration: none;
    color: #555;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.mobile-menu-link:hover {
    background-color: rgba(30, 58, 138, 0.1);
    color: #1e3a8a;
    border-left-color: #1e3a8a;
}

.mobile-mega-category {
    font-weight: 600;
    color: #1e3a8a;
    padding: 12px 15px 8px 45px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: rgba(30, 58, 138, 0.08);
    margin-top: 5px;
}

.mobile-nav-item {
    position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const offcanvasElement = document.getElementById('mobileMenu');
    
    if (!offcanvasElement) return;
    
    // Get all toggle buttons
    const toggleButtons = offcanvasElement.querySelectorAll('.mobile-nav-toggle');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('data-target');
            const targetMenu = document.getElementById(targetId);
            
            if (!targetMenu) return;
            
            const isOpen = targetMenu.classList.contains('open');
            
            // Close all other open menus (accordion behavior)
            const allMenus = offcanvasElement.querySelectorAll('.mobile-submenu');
            const allButtons = offcanvasElement.querySelectorAll('.mobile-nav-toggle');
            
            allMenus.forEach(function(menu) {
                if (menu !== targetMenu) {
                    menu.classList.remove('open');
                }
            });
            
            allButtons.forEach(function(btn) {
                if (btn !== button) {
                    btn.classList.remove('active');
                }
            });
            
            // Toggle current menu
            if (isOpen) {
                targetMenu.classList.remove('open');
                this.classList.remove('active');
            } else {
                targetMenu.classList.add('open');
                this.classList.add('active');
            }
        });
    });
    
    // Close offcanvas when clicking menu links
    const menuLinks = offcanvasElement.querySelectorAll('.mobile-menu-link');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (typeof bootstrap !== 'undefined') {
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }
        });
    });
});
</script>
