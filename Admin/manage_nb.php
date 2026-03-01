<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/cnb/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===================================
   ADD / UPDATE CNB
   =================================== */
if (isset($_POST['save_nb'])) {

    $id = intval($_POST['nb_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $notice_date = $conn->real_escape_string($_POST['notice_date']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']);  // Summernote HTML content

    // Get old file
    $oldFile = "";
    if ($id > 0) {
        $q = $conn->query("SELECT file FROM cnb WHERE id=$id LIMIT 1");
        if ($q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file'];
        }
    }

    $filePath = $oldFile;

    // Handle new file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $fileName = "cnb_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $filePath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

            if (!empty($oldFile) && file_exists($oldFile)) unlink($oldFile);
        } else {
            $message = "Invalid file format!";
            $messageType = "danger";
        }
    }

    if (empty($message)) {

        if ($id == 0) {
            $sql = "INSERT INTO cnb (title, notice_date, display_order, file, description)
                    VALUES ('$title', '$notice_date', $display_order, '$filePath', '$description')";
            $conn->query($sql);
            $message = "Notice added successfully!";
        } else {
            $sql = "UPDATE cnb SET 
                    title='$title',
                    notice_date='$notice_date',
                    display_order=$display_order,
                    file='$filePath',
                    description='$description'
                    WHERE id=$id";
            $conn->query($sql);
            $message = "Notice updated successfully!";
        }

        $messageType = "success";
    }
}

/* ===================================
   DELETE
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file FROM cnb WHERE id=$id LIMIT 1");
    if ($q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM cnb WHERE id=$id");
    $message = "Notice deleted!";
    $messageType = "success";
}

// Fetch all records
$records = $conn->query("SELECT * FROM cnb ORDER BY display_order ASC, id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <!-- Summernote -->
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-bullhorn me-2"></i>Manage CNB</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal"
        data-bs-target="#nbModal" onclick="addNB()">
    <i class="fas fa-plus-circle"></i> Add Notice
</button>

<div class="form-card">
    <table id="nbTable" class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th width="130">Date</th>
                <th width="90">Order</th>
                <th>File</th>
                <th width="120">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>

                <td class="text-center"><?php echo $row['notice_date']; ?></td>

                <td class="text-center"><?php echo $row['display_order']; ?></td>

                <td class="text-center">
                    <?php if ($row['file'] && file_exists($row['file'])): ?>
                        <a href="<?php echo $row['file']; ?>" target="_blank" class="btn btn-info btn-sm">View</a>
                    <?php else: ?>
                        <span class="text-muted">No File</span>
                    <?php endif; ?>
                </td>

                <td class="text-center">
                    <button class="btn btn-warning btn-sm"
                            onclick='editNB(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                            data-bs-toggle="modal"
                            data-bs-target="#nbModal">
                        <i class="fas fa-edit"></i>
                    </button>

                    <a href="?delete=<?php echo $row['id']; ?>"
                       onclick="return confirm('Delete this notice?');"
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

<!-- MODAL -->
<div class="modal fade" id="nbModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Notice</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="nb_id" id="nb_id">

                    <label>Title *</label>
                    <input type="text" name="title" id="title"
                           class="form-control mb-3" required>

                    <label>Date *</label>
                    <input type="date" name="notice_date" id="notice_date"
                           class="form-control mb-3" required>

                    <label>Order *</label>
                    <input type="number" name="display_order" id="display_order"
                           class="form-control mb-3" required>

                    <label>File</label>
                    <input type="file" name="file" id="file"
                           class="form-control mb-3">

                    <label>Description</label>
                    <textarea name="description" id="description"
                              class="form-control"></textarea>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_nb" class="btn btn-primary">
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
    $('#nbTable').DataTable({
        order: [[3, 'asc']]
    });
});

// Summernote config
// Summernote config moved to summernote-config.js

// Single shown.bs.modal handler: init Summernote, then load pending content
$('#nbModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter notice description..."});
    }
    if (window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = undefined;
    }
});

// Destroy on close
$('#nbModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = undefined;
});

// Add function
function addNB() {
    document.getElementById("modalTitle").innerText = "Add Notice";
    document.getElementById("nb_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("notice_date").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("file").value = "";
    window.pendingDescription = undefined;
}

// Edit function
function editNB(f) {
    document.getElementById("modalTitle").innerText = "Update Notice";
    document.getElementById("nb_id").value = f.id;
    document.getElementById("title").value = f.title;
    document.getElementById("notice_date").value = f.notice_date;
    document.getElementById("display_order").value = f.display_order;

    window.pendingDescription = f.description;
}
</script>

</body>
</html>
