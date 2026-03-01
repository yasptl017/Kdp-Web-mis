<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/departments/";
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
        "SELECT id, display_order FROM about_department WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM about_department
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM about_department
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE about_department SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE about_department SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: about_dept.php");
    exit;
}

/* ===================================
   ADD / UPDATE DEPARTMENT
   =================================== */
if (isset($_POST['save_dept'])) {

    $id = intval($_POST['dept_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']); // HTML content from Summernote

    // Fetch old photos
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM about_department WHERE id=$id LIMIT 1");
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
                    $newName = "dept_" . time() . "_" . rand(1000,9999) . "." . $ext;
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

    if ($id == 0) {
        // Insert
        $sql = "INSERT INTO about_department (dept_name, display_order, description, photos)
                VALUES ('$department', $display_order, '$description', '$photosJSON')";
        $conn->query($sql);
        $message = "Department added successfully!";
    } else {
        // Update
        $sql = "UPDATE about_department SET
                dept_name='$department',
                display_order=$display_order,
                description='$description',
                photos='$photosJSON'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Department updated successfully!";
    }

    $messageType = "success";
}

/* ===================================
   DELETE DEPARTMENT
   =================================== */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);
    
    // Delete photos first
    $q = $conn->query("SELECT photos FROM about_department WHERE id=$id LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $photos = json_decode($q->fetch_assoc()['photos'], true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                if (file_exists($photo)) unlink($photo);
            }
        }
    }

    $conn->query("DELETE FROM about_department WHERE id=$id");

    $message = "Department deleted!";
    $messageType = "success";
}

/* ===================================
   FETCH ALL
   =================================== */
// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $data = $conn->query("SELECT * FROM about_department ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $data = $conn->query("SELECT * FROM about_department WHERE dept_name='$userDept' ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
}
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
    
    .photo-preview {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .thumb-img {
        height: 60px;
        border-radius: 4px;
        border: 1px solid #ccc;
        object-fit: cover;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-building me-2"></i>Manage Department About</h2>

    <!-- Alerts -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ADD BUTTON -->
    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#deptModal"
            onclick="addDept()">
        <i class="fas fa-plus-circle"></i> Add Department Info
    </button>

    <!-- TABLE -->
    <div class="form-card">
        <table id="deptTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Order</th>
                    <th>Photos</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php $i=1; while ($row = $data->fetch_assoc()): ?>
                <?php
                // Decode JSON photos
                $photos = json_decode($row['photos'], true) ?? [];
                ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    
                    <td class="text-center">
                        <div class="photo-preview">
                            <?php if (is_array($photos) && count($photos) > 0): ?>
                                <?php foreach ($photos as $photo): ?>
                                    <?php if (file_exists($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" 
                                             class="thumb-img" 
                                             alt="Department Photo"
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
                                    onclick='editDept(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#deptModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this department and all its photos?');"
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

<!-- MODAL -->
<div class="modal fade" id="deptModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Department Info</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="dept_id" id="dept_id">

                    <!-- Department Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department" id="department" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php 
                            $departments->data_seek(0); // Reset pointer for modal
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <!-- Photos Upload -->
                    <div class="mb-3">
                        <label class="form-label">Upload Photos (Multiple)</label>
                        <input type="file" name="photos[]" id="photos" multiple class="form-control" accept="image/*">
                        <small class="text-muted">You can select multiple images at once. Supported: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <div id="existingPhotos" class="mb-3"></div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_dept" class="btn btn-success">
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

<script>
console.log('=== Department About Management ===');

// Summernote configuration
// Summernote config moved to summernote-config.js

// Initialize Summernote when modal is shown, then load pending content
$('#deptModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 300, placeholder: "Enter department description here..."});
    }
    if (window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = undefined;
    }
});

// Destroy Summernote when modal is hidden
$('#deptModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = undefined;
});

function addDept() {
    document.getElementById("modalTitle").innerText = "Add Department Info";
    document.getElementById("dept_id").value = "";
    document.getElementById("department").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("photos").value = "";
    document.getElementById("existingPhotos").innerHTML = "";
    window.pendingDescription = undefined;
}

function editDept(f) {
    document.getElementById("modalTitle").innerText = "Update Department Info";
    document.getElementById("dept_id").value = f.id;
    document.getElementById("department").value = f.dept_name;
    document.getElementById("display_order").value = f.display_order;

    // Store description for when Summernote is shown
    window.pendingDescription = f.description;

    // Show existing photos with checkboxes
    let photos = [];
    try {
        photos = JSON.parse(f.photos);
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
    $('#deptTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']], // Sort by Order column
        language: {
            search: "Search Departments:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ departments",
            infoEmpty: "No departments available",
            zeroRecords: "No matching departments found"
        },
        columnDefs: [
            { orderable: false, targets: [3, 4] } // Disable sorting on Photos and Actions
        ]
    });
    console.log('✓ DataTable initialized');
});

console.log('✓ Department About Management page loaded');
</script>

</body>
</html>