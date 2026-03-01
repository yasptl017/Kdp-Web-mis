<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";
$message = "";
$messageType = "";
$uploadDir = "uploads/gallery_photos/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* -------------------------
   MOVE UP / DOWN
------------------------- */
if (isset($_GET['move'], $_GET['id'])) {

    $id = intval($_GET['id']);
    $direction = $_GET['move']; // up | down

    $current = $conn->query(
        "SELECT id, display_order FROM gallery WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM gallery
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM gallery
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE gallery SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE gallery SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_gallery.php");
    exit;
}

/* ==================================================
   ADD / UPDATE GALLERY
   ================================================== */
if (isset($_POST['save_gallery'])) {
    $id = intval($_POST['gallery_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    
    // OLD photos (if editing)
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM gallery WHERE id=$id");
        if ($q->num_rows > 0) {
            $oldPhotos = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        }
    }
    
    // Handle photo removal (keep only checked photos)
    $keepPhotos = [];
    if ($id > 0 && isset($_POST['keep_photos']) && is_array($_POST['keep_photos'])) {
        // Keep only the photos that were checked
        foreach ($_POST['keep_photos'] as $photoPath) {
            if (in_array($photoPath, $oldPhotos)) {
                $keepPhotos[] = $photoPath;
            }
        }
        
        // Delete unchecked photos from disk
        foreach ($oldPhotos as $oldPhoto) {
            if (!in_array($oldPhoto, $keepPhotos)) {
                if (file_exists($oldPhoto)) {
                    unlink($oldPhoto);
                }
            }
        }
    } else {
        // If no keep_photos submitted, keep all old photos
        $keepPhotos = $oldPhotos;
    }
    
    $photos = $keepPhotos;
    
    // Upload new photos
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if ($_FILES['photos']['error'][$i] == 0) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $newName = "gallery_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $targetPath = $uploadDir . $newName;
                    move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetPath);
                    $photos[] = $targetPath;
                }
            }
        }
    }
    
    $photosJSON = json_encode(array_values($photos), JSON_UNESCAPED_SLASHES);
    $photosJSON = $conn->real_escape_string($photosJSON);
    
    if ($id == 0) {
        $sql = "INSERT INTO gallery (title, display_order, photos)
                VALUES ('$title', $display_order, '$photosJSON')";
        $conn->query($sql);
        $message = "Gallery added successfully!";
    } else {
        $sql = "UPDATE gallery SET
                title='$title',
                display_order=$display_order,
                photos='$photosJSON'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Gallery updated successfully!";
    }
    $messageType = "success";
}

