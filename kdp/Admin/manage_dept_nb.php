<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/nb/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===================================
   ADD / UPDATE NB
   =================================== */
if (isset($_POST['save_nb'])) {

    $id = intval($_POST['nb_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $title = $conn->real_escape_string($_POST['title']);
    $date = $conn->real_escape_string($_POST['date']);
    $description = $conn->real_escape_string($_POST['description']);  // Summernote HTML escaped

    // Get old file
    $oldFile = "";
    if ($id > 0) {
        $q = $conn->query("SELECT file FROM nb WHERE id=$id LIMIT 1");
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
            $fileName = "nb_" . time() . "_" . rand(1000, 9999) . "." . $ext;
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
            $sql = "INSERT INTO nb (department, title, date, file, description)
                    VALUES ('$department', '$title', '$date', '$filePath', '$description')";
            $conn->query($sql);
            $message = "Notice added successfully!";
        } else {
            $sql = "UPDATE nb SET 
                    department='$department',
                    title='$title',
                    date='$date',
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

    $q = $conn->query("SELECT file FROM nb WHERE id=$id LIMIT 1");
    if ($q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM nb WHERE id=$id");
    $message = "Notice deleted!";
    $messageType = "success";
}

// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $records = $conn->query("SELECT * FROM nb ORDER BY date DESC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility=1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $records = $conn->query("SELECT * FROM nb WHERE department='$userDept' ORDER BY date DESC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility=1 AND department='$userDept' ORDER BY department ASC");
}
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet" />

<style>
    .note-editor.note-frame {
        border: 1px solid #ced4da !important;
    }
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-bullhorn me-2"></i>Manage Notice Board</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#nbModal" onclick="addNB()">
        <i class="fas fa-plus-circle"></i> Add Notice
    </button>

    <div class="form-card">
        <table id="nbTable" class="table table-bordered table-striped">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>File</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i=1; while($row=$records->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td><?php echo $row['title']; ?></td>
                    <td class="text-center"><?php echo $row['date']; ?></td>

                    <td class="text-center">
                        <?php if ($row['file'] && file_exists($row['file'])): ?>
                            <a href="<?php echo $row['file']; ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                        <?php else: ?>
                            <span class="text-muted">No File</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center">
                        <button class="btn btn-sm btn-warning"
                                onclick='editNB(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#nbModal">
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="?delete=<?php echo $row['id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this notice?');">
                           <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- Modal -->
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

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label>Department *</label>
                            <select name="department" id="department" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <?php 
                                // Reset pointer for departments in modal
                                $departments->data_seek(0);
                                while($d=$departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $d['department']; ?>">
                                        <?php echo $d['department']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Date *</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                    </div>

                    <label>Title *</label>
                    <input type="text" name="title" id="title" class="form-control mb-3" required>

                    <label>File</label>
                    <input type="file" name="file" id="file" class="form-control mb-3">

                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control"></textarea>

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

<!-- Page-specific Scripts (load AFTER footer.php which has jQuery) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Notice Board Management ===');

// Initialize DataTable
$(document).ready(function () {
    $('#nbTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc'], [0, 'desc']], // Sort by Date descending, then ID descending
        language: {
            search: "Search Notices:",
            lengthMenu: "Show _MENU_ notices per page",
            info: "Showing _START_ to _END_ of _TOTAL_ notices",
            infoEmpty: "No notices available",
            zeroRecords: "No matching notices found"
        }
    });
    console.log('✓ DataTable initialized');
});

/* Summernote Config */
const SNconfig = {
    height: 250,
    placeholder: "Enter notice description...",
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

// Initialize Summernote when modal opens
$('#nbModal').on('shown.bs.modal', function () {
    console.log('Modal shown - checking Summernote');
    
    if (!$('#description').next('.note-editor').length) {
        console.log('Initializing Summernote');
        $('#description').summernote(SNconfig);
    }
    
    // Load pending description if editing
    if (window.pendingDescription !== undefined) {
        console.log('Loading pending description');
        setTimeout(() => {
            $('#description').summernote('code', window.pendingDescription);
            window.pendingDescription = undefined;
        }, 100);
    }
});

// Destroy Summernote when modal closes
$('#nbModal').on('hidden.bs.modal', function () {
    console.log('Modal hidden - destroying Summernote');
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

// Add Notice function
function addNB() {
    console.log('Adding new notice');
    
    document.getElementById("modalTitle").innerText = "Add Notice";
    document.getElementById("nb_id").value = "";
    document.getElementById("department").value = "";
    document.getElementById("title").value = "";
    document.getElementById("date").value = "";
    document.getElementById("file").value = "";
    document.getElementById("description").value = "";
    
    window.pendingDescription = undefined;
}

// Edit Notice function
function editNB(f) {
    console.log('Editing notice:', f.id);
    
    document.getElementById("modalTitle").innerText = "Update Notice";
    document.getElementById("nb_id").value = f.id;
    document.getElementById("department").value = f.department;
    document.getElementById("title").value = f.title;
    document.getElementById("date").value = f.date;

    // Store description for when Summernote initializes
    window.pendingDescription = f.description;
}

console.log('✓ Notice Board page loaded');
</script>

</body>
</html>