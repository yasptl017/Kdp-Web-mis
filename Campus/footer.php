<!-- Footer -->
<?php
// Detect current directory for proper link paths
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
$is_in_about = ($current_dir === 'About');
$is_in_academics = ($current_dir === 'Academics');
$is_in_students = ($current_dir === 'Students');
$is_in_campus = ($current_dir === 'Campus');
$is_subdirectory = ($current_dir !== '' && $current_dir !== '/');
$base_path = ($is_in_about || $is_in_academics || $is_in_students || $is_in_campus) ? '../' : '';
$about_path = $is_in_about ? '' : ($is_in_academics || $is_in_students || $is_in_campus ? '../About/' : 'About/');
?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-lg-4 col-md-6">
                <h5>About K.D. Polytechnic</h5>
                <p style="color: #d1d5db; line-height: 1.8;">
                    K.D. Polytechnic, Patan is a premier technical institution committed to excellence in education, 
                    preparing students for successful careers in engineering and technology.
                </p>
                <div class="footer-social mt-4">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="<?php echo $about_path; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo $base_path; ?>admissions.php">Admissions</a></li>
                    <li><a href="<?php echo $base_path; ?>courses.php">Courses</a></li>
                    <li><a href="<?php echo $base_path; ?>gallery.php">Gallery</a></li>
                    <li><a href="<?php echo $base_path; ?>contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Departments -->
            <div class="col-lg-3 col-md-6">
                <h5>Departments</h5>
                <ul>
                    <li><a href="<?php echo $base_path; ?>dept-computer.php">Computer Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>dept-electrical.php">Electrical Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>dept-mechanical.php">Mechanical Engineering</a></li>
                    <li><a href="<?php echo $base_path; ?>dept-civil.php">Civil Engineering</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h5>Contact Us</h5>
                <ul style="list-style: none; padding: 0;">
                    <li class="mb-3">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-orange);"></i>
                        <span style="color: #d1d5db;">Patan, Gujarat, India</span>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone me-2" style="color: var(--accent-orange);"></i>
                        <a href="tel:+912766222222">+91-2766-222222</a>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope me-2" style="color: var(--accent-orange);"></i>
                        <a href="mailto:info@kdpolytechnic.edu.in">info@kdpolytechnic.edu.in</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="mb-1">&copy; 2024 K.D. Polytechnic, Patan. All Rights Reserved.</p>
            <p class="mb-0">Designed & Developed with <i class="fas fa-heart" style="color: #f97316;"></i> for Excellence in Education</p>
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
</script>
