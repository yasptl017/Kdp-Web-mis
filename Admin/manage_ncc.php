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
        "SELECT id, display_order FROM ncc WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM ncc
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM ncc
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE ncc SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE ncc SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_ncc.php");
    exit;
}

/* ======================================
   ADD / UPDATE NCC ACTIVITY
   ====================================== */
if (isset($_POST['save_ncc'])) {

    $id = intval($_POST['ncc_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']); // summernote HTML

    if ($id == 0) {
        // INSERT
        $sql = "INSERT INTO ncc (title, display_order, description)
                VALUES ('$title', $display_order, '$description')";
        $conn->query($sql);

        $message = "NCC activity added successfully!";
        $messageType = "success";

    } else {
        // UPDATE
        $sql = "UPDATE ncc SET
                title='$title',
                display_order=$display_order,
                description='$description'
                WHERE id=$id";
        $conn->query($sql);

        $message = "NCC activity updated successfully!";
        $messageType = "success";
    }
}

/* ======================================
   DELETE
   ====================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM ncc WHERE id=$id");

    $message = "NCC activity deleted!";
    $messageType = "success";
}

$records = $conn->query("SELECT * FROM ncc ORDER BY display_order ASC, id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Summernote CSS -->
<style>
        .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        .action-buttons{display:flex;gap:5px;justify-content:center}
        .note-editor.note-frame {
            border: 1px solid #ced4da;
        }
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-flag me-2"></i>Manage NCC Activities</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#nccModal" onclick="addNCC()">
    <i class="fas fa-plus-circle"></i> Add NCC Activity
</button>

<div class="form-card">
    <table id="nccTable" class="table table-bordered table-striped align-middle">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th>Order</th>
                <th width="200">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php $i = 1; while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>
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

                        <button class="btn btn-sm btn-warning"
                                onclick='editNCC(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#nccModal">
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Delete this entry?');"
                           class="btn btn-sm btn-danger">
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

<!-- Modal -->
<div class="modal fade" id="nccModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add NCC Activity</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="ncc_id" id="ncc_id">

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_ncc" class="btn btn-success">
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
<!-- Summernote -->
<script>
$(document).ready(function () {
    $('#nccTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});

/* Summernote Config */
// Summernote config moved to summernote-config.js

$('#nccModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter description..."});
    }
    if (window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription);
        window.pendingDescription = undefined;
    }
});

$('#nccModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = undefined;
});

function addNCC() {
    document.getElementById("modalTitle").innerHTML = "Add NCC Activity";
    document.getElementById("ncc_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("description").value = "";
    window.pendingDescription = undefined;
}

function editNCC(f) {
    document.getElementById("modalTitle").innerHTML = "Update NCC Activity";
    document.getElementById("ncc_id").value = f.id;
    document.getElementById("title").value = f.title;
    document.getElementById("display_order").value = f.display_order;

    window.pendingDescription = f.description;
}
</script>

</body>
</html>