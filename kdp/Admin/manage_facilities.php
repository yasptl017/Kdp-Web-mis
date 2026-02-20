<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/facilities/";
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
        "SELECT id, display_order FROM facilities WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM facilities
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM facilities
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE facilities SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE facilities SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_facilities.php");
    exit;
}

/* ===================================
   ADD / UPDATE FACILITY
   =================================== */
if (isset($_POST['save_facility'])) {

    $id = intval($_POST['facility_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $_POST['description']; // Summernote HTML

    // Fetch old photos
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM facilities WHERE id=$id LIMIT 1");
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

    $newPhotos = [];

    // Handle Multiple Photo Upload (add new photos)
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $fileName) {

            if ($_FILES['photos']['error'][$index] == 0) {
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp','gif'];

                if (in_array($ext, $allowed)) {
                    $newName = "facility_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $target = $uploadDir . $newName;

                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$index], $target)) {
                        $newPhotos[] = $target;
                    }
                }
            }
        }
    }

    // Merge kept photos + new photos
    $allPhotos = array_merge($keepPhotos, $newPhotos);
    $photosJSON = json_encode($allPhotos, JSON_UNESCAPED_SLASHES);
    $photosJSON = $conn->real_escape_string($photosJSON);

    // Insert / Update
    if ($id == 0) {
        $sql = "INSERT INTO facilities (title, display_order, photos, description)
                VALUES ('$title', $display_order, '$photosJSON', '$description')";
        $conn->query($sql);
        $message = "Facility added successfully!";
        $messageType = "success";
    } else {
        $sql = "UPDATE facilities SET 
                title='$title',
                display_order=$display_order,
                photos='$photosJSON',
                description='$description'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Facility updated successfully!";
        $messageType = "success";
    }
}

/* ===================================
   DELETE FACILITY
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Delete photos first
    $q = $conn->query("SELECT photos FROM facilities WHERE id=$id LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $photos = json_decode($q->fetch_assoc()['photos'], true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                if (file_exists($photo)) unlink($photo);
            }
        }
    }

    $conn->query("DELETE FROM facilities WHERE id=$id");
    $message = "Facility deleted!";
    $messageType = "success";
}

$facilities = $conn->query("SELECT * FROM facilities ORDER BY display_order ASC, id DESC");
?>

<!-- Page-specific CSS (Summernote + DataTables) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
.form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
.action-buttons{display:flex;gap:5px;justify-content:center}
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-building me-2"></i>Facility Management</h2>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#facilityModal"
            onclick="addFacility()">
        <i class="fas fa-plus-circle"></i> Add Facility
    </button>

    <div class="form-card">
        <table id="facilityTable" class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>Photos</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $facilities->fetch_assoc()): ?>
                <?php
                // Decode JSON photos
                $photos = json_decode($row['photos'], true) ?? [];
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
                                             alt="Facility Photo"
                                             title="<?php echo htmlspecialchars(basename($photo)); ?>">
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
                                    onclick='editFacility(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#facilityModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this facility and all its photos?');"
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

<!-- Facility Modal -->
<div class="modal fade" id="facilityModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data" id="facilityForm">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-building me-2"></i>Add Facility
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="facility_id" id="facility_id">

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" 
                               placeholder="e.g., Computer Lab" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Photos (Multiple)</label>
                        <input type="file" name="photos[]" id="photos" multiple class="form-control" accept="image/*">
                        <small class="text-muted">You can select multiple images at once. Supported: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <div id="existingPhotos" class="mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_facility" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts (Load AFTER footer.php which has jQuery) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Facilities Management ===');

// Summernote configuration
const summernoteConfig = {
    height: 300,
    placeholder: 'Enter facility description here...',
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture']],
        ['view', ['fullscreen', 'codeview']]
    ]
};

// Initialize Summernote when modal is shown
$('#facilityModal').on('shown.bs.modal', function () {
    console.log('Modal shown - initializing Summernote');
    
    if (!$('#description').next('.note-editor').length) {
        $('#description').summernote(summernoteConfig);
        console.log('✓ Summernote initialized');
    }
    
    // Load pending description if editing
    if (window.pendingDescription !== undefined) {
        console.log('Loading pending description');
        setTimeout(function() {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 100);
    }
});

// Destroy Summernote when modal is hidden
$('#facilityModal').on('hidden.bs.modal', function () {
    console.log('Modal hidden - destroying Summernote');
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
        console.log('✓ Summernote destroyed');
    }
});

// Add Facility function
function addFacility() {
    console.log('Adding new facility');
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-building me-2"></i>Add Facility';
    document.getElementById('facility_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('display_order').value = '';
    document.getElementById('description').value = '';
    document.getElementById('photos').value = '';
    document.getElementById('existingPhotos').innerHTML = '';
    
    window.pendingDescription = undefined;
}

// Edit Facility function
function editFacility(facility) {
    console.log('Editing facility:', facility.id);
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Update Facility';
    document.getElementById('facility_id').value = facility.id;
    document.getElementById('title').value = facility.title;
    document.getElementById('display_order').value = facility.display_order;
    
    // Store description for when Summernote initializes
    window.pendingDescription = facility.description;

    // Show existing photos with checkboxes
    let photos = [];
    try {
        photos = JSON.parse(facility.photos);
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

// Initialize DataTable
$(document).ready(function() {
    console.log('Initializing DataTable');
    
    $('#facilityTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']], // Sort by display_order ascending
        language: {
            search: "Search Facilities:",
            lengthMenu: "Show _MENU_ facilities per page",
            info: "Showing _START_ to _END_ of _TOTAL_ facilities",
            infoEmpty: "No facilities available",
            zeroRecords: "No matching facilities found"
        },
        columnDefs: [
            { orderable: false, targets: [3, 4] } // Disable sorting on Photos and Actions columns
        ]
    });
    
    console.log('✓ DataTable initialized');
});

console.log('✓ Facilities Management page loaded');
</script>

</body>
</html>