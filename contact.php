<?php
include_once "Admin/dbconfig.php";
?>
<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Contact Us - K.D. Polytechnic"; ?>
<?php include 'assets/preload/head.php'; ?>
<body>
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

    <!-- Map & Contact Information -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-5">
                <!-- Google Map - Left Side -->
                <div class="col-lg-6">
                    <div class="map-wrapper">
                        <h3 class="contact-section-title">Find Us on Map</h3>
                        <p class="text-muted mb-4">Visit our campus and explore our facilities.</p>
                        
                        <div class="map-container">
                            <iframe 
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3648.876478304796!2d72.1359549!3d23.858519599999997!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395c87e13b3c42b3%3A0xe28f6043d4bb1e48!2s631%20k%20d%20polytechnic%20Patan!5e0!3m2!1sen!2sin!4v1771481741964!5m2!1sen!2sin" 
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

                <!-- Contact Details - Right Side -->
                <div class="col-lg-6">
                    <div class="contact-details-wrapper">
                        <h3 class="contact-section-title">Get in Touch</h3>
                        <p class="text-muted mb-4">Feel free to reach out to us through any of the following channels.</p>
                        
                        <div class="contact-details-list">
                            <!-- Address -->
                            <div class="contact-detail-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-content">
                                    <h5>Our Address</h5>
                                    <p><?php echo nl2br($college['address']); ?></p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="contact-detail-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-content">
                                    <h5>Call Us</h5>
                                    <p><a href="tel:<?php echo $college['contact_no']; ?>"><?php echo $college['contact_no']; ?></a></p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="contact-detail-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-content">
                                    <h5>Email Us</h5>
                                    <p><a href="mailto:<?php echo $college['email']; ?>"><?php echo $college['email']; ?></a></p>
                                </div>
                            </div>


                            <!-- Social Media -->
                            <div class="contact-detail-item">
                                <div class="contact-icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <div class="contact-content">
                                    <h5>Connect With Us</h5>
                                    <div class="social-links-inline">
                                        <?php if (!empty($college['facebook_link'])): ?>
                                            <a href="<?php echo $college['facebook_link']; ?>" target="_blank" class="social-link facebook" title="Facebook">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($college['instagram_link'])): ?>
                                            <a href="<?php echo $college['instagram_link']; ?>" target="_blank" class="social-link instagram" title="Instagram">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($college['twitter_link'])): ?>
                                            <a href="<?php echo $college['twitter_link']; ?>" target="_blank" class="social-link twitter" title="Twitter">
                                                <i class="fab fa-twitter"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($college['linkedin_link'])): ?>
                                            <a href="<?php echo $college['linkedin_link']; ?>" target="_blank" class="social-link linkedin" title="LinkedIn">
                                                <i class="fab fa-linkedin-in"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Info Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="info-box text-center">
                        <div class="info-icon mb-3">
                            <i class="fas fa-graduation-cap fa-3x text-primary"></i>
                        </div>
                        <h5>Admissions</h5>
                        <p class="text-muted">Learn about our admission process and requirements for prospective students.</p>
                        <a href="Academics/admission.php" class="btn btn-outline-primary btn-sm">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="info-box text-center">
                        <div class="info-icon mb-3">
                            <i class="fas fa-book fa-3x text-primary"></i>
                        </div>
                        <h5>Academics</h5>
                        <p class="text-muted">Explore our diverse range of programs and courses designed for excellence.</p>
                        <a href="Academics/intake.php" class="btn btn-outline-primary btn-sm">View Courses</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="info-box text-center">
                        <div class="info-icon mb-3">
                            <i class="fas fa-building fa-3x text-primary"></i>
                        </div>
                        <h5>Campus Life</h5>
                        <p class="text-muted">Discover our state-of-the-art facilities and vibrant campus environment.</p>
                        <a href="About/facilities.php" class="btn btn-outline-primary btn-sm">Explore Campus</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>

    <style>
        .contact-details-wrapper {
            height: 100%;
        }

        .contact-details-list {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .contact-detail-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .contact-detail-item:hover {
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .contact-detail-item .contact-icon {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            border-radius: 10px;
            font-size: 20px;
        }

        .contact-detail-item .contact-content {
            flex: 1;
        }

        .contact-detail-item .contact-content h5 {
            margin: 0 0 10px 0;
            color: #1e3a8a;
            font-size: 18px;
            font-weight: 600;
        }

        .contact-detail-item .contact-content p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }

        .contact-detail-item .contact-content a {
            color: #f97316;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-detail-item .contact-content a:hover {
            color: #ea580c;
        }

        .social-links-inline {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .social-links-inline .social-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links-inline .social-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .social-links-inline .social-link.facebook {
            background: #1877f2;
        }

        .social-links-inline .social-link.instagram {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .social-links-inline .social-link.twitter {
            background: #1da1f2;
        }

        .social-links-inline .social-link.linkedin {
            background: #0077b5;
        }

        .info-box {
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .info-box:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .info-box h5 {
            color: #1e3a8a;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .contact-section-title {
            color: #1e3a8a;
            font-weight: 700;
            margin-bottom: 10px;
        }

        @media (max-width: 991px) {
            .contact-detail-item {
                flex-direction: column;
                text-align: center;
            }

            .contact-detail-item .contact-icon {
                margin: 0 auto;
            }

            .social-links-inline {
                justify-content: center;
            }
        }
    </style>
</body>
</html>