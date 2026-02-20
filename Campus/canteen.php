<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Canteen - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Canteen</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Canteen</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $canteen_query = "SELECT * FROM canteen WHERE display_order >= 0 ORDER BY display_order ASC LIMIT 1";
    $canteen_result = $conn->query($canteen_query);
    $canteen = $canteen_result->fetch_assoc();
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($canteen): 
                $photos = json_decode($canteen['photos'], true);
            ?>
                <div class="facility-detail-card">
                    <h2 class="facility-main-title">
                        <i class="fas fa-utensils me-2"></i><?php echo $canteen['title']; ?>
                    </h2>
                    <?php if (!empty($canteen['description'])): ?>
                        <div class="facility-description"><?php echo $canteen['description']; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($photos) && is_array($photos)): ?>
                        <div class="facility-photos-section">
                            <h4 class="photos-title">
                                <i class="fas fa-images me-2"></i>Canteen Photos (<?php echo count($photos); ?>)
                            </h4>
                            <div class="photo-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="photo-item" onclick="openCanteenModal('canteen', <?php echo $index; ?>)">
                                        <img src="../Admin/<?php echo $photo; ?>" alt="Canteen">
                                        <div class="photo-overlay">
                                            <i class="fas fa-expand"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Modal -->
                        <div id="imageModal-canteen" class="image-modal" onclick="closeCanteenModal('canteen')">
                            <span class="modal-close">&times;</span>
                            <span class="modal-prev" onclick="event.stopPropagation(); navigateCanteenModal('canteen', -1)">&#10094;</span>
                            <span class="modal-next" onclick="event.stopPropagation(); navigateCanteenModal('canteen', 1)">&#10095;</span>
                            <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-counter" id="counter-canteen"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Canteen information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let canteenModalIndex = {};
        
        function openCanteenModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            canteenModalIndex[id] = index;
            showCanteenModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeCanteenModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigateCanteenModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            canteenModalIndex[id] += direction;
            if (canteenModalIndex[id] >= images.length) canteenModalIndex[id] = 0;
            if (canteenModalIndex[id] < 0) canteenModalIndex[id] = images.length - 1;
            showCanteenModalImage(id, canteenModalIndex[id]);
        }
        
        function showCanteenModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Object.keys(canteenModalIndex).forEach(id => closeCanteenModal(id));
            }
            if (e.key === 'ArrowLeft') {
                Object.keys(canteenModalIndex).forEach(id => navigateCanteenModal(id, -1));
            }
            if (e.key === 'ArrowRight') {
                Object.keys(canteenModalIndex).forEach(id => navigateCanteenModal(id, 1));
            }
        });
    </script>
</body>
</html>