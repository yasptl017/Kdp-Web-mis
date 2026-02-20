<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

/* -------------------------
   MOVE UP / DOWN
------------------------- */
if (isset($_GET['move'], $_GET['id'])) {

    $id = intval($_GET['id']);
    $direction = $_GET['move']; // up | down

    $current = $conn->query(
        "SELECT id, display_order FROM admissions WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM admissions
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM admissions
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE admissions SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE admissions SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_admission.php");
    exit;
}

/* -------------------------
   ADD / UPDATE Admission
------------------------- */
if (isset($_POST['save_admission'])) {

    $id = $_POST['admission_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']);

    if (empty($id)) {
        $sql = "INSERT INTO admissions (title, display_order, description)
                VALUES ('$title', $display_order, '$description')";
        $conn->query($sql);
        $message = "Admission added successfully!";
    } else {
        $sql = "UPDATE admissions SET
                title='$title',
                display_order=$display_order,
                description='$description'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Admission updated successfully!";
    }

    $messageType = "success";
}

/* -------------------------
          DELETE
------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM admissions WHERE id=$id");
    $message = "Admission deleted!";
    $messageType = "success";
}

/* -------------------------
        FETCH ALL
------------------------- */
$records = $conn->query(
    "SELECT * FROM admissions ORDER BY display_order ASC, id DESC"
);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
.form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
.action-buttons{display:flex;gap:5px;justify-content:center}
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

<h2 class="h4 mb-4">Manage Admission</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
<?php echo $message; ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#admissionModal"
        onclick="addAdmission()">
Add Admission
</button>

<div class="form-card">
<table id="admissionTable" class="table table-bordered table-striped align-middle">
<thead>
<tr class="text-center">
<th>#</th>
<th>Title</th>
<th>Order</th>
<th>Description</th>
<th width="200">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$records->fetch_assoc()): ?>
<tr>
<td class="text-center"><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['title']); ?></td>
<td class="text-center"><?php echo $row['display_order']; ?></td>
<td><?php echo substr(strip_tags($row['description']),0,120); ?>...</td>
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
onclick='editAdmission(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
data-bs-toggle="modal"
data-bs-target="#admissionModal">
<i class="fas fa-edit"></i>
</button>

<a href="?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete this admission?');"
class="btn btn-danger btn-sm">
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
<div class="modal fade" id="admissionModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

<form method="POST">

<div class="modal-header bg-primary text-white">
<h5 id="modalTitle">Add Admission</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="admission_id" id="admission_id">

<div class="mb-3">
<label>Title *</label>
<input type="text" name="title" id="title" class="form-control" required>
</div>

<div class="mb-3">
<label>Display Order *</label>
<input type="number" name="display_order" id="display_order"
class="form-control" required>
</div>

<div class="mb-3">
<label>Description *</label>
<textarea name="description" id="description" class="form-control"></textarea>
</div>

</div>

<div class="modal-footer">
<button type="submit" name="save_admission" class="btn btn-success">Save</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
const SNconfig={height:350};

$('#admissionModal').on('shown.bs.modal',()=>{
if(!$('#description').next('.note-editor').length){
$('#description').summernote(SNconfig);
}
if(window.pendingDescription!==undefined){
setTimeout(()=>{
$('#description').summernote('code',window.pendingDescription);
window.pendingDescription=undefined;
},100);
}
});

$('#admissionModal').on('hidden.bs.modal',()=>{
if($('#description').next('.note-editor').length){
$('#description').summernote('destroy');
}
});

function addAdmission(){
modalTitle.innerText='Add Admission';
admission_id.value='';
title.value='';
display_order.value='';
description.value='';
window.pendingDescription=undefined;
}

function editAdmission(ad){
modalTitle.innerText='Update Admission';
admission_id.value=ad.id;
title.value=ad.title;
display_order.value=ad.display_order;
window.pendingDescription=ad.description;
}

$(document).ready(()=>{
$('#admissionTable').DataTable({
pageLength:25,
order:[[2,'asc']]
});
});
</script>

</body>
</html>
