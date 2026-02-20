<!-- Footer -->
<?php
// Detect current directory for proper link paths
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
$is_in_about = ($current_dir === 'About');
$is_in_academics = ($current_dir === 'Academics');
$is_in_students = ($current_dir === 'Students');
$is_in_campus = ($current_dir === 'Campus');
$is_in_dept = in_array($current_dir, ['Computer', 'Mechanical', 'Civil', 'Electrical', 'Hns']);
$base_path = ($is_in_about || $is_in_academics || $is_in_students || $is_in_campus || $is_in_dept) ? '../' : '';
$about_path = $is_in_about ? '' : (($is_in_academics || $is_in_students || $is_in_campus || $is_in_dept) ? '../About/' : 'About/');
$academics_path = $is_in_academics ? '' : (($is_in_about || $is_in_students || $is_in_campus || $is_in_dept) ? '../Academics/' : 'Academics/');
$students_path = $is_in_students ? '' : (($is_in_about || $is_in_academics || $is_in_campus || $is_in_dept) ? '../Students/' : 'Students/');
$campus_path = $is_in_campus ? '' : (($is_in_about || $is_in_academics || $is_in_students || $is_in_dept) ? '../Campus/' : 'Campus/');

// Fetch college details
$college_query = "SELECT * FROM college_details LIMIT 1";
$college_result = $conn->query($college_query);
$college = $college_result->fetch_assoc();
?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <h5><?php echo htmlspecialchars($college['college_name']); ?></h5>
                <div class="footer-social mt-4">
                    <?php if (!empty($college['facebook_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['facebook_link']); ?>" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['twitter_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['twitter_link']); ?>" target="_blank" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['linkedin_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['linkedin_link']); ?>" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['instagram_link'])): ?>
                        <a href="<?php echo htmlspecialchars($college['instagram_link']); ?>" target="_blank" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="<?php echo $about_path; ?>about.php">About</a></li>
                    <li><a href="<?php echo $academics_path; ?>admission.php">Admission</a></li>
                    <li><a href="<?php echo $academics_path; ?>academic-calendar.php">Academic Calendar</a></li>
                    <li><a href="<?php echo $campus_path; ?>gallery.php">Gallery</a></li>
                    <li><a href="<?php echo $students_path; ?>notice-board.php">Student Notice Board</a></li>
                </ul>
            </div>

            <!-- Departments -->
            <div class="col-lg-3 col-md-6">
                <h5>Departments</h5>
                <ul>
                    <li><a href="<?php echo $base_path; ?>Computer/aboutdpt.php">Computer Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>Electrical/aboutdpt.php">Electrical Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>Mechanical/aboutdpt.php">Mechanical Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>Civil/aboutdpt.php">Civil Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>Hns/aboutdpt.php">Science & Humanities</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h5>Contact Us</h5>
                <ul style="list-style: none; padding: 0;">
                    <li class="mb-3">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-orange);"></i>
                        <span style="color: #d1d5db;"><?php echo nl2br(htmlspecialchars($college['address'])); ?></span>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone me-2" style="color: var(--accent-orange);"></i>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $college['contact_no']); ?>">
                            <?php echo htmlspecialchars($college['contact_no']); ?>
                        </a>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope me-2" style="color: var(--accent-orange);"></i>
                        <a href="mailto:<?php echo htmlspecialchars($college['email']); ?>">
                            <?php echo htmlspecialchars($college['email']); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="mb-1">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($college['college_name']); ?>. All Rights Reserved.</p>
            <p class="mb-0">Designed & Developed for Excellence in Education</p>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button class="scroll-top" id="scrollTopBtn" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
// Scroll to Top Button
window.addEventListener('scroll', function() {
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (window.pageYOffset > 300) {
        scrollTopBtn.style.display = 'flex';
    } else {
        scrollTopBtn.style.display = 'none';
    }
});

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Active Navigation Link
const currentLocation = window.location.pathname;
const navLinks = document.querySelectorAll('.main-navigation .nav-link');
navLinks.forEach(link => {
    if (link.getAttribute('href') === currentLocation.split('/').pop()) {
        link.classList.add('active');
    }
});

// Wrap tables inside Summernote content areas for mobile horizontal scrolling
(function() {
    const contentSelectors = [
        '.content-text',
        '.description-content',
        '.facility-description',
        '.vm-content',
        '.message-content',
        '.dept-description',
        '.tab-content-wrapper',
        '.admission-content',
        '.event-description',
        '.program-content'
    ].join(', ');

    document.querySelectorAll(contentSelectors).forEach(function(container) {
        container.querySelectorAll('table').forEach(function(table) {
            if (table.parentElement.classList.contains('summernote-table-wrap')) return;
            var wrapper = document.createElement('div');
            wrapper.className = 'summernote-table-wrap';
            wrapper.style.cssText = 'overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%;margin:15px 0;';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
            table.style.minWidth = '500px';
            table.style.width = '100%';
        });
    });
})();
</script>