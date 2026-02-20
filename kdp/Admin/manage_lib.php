<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/library/";
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
        "SELECT id, display_order FROM library WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM library
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM library
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE library SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE library SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_lib.php");
    exit;
}

/* ===================================
   ADD / UPDATE LIBRARY ENTRY
   =================================== */
if (isset($_POST['save_lib'])) {

    $id = intval($_POST['lib_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $_POST['description']; // Summernote HTML

    // Fetch old photos
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM library WHERE id=$id LIMIT 1");
        if ($q->num_rows > 0) {
            $photosJson = $q->fetch_assoc()['photos'];
            $oldPhotos = json_decode($photosJson, true);
            if (!is_array($oldPhotos)) $oldPhotos = [];
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
        // If no keep_photos submitted, keep all old photos (for backwards compatibility)
        $keepPhotos = $oldPhotos;
    }

    $newPhotos = $keepPhotos;

    // Multiple file upload (add new photos)
    if (!empty($_FILES['photos']['name'][0])) {

        foreach ($_FILES['photos']['name'] as $idx => $val) {

            if ($_FILES['photos']['error'][$idx] == 0) {

                $allowed = ['jpg','jpeg','png','gif','webp'];
                $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {

                    $fileName = "library_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$idx], $filePath)) {
                        $newPhotos[] = $filePath;
                    }
                }
            }
        }
    }

    // FIXED: Use JSON_UNESCAPED_SLASHES to prevent slash escaping
    $jsonPhotos = json_encode($newPhotos, JSON_UNESCAPED_SLASHES);
    $jsonPhotos = $conn->real_escape_string($jsonPhotos);

    if ($id == 0) {
        $sql = "INSERT INTO library (title, display_order, photos, description)
                VALUES ('$title', $display_order, '$jsonPhotos', '$description')";
        $conn->query($sql);
        $message = "Library item added successfully!";
    } else {
        $sql = "UPDATE library SET
                title='$title',
                display_order=$display_order,
                photos='$jsonPhotos',
                description='$description'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Library item updated successfully!";
    }

    $messageType = "success";
}

/* ===================================
   DELETE ITEM
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT photos FROM library WHERE id=$id LIMIT 1");
    if ($q->num_rows > 0) {
        $photosJson = $q->fetch_assoc()['photos'];
        $photos = json_decode($photosJson, true);

        if (is_array($photos)) {
            foreach ($photos as $p) {
                if (file_exists($p)) unlink($p);
            }
        }
    }

    $conn->query("DELETE FROM library WHERE id=$id");
    $message = "Library item deleted!";
    $messageType = "success";
}

// Fetch data
$records = $conn->query("SELECT * FROM library ORDER BY display_order ASC, id DESC");
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" />

<style>
    .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
    .action-buttons{display:flex;gap:5px;justify-content:center}
    
    .thumb-img {
        height: 60px;
        border-radius: 5px;
        border: 1px solid #ccc;
        object-fit: cover;
    }
    .photo-preview {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
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
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-book me-2"></i>Manage Library</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#libModal"
            onclick="addLib()">
        <i class="fas fa-plus-circle"></i> Add Library Details
    </button>

    <div class="form-card">
        <table id="libTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>Photos</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i=1; while($row=$records->fetch_assoc()): ?>
                <?php
                // Decode JSON photos
                $photos = json_decode($row['photos'], true);
                ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>

                    <td class="text-center">
                        <div class="photo-preview">
                            <?php if (is_array($photos) && count($photos) > 0): ?>
                                <?php foreach ($photos as $photo): ?>
                                    <?php if (file_exists($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" 
                                             class="thumb-img" 
                                             alt="Library Photo"
                                             title="<?php echo htmlspecialchars(basename($photo)); ?>">
                                    <?php else: ?>
                                        <span class="text-danger small">Missing: <?php echo htmlspecialchars(basename($photo)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No Photos</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td>
                        <div class="action-buttons">

                            <a href="?move=up&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Up">
                               <i class="fas fa-arrow-up"></i>
                            </a>

                            <a href="?move=down&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Down">
                               <i class="fas fa-arrow-down"></i>
                            </a>

                            <button class="btn btn-warning btn-sm"
                                    onclick='editLib(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#libModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this library item and all its photos?')"
                               class="btn btn-danger btn-sm"
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

<!-- Modal -->
<div class="modal fade" id="libModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Library Item</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="lib_id" id="lib_id">

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Photos (Multiple)</label>
                        <input type="file" 
                               name="photos[]" 
                               id="photos" 
                               multiple 
                               accept="image/*"
                               class="form-control">
                        <small class="text-muted">You can select multiple images at once. Supported: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <div id="existingPhotos" class="mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_lib" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Library Management Page ===');

// Initialize DataTable
$(document).ready(function () {
    $('#libTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']],
        language: {
            search: "Search Library:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ library items",
            infoEmpty: "No library items available",
            zeroRecords: "No matching library items found"
        }
    });
    console.log('✓ DataTable initialized');
});

// Summernote Config
const SNconfig = {
    height: 250,
    placeholder: "Enter library description...",
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'picture', 'table']],
        ['view', ['fullscreen', 'codeview']]
    ]
};

// Initialize Summernote when modal opens
$('#libModal').on('shown.bs.modal', function () {
    console.log('Modal shown - initializing Summernote');
    
    if (!$('#description').next('.note-editor').length) {
        $('#description').summernote(SNconfig);
        console.log('✓ Summernote initialized');
    }
    
    // Load pending description if editing
    if (window.pendingDescription !== undefined) {
        console.log('Loading pending description');
        setTimeout(() => {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 100);
    }
});

// Destroy Summernote when modal closes
$('#libModal').on('hidden.bs.modal', function () {
    console.log('Modal hidden - destroying Summernote');
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

// Add Library Item
function addLib() {
    console.log('Adding new library item');
    
    document.getElementById("modalTitle").innerText = "Add Library Item";
    document.getElementById("lib_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("description").value = "";
    document.getElementById("photos").value = "";
    document.getElementById("existingPhotos").innerHTML = "";
    
    window.pendingDescription = undefined;
}

// Edit Library Item
function editLib(item) {
    console.log('Editing library item:', item.id);
    
    document.getElementById("modalTitle").innerText = "Update Library Item";
    document.getElementById("lib_id").value = item.id;
    document.getElementById("title").value = item.title;
    document.getElementById("display_order").value = item.display_order;
    
    // Show existing photos with checkboxes
    let photos = [];
    try {
        photos = JSON.parse(item.photos);
    } catch(e) {
        console.error('Error parsing photos JSON:', e);
    }
    
    if (photos && photos.length > 0) {
        let html = '<div class="alert alert-info">';
        html += '<strong><i class="fas fa-images me-2"></i>Existing Photos:</strong>';
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
    
    // Store description for when Summernote initializes
    window.pendingDescription = item.description;
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

console.log('✓ Library Management page loaded');
</script>

</body>
</html>