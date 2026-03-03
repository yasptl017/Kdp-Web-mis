<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/placement/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===================================
   ADD / UPDATE PLACEMENT
=================================== */
if (isset($_POST['save_placement'])) {

    $id           = intval($_POST['placement_id']);
    $department   = $conn->real_escape_string($_POST['department']);
    $title        = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description  = $conn->real_escape_string($_POST['description']);

    // Event tags – store as JSON array
    $tagsRaw  = isset($_POST['event_tags']) ? $_POST['event_tags'] : [];
    $tagsJSON = $conn->real_escape_string(json_encode(array_values(array_filter(array_map('trim', $tagsRaw))), JSON_UNESCAPED_UNICODE));

    // Fetch old photos
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM dept_placement WHERE id=$id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $oldPhotos = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        }
    }

    // Handle photo removal (keep only checked photos)
    $keepPhotos = [];
    if ($id > 0 && isset($_POST['keep_photos']) && is_array($_POST['keep_photos'])) {
        foreach ($_POST['keep_photos'] as $photoPath) {
            if (in_array($photoPath, $oldPhotos)) {
                $keepPhotos[] = $photoPath;
            }
        }
        foreach ($oldPhotos as $oldPhoto) {
            if (!in_array($oldPhoto, $keepPhotos) && file_exists($oldPhoto)) {
                unlink($oldPhoto);
            }
        }
    } else {
        $keepPhotos = $oldPhotos;
    }

    // New photo uploads
    $newPhotos = [];
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $index => $fileName) {
            if ($_FILES['photos']['error'][$index] == 0) {
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                    $newName = "dept_placement_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $target  = $uploadDir . $newName;
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$index], $target)) {
                        $newPhotos[] = $target;
                    }
                }
            }
        }
    }

    $allPhotos  = array_merge($keepPhotos, $newPhotos);
    $photosJSON = $conn->real_escape_string(json_encode($allPhotos, JSON_UNESCAPED_SLASHES));

    if ($id == 0) {
        $sql = "INSERT INTO dept_placement (department, title, display_order, description, photos, event_tags)
                VALUES ('$department', '$title', $display_order, '$description', '$photosJSON', '$tagsJSON')";
        if ($conn->query($sql)) {
            $message = "Placement record added successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    } else {
        $sql = "UPDATE dept_placement SET
                    department='$department',
                    title='$title',
                    display_order=$display_order,
                    description='$description',
                    photos='$photosJSON',
                    event_tags='$tagsJSON'
                WHERE id=$id";
        if ($conn->query($sql)) {
            $message = "Placement record updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    }
}

/* ===================================
   DELETE
=================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $conn->query("SELECT photos FROM dept_placement WHERE id=$id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $photos = json_decode($result->fetch_assoc()['photos'], true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                if (!empty($photo) && file_exists($photo)) unlink($photo);
            }
        }
    }
    if ($conn->query("DELETE FROM dept_placement WHERE id=$id")) {
        $message = "Record deleted successfully!";
        $messageType = "success";
    }
}

// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $records     = $conn->query("SELECT * FROM dept_placement ORDER BY department ASC, display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
    $defaultDept = '';
} else {
    $userDept    = $conn->real_escape_string($_SESSION['user_name']);
    $records     = $conn->query("SELECT * FROM dept_placement WHERE department='$userDept' ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
    $defaultDept = $userDept;
}

// All event categories for tag suggestions
$allCategories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM event_activities WHERE category != '' ORDER BY category ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $allCategories[] = $row['category'];
    }
}
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    .form-card {
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .action-buttons { display: flex; gap: 5px; justify-content: center; }
    .photo-selector { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 10px; }
    .photo-item { position: relative; border: 2px solid #ddd; border-radius: 8px; padding: 8px; background: #f8f9fa; transition: all 0.3s; }
    .photo-item:hover { border-color: #4e73df; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .photo-item.selected { border-color: #28a745; background: #d4edda; }
    .photo-item img { width: 100%; height: 120px; object-fit: cover; border-radius: 4px; }
    .photo-checkbox { position: absolute; top: 15px; right: 15px; width: 24px; height: 24px; cursor: pointer; z-index: 10; }
    .photo-filename { font-size: 11px; color: #666; margin-top: 5px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .select-all-controls { margin-bottom: 10px; padding: 10px; background: #e7f3ff; border-radius: 5px; display: flex; gap: 10px; align-items: center; }
    .thumb-img { height: 60px; border-radius: 4px; border: 1px solid #ccc; object-fit: cover; }
    .tag-item { display: inline-flex; align-items: center; gap: 6px; background: #e7f3ff; border: 1px solid #b8daff; border-radius: 20px; padding: 4px 12px; margin: 3px; font-size: 13px; }
    .tag-item .remove-tag { cursor: pointer; color: #dc3545; font-weight: bold; font-size: 16px; line-height: 1; }
    .tag-input-row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="fas fa-briefcase me-2"></i>Manage Department Placement</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#placementModal" onclick="addPlacement()">
            <i class="fas fa-plus-circle"></i> Add Placement
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <table id="placementTable" class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="50">#</th>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Photos</th>
                    <th>Event Tags</th>
                    <th width="130">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $records->fetch_assoc()):
                    $photos = json_decode($row['photos'], true) ?? [];
                    $tags   = json_decode($row['event_tags'], true) ?? [];
                ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td>
                        <div style="max-width:250px; overflow:hidden; text-overflow:ellipsis;">
                            <?php $desc = strip_tags($row['description']); echo htmlspecialchars(substr($desc, 0, 80)) . (strlen($desc) > 80 ? '...' : ''); ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($photos)): ?>
                            <div style="display:flex; flex-wrap:wrap; gap:3px;">
                                <?php foreach ($photos as $ph): if (file_exists($ph)): ?>
                                    <img src="<?php echo htmlspecialchars($ph); ?>" class="thumb-img" alt="">
                                <?php endif; endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($tags)): ?>
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-warning"
                                    onclick='editPlacement(<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'
                                    data-bs-toggle="modal" data-bs-target="#placementModal" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this record and all its photos?');"
                               class="btn btn-sm btn-danger" title="Delete">
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

<!-- PLACEMENT MODAL -->
<div class="modal fade" id="placementModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="placementForm">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-briefcase me-2"></i>Add Placement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="placement_id" id="placement_id">
                    <div id="tagsHiddenContainer"></div>

                    <div class="row">

                        <!-- Department -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" id="department" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <?php
                                $departments->data_seek(0);
                                while ($dept = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g., Campus Placements 2024" required>
                        </div>

                        <!-- Display Order -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span>
                                <small class="text-muted">(hidden on department page if order &lt; 0)</small>
                            </label>
                            <input type="number" name="display_order" id="display_order" class="form-control" value="0" required>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>

                        <!-- Photos -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Upload Photos (Multiple)</label>
                            <input type="file" name="photos[]" id="photos" multiple class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF, WEBP supported</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div id="existingPhotos"></div>
                        </div>

                        <!-- Event Tags -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Event Tags
                                <small class="text-muted">(links matching events from Events &amp; Activities on the placement page)</small>
                            </label>
                            <div id="tagsDisplay" style="min-height:36px; border:1px solid #ced4da; border-radius:6px; padding:6px 10px; background:#fff;"></div>
                            <div class="tag-input-row">
                                <input type="text" id="tagInput" class="form-control" placeholder="Type a tag or pick a category..." list="categorySuggestions" style="max-width:320px;">
                                <datalist id="categorySuggestions">
                                    <?php foreach ($allCategories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTag()">Add Tag</button>
                            </div>
                            <small class="text-muted">Press Enter or click Add Tag. Events whose category matches any tag will be shown on the placement page.</small>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_placement" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
// ── Tag management ────────────────────────────────────────────────
var currentTags = [];

function renderTags() {
    var html = '';
    currentTags.forEach(function(tag, i) {
        html += '<span class="tag-item">' + escHtml(tag) +
                '<span class="remove-tag" onclick="removeTag(' + i + ')">&times;</span></span>';
    });
    document.getElementById('tagsDisplay').innerHTML = html || '<span class="text-muted small">No tags added</span>';

    // Sync hidden inputs
    var container = document.getElementById('tagsHiddenContainer');
    container.innerHTML = '';
    currentTags.forEach(function(tag) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'event_tags[]'; inp.value = tag;
        container.appendChild(inp);
    });
}

function addTag() {
    var val = document.getElementById('tagInput').value.trim();
    if (val && !currentTags.includes(val)) {
        currentTags.push(val);
        renderTags();
    }
    document.getElementById('tagInput').value = '';
}

function removeTag(index) {
    currentTags.splice(index, 1);
    renderTags();
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('tagInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addTag(); }
});

// ── Summernote ────────────────────────────────────────────────────
$('#placementModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter placement description..."});
    }
    if (window.pendingDescription !== undefined) {
        setTimeout(function() {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 100);
    }
});

$('#placementModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

// ── Add / Edit ────────────────────────────────────────────────────
function addPlacement() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-briefcase me-2"></i>Add Placement';
    document.getElementById('placement_id').value = '';
    document.getElementById('department').value = <?php echo json_encode($defaultDept); ?>;
    document.getElementById('title').value = '';
    document.getElementById('display_order').value = '0';
    document.getElementById('description').value = '';
    document.getElementById('photos').value = '';
    document.getElementById('existingPhotos').innerHTML = '';
    currentTags = [];
    renderTags();
    window.pendingDescription = undefined;
}

function editPlacement(rec) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Update Placement';
    document.getElementById('placement_id').value = rec.id;
    document.getElementById('department').value = rec.department;
    document.getElementById('title').value = rec.title;
    document.getElementById('display_order').value = rec.display_order;
    window.pendingDescription = rec.description;

    // Tags
    try { currentTags = JSON.parse(rec.event_tags) || []; } catch(e) { currentTags = []; }
    renderTags();

    // Existing photos
    var photos = [];
    try { photos = JSON.parse(rec.photos) || []; } catch(e) {}

    if (photos.length > 0) {
        var html = '<div class="alert alert-info">';
        html += '<strong><i class="fas fa-images me-2"></i>Existing Photos:</strong>';
        html += '<p class="mb-2 small text-muted">Uncheck photos to remove them.</p>';
        html += '<div class="select-all-controls">';
        html += '<button type="button" class="btn btn-sm btn-success" onclick="selectAllPhotos(true)"><i class="fas fa-check-square"></i> Select All</button>';
        html += '<button type="button" class="btn btn-sm btn-warning" onclick="selectAllPhotos(false)"><i class="fas fa-square"></i> Deselect All</button>';
        html += '<span class="ms-auto text-muted small"><span id="selectedCount">' + photos.length + '</span> / ' + photos.length + ' selected</span>';
        html += '</div><div class="photo-selector" id="photoSelector">';
        photos.forEach(function(photo, index) {
            var filename = photo.split('/').pop();
            html += '<div class="photo-item selected" id="photoItem_' + index + '">';
            html += '<input type="checkbox" name="keep_photos[]" value="' + photo + '" class="photo-checkbox" id="photo_' + index + '" checked onchange="togglePhotoSelection(' + index + ')">';
            html += '<label for="photo_' + index + '" style="cursor:pointer;margin:0;">';
            html += '<img src="' + photo + '" alt="Photo">';
            html += '<div class="photo-filename" title="' + filename + '">' + filename + '</div>';
            html += '</label></div>';
        });
        html += '</div></div>';
        document.getElementById('existingPhotos').innerHTML = html;
    } else {
        document.getElementById('existingPhotos').innerHTML = '';
    }
}

function togglePhotoSelection(index) {
    var cb = document.getElementById('photo_' + index);
    document.getElementById('photoItem_' + index).classList.toggle('selected', cb.checked);
    updateSelectedCount();
}
function selectAllPhotos(select) {
    document.querySelectorAll('.photo-checkbox').forEach(function(cb, i) {
        cb.checked = select;
        document.querySelectorAll('.photo-item')[i].classList.toggle('selected', select);
    });
    updateSelectedCount();
}
function updateSelectedCount() {
    var el = document.getElementById('selectedCount');
    if (el) el.textContent = document.querySelectorAll('.photo-checkbox:checked').length;
}

// ── DataTable ─────────────────────────────────────────────────────
$(document).ready(function() {
    $('#placementTable').DataTable({
        pageLength: 25,
        order: [[1,'asc'],[3,'asc']],
        columnDefs: [{ orderable: false, targets: [5,6,7] }],
        language: {
            search: "Search:",
            infoEmpty: "No records",
            zeroRecords: "No matching records found"
        }
    });
});
</script>

</body>
</html>
