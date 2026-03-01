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
        "SELECT id, display_order FROM alumni WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM alumni
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM alumni
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE alumni SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE alumni SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_alumni.php");
    exit;
}

/* ===================================
   ADD / UPDATE ALUMNI
   =================================== */
if (isset($_POST['save_alumni'])) {

    $id = intval($_POST['alumni_id']);
    $title = $_POST['title'];
    $display_order = intval($_POST['display_order']);
    $description = $_POST['description'];

    if ($id == 0) {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO alumni (title, display_order, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $title, $display_order, $description);
        $stmt->execute();
        $stmt->close();
        $message = "Alumni content added successfully!";
    } else {
        // UPDATE
        $stmt = $conn->prepare("UPDATE alumni SET title=?, display_order=?, description=? WHERE id=?");
        $stmt->bind_param("sisi", $title, $display_order, $description, $id);
        $stmt->execute();
        $stmt->close();
        $message = "Alumni content updated successfully!";
    }

    $messageType = "success";
}

/* ===================================
   DELETE
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM alumni WHERE id=$id");
    $message = "Alumni entry deleted!";
    $messageType = "success";
}

$records = $conn->query("SELECT * FROM alumni ORDER BY display_order ASC, id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Summernote CSS -->
<style>
        .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        .action-buttons{display:flex;gap:5px;justify-content:center}
        .note-editor.note-frame {
            border: 1px solid #ced4da !important;
        }
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">
<h2 class="h4 mb-4"><i class="fas fa-users me-2"></i>Manage Alumni</h2>

<!-- MESSAGE -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ADD BUTTON -->
<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#alumniModal" onclick="addAlumni()">
    <i class="fas fa-plus-circle"></i> Add Alumni Content
</button>

<!-- LIST TABLE -->
<div class="form-card">
    <table id="alumniTable" class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th>Order</th>
                <th width="200">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td class="text-center"><?php echo $row['display_order']; ?></td>

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

                        <button class="btn btn-sm btn-warning edit-btn"
                                data-alumni='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>'
                                data-bs-toggle="modal"
                                data-bs-target="#alumniModal">
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="?delete=<?php echo $row['id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this alumni entry?');">
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
<div class="modal fade" id="alumniModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Alumni Content</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="alumni_id" id="alumni_id">

                    <div class="mb-3">
                        <label>Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_alumni" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- JS Libraries -->
<script>
$(document).ready(function () {
    $('#alumniTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
    
    // Handle edit button clicks using event delegation
    $(document).on('click', '.edit-btn', function() {
        var alumniData = $(this).data('alumni');
        editAlumni(alumniData);
    });
});

/* Summernote Config */
// Summernote config moved to summernote-config.js

$('#alumniModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter alumni description..."});
    }
    
    if (window.pendingDescription !== null && window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = null;
    }
});

$('#alumniModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = null;
});

/* ADD */
function addAlumni() {
    document.getElementById("modalTitle").innerText = "Add Alumni Content";
    document.getElementById("alumni_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('code', '');
    } else {
        document.getElementById("description").value = "";
    }
    
    window.pendingDescription = null;
}

/* EDIT */
function editAlumni(item) {
    console.log('Editing alumni:', item);
    
    document.getElementById("modalTitle").innerText = "Update Alumni Content";
    document.getElementById("alumni_id").value = item.id || "";
    document.getElementById("title").value = item.title || "";
    document.getElementById("display_order").value = item.display_order || "";

    window.pendingDescription = item.description || "";
    
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('code', item.description || '');
    }
}
</script>

</body>
</html>