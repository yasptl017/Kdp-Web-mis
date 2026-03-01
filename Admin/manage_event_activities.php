<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

$uploadDir = "uploads/event_photos/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ==================================================
   ADD / UPDATE EVENT ACTIVITY
   ================================================== */
if (isset($_POST['save_event'])) {

    $id = intval($_POST['event_id']);
    $title = $conn->real_escape_string($_POST['title']);
    
    // Handle multiple categories
    $categories = [];
    if (isset($_POST['category']) && is_array($_POST['category'])) {
        foreach ($_POST['category'] as $cat) {
            $cat = trim($cat);
            if (!empty($cat)) {
                $categories[] = $cat;
            }
        }
    }
    $categoryJSON = $conn->real_escape_string(json_encode($categories, JSON_UNESCAPED_SLASHES));
    
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $description = $conn->real_escape_string($_POST['description']);

    // OLD photos (if editing)
    $oldPhotos = [];
    if ($id > 0) {
        $q = $conn->query("SELECT photos FROM event_activities WHERE id=$id");
        if ($q->num_rows > 0) {
            $oldPhotos = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        }
    }

    // Handle photo removal
    $keepPhotos = [];
    if ($id > 0 && isset($_POST['keep_photos'])) {
        foreach ($_POST['keep_photos'] as $p) {
            if (in_array($p, $oldPhotos)) {
                $keepPhotos[] = $p;
            }
        }
        foreach ($oldPhotos as $old) {
            if (!in_array($old, $keepPhotos) && file_exists($old)) {
                unlink($old);
            }
        }
    } else {
        $keepPhotos = $oldPhotos;
    }

    $photos = $keepPhotos;

    // Upload new photos
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if ($_FILES['photos']['error'][$i] === 0) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $newName = "event_" . time() . "_" . rand(1000,9999) . "." . $ext;
                    $target = $uploadDir . $newName;
                    move_uploaded_file($_FILES['photos']['tmp_name'][$i], $target);
                    $photos[] = $target;
                }
            }
        }
    }

    $photosJSON = $conn->real_escape_string(json_encode($photos, JSON_UNESCAPED_SLASHES));

    if ($id === 0) {
        $sql = "INSERT INTO event_activities (title, category, event_date, description, photos)
                VALUES ('$title', '$categoryJSON', '$event_date', '$description', '$photosJSON')";
        $conn->query($sql);
        $message = "Event Activity added successfully!";
    } else {
        $sql = "UPDATE event_activities SET
                title='$title',
                category='$categoryJSON',
                event_date='$event_date',
                description='$description',
                photos='$photosJSON'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Event Activity updated successfully!";
    }

    $messageType = "success";
}

