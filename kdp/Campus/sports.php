<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Sports - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Sports</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Sports</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $sports_query = "SELECT * FROM sports WHERE display_order >= 0 ORDER BY display_order ASC";
    $sports_result = $conn->query($sports_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($sports_result && $sports_result->num_rows > 0): ?>
                <?php 
                $sportIndex = 0;
                while ($sport = $sports_result->fetch_assoc()): 
                    $photos = json_decode($sport['photos'], true);
                    $sportId = 'sport-' . $sportIndex++;
                ?>
                    <div class="facility-detail-card mb-5">
                        <h2 class="facility-main-title">
                            <i class="fas fa-trophy me-2"></i><?php echo $sport['title']; ?>
                        </h2>
                        <?php if (!empty($sport['description'])): ?>
                            <div class="facility-description"><?php echo $sport['description']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($photos) && is_array($photos)): ?>
                            <div class="facility-photos-section">
                                <h4 class="photos-title">
                                    <i class="fas fa-images me-2"></i>Sports Photos (<?php echo count($photos); ?>)
                                </h4>
                                <div class="photo-grid">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" onclick="openSportModal('<?php echo $sportId; ?>', <?php echo $index; ?>)">
                                            <img src="../Admin/<?php echo $photo; ?>" alt="Sports">
                                            <div class="photo-overlay">
                                                <i class="fas fa-expand"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Modal -->
                            <div id="imageModal-<?php echo $sportId; ?>" class="image-modal" onclick="closeSportModal('<?php echo $sportId; ?>')">
                                <span class="modal-close">&times;</span>
                                <span class="modal-prev" onclick="event.stopPropagation(); navigateSportModal('<?php echo $sportId; ?>', -1)">&#10094;</span>
                                <span class="modal-next" onclick="event.stopPropagation(); navigateSportModal('<?php echo $sportId; ?>', 1)">&#10095;</span>
                                <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <div class="modal-counter" id="counter-<?php echo $sportId; ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Sports information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let sportModalIndex = {};
        
        function openSportModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            sportModalIndex[id] = index;
            showSportModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeSportModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigateSportModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            sportModalIndex[id] += direction;
            if (sportModalIndex[id] >= images.length) sportModalIndex[id] = 0;
            if (sportModalIndex[id] < 0) sportModalIndex[id] = images.length - 1;
            showSportModalImage(id, sportModalIndex[id]);
        }
        
        function showSportModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Object.keys(sportModalIndex).forEach(id => closeSportModal(id));
            }
            if (e.key === 'ArrowLeft') {
                Object.keys(sportModalIndex).forEach(id => navigateSportModal(id, -1));
            }
            if (e.key === 'ArrowRight') {
                Object.keys(sportModalIndex).forEach(id => navigateSportModal(id, 1));
            }
        });
    </script>
</body>
</html>