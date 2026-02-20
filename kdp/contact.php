<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Contact Us - K.D. Polytechnic"; ?>
<?php include 'assets/preload/head.php'; ?>
<body>
    <?php include_once "Admin/dbconfig.php"; ?>
    
    <?php include 'assets/preload/topbar.php'; ?>
    <?php include 'assets/preload/header.php'; ?>
    <?php include 'assets/preload/navigation.php'; ?>
    <?php include 'assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Contact Us</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Contact</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information Cards -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Our Address</h5>
                        <p><?php echo nl2br($college['address']); ?></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h5>Call Us</h5>
                        <p><a href="tel:<?php echo $college['contact_no']; ?>"><?php echo $college['contact_no']; ?></a></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Us</h5>
                        <p><a href="mailto:<?php echo $college['email']; ?>"><?php echo $college['email']; ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-6">
                    <div class="contact-form-wrapper">
                        <h3 class="contact-section-title">Send Us a Message</h3>
                        <p class="text-muted mb-4">Fill out the form below and we'll get back to you as soon as possible.</p>
                        
                        <form id="contactForm" method="POST" action="contact-handler.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                        
                        <div id="formMessage" class="mt-3"></div>
                    </div>
                </div>

                <!-- Google Map -->
                <div class="col-lg-6">
                    <div class="map-wrapper">
                        <h3 class="contact-section-title">Find Us on Map</h3>
                        <p class="text-muted mb-4">Visit our campus and explore our facilities.</p>
                        
                        <div class="map-container">
                            <!-- Replace with your actual Google Maps embed code -->
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3671.9883936248467!2d72.56873631496286!3d23.02196668494945!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395e848aba5bd449%3A0x4fcedd11614f6516!2sAhmedabad%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1234567890123!5m2!1sen!2sin" 
                                width="100%" 
                                height="500" 
                                style="border:0; border-radius: 10px;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center">
                <h3 class="contact-section-title">Connect With Us</h3>
                <p class="text-muted mb-4">Follow us on social media for latest updates</p>
                <div class="social-links-large">
                    <?php if (!empty($college['facebook_link'])): ?>
                        <a href="<?php echo $college['facebook_link']; ?>" target="_blank" class="social-link facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['instagram_link'])): ?>
                        <a href="<?php echo $college['instagram_link']; ?>" target="_blank" class="social-link instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['twitter_link'])): ?>
                        <a href="<?php echo $college['twitter_link']; ?>" target="_blank" class="social-link twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($college['linkedin_link'])): ?>
                        <a href="<?php echo $college['linkedin_link']; ?>" target="_blank" class="social-link linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>

    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('formMessage');
            
            fetch('contact-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                    document.getElementById('contactForm').reset();
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.</div>';
            });
        });
    </script>
</body>
</html>