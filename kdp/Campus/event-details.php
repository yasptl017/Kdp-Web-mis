<!DOCTYPE html>
<html lang="en">
<?php 
include_once "../Admin/dbconfig.php";

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$event_query = "SELECT * FROM event_activities WHERE id = $event_id";
$event_result = $conn->query($event_query);

if (!$event_result || $event_result->num_rows == 0) {
    header("Location: events.php");
    exit();
}

$event = $event_result->fetch_assoc();
$photos = json_decode($event['photos'], true);
$page_title = $event['title'] . " - K.D. Polytechnic"; 
?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Event Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($event['title']); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="activity-detail-card">
                        <div class="activity-detail-header">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h2 class="activity-detail-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                                <span class="badge-category"><?php echo htmlspecialchars($event['category']); ?></span>
                            </div>
                            
                            <div class="activity-meta-info">
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date("F d, Y", strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Posted on <?php echo date("F d, Y", strtotime($event['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($event['description'])): ?>
                        <div class="activity-detail-description">
                            <h5 class="section-heading">
                                <i class="fas fa-align-left me-2"></i>Description
                            </h5>
                            <div class="description-content">
                                <?php echo $event['description']; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($photos) && is_array($photos) && count($photos) > 0): ?>
                        <div class="activity-detail-gallery">
                            <h5 class="section-heading">
                                <i class="fas fa-images me-2"></i>Photo Gallery (<?php echo count($photos); ?> Photos)
                            </h5>
                            <div class="photo-gallery-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                <div class="gallery-photo-item" onclick="openPhotoModal(<?php echo $index; ?>)">
                                    <img src="../Admin/<?php echo $photo; ?>" alt="Event Photo <?php echo $index + 1; ?>">
                                    <div class="photo-overlay">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="activity-detail-footer">
                            <a href="events.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Photo Modal -->
    <?php if (!empty($photos) && is_array($photos)): ?>
    <div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
        <span class="modal-close-btn">&times;</span>
        <button class="modal-nav-btn modal-prev-btn" onclick="event.stopPropagation(); navigatePhoto(-1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="modal-nav-btn modal-next-btn" onclick="event.stopPropagation(); navigatePhoto(1)">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="modal-image-container" onclick="event.stopPropagation()">
            <?php foreach ($photos as $index => $photo): ?>
            <img src="../Admin/<?php echo $photo; ?>" class="modal-photo" data-index="<?php echo $index; ?>" style="display: none;">
            <?php endforeach; ?>
        </div>
        <div class="modal-counter"></div>
    </div>
    <?php endif; ?>

    <?php include '../assets/preload/footer.php'; ?>

    <style>
    .activity-detail-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .activity-detail-header {
        border-bottom: 3px solid #f3f4f6;
        padding-bottom: 30px;
        margin-bottom: 30px;
    }

    .activity-detail-title {
        color: #1e3a8a;
        font-weight: 700;
        font-size: 2rem;
        line-height: 1.3;
        margin: 0;
    }

    .badge-category {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .activity-meta-info {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
        margin-top: 20px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #6b7280;
        font-size: 0.95rem;
    }

    .meta-item i {
        color: #3b82f6;
        font-size: 1.1rem;
    }

    .activity-detail-description {
        margin-bottom: 40px;
    }

    .section-heading {
        color: #1e3a8a;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .description-content {
        color: #4b5563;
        line-height: 1.8;
        font-size: 1.05rem;
    }

    .activity-detail-gallery {
        margin-bottom: 40px;
    }

    .photo-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .gallery-photo-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        aspect-ratio: 1;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .gallery-photo-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .gallery-photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .gallery-photo-item:hover img {
        transform: scale(1.1);
    }

    .photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .gallery-photo-item:hover .photo-overlay {
        opacity: 1;
    }

    .photo-overlay i {
        color: white;
        font-size: 2rem;
    }

    .activity-detail-footer {
        border-top: 3px solid #f3f4f6;
        padding-top: 30px;
        text-align: center;
    }

    /* Photo Modal */
    .photo-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.95);
        align-items: center;
        justify-content: center;
    }

    .modal-image-container {
        max-width: 90%;
        max-height: 90vh;
        position: relative;
    }

    .modal-photo {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 8px;
    }

    .modal-close-btn {
        position: absolute;
        top: 20px;
        right: 40px;
        color: white;
        font-size: 50px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
        transition: color 0.3s;
    }

    .modal-close-btn:hover {
        color: #f97316;
    }

    .modal-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 2rem;
        padding: 20px 15px;
        cursor: pointer;
        z-index: 10000;
        transition: all 0.3s;
        border-radius: 8px;
    }

    .modal-nav-btn:hover {
        background: rgba(249, 115, 22, 0.8);
    }

    .modal-prev-btn {
        left: 30px;
    }

    .modal-next-btn {
        right: 30px;
    }

    .modal-counter {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .activity-detail-card {
            padding: 25px;
        }

        .activity-detail-title {
            font-size: 1.5rem;
        }

        .photo-gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .activity-meta-info {
            flex-direction: column;
            gap: 15px;
        }

        .modal-nav-btn {
            padding: 15px 10px;
            font-size: 1.5rem;
        }

        .modal-prev-btn {
            left: 10px;
        }

        .modal-next-btn {
            right: 10px;
        }
    }
    </style>

    <script>
    let currentPhotoIndex = 0;
    const totalPhotos = <?php echo !empty($photos) ? count($photos) : 0; ?>;

    function openPhotoModal(index) {
        currentPhotoIndex = index;
        document.getElementById('photoModal').style.display = 'flex';
        showPhoto(currentPhotoIndex);
        document.body.style.overflow = 'hidden';
    }

    function closePhotoModal() {
        document.getElementById('photoModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function navigatePhoto(direction) {
        currentPhotoIndex += direction;
        if (currentPhotoIndex >= totalPhotos) currentPhotoIndex = 0;
        if (currentPhotoIndex < 0) currentPhotoIndex = totalPhotos - 1;
        showPhoto(currentPhotoIndex);
    }

    function showPhoto(index) {
        const photos = document.querySelectorAll('.modal-photo');
        photos.forEach(photo => photo.style.display = 'none');
        if (photos[index]) {
            photos[index].style.display = 'block';
            document.querySelector('.modal-counter').textContent = (index + 1) + ' / ' + totalPhotos;
        }
    }

    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('photoModal');
        if (modal && modal.style.display === 'flex') {
            if (e.key === 'Escape') closePhotoModal();
            if (e.key === 'ArrowLeft') navigatePhoto(-1);
            if (e.key === 'ArrowRight') navigatePhoto(1);
        }
    });
    </script>
</body>
</html>