<!DOCTYPE html>
<html lang="en">
<?php 
include_once "Admin/dbconfig.php";
$page_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$page_query = "SELECT * FROM dynamic_menu WHERE id = ?";
$stmt = $conn->prepare($page_query);
$stmt->bind_param("i", $page_id);
$stmt->execute();
$page_result = $stmt->get_result();
$page = $page_result->fetch_assoc();
$stmt->close();
$page_title = $page ? $page['title'] . " - K.D. Polytechnic" : "Page Not Found - K.D. Polytechnic";
?>
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
                    <h1 class="page-title"><?php echo $page ? htmlspecialchars($page['title']) : 'Page Not Found'; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <?php if ($page): ?>
                                <li class="breadcrumb-item"><a href="#"><?php echo htmlspecialchars($page['menu']); ?></a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($page['title']); ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active">Error</li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <?php if ($page): 
                $photos = !empty($page['photos']) ? json_decode($page['photos'], true) : null;
            ?>
                <div class="facility-detail-card">
                    <h2 class="facility-main-title"><?php echo htmlspecialchars($page['title']); ?></h2>
                    <?php if (!empty($page['description'])): ?>
                        <div class="facility-description"><?php echo $page['description']; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($photos) && is_array($photos)): ?>
                        <div class="facility-photos-section">
                            <h4 class="photos-title"><i class="fas fa-images me-2"></i>Photo Gallery (<?php echo count($photos); ?> Photos)</h4>
                            <div class="photo-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="photo-item" onclick="openPhotoModal('dynamic-<?php echo $page['id']; ?>', <?php echo $index; ?>)">
                                        <img src="Admin/<?php echo $photo; ?>" alt="<?php echo htmlspecialchars($page['title']); ?>">
                                        <div class="photo-overlay">
                                            <i class="fas fa-expand"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div id="imageModal-dynamic-<?php echo $page['id']; ?>" class="image-modal" onclick="closePhotoModal('dynamic-<?php echo $page['id']; ?>')">
                            <span class="modal-close">&times;</span>
                            <span class="modal-prev" onclick="event.stopPropagation(); navigatePhotoModal('dynamic-<?php echo $page['id']; ?>', -1)">&#10094;</span>
                            <span class="modal-next" onclick="event.stopPropagation(); navigatePhotoModal('dynamic-<?php echo $page['id']; ?>', 1)">&#10095;</span>
                            <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <img src="Admin/<?php echo $photo; ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-counter" id="counter-dynamic-<?php echo $page['id']; ?>"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>Page not found.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>

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