/* ==================================================
   DELETE GALLERY
   ================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $q = $conn->query("SELECT photos FROM gallery WHERE id=$id");
    if ($q->num_rows > 0) {
        $photoArr = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        foreach ($photoArr as $p) {
            if (file_exists($p)) unlink($p);
        }
    }
    $conn->query("DELETE FROM gallery WHERE id=$id");
    $message = "Gallery deleted!";
    $messageType = "success";
}

$galleries = $conn->query("SELECT * FROM gallery ORDER BY display_order ASC, id DESC");
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
    .action-buttons{display:flex;gap:5px;justify-content:center}
    
    /* Photo selection styles */
    .photo-selector {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .photo-item {
        position: relative;
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 8px;
        background: #f8f9fa;
        transition: all 0.3s;
    }
    
    .photo-item:hover {
        border-color: #4e73df;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .photo-item.selected {
        border-color: #28a745;
        background: #d4edda;
    }
    
    .photo-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .photo-checkbox {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 24px;
        height: 24px;
        cursor: pointer;
        z-index: 10;
    }
    
    .photo-filename {
        font-size: 11px;
        color: #666;
        margin-top: 5px;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .select-all-controls {
        margin-bottom: 10px;
        padding: 10px;
        background: #e7f3ff;
        border-radius: 5px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .photo-count-badge {
        background: #4e73df;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-images me-2"></i>Manage Gallery</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#galleryModal" onclick="addGallery()">
        <i class="fas fa-plus-circle"></i> Add Gallery
    </button>

    <div class="form-card">
        <table id="galleryTable" class="table table-bordered table-striped align-middle">
            <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th>Order</th>
                <th>Photos</th>
                <th>Thumbnail</th>
                <th width="200">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $i=1; while($gallery=$galleries->fetch_assoc()): 
                $photos = json_decode($gallery['photos'], true) ?? [];
                $firstPhoto = $photos[0] ?? "";
                $photoCount = count($photos);
            ?>
                <tr>
                    <td class="text-center"><?= $i++; ?></td>
                    <td><?= htmlspecialchars($gallery['title']); ?></td>
                    <td class="text-center"><?= $gallery['display_order']; ?></td>
                    <td class="text-center">
                        <span class="photo-count-badge">
                            <i class="fas fa-images"></i> <?= $photoCount; ?> Photo<?= $photoCount != 1 ? 's' : ''; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($firstPhoto && file_exists($firstPhoto)): ?>
                            <img src="<?= htmlspecialchars($firstPhoto) ?>" height="60" style="border-radius:5px;object-fit:cover;">
                        <?php else: ?>
                            <span class="text-muted">No photo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">

                            <a href="?move=up&id=<?= $gallery['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Up">
                               <i class="fas fa-arrow-up"></i>
                            </a>

                            <a href="?move=down&id=<?= $gallery['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Down">
                               <i class="fas fa-arrow-down"></i>
                            </a>

                            <button class="btn btn-sm btn-warning"
                                onclick='editGallery(<?= json_encode($gallery, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#galleryModal"
                                title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?= $gallery['id']; ?>" 
                               onclick="return confirm('Delete this gallery and all its photos?');"
                               class="btn btn-sm btn-danger"
                               title="Delete">
                               <i class="fas fa-trash"></i>
                            </a>

                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- GALLERY MODAL -->
<div class="modal fade" id="galleryModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Gallery</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="gallery_id" id="gallery_id">

                    <div class="mb-3">
                        <label class="form-label">Gallery Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Photos (Multiple) <span class="text-danger">*</span></label>
                        <input type="file" name="photos[]" id="photos" class="form-control" multiple accept="image/*">
                        <small class="text-muted">Supported: JPG, PNG, GIF, WEBP. You can select multiple photos at once.</small>
                    </div>

                    <div id="existingPhotos" class="mb-3"></div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_gallery" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Gallery
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts -->
<script>
console.log('=== Gallery Management ===');

// Initialize DataTable
$(document).ready(() => {
    $('#galleryTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']],
        language: {
            search: "Search Galleries:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ galleries",
            infoEmpty: "No galleries available",
            zeroRecords: "No matching galleries found"
        }
    });
    console.log('✓ DataTable initialized');
});

// Add Gallery
function addGallery() {
    console.log('Adding new gallery');
    
    document.getElementById("modalTitle").innerText = "Add Gallery";
    document.getElementById("gallery_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("photos").value = "";
    document.getElementById("existingPhotos").innerHTML = "";
}

// Edit Gallery
function editGallery(gallery) {
    console.log('Editing gallery:', gallery.id);
    
    document.getElementById("modalTitle").innerText = "Update Gallery";
    document.getElementById("gallery_id").value = gallery.id;
    document.getElementById("title").value = gallery.title;
    document.getElementById("display_order").value = gallery.display_order;
    
    // Show existing photos with checkboxes
    let photos = [];
    try {
        photos = JSON.parse(gallery.photos);
    } catch(e) {
        console.error('Error parsing photos JSON:', e);
    }
    
    if (photos && photos.length > 0) {
        let html = '<div class="alert alert-info">';
        html += '<strong><i class="fas fa-images me-2"></i>Existing Photos (' + photos.length + '):</strong>';
        html += '<p class="mb-2 small text-muted">Uncheck photos you want to remove. New photos will be added to the checked ones.</p>';
        
        // Select All / Deselect All controls
        html += '<div class="select-all-controls">';
        html += '<button type="button" class="btn btn-sm btn-success" onclick="selectAllPhotos(true)"><i class="fas fa-check-square"></i> Select All</button>';
        html += '<button type="button" class="btn btn-sm btn-warning" onclick="selectAllPhotos(false)"><i class="fas fa-square"></i> Deselect All</button>';
        html += '<span class="ms-auto text-muted small"><span id="selectedCount">' + photos.length + '</span> / ' + photos.length + ' selected</span>';
        html += '</div>';
        
        html += '<div class="photo-selector" id="photoSelector">';
        
        photos.forEach(function(photo, index) {
            let filename = photo.split('/').pop();
            html += '<div class="photo-item selected" id="photoItem_' + index + '">';
            html += '<input type="checkbox" name="keep_photos[]" value="' + photo + '" class="photo-checkbox" id="photo_' + index + '" checked onchange="togglePhotoSelection(' + index + ')">';
            html += '<label for="photo_' + index + '" style="cursor:pointer; margin:0;">';
            html += '<img src="' + photo + '" alt="Photo">';
            html += '<div class="photo-filename" title="' + filename + '">' + filename + '</div>';
            html += '</label>';
            html += '</div>';
        });
        
        html += '</div></div>';
        document.getElementById("existingPhotos").innerHTML = html;
    } else {
        document.getElementById("existingPhotos").innerHTML = "";
    }
}

// Toggle photo selection
function togglePhotoSelection(index) {
    const checkbox = document.getElementById('photo_' + index);
    const photoItem = document.getElementById('photoItem_' + index);
    
    if (checkbox.checked) {
        photoItem.classList.add('selected');
    } else {
        photoItem.classList.remove('selected');
    }
    
    updateSelectedCount();
}

// Select/Deselect all photos
function selectAllPhotos(select) {
    const checkboxes = document.querySelectorAll('.photo-checkbox');
    const photoItems = document.querySelectorAll('.photo-item');
    
    checkboxes.forEach((checkbox, index) => {
        checkbox.checked = select;
        if (select) {
            photoItems[index].classList.add('selected');
        } else {
            photoItems[index].classList.remove('selected');
        }
    });
    
    updateSelectedCount();
}

// Update selected photo count
function updateSelectedCount() {
    const checkedCount = document.querySelectorAll('.photo-checkbox:checked').length;
    const countElement = document.getElementById('selectedCount');
    if (countElement) {
        countElement.textContent = checkedCount;
    }
}

console.log('✓ Gallery Management page loaded');
</script>

</body>
</html>