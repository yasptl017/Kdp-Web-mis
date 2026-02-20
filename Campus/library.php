<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Library - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Library</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Library</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $library_query = "SELECT * FROM library WHERE display_order >= 0 ORDER BY display_order ASC, created_at DESC";
    $library_result = $conn->query($library_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <?php if ($library_result && $library_result->num_rows > 0): ?>
                <?php while ($library = $library_result->fetch_assoc()): 
                    $photos = json_decode($library['photos'], true);
                ?>
                    <div class="facility-detail-card">
                        <h2 class="facility-main-title"><?php echo $library['title']; ?></h2>
                        <?php if (!empty($library['description'])): ?>
                            <div class="facility-description"><?php echo $library['description']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($photos) && is_array($photos)): ?>
                            <div class="facility-photos-section">
                                <h4 class="photos-title"><i class="fas fa-images me-2"></i>Photo Gallery (<?php echo count($photos); ?> Photos)</h4>
                                <div class="photo-grid">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" onclick="openPhotoModal('library-<?php echo $library['id']; ?>', <?php echo $index; ?>)">
                                            <img src="../Admin/<?php echo $photo; ?>" alt="Library">
                                            <div class="photo-overlay">
                                                <i class="fas fa-expand"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Image Modal -->
                            <div id="imageModal-library-<?php echo $library['id']; ?>" class="image-modal" onclick="closePhotoModal('library-<?php echo $library['id']; ?>')">
                                <span class="modal-close">&times;</span>
                                <span class="modal-prev" onclick="event.stopPropagation(); navigatePhotoModal('library-<?php echo $library['id']; ?>', -1)">&#10094;</span>
                                <span class="modal-next" onclick="event.stopPropagation(); navigatePhotoModal('library-<?php echo $library['id']; ?>', 1)">&#10095;</span>
                                <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <div class="modal-counter" id="counter-library-<?php echo $library['id']; ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Library information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let photoModalIndex = {};
        
        function openPhotoModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            photoModalIndex[id] = index;
            showPhotoModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closePhotoModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigatePhotoModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            photoModalIndex[id] += direction;
            if (photoModalIndex[id] >= images.length) photoModalIndex[id] = 0;
            if (photoModalIndex[id] < 0) photoModalIndex[id] = images.length - 1;
            showPhotoModalImage(id, photoModalIndex[id]);
        }
        
        function showPhotoModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Object.keys(photoModalIndex).forEach(id => closePhotoModal(id));
            }
            if (e.key === 'ArrowLeft') {
                Object.keys(photoModalIndex).forEach(id => navigatePhotoModal(id, -1));
            }
            if (e.key === 'ArrowRight') {
                Object.keys(photoModalIndex).forEach(id => navigatePhotoModal(id, 1));
            }
        });
    </script>
</body>
</html>