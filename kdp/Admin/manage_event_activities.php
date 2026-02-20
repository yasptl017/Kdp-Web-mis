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
    $category = $conn->real_escape_string($_POST['category']);
    $event_date = $conn->real_escape_string($_POST['event_date']);
    $description = $_POST['description'];

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
                VALUES ('$title', '$category', '$event_date', '$description', '$photosJSON')";
        $conn->query($sql);
        $message = "Event Activity added successfully!";
    } else {
        $sql = "UPDATE event_activities SET
                title='$title',
                category='$category',
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css">

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
?>
<tr>
<td class="text-center"><?= $i++; ?></td>
<td><?= htmlspecialchars($ev['title']); ?></td>
<td><?= htmlspecialchars($ev['category']); ?></td>
<td><?= date('d M Y', strtotime($ev['event_date'])); ?></td>
<td class="text-center">
<?php if (!empty($photos[0]) && file_exists($photos[0])): ?>
<img src="<?= $photos[0]; ?>" height="60">
<?php else: ?>No photo<?php endif; ?>
</td>
<td class="text-center">
<button class="btn btn-warning btn-sm"
onclick='editEvent(<?= json_encode($ev, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
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

<label>Category</label>
<input type="text" name="category" id="category" class="form-control mb-3" required>

<label>Event Date</label>
<input type="date" name="event_date" id="event_date" class="form-control mb-3" required>

<label>Description</label>
<textarea name="description" id="description" class="form-control mb-3"></textarea>

<label>Photos</label>
<input type="file" name="photos[]" multiple class="form-control mb-3">

<div id="existingPhotos"></div>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(()=>{
$('#eventTable').DataTable();
});

$('#eventModal').on('shown.bs.modal',()=>{
if(!$('#description').next('.note-editor').length){
$('#description').summernote({height:250});
}
if(window.pendingDescription){
$('#description').summernote('code',window.pendingDescription);
window.pendingDescription=null;
}
});

function addEvent(){
document.getElementById("modalTitle").innerText="Add Event Activity";
event_id.value=title.value=category.value=event_date.value="";
description.value="";
existingPhotos.innerHTML="";
window.pendingDescription=null;
}

function editEvent(ev){
modalTitle.innerText="Update Event Activity";
event_id.value=ev.id;
title.value=ev.title;
category.value=ev.category;
event_date.value=ev.event_date;
window.pendingDescription=ev.description;
}
</script>

</body>
</html>
