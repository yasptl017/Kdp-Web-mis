<!DOCTYPE html>
<html lang="en">
<?php
include 'dptname.php';
$page_title = "Placement - " . $DEPARTMENT_NAME . " - K.D. Polytechnic";
?>
<?php include '../assets/preload/head.php'; ?>
<link rel="stylesheet" href="../assets/css/dept-pages.css">
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
                    <h1 class="page-title">Placement</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="aboutdpt.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Placement</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Department Navigation -->
    <?php include 'dptnavigation.php'; ?>

    <?php
    $dept_esc = $conn->real_escape_string($DEPARTMENT_NAME);
    $placements_result = $conn->query(
        "SELECT * FROM dept_placement
         WHERE department = '$dept_esc' AND display_order >= 0
         ORDER BY display_order ASC, id DESC"
    );
    ?>

    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($placements_result && $placements_result->num_rows > 0): ?>
                <?php
                $placementIndex = 0;
                while ($placement = $placements_result->fetch_assoc()):
                    $photos    = json_decode($placement['photos'], true) ?? [];
                    $tags      = json_decode($placement['event_tags'], true) ?? [];
                    $modalId   = 'placement-' . $placementIndex++;

                    // Fetch matching events by tag (category match)
                    $matchedEvents = [];
                    if (!empty($tags)) {
                        $jsonConditions = implode(' OR ', array_map(function($t) use ($conn) {
                            $tj = $conn->real_escape_string(json_encode(trim($t)));
                            return "JSON_CONTAINS(category, '$tj', '$')";
                        }, $tags));
                        $evResult = $conn->query(
                            "SELECT id, title, event_date, photos FROM event_activities
                             WHERE $jsonConditions
                             ORDER BY event_date DESC"
                        );
                        if ($evResult) {
                            while ($ev = $evResult->fetch_assoc()) {
                                $matchedEvents[] = $ev;
                            }
                        }
                    }
                ?>
                <div class="facility-detail-card mb-5">
                    <h2 class="facility-main-title">
                        <i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($placement['title']); ?>
                    </h2>

                    <?php if (!empty($placement['description'])): ?>
                        <div class="dept-description"><?php echo $placement['description']; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($photos) && is_array($photos)): ?>
                        <div class="facility-photos-section mt-4">
                            <h4 class="photos-title">
                                <i class="fas fa-images me-2"></i>Photos
                                <span class="badge bg-secondary ms-2"><?php echo count($photos); ?></span>
                            </h4>
                            <div class="photo-grid">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <div class="photo-item" onclick="openPlacementModal('<?php echo $modalId; ?>', <?php echo $index; ?>)">
                                        <img src="../Admin/<?php echo htmlspecialchars($photo); ?>" alt="Placement Photo">
                                        <div class="photo-overlay"><i class="fas fa-expand"></i></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Photo Lightbox Modal -->
                        <div id="imageModal-<?php echo $modalId; ?>" class="image-modal" onclick="closePlacementModal('<?php echo $modalId; ?>')">
                            <span class="modal-close">&times;</span>
                            <span class="modal-prev" onclick="event.stopPropagation(); navigatePlacementModal('<?php echo $modalId; ?>', -1)">&#10094;</span>
                            <span class="modal-next" onclick="event.stopPropagation(); navigatePlacementModal('<?php echo $modalId; ?>', 1)">&#10095;</span>
                            <div class="modal-content-wrapper" onclick="event.stopPropagation()">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <img src="../Admin/<?php echo htmlspecialchars($photo); ?>" class="modal-image" data-index="<?php echo $index; ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="modal-counter" id="counter-<?php echo $modalId; ?>"></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($matchedEvents)): ?>
                        <div class="mt-4">
                            <h4 class="photos-title">
                                <i class="fas fa-calendar-alt me-2"></i>Related Events
                                <span class="badge bg-secondary ms-2"><?php echo count($matchedEvents); ?></span>
                            </h4>
                            <div class="row g-3">
                                <?php foreach ($matchedEvents as $ev):
                                    $evPhotos  = json_decode($ev['photos'], true) ?? [];
                                    $evThumb   = !empty($evPhotos) ? '../Admin/' . $evPhotos[0] : '';
                                ?>
                                <div class="col-md-4 col-sm-6">
                                    <a href="../Campus/event-details.php?id=<?php echo $ev['id']; ?>" style="text-decoration:none; color:inherit;">
                                        <div class="scroll-notice-item d-flex gap-3 align-items-center">
                                            <?php if ($evThumb): ?>
                                                <img src="<?php echo htmlspecialchars($evThumb); ?>"
                                                     alt="<?php echo htmlspecialchars($ev['title']); ?>"
                                                     style="width:70px; height:70px; object-fit:cover; border-radius:6px; flex-shrink:0;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="scroll-notice-title" style="font-size:14px;">
                                                    <?php echo htmlspecialchars($ev['title']); ?>
                                                </div>
                                                <div class="scroll-notice-date" style="font-size:12px;">
                                                    <i class="fas fa-calendar-check"></i>
                                                    <?php echo date("d M Y", strtotime($ev['event_date'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Placement information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
        var placementModalIndex = {};

        function openPlacementModal(id, index) {
            var modal = document.getElementById('imageModal-' + id);
            modal.style.display = 'flex';
            placementModalIndex[id] = index;
            showPlacementModalImage(id, index);
            document.body.style.overflow = 'hidden';
        }
        function closePlacementModal(id) {
            document.getElementById('imageModal-' + id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        function navigatePlacementModal(id, direction) {
            var images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            placementModalIndex[id] += direction;
            if (placementModalIndex[id] >= images.length) placementModalIndex[id] = 0;
            if (placementModalIndex[id] < 0) placementModalIndex[id] = images.length - 1;
            showPlacementModalImage(id, placementModalIndex[id]);
        }
        function showPlacementModalImage(id, index) {
            var images = document.querySelectorAll('#imageModal-' + id + ' .modal-image');
            images.forEach(function(img) { img.style.display = 'none'; });
            if (images[index]) {
                images[index].style.display = 'block';
                document.getElementById('counter-' + id).textContent = (index + 1) + ' / ' + images.length;
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape')      Object.keys(placementModalIndex).forEach(function(id) { closePlacementModal(id); });
            if (e.key === 'ArrowLeft')   Object.keys(placementModalIndex).forEach(function(id) { navigatePlacementModal(id, -1); });
            if (e.key === 'ArrowRight')  Object.keys(placementModalIndex).forEach(function(id) { navigatePlacementModal(id, 1); });
        });
    </script>
</body>
</html>
