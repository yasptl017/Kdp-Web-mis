<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

/* -------------------------
   ADD / UPDATE
------------------------- */
if (isset($_POST['save_administration'])) {

    $id = $_POST['administration_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);

    if (empty($id)) {
        $sql = "INSERT INTO administration (title, description)
                VALUES ('$title', '$description')";
        $conn->query($sql);
        $message = "Administration record added successfully!";
    } else {
        $sql = "UPDATE administration SET
                title='$title',
                description='$description'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Administration record updated successfully!";
    }

    $messageType = "success";
}

/* -------------------------
          DELETE
------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM administration WHERE id=$id");
    $message = "Administration record deleted!";
    $messageType = "success";
}

/* -------------------------
        FETCH ALL
------------------------- */
$records = $conn->query(
    "SELECT * FROM administration ORDER BY id DESC"
);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
.form-card{
    background:#fff;
    padding:2rem;
    border-radius:8px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1)
}
.action-buttons{
    display:flex;
    gap:6px;
    justify-content:center
}
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

<h2 class="h4 mb-4">Manage Administration</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
<?php echo $message; ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#administrationModal"
        onclick="addAdministration()">
Add Administration
</button>

<div class="form-card">
<table id="administrationTable" class="table table-bordered table-striped align-middle">
<thead>
<tr class="text-center">
<th>#</th>
<th>Title</th>
<th>Description</th>
<th width="160">Action</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row=$records->fetch_assoc()): ?>
<tr>
<td class="text-center"><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['title']); ?></td>
<td><?php echo substr(strip_tags($row['description']),0,150); ?>...</td>
<td>
<div class="action-buttons">

<button class="btn btn-warning btn-sm"
onclick='editAdministration(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
data-bs-toggle="modal"
data-bs-target="#administrationModal">
<i class="fas fa-edit"></i>
</button>

<a href="?delete=<?php echo $row['id']; ?>"
onclick="return confirm('Delete this record?');"
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
<div class="modal fade" id="administrationModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

<form method="POST">

<div class="modal-header bg-primary text-white">
<h5 id="modalTitle">Add Administration</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="administration_id" id="administration_id">

<div class="mb-3">
<label>Title *</label>
<input type="text" name="title" id="title" class="form-control" required>
</div>

<div class="mb-3">
<label>Description *</label>
<textarea name="description" id="description" class="form-control"></textarea>
</div>

</div>

<div class="modal-footer">
<button type="submit" name="save_administration" class="btn btn-success">Save</button>
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
const SNconfig = { height: 350 };

$('#administrationModal').on('shown.bs.modal', () => {
    if (!$('#description').next('.note-editor').length) {
        $('#description').summernote(SNconfig);
    }
    if (window.pendingDescription !== undefined) {
        setTimeout(() => {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 100);
    }
});

$('#administrationModal').on('hidden.bs.modal', () => {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

function addAdministration() {
    modalTitle.innerText = 'Add Administration';
    administration_id.value = '';
    title.value = '';
    description.value = '';
    window.pendingDescription = undefined;
}

function editAdministration(ad) {
    modalTitle.innerText = 'Update Administration';
    administration_id.value = ad.id;
    title.value = ad.title;
    window.pendingDescription = ad.description;
}

$(document).ready(() => {
    $('#administrationTable').DataTable({
        pageLength: 25,
        order: [[0,'desc']]
    });
});
</script>

</body>
</html>
