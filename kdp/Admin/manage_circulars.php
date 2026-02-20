<!DOCTYPE html>
<html lang="en">

<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/circulars/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* =============================
   ADD / UPDATE CIRCULAR
   ============================= */
if (isset($_POST['save_circular'])) {

    $id = intval($_POST['circular_id']);
    $date = $conn->real_escape_string($_POST['date']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $_POST['description']; // Summernote HTML
    $remark = $conn->real_escape_string($_POST['remark']);

    // Get old file if editing
    $oldFile = "";
    if ($id > 0) {
        $q = $conn->query("SELECT file FROM circulars WHERE id=$id LIMIT 1");
        if ($q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file'];
        }
    }

    $filePath = $oldFile;

    /* === FILE UPLOAD === */
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {

        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $fileName = "circular_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $filePath = $uploadDir . $fileName;

            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

            if (!empty($oldFile) && file_exists($oldFile)) {
                unlink($oldFile);
            }

        } else {
            $message = "Invalid file format!";
            $messageType = "danger";
        }
    }

    if (empty($message)) {

        if ($id == 0) {
            // INSERT
            $sql = "INSERT INTO circulars (date, title, file, description, remark)
                    VALUES ('$date', '$title', '$filePath', '$description', '$remark')";
            $conn->query($sql);

            $message = "Circular added successfully!";
        } else {
            // UPDATE
            $sql = "UPDATE circulars SET 
                    date='$date',
                    title='$title',
                    file='$filePath',
                    description='$description',
                    remark='$remark'
                    WHERE id=$id";
            $conn->query($sql);

            $message = "Circular updated successfully!";
        }

        $messageType = "success";
    }
}

/* =============================
   DELETE CIRCULAR
   ============================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file FROM circulars WHERE id=$id LIMIT 1");
    if ($q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (!empty($file) && file_exists($file)) {
            unlink($file);
        }
    }

    $conn->query("DELETE FROM circulars WHERE id=$id");

    $message = "Circular deleted!";
    $messageType = "success";
}

$records = $conn->query("SELECT * FROM circulars ORDER BY id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Summernote -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet" />
</head>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-envelope-open-text me-2"></i>Manage Circulars</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#circularModal" onclick="addCircular()">
    <i class="fas fa-plus-circle"></i> Add Circular
</button>

<div class="form-card">
    <table id="circularTable" class="table table-bordered table-striped align-middle">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Date</th>
                <th>Title</th>
                <th>File</th>
                <th width="150">Action</th>
            </tr>
        </thead>
        <tbody>

        <?php $i=1; while($row=$records->fetch_assoc()): ?>
        <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['title']; ?></td>

            <td class="text-center">
                <?php if ($row['file'] && file_exists($row['file'])): ?>
                    <a href="<?php echo $row['file']; ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                <?php else: ?>
                    <span class="text-muted">No File</span>
                <?php endif; ?>
            </td>

            <td class="text-center">
                <button class="btn btn-warning btn-sm"
                        onclick='editCircular(<?php echo json_encode($row); ?>)'
                        data-bs-toggle="modal"
                        data-bs-target="#circularModal">
                        <i class="fas fa-edit"></i>
                </button>

                <a href="?delete=<?php echo $row['id']; ?>"
                   onclick="return confirm('Delete this circular?');"
                   class="btn btn-danger btn-sm">
                   <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>

        </tbody>
    </table>
</div>

</main>

<!-- CIRCULAR MODAL -->
<div class="modal fade" id="circularModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Circular</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="circular_id" id="circular_id">

                    <label>Date *</label>
                    <input type="date" name="date" id="date" class="form-control mb-3" required>

                    <label>Title *</label>
                    <input type="text" name="title" id="title" class="form-control mb-3" required>

                    <label>File</label>
                    <input type="file" name="file" id="file" class="form-control mb-3">

                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control mb-3"></textarea>

                    <label>Remark</label>
                    <input type="text" name="remark" id="remark" class="form-control">

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_circular" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>


<?php include "footer.php"; ?>

<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    $('#circularTable').DataTable();
});

/* Summernote Setup */
const SNcfg = {
    height: 250,
    placeholder: "Enter circular description...",
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

$('#circularModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        $('#description').summernote(SNcfg);
    }
});

$('#circularModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

/* FUNCTIONS */

function addCircular() {
    document.getElementById("modalTitle").innerText = "Add Circular";
    document.getElementById("circular_id").value = "";
    document.getElementById("date").value = "";
    document.getElementById("title").value = "";
    document.getElementById("file").value = "";
    document.getElementById("remark").value = "";
    document.getElementById("description").value = "";
}

function editCircular(c) {
    document.getElementById("modalTitle").innerText = "Update Circular";
    document.getElementById("circular_id").value = c.id;
    document.getElementById("date").value = c.date;
    document.getElementById("title").value = c.title;
    document.getElementById("remark").value = c.remark;

    window.pendingDescription = c.description;
}

$('#circularModal').on('shown.bs.modal', function () {
    if (window.pendingDescription !== undefined) {
        setTimeout(() => {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 120);
    }
});
</script>

</body>
</html>
