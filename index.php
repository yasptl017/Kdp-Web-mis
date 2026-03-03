<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Home - K.D. Polytechnic"; ?>
<?php include 'assets/preload/head.php'; ?>
<body>
    <?php include_once "Admin/dbconfig.php"; ?>
    
    <!-- Top Info Bar -->
    <?php include 'assets/preload/topbar.php'; ?>

    <!-- Header -->
    <?php include 'assets/preload/header.php'; ?>

    <!-- Navigation -->
    <?php include 'assets/preload/navigation.php'; ?>

    <!-- Mobile Navigation -->
    <?php include 'assets/preload/mobilenav.php'; ?>

    <!-- Hero Slider -->
    <?php
    $slider_query = "SELECT * FROM slider ORDER BY id ASC";
    $slider_result = $conn->query($slider_query);
    $first_active = true;
    ?>
    <section id="heroSlider" class="carousel slide hero-slider" data-bs-ride="carousel" style="position: relative;">
        <style>
            #heroSlider .carousel-item::before,
            #heroSlider .carousel-item::after,
            .hero-slider .carousel-item::before,
            .hero-slider .carousel-item::after {
                display: none !important;
                content: none !important;
            }
            #heroSlider .carousel-item {
                background: none !important;
            }
            #heroSlider .carousel-item img {
                filter: none !important;
                opacity: 1 !important;
            }
        </style>
        <div class="carousel-indicators">
            <?php
            $i = 0;
            $indicator_sql = $conn->query("SELECT id FROM slider");
            while ($row = $indicator_sql->fetch_assoc()) {
                echo '<button type="button" data-bs-target="#heroSlider" data-bs-slide-to="'.$i.'" class="'.($i == 0 ? 'active' : '').'" aria-current="'.($i == 0 ? 'true' : 'false').'" aria-label="Slide '.($i+1).'"></button>';
                $i++;
            }
            ?>
        </div>

        <div class="carousel-inner">
            <?php while ($slide = $slider_result->fetch_assoc()) { ?>
                <div class="carousel-item <?php echo $first_active ? 'active' : ''; ?>">
                    <?php $first_active = false; ?>
                    <img src="Admin/<?php echo $slide['slider_image']; ?>" class="d-block w-100" alt="<?php echo $slide['title']; ?>">
                </div>
            <?php } ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </section>

    <!-- Welcome Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Welcome to <?php echo $college['college_name']; ?></h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <p class="lead text-center text-muted" style="line-height: 1.8;">
                        <?php echo nl2br($college['college_description']); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section (hidden) -->

    <!-- Notice Board & Events Section -->
    <?php
    $events_result = $conn->query("SELECT * FROM nb ORDER BY date DESC LIMIT 10");
    $activities_result = $conn->query("SELECT * FROM event_activities ORDER BY event_date DESC LIMIT 5");
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <!-- Notice Board -->
                <div class="col-lg-6">
                    <div class="notice-header">
                        <h3><i class="fas fa-clipboard-list me-2"></i>Notice Board</h3>
                        <a href="Students/notice-board.php" class="btn btn-accent btn-sm">View All</a>
                    </div>
                    
                    <div class="notice-scroll-container">
                        <div class="notice-scroll-wrapper">
                            <?php while ($event = $events_result->fetch_assoc()): ?>
                                <a href="Students/notice-details.php?id=<?php echo $event['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="scroll-notice-item">
                                        <div class="scroll-notice-title"><?php echo $event['title']; ?></div>
                                        <div class="scroll-notice-date">
                                            <i class="fas fa-calendar-check"></i>
                                            <?php echo date("F d, Y", strtotime($event['date'])); ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Events & Activities -->
                <div class="col-lg-6">
                    <div class="notice-header">
                        <h3><i class="fas fa-calendar-alt me-2"></i>Events & Activities</h3>
                        <a href="Campus/events.php" class="btn btn-accent btn-sm">View All</a>
                    </div>
                    
                    <div class="notice-scroll-container">
                        <div class="notice-scroll-wrapper">
                            <?php while ($activity = $activities_result->fetch_assoc()): 
                                $photos = json_decode($activity['photos'], true);
                                $firstPhoto = (!empty($photos) && is_array($photos)) ? $photos[0] : '';
                            ?>
                                <a href="Campus/event-details.php?id=<?php echo $activity['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="scroll-notice-item" style="display: flex; gap: 15px; align-items: center;">
                                        <?php if (!empty($firstPhoto)): ?>
                                            <img src="Admin/<?php echo $firstPhoto; ?>" alt="<?php echo $activity['title']; ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; flex-shrink: 0;">
                                        <?php endif; ?>
                                        <div style="flex: 1;">
                                            <div class="scroll-notice-title"><?php echo $activity['title']; ?></div>
                                            <div class="scroll-notice-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo date("F d, Y", strtotime($activity['event_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <?php
    $testimonials = $conn->query("
        SELECT f.*, d.department 
        FROM feedback f
        JOIN departments d ON d.id = f.department_id
        ORDER BY f.created_at DESC
        LIMIT 3
    ");
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Student Testimonials</h2>
                <p class="section-subtitle">What Our Students Say About Us</p>
            </div>
            
            <div class="row g-4">
                <?php while ($row = $testimonials->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="testimonial-card">
                            <p class="testimonial-text"><?php echo $row['feedback']; ?></p>
                            <div class="testimonial-author">
                                <h6><?php echo $row['student_name']; ?></h6>
                                <p><?php echo $row['department']; ?> • <?php echo $row['passing_year']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Campus Life Gallery - Simple Grid -->
    <?php
    $gallery_result = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
    ?>
    <section class="gallery-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Campus Life Gallery</h2>
                <p class="section-subtitle">Glimpses of Our Vibrant Campus</p>
            </div>

            <div class="gallery-grid">
                <?php 
                $photo_count = 0;
                while ($gallery = $gallery_result->fetch_assoc()): 
                    $photos = json_decode($gallery['photos'], true);
                    if (is_array($photos) && count($photos) > 0):
                        foreach ($photos as $photo):
                            if ($photo_count >= 12) break 2;
                ?>
                            <div class="gallery-image-item" onclick="openGalleryImage('Admin/<?php echo htmlspecialchars($photo); ?>', '<?php echo htmlspecialchars(addslashes($gallery['title'])); ?>')">
                                <img src="Admin/<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($gallery['title']); ?>">
                                <div class="gallery-image-title">
                                    <?php echo htmlspecialchars($gallery['title']); ?>
                                </div>
                                <div class="gallery-zoom-icon">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                <?php
                            $photo_count++;
                        endforeach;
                    endif;
                endwhile;
                ?>
            </div>

            <div class="text-center mt-5">
                <a href="Campus/gallery.php" class="btn btn-primary btn-lg px-5">Explore Full Gallery</a>
            </div>
        </div>
    </section>

    <!-- Image Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="galleryModalLabel">Gallery Image</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0 bg-dark">
                    <img id="galleryModalImage" src="" class="img-fluid" alt="Gallery Image" style="max-height: 85vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'assets/preload/footer.php'; ?>

    <script>
    function openGalleryImage(imageSrc, imageTitle) {
        document.getElementById('galleryModalImage').src = imageSrc;
        document.getElementById('galleryModalLabel').textContent = imageTitle;
        var myModal = new bootstrap.Modal(document.getElementById('galleryModal'));
        myModal.show();
    }

    // Duplicate scroll wrapper content so the loop is seamless, then start animation.
    // Animation is paused in CSS until .scroll-ready is added, preventing blank-screen flash.
    document.querySelectorAll('.notice-scroll-wrapper').forEach(function(wrapper) {
        wrapper.innerHTML += wrapper.innerHTML;
        wrapper.classList.add('scroll-ready');
    });
    </script>
</body>
</html>