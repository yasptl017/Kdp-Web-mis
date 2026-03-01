<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/tfp/";
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
        "SELECT id, display_order FROM tfp WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM tfp
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM tfp
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE tfp SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE tfp SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_tfp.php");
    exit;
}

/* ===================================
   ADD / UPDATE TFP
   =================================== */
if (isset($_POST['save_tfp'])) {

    $id = intval($_POST['tfp_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']); // Summernote HTML

    // Get old file
    $oldFile = "";
    if ($id > 0) {
        $q = $conn->query("SELECT file FROM tfp WHERE id=$id LIMIT 1");
        if ($q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file'];
        }
    }

    $filePath = $oldFile;

    // File upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $fileName = "tfp_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $filePath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

            if (!empty($oldFile) && file_exists($oldFile)) unlink($oldFile);
        } else {
            $message = "Invalid file type!";
            $messageType = "danger";
        }
    }

    if (empty($message)) {

        if ($id == 0) {
            // INSERT
            $sql = "INSERT INTO tfp (title, display_order, file, description)
                    VALUES ('$title', $display_order, '$filePath', '$description')";
            $conn->query($sql);
            $message = "TFP record added successfully!";
        } else {
            // UPDATE
            $sql = "UPDATE tfp SET 
                    title='$title',
                    display_order=$display_order,
                    file='$filePath',
                    description='$description'
                    WHERE id=$id";
            $conn->query($sql);
            $message = "TFP updated successfully!";
        }

        $messageType = "success";
    }
}

/* ===================================
   DELETE TFP
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file FROM tfp WHERE id=$id LIMIT 1");
    if ($q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM tfp WHERE id=$id");
    $message = "TFP deleted!";
    $messageType = "success";
}

// Fetch all records
$records = $conn->query("SELECT * FROM tfp ORDER BY display_order ASC, id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Summernote CSS -->
<style>
        .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        .action-buttons{display:flex;gap:5px;justify-content:center}
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-book me-2"></i>Manage TFP</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#tfpModal"
        onclick="addTFP()">
    <i class="fas fa-plus-circle"></i> Add TFP Record
</button>

<div class="form-card">
    <table id="tfpTable" class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th>Order</th>
                <th>File</th>
                <th width="200">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>
                <td class="text-center"><?php echo $row['display_order']; ?></td>

                <td class="text-center">
                    <?php if (!empty($row['file']) && file_exists($row['file'])): ?>
                        <a href="<?php echo $row['file']; ?>"
                           class="btn btn-info btn-sm" target="_blank">View</a>
                    <?php else: ?>
                        <span class="text-muted">No File</span>
                    <?php endif; ?>
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
                                onclick='editTFP(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#tfpModal">
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
<div class="modal fade" id="tfpModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add TFP</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="tfp_id" id="tfp_id">

                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>File</label>
                        <input type="file" name="file" id="file" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_tfp" class="btn btn-success">
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
<script>
$(document).ready(function () {
    $('#tfpTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});

// Summernote config
// Summernote config moved to summernote-config.js

$('#tfpModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter description..."});
    }
    if (window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = undefined;
    }
});

$('#tfpModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = undefined;
});

// Add new
function addTFP() {
    document.getElementById("modalTitle").innerText = "Add TFP";
    document.getElementById("tfp_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("file").value = "";
    document.getElementById("description").value = "";
    window.pendingDescription = undefined;
}

// Edit
function editTFP(f) {
    document.getElementById("modalTitle").innerText = "Update TFP";
    document.getElementById("tfp_id").value = f.id;
    document.getElementById("title").value = f.title;
    document.getElementById("display_order").value = f.display_order;

    window.pendingDescription = f.description;
}
</script>

</body>
</html>