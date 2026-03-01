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
        "SELECT id, display_order FROM nss WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM nss
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM nss
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE nss SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE nss SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_nss.php");
    exit;
}

/* -------------------------
   ADD / UPDATE NSS
   ------------------------- */
if (isset($_POST['save_nss'])) {

    $id = intval($_POST['nss_id']);
    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description'] ?? '');

    if ($id === 0) {
        // Insert
        $sql = "INSERT INTO nss (title, display_order, description) VALUES ('$title', $display_order, '$description')";
        if ($conn->query($sql)) {
            $message = "NSS record added successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    } else {
        // Update
        $sql = "UPDATE nss SET title='$title', display_order=$display_order, description='$description' WHERE id=$id";
        if ($conn->query($sql)) {
            $message = "NSS record updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    }
}

/* -------------------------
   DELETE NSS
   ------------------------- */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM nss WHERE id=$id")) {
        $message = "NSS record deleted!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}

/* -------------------------
   FETCH ALL RECORDS
   ------------------------- */
$records = $conn->query("SELECT * FROM nss ORDER BY display_order ASC, id DESC");
?>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage NSS</title>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- Summernote CSS -->
<style>
        .form-card {
            background: #fff;
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .action-buttons{display:flex;gap:5px;justify-content:center}
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="fas fa-hands-helping me-2"></i>Manage NSS</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nssModal" onclick="addNSS()">
            <i class="fas fa-plus-circle me-1"></i> Add NSS
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <table id="nssTable" class="table table-striped table-bordered align-middle">
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

                            <button class="btn btn-warning btn-sm"
                                    onclick='editNSS(<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#nssModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this NSS record?');"
                               class="btn btn-danger btn-sm" title="Delete">
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

<!-- NSS Modal -->
<div class="modal fade" id="nssModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="nssForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add NSS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="nss_id" id="nss_id" value="0">

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order <span class="text-danger">*</span></label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                        <small class="text-muted">Use the editor toolbar to add formatted content.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_nss" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Scripts: Summernote and DataTables -->
<script>
$(document).ready(function () {
    $('#nssTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});

/* Summernote configuration */
// Summernote config moved to summernote-config.js

// Initialize Summernote when modal shown
$('#nssModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 260, placeholder: "Enter NSS description..."});
    }
    
    if (window.pendingDescription !== undefined) {
        setTimeout(function () {
            $('#description').summernote('code', window.pendingDescription || '');
            window.pendingDescription = undefined;
        }, 120);
    }
});

// Destroy Summernote when hidden to avoid duplicates
$('#nssModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
});

/* Add / Edit helpers */
function addNSS() {
    document.getElementById('modalTitle').innerText = 'Add NSS';
    document.getElementById('nss_id').value = 0;
    document.getElementById('title').value = '';
    document.getElementById('display_order').value = '';
    document.getElementById('description').value = '';

    window.pendingDescription = "";
}

function editNSS(rec) {
    document.getElementById('modalTitle').innerText = 'Update NSS';
    document.getElementById('nss_id').value = rec.id;
    document.getElementById('title').value = rec.title;
    document.getElementById('display_order').value = rec.display_order;

    // Hold description to be set after Summernote init
    window.pendingDescription = rec.description;
}

// Ensure form submits Summernote content
document.getElementById('nssForm').addEventListener('submit', function () {
    if (typeof $('#description').summernote === 'function') {
        $('#description').summernote('save'); // ensure content synced
    }
});
</script>

</body>
</html>