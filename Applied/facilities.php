<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

$page_title = "Facilities - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
?>
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
                    <h1 class="page-title">Department Facilities</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="index.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Facilities</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
<?php include 'dptnavigation.php'; ?>

    <?php
    $facilities_query = "SELECT * FROM dept_facilities WHERE department = '$DEPARTMENT_NAME' AND display_order >= 0 ORDER BY display_order ASC";
    $facilities_result = $conn->query($facilities_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($facilities_result && $facilities_result->num_rows > 0): ?>
                <?php 
                $facilityIndex = 0;
                while ($facility = $facilities_result->fetch_assoc()): 
                    $photos = json_decode($facility['photos'], true);
                    $facilityId = 'facility-' . $facilityIndex++;
                ?>
                    <div class="facility-detail-card mb-5">
                        <h2 class="facility-main-title">
                            <i class="fas fa-building me-2"></i><?php echo $facility['facility']; ?>
                        </h2>
                        <?php if (!empty($facility['description'])): ?>
                            <div class="facility-description"><?php echo $facility['description']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($photos) && is_array($photos)): ?>
                            <div class="facility-photos-section">
                                <h4 class="photos-title">
                                    <i class="fas fa-images me-2"></i>Facility Photos (<?php echo count($photos); ?>)
                                </h4>
                                <div class="photo-grid">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="photo-item" onclick="openFacilityModal('<?php echo $facilityId; ?>', <?php echo $index; ?>)">
                                            <img src="../Admin/<?php echo $photo; ?>" alt="Facility">
                                            <div class="photo-overlay">
                                                <i class="fas fa-expand"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Modal -->
                            <div id="imageModal-<?php echo $facilityId; ?>" class="image-modal" onclick="closeFacilityModal('<?php echo $facilityId; ?>')">
                                <span class="modal-close">&times;</span>
                                <span class="modal-prev" onclick="event.stopPropagation(); navigateFacilityModal('<?php echo $facilityId; ?>', -1)">&#10094;</span>
                                <span class="modal-next" onclick="event.stopPropagation(); navigateFacilityModal('<?php echo $facilityId; ?>', 1)">&#10095;</span>
                                <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <div class="modal-counter" id="counter-<?php echo $facilityId; ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Facilities information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let facilityModalIndex = {};
        
        function openFacilityModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            facilityModalIndex[id] = index;
            showFacilityModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeFacilityModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigateFacilityModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            facilityModalIndex[id] += direction;
            if (facilityModalIndex[id] >= images.length) facilityModalIndex[id] = 0;
            if (facilityModalIndex[id] < 0) facilityModalIndex[id] = images.length - 1;
            showFacilityModalImage(id, facilityModalIndex[id]);
        }
        
        function showFacilityModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') Object.keys(facilityModalIndex).forEach(id => closeFacilityModal(id));
            if (e.key === 'ArrowLeft') Object.keys(facilityModalIndex).forEach(id => navigateFacilityModal(id, -1));
            if (e.key === 'ArrowRight') Object.keys(facilityModalIndex).forEach(id => navigateFacilityModal(id, 1));
        });
    </script>
</body>
</html>