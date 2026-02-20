<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Hostel - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Hostel Facilities</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Hostel</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $hostel_query = "SELECT * FROM hostel WHERE display_order >= 0 ORDER BY display_order ASC";
    $hostel_result = $conn->query($hostel_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($hostel_result && $hostel_result->num_rows > 0): ?>
                <?php 
                $hostelIndex = 0;
                while ($hostel = $hostel_result->fetch_assoc()): 
                    $photos = json_decode($hostel['photos'], true);
                    $hostelId = 'hostel-' . $hostelIndex++;
                ?>
                    <div class="facility-detail-card mb-5">
                        <h2 class="facility-main-title">
                            <i class="fas fa-bed me-2"></i><?php echo $hostel['title']; ?>
                        </h2>
                        <?php if (!empty($hostel['description'])): ?>
                            <div class="facility-description"><?php echo $hostel['description']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($photos) && is_array($photos)): ?>
                            <div class="facility-photos-section">
                                <h4 class="photos-title">
                                    <i class="fas fa-images me-2"></i>Hostel Photos (<?php echo count($photos); ?>)
                                </h4>
                                <div class="photo-grid">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" onclick="openHostelModal('<?php echo $hostelId; ?>', <?php echo $index; ?>)">
                                            <img src="../Admin/<?php echo $photo; ?>" alt="Hostel">
                                            <div class="photo-overlay">
                                                <i class="fas fa-expand"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Modal -->
                            <div id="imageModal-<?php echo $hostelId; ?>" class="image-modal" onclick="closeHostelModal('<?php echo $hostelId; ?>')">
                                <span class="modal-close">&times;</span>
                                <span class="modal-prev" onclick="event.stopPropagation(); navigateHostelModal('<?php echo $hostelId; ?>', -1)">&#10094;</span>
                                <span class="modal-next" onclick="event.stopPropagation(); navigateHostelModal('<?php echo $hostelId; ?>', 1)">&#10095;</span>
                                <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <div class="modal-counter" id="counter-<?php echo $hostelId; ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Hostel information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let hostelModalIndex = {};
        
        function openHostelModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            hostelModalIndex[id] = index;
            showHostelModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeHostelModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigateHostelModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            hostelModalIndex[id] += direction;
            if (hostelModalIndex[id] >= images.length) hostelModalIndex[id] = 0;
            if (hostelModalIndex[id] < 0) hostelModalIndex[id] = images.length - 1;
            showHostelModalImage(id, hostelModalIndex[id]);
        }
        
        function showHostelModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Object.keys(hostelModalIndex).forEach(id => closeHostelModal(id));
            }
            if (e.key === 'ArrowLeft') {
                Object.keys(hostelModalIndex).forEach(id => navigateHostelModal(id, -1));
            }
            if (e.key === 'ArrowRight') {
                Object.keys(hostelModalIndex).forEach(id => navigateHostelModal(id, 1));
            }
        });
    </script>
</body>
</html>