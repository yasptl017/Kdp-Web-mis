<!DOCTYPE html>
<html lang="en">
<?php 
// ============================================
// DEPARTMENT CONFIGURATION
// Change this variable for other departments
// ============================================
include 'dptname.php';
// ============================================

$page_title = $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
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
                    <h1 class="page-title"><?php echo $DEPARTMENT_NAME; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item active"><?php echo $DEPARTMENT_NAME; ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
    <?php include 'dptnavigation.php'; ?>

    <?php
    $dept_query = "SELECT * FROM about_department WHERE dept_name = '$DEPARTMENT_NAME' LIMIT 1";
    $dept_result = $conn->query($dept_query);
    $dept = $dept_result->fetch_assoc();
    ?>

    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($dept): 
                $photos = json_decode($dept['photos'], true);
            ?>
                <div class="dept-about-card">
                    <h2 class="dept-main-title">About <?php echo $DEPARTMENT_NAME; ?></h2>
                    
                    <?php if (!empty($dept['description'])): ?>
                        <div class="dept-description">
                            <?php echo $dept['description']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($photos) && is_array($photos)): ?>
                        <div class="dept-photos-section">
                            <h4 class="photos-section-title">
                                <i class="fas fa-images me-2"></i>Department Gallery
                            </h4>
                            <div class="dept-photo-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="dept-photo-item" onclick="openDeptModal('dept', <?php echo $index; ?>)">
                                        <img src="../Admin/<?php echo $photo; ?>" alt="Department">
                                        <div class="dept-photo-overlay">
                                            <i class="fas fa-expand"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Modal -->
                        <div id="imageModal-dept" class="image-modal" onclick="closeDeptModal('dept')">
                            <span class="modal-close">&times;</span>
                            <span class="modal-prev" onclick="event.stopPropagation(); navigateDeptModal('dept', -1)">&#10094;</span>
                            <span class="modal-next" onclick="event.stopPropagation(); navigateDeptModal('dept', 1)">&#10095;</span>
                            <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <img src="../Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-counter" id="counter-dept"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Department information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        let deptModalIndex = {};
        
        function openDeptModal(id, index) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            deptModalIndex[id] = index;
            showDeptModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeptModal(id) {
            const modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function navigateDeptModal(id, direction) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            deptModalIndex[id] += direction;
            if (deptModalIndex[id] >= images.length) deptModalIndex[id] = 0;
            if (deptModalIndex[id] < 0) deptModalIndex[id] = images.length - 1;
            showDeptModalImage(id, deptModalIndex[id]);
        }
        
        function showDeptModalImage(id, index) {
            const images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(img => img.style.display = 'none');
            if(images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') Object.keys(deptModalIndex).forEach(id => closeDeptModal(id));
            if (e.key === 'ArrowLeft') Object.keys(deptModalIndex).forEach(id => navigateDeptModal(id, -1));
            if (e.key === 'ArrowRight') Object.keys(deptModalIndex).forEach(id => navigateDeptModal(id, 1));
        });
    </script>
</body>
</html>
