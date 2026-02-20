<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Gallery - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include_once "../Admin/dbconfig.php"; ?>
    
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Photo Gallery</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Gallery</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $gallery_query = "SELECT * FROM gallery WHERE display_order >= 0 ORDER BY display_order ASC";
    $gallery_result = $conn->query($gallery_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($gallery_result && $gallery_result->num_rows > 0): ?>
                <?php while ($gallery = $gallery_result->fetch_assoc()): 
                    $photos = json_decode($gallery['photos'], true);
                    $galleryId = 'gallery-' . $gallery['id'];
                ?>
                    <div class="gallery-album mb-5">
                        <div class="album-header">
                            <h3><i class="fas fa-images me-2"></i><?php echo $gallery['title']; ?></h3>
                            <span class="photo-count"><?php echo count($photos); ?> Photos</span>
                        </div>
                        <?php if (!empty($photos) && is_array($photos)): ?>
                            <div class="gallery-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="gallery-image-wrapper" onclick="openGalleryLightbox('<?php echo $galleryId; ?>', <?php echo $index; ?>)">
                                        <img src="../Admin/<?php echo $photo; ?>" class="gallery-image" alt="Gallery">
                                        <div class="image-overlay">
                                            <i class="fas fa-search-plus"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Lightbox Modal -->
                            <div id="lightbox-<?php echo $galleryId; ?>" class="lightbox-modal" onclick="closeGalleryLightbox('<?php echo $galleryId; ?>')">
                                <span class="lightbox-close">&times;</span>
                                <span class="lightbox-prev" onclick="event.stopPropagation(); changeGallerySlide('<?php echo $galleryId; ?>', -1)">&#10094;</span>
                                <span class="lightbox-next" onclick="event.stopPropagation(); changeGallerySlide('<?php echo $galleryId; ?>', 1)">&#10095;</span>
                                <div class="lightbox-content" onclick="event.stopPropagation()">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <img src="../Admin/<?php echo $photo; ?>" class="lightbox-slide" data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <div class="lightbox-caption" id="caption-<?php echo $galleryId; ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No images available.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let galleryCurrentSlides = {};
        
        function openGalleryLightbox(galleryId, index) {
            const modal = document.getElementById('lightbox-' + galleryId);
            modal.style.display = 'flex';
            galleryCurrentSlides[galleryId] = index;
            showGallerySlide(galleryId, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeGalleryLightbox(galleryId) {
            const modal = document.getElementById('lightbox-' + galleryId);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function changeGallerySlide(galleryId, n) {
            const slides = document.querySelectorAll('#lightbox-' + galleryId + ' .lightbox-slide');
            galleryCurrentSlides[galleryId] += n;
            if (galleryCurrentSlides[galleryId] >= slides.length) galleryCurrentSlides[galleryId] = 0;
            if (galleryCurrentSlides[galleryId] < 0) galleryCurrentSlides[galleryId] = slides.length - 1;
            showGallerySlide(galleryId, galleryCurrentSlides[galleryId]);
        }
        
        function showGallerySlide(galleryId, index) {
            const slides = document.querySelectorAll('#lightbox-' + galleryId + ' .lightbox-slide');
            slides.forEach(slide => slide.style.display = 'none');
            if(slides[index]) {
                slides[index].style.display = 'block';
                document.getElementById('caption-' + galleryId).innerHTML = (index + 1) + ' / ' + slides.length;
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                Object.keys(galleryCurrentSlides).forEach(id => closeGalleryLightbox(id));
            }
            if (event.key === 'ArrowLeft') {
                Object.keys(galleryCurrentSlides).forEach(id => changeGallerySlide(id, -1));
            }
            if (event.key === 'ArrowRight') {
                Object.keys(galleryCurrentSlides).forEach(id => changeGallerySlide(id, 1));
            }
        });
    </script>
</body>
</html>