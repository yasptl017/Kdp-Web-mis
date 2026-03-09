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

                <?php
                // Get event tags from dynamic_menu
                $event_tags = !empty($page['event_tags']) ? json_decode($page['event_tags'], true) : [];
                
                if (!empty($event_tags) && is_array($event_tags)):
                    // Pagination
                    $items_per_page = 9;
                    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $offset = ($current_page - 1) * $items_per_page;
                    
                    // category column stores a JSON array e.g. ["FacultyAchievement"]
                    // Use LIKE to match any tag inside that array (handles both JSON arrays and single string values)
                    $category_conditions = [];
                    foreach ($event_tags as $tag) {
                        $tag = trim($tag);
                        if ($tag !== '') {
                            $tag_escaped = $conn->real_escape_string($tag);
                            // Check if category exists in JSON array or equals single category
                            $category_conditions[] = "(category LIKE '%\"$tag_escaped\"%' OR category = '$tag_escaped')";
                        }
                    }
                    if (empty($category_conditions)) { $category_conditions[] = '1=0'; }
                    $where_clause = "(" . implode(" OR ", $category_conditions) . ")";
                    
                    // Count total matching events
                    $count_query = "SELECT COUNT(*) as total FROM event_activities WHERE $where_clause";
                    $count_result = $conn->query($count_query);
                    $total_events = $count_result->fetch_assoc()['total'];
                    $total_pages = ceil($total_events / $items_per_page);
                    
                    // Fetch events with pagination
                    $events_query = "SELECT * FROM event_activities WHERE $where_clause ORDER BY event_date DESC LIMIT $items_per_page OFFSET $offset";
                    $events_result = $conn->query($events_query);
                ?>

                <div class="related-events-section mt-5">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-check me-2"></i>Related Events & Activities
                        </h3>
                        <div class="results-badge">
                            <i class="fas fa-info-circle"></i>
                            <strong><?php echo $total_events; ?></strong> <?php echo $total_events == 1 ? 'Event' : 'Events'; ?>
                        </div>
                    </div>

                    <?php if ($events_result && $events_result->num_rows > 0): ?>
                        <div class="row g-4">
                            <?php while ($event = $events_result->fetch_assoc()): 
                                $event_photos = json_decode($event['photos'], true);
                                
                                // Handle multiple categories
                                $categories = json_decode($event['category'], true);
                                if (!is_array($categories)) {
                                    $categories = [$event['category']];
                                }
                            ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="activity-card" onclick="window.location.href='Campus/event-details.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                                        <div class="activity-category-badges">
                                            <?php foreach ($categories as $cat): ?>
                                                <span class="activity-category-badge">
                                                    <?php echo htmlspecialchars($cat); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if (!empty($event_photos) && is_array($event_photos) && count($event_photos) > 0): ?>
                                            <div class="activity-image">
                                                <img src="Admin/<?php echo $event_photos[0]; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                                <div class="activity-image-overlay">
                                                    <i class="fas fa-eye"></i>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="activity-image activity-no-image">
                                                <i class="fas fa-image"></i>
                                                <p>No Image</p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="activity-content">
                                            <h5 class="activity-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                            
                                            <div class="activity-date mb-2">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <?php echo date("F d, Y", strtotime($event['event_date'])); ?>
                                            </div>

                                            <p class="activity-description">
                                                <?php 
                                                $desc = strip_tags($event['description']);
                                                echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav class="pagination-nav mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $page_id; ?>&page=<?php echo $current_page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $page_id; ?>&page=1">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $page_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $page_id; ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?php echo $page_id; ?>&page=<?php echo $current_page + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times" style="font-size: 4rem; color: #cbd5e1;"></i>
                            <h4 class="text-muted mt-3">No Related Events Found</h4>
                        </div>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>Page not found.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>

    <style>
    .related-events-section {
        margin-top: 60px;
        padding-top: 40px;
        border-top: 3px solid #e5e7eb;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .section-title {
        color: #1e3a8a;
        font-weight: 700;
        font-size: 1.8rem;
        margin: 0;
        text-align: left !important;
    }
    .section-title::after {
        left: 0 !important;
        transform: none !important;
    }

    .results-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 0.9rem;
        border: 1px solid #bfdbfe;
    }

    .results-badge i {
        font-size: 1rem;
    }

    .activity-card { 
        background: white; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
        transition: all 0.3s ease; 
        height: 100%; 
        position: relative; 
        border: 2px solid transparent; 
    }
    
    .activity-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); 
        border-color: #f97316; 
    }
    
    .activity-category-badge { 
        top: 12px; 
        right: 12px; 
        background: linear-gradient(135deg, #f97316, #ea580c); 
        color: white; 
        padding: 5px 14px; 
        border-radius: 20px; 
        font-size: 0.75rem; 
        font-weight: 600; 
        z-index: 2; 
        box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3); 
    }
    
    .activity-category-badges { 
        position: absolute; 
        top: 12px; 
        right: 12px; 
        z-index: 2; 
        display: flex; 
        flex-direction: column; 
        gap: 6px; 
        align-items: flex-end;
    }
    
    .activity-image { 
        width: 100%; 
        height: 240px; 
        overflow: hidden; 
        position: relative; 
    }
    
    .activity-image img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: transform 0.3s ease; 
    }
    
    .activity-card:hover .activity-image img { 
        transform: scale(1.08); 
    }
    
    .activity-image-overlay { 
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
    
    .activity-card:hover .activity-image-overlay { 
        opacity: 1; 
    }
    
    .activity-image-overlay i { 
        color: white; 
        font-size: 2rem; 
    }
    
    .activity-no-image { 
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb); 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        color: #9ca3af; 
    }
    
    .activity-no-image i { 
        font-size: 3rem; 
        margin-bottom: 10px; 
    }
    
    .activity-content { 
        padding: 20px; 
    }
    
    .activity-title { 
        color: #1e3a8a; 
        font-weight: 700; 
        margin-bottom: 12px; 
        font-size: 1.1rem; 
        line-height: 1.4; 
        min-height: 55px; 
    }
    
    .activity-date { 
        color: #6b7280; 
        font-size: 0.85rem; 
        font-weight: 500; 
    }
    
    .activity-description { 
        color: #6b7280; 
        line-height: 1.6; 
        font-size: 0.9rem; 
    }

    .pagination-nav {
        margin-top: 40px;
    }

    .pagination {
        gap: 5px;
    }

    .page-item .page-link {
        border: 2px solid #e5e7eb;
        color: #475569;
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .page-item .page-link:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e3a8a;
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        border-color: #1e3a8a;
        color: white;
    }

    .page-item.disabled .page-link {
        background: #f8f9fa;
        border-color: #e5e7eb;
        color: #cbd5e1;
    }

    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .section-title {
            font-size: 1.4rem;
        }

        .pagination {
            flex-wrap: wrap;
            gap: 3px;
        }

        .page-item .page-link {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
    }
    </style>

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