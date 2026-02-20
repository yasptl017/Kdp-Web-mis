<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload Directory
$uploadDir = "uploads/activities/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===================================
   ADD / UPDATE ACTIVITY
   =================================== */
if (isset($_POST['save_activity'])) {

    $id = intval($_POST['activity_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $category = $conn->real_escape_string($_POST['category']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $_POST['description'];  // Summernote HTML
    $date = $conn->real_escape_string($_POST['date']);
    $remark = $conn->real_escape_string($_POST['remark']);

    // Fetch old photos
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM activities WHERE id=$id LIMIT 1");
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
                    $newName = "activity_" . time() . "_" . rand(1000,9999) . "." . $ext;
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
        $sql = "INSERT INTO activities (department, category, title, description, date, remark, photos)
                VALUES ('$department', '$category', '$title', '$description', '$date', '$remark', '$photosJSON')";
        $conn->query($sql);
        $message = "Activity added successfully!";
        $messageType = "success";
    } else {
        $sql = "UPDATE activities SET 
                department='$department',
                category='$category',
                title='$title',
                description='$description',
                date='$date',
                remark='$remark',
                photos='$photosJSON'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Activity updated successfully!";
        $messageType = "success";
    }
}

/* ===================================
   DELETE ACTIVITY
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Delete photos first
    $q = $conn->query("SELECT photos FROM activities WHERE id=$id LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $photos = json_decode($q->fetch_assoc()['photos'], true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                if (file_exists($photo)) unlink($photo);
            }
        }
    }

    $conn->query("DELETE FROM activities WHERE id=$id");
    $message = "Activity deleted!";
    $messageType = "success";
}

// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $activities = $conn->query("SELECT * FROM activities ORDER BY id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $activities = $conn->query("SELECT * FROM activities WHERE department='$userDept' ORDER BY id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
}
?>

<!-- Page-specific CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
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
        height: 50px;
        border-radius: 4px;
        border: 1px solid #ccc;
        object-fit: cover;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-tasks me-2"></i>Manage Activities</h2>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#activityModal"
            onclick="addActivity()">
        <i class="fas fa-plus-circle"></i> Add Activity
    </button>

    <div class="form-card">
        <table id="activityTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Photos</th>
                    <th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $activities->fetch_assoc()): ?>
                <?php
                // Decode JSON photos
                $photos = json_decode($row['photos'], true) ?? [];
                ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['date']); ?></td>
                    
                    <td class="text-center">
                        <div class="photo-preview">
                            <?php if (is_array($photos) && count($photos) > 0): ?>
                                <?php foreach ($photos as $photo): ?>
                                    <?php if (file_exists($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" 
                                             class="thumb-img" 
                                             alt="Activity Photo"
                                             title="<?php echo htmlspecialchars(basename($photo)); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No Photos</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm"
                                onclick='editActivity(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#activityModal"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Delete this activity and all its photos?');"
                           class="btn btn-danger btn-sm"
                           title="Delete">
                           <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>


<!-- ACTIVITY MODAL -->
<div class="modal fade" id="activityModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" id="activityForm" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Activity</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="activity_id" id="activity_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="category" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label class="form-label">Remark</label>
                            <input type="text" name="remark" id="remark" class="form-control">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Upload Photos (Multiple)</label>
                            <input type="file" name="photos[]" id="photos" multiple class="form-control" accept="image/*">
                            <small class="text-muted">Supported: JPG, PNG, GIF, WEBP</small>
                        </div>

                        <div class="col-md-12">
                            <div id="existingPhotos"></div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_activity" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
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
console.log('=== Activities Management ===');

// Summernote configuration
const summernoteConfig = {
    height: 250,
    placeholder: 'Enter activity description here...',
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

// Initialize Summernote when modal is shown
$('#activityModal').on('shown.bs.modal', function () {
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
$('#activityModal').on('hidden.bs.modal', function () {
    console.log('Modal hidden - destroying Summernote');
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
        console.log('✓ Summernote destroyed');
    }
});

// Add Activity function
function addActivity() {
    console.log('Adding new activity');
    
    document.getElementById("modalTitle").innerHTML = "Add Activity";
    document.getElementById("activity_id").value = "";
    document.getElementById("department").value = "";
    document.getElementById("category").value = "";
    document.getElementById("title").value = "";
    document.getElementById("date").value = "";
    document.getElementById("remark").value = "";
    document.getElementById("description").value = "";
    document.getElementById("photos").value = "";
    document.getElementById("existingPhotos").innerHTML = "";
    
    window.pendingDescription = undefined;
}

// Edit Activity function
function editActivity(activity) {
    console.log('Editing activity:', activity.id);
    
    document.getElementById("modalTitle").innerHTML = "Update Activity";
    document.getElementById("activity_id").value = activity.id;
    document.getElementById("department").value = activity.department;
    document.getElementById("category").value = activity.category;
    document.getElementById("title").value = activity.title;
    document.getElementById("date").value = activity.date;
    document.getElementById("remark").value = activity.remark;
    
    // Store description to be loaded when Summernote initializes
    window.pendingDescription = activity.description;

    // Show existing photos with checkboxes
    let photos = [];
    try {
        photos = JSON.parse(activity.photos);
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

// DataTable initialization
$(document).ready(function() {
    console.log('Initializing DataTable');
    $('#activityTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search Activities:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ activities",
            infoEmpty: "No activities available",
            zeroRecords: "No matching activities found"
        }
    });
    console.log('✓ DataTable initialized');
});

console.log('✓ Activities Management page loaded');
</script>

</body>
</html>