/* ==================================================
   DELETE EVENT
   ================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $q = $conn->query("SELECT photos FROM event_activities WHERE id=$id");
    if ($q->num_rows > 0) {
        $arr = json_decode($q->fetch_assoc()['photos'], true) ?? [];
        foreach ($arr as $p) {
            if (file_exists($p)) unlink($p);
        }
    }
    $conn->query("DELETE FROM event_activities WHERE id=$id");
    $message = "Event deleted!";
    $messageType = "success";
}

$events = $conn->query("SELECT * FROM event_activities ORDER BY event_date DESC");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

<h2 class="h4 mb-4">Manage Event Activities</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="addEvent()">Add Event</button>

<table id="eventTable" class="table table-bordered table-striped">
<thead>
<tr class="text-center">
    <th>#</th>
    <th>Title</th>
    <th>Category</th>
    <th>Date</th>
    <th>Thumbnail</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($ev=$events->fetch_assoc()):
$photos = json_decode($ev['photos'], true) ?? [];
$categories = json_decode($ev['category'], true) ?? [$ev['category']];
?>
<tr>
<td class="text-center"><?= $i++; ?></td>
<td><?= htmlspecialchars($ev['title']); ?></td>
<td><?= is_array($categories) ? implode(', ', array_map('htmlspecialchars', $categories)) : htmlspecialchars($ev['category']); ?></td>
<td><?= date('d M Y', strtotime($ev['event_date'])); ?></td>
<td class="text-center">
<?php if (!empty($photos[0]) && file_exists($photos[0])): ?>
<img src="<?= $photos[0]; ?>" height="60">
<?php else: ?>No photo<?php endif; ?>
</td>
<td class="text-center">
<button class="btn btn-warning btn-sm edit-event-btn"
data-event='<?php echo htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8'); ?>'
data-bs-toggle="modal"
data-bs-target="#eventModal">Edit</button>
<a href="?delete=<?= $ev['id']; ?>" class="btn btn-danger btn-sm"
onclick="return confirm('Delete event?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</main>

<!-- MODAL -->
<div class="modal fade" id="eventModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

<form method="POST" enctype="multipart/form-data">
<div class="modal-header">
<h5 id="modalTitle">Add Event Activity</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="hidden" name="event_id" id="event_id">

<label>Title</label>
<input type="text" name="title" id="title" class="form-control mb-3" required>

<label>Categories</label>
<div id="categoryContainer" class="mb-3">
    <div class="category-row mb-2">
        <div class="input-group">
            <input type="text" name="category[]" class="form-control" required>
            <button type="button" class="btn btn-success" onclick="addCategoryField()">
                <i class="fas fa-plus"></i> Add New
            </button>
        </div>
    </div>
</div>

<label>Event Date</label>
<input type="date" name="event_date" id="event_date" class="form-control mb-3" required>

<label>Description</label>
<textarea name="description" id="description" class="form-control mb-3"></textarea>

<div id="existingPhotos" class="mb-3"></div>

<label>Add New Photos</label>
<input type="file" name="photos[]" multiple class="form-control mb-3" accept="image/*">
</div>

<div class="modal-footer">
<button type="submit" name="save_event" class="btn btn-primary">Save</button>
<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</form>

</div>
</div>
</div>

<?php include "footer.php"; ?>
<script>
$(document).ready(()=>{
    $('#eventTable').DataTable();
    
    // Handle edit button clicks using event delegation
    $(document).on('click', '.edit-event-btn', function() {
        var eventData = $(this).data('event');
        editEvent(eventData);
    });
});

// Initialize Summernote when modal is shown
$('#eventModal').on('shown.bs.modal', function(){
    if(!$('#description').next('.note-editor').length){
        initSummernote('#description', {height: 250});
    }
    
    // Set pending description if exists
    if(window.pendingDescription !== null && window.pendingDescription !== undefined){
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = null;
    }
});

// Destroy Summernote when modal is hidden
$('#eventModal').on('hidden.bs.modal', function(){
    if($('#description').next('.note-editor').length){
        $('#description').summernote('destroy');
    }
    window.pendingDescription = null;
});

function addCategoryField(){
    const container = document.getElementById('categoryContainer');
    const newRow = document.createElement('div');
    newRow.className = 'category-row mb-2';
    newRow.innerHTML = `
        <div class="input-group">
            <input type="text" name="category[]" class="form-control">
            <button type="button" class="btn btn-danger" onclick="removeCategoryField(this)">
                <i class="fas fa-minus"></i> Remove
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeCategoryField(btn){
    const row = btn.closest('.category-row');
    row.remove();
}

function addEvent(){
    document.getElementById("modalTitle").innerText = "Add Event Activity";
    document.getElementById("event_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("event_date").value = "";
    document.getElementById("existingPhotos").innerHTML = "";
    
    // Reset categories
    document.getElementById("categoryContainer").innerHTML = `
        <div class="category-row mb-2">
            <div class="input-group">
                <input type="text" name="category[]" class="form-control" required>
                <button type="button" class="btn btn-success" onclick="addCategoryField()">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </div>
        </div>
    `;
    
    // Reset Summernote
    if($('#description').next('.note-editor').length){
        $('#description').summernote('code', '');
    } else {
        document.getElementById("description").value = "";
    }
    
    window.pendingDescription = null;
}

function editEvent(ev){
    console.log('Editing event:', ev);
    
    document.getElementById("modalTitle").innerText = "Update Event Activity";
    document.getElementById("event_id").value = ev.id || "";
    document.getElementById("title").value = ev.title || "";
    document.getElementById("event_date").value = ev.event_date || "";
    
    // Set description for Summernote
    window.pendingDescription = ev.description || "";
    
    // If Summernote is already initialized, set the content directly
    if($('#description').next('.note-editor').length){
        $('#description').summernote('code', ev.description || '');
    }
    
    // Handle categories
    let categories = [];
    try {
        categories = typeof ev.category === 'string' ? JSON.parse(ev.category) : ev.category;
        if (!Array.isArray(categories)) {
            categories = [ev.category];
        }
    } catch(e) {
        categories = [ev.category || ''];
    }
    
    let categoryHTML = '';
    categories.forEach((cat, index) => {
        if (index === 0) {
            categoryHTML += `
                <div class="category-row mb-2">
                    <div class="input-group">
                        <input type="text" name="category[]" class="form-control" value="${cat}" required>
                        <button type="button" class="btn btn-success" onclick="addCategoryField()">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                </div>
            `;
        } else {
            categoryHTML += `
                <div class="category-row mb-2">
                    <div class="input-group">
                        <input type="text" name="category[]" class="form-control" value="${cat}">
                        <button type="button" class="btn btn-danger" onclick="removeCategoryField(this)">
                            <i class="fas fa-minus"></i> Remove
                        </button>
                    </div>
                </div>
            `;
        }
    });
    
    document.getElementById("categoryContainer").innerHTML = categoryHTML;
    
    // Handle existing photos
    let photosHTML = "";
    if(ev.photos){
        try {
            let photos = typeof ev.photos === 'string' ? JSON.parse(ev.photos) : ev.photos;
            if(photos && photos.length > 0){
                photosHTML = '<label class="fw-bold mb-2">Existing Photos (uncheck to remove):</label><div class="row">';
                photos.forEach(function(photo, index){
                    photosHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="${photo}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="keep_photos[]" 
                                           value="${photo}" id="photo_${index}" checked>
                                    <label class="form-check-label" for="photo_${index}">
                                        Keep this photo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                photosHTML += '</div>';
            }
        } catch(e) {
            console.error('Error parsing photos:', e);
        }
    }
    
    document.getElementById("existingPhotos").innerHTML = photosHTML;
}
</script>

<style>
.card-img-top {
    border-radius: 0.25rem 0.25rem 0 0;
}
.form-check-label {
    font-size: 0.875rem;
}
.category-row {
    position: relative;
}
</style>

</body>
</html>