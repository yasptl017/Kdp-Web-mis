<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/newsletter/";
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
        "SELECT id, display_order FROM newsletters WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM newsletters
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM newsletters
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE newsletters SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE newsletters SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_newsletter.php");
    exit;
}

// Add / Update Newsletter
if (isset($_POST['save_newsletter'])) {
    $id = $_POST['newsletter_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $remark = $conn->real_escape_string($_POST['remark']);

    // Fetch old file
    $oldFile = "";
    if ($id != "") {
        $q = $conn->query("SELECT file FROM newsletters WHERE id=$id");
        if ($q && $q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file'];
        }
    }

    // Upload file if selected
    $uploadedFile = $oldFile;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $uploadedFile = $uploadDir . time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadedFile);

            // Delete old
            if (!empty($oldFile) && file_exists($oldFile)) {
                unlink($oldFile);
            }
        } else {
            $message = "Invalid file format. Only PDF, DOC, DOCX allowed.";
            $messageType = "danger";
        }
    }

    // Insert or Update
    if ($id == "") {
        $sql = "INSERT INTO newsletters (title, display_order, file, remark)
                VALUES ('$title', $display_order, '$uploadedFile', '$remark')";
        if ($conn->query($sql)) {
            $message = "Newsletter added successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    } else {
        $sql = "UPDATE newsletters SET 
                  title='$title',
                  display_order=$display_order,
                  file='$uploadedFile',
                  remark='$remark'
                WHERE id=$id";
        if ($conn->query($sql)) {
            $message = "Newsletter updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "danger";
        }
    }
}

// Delete Newsletter
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file FROM newsletters WHERE id=$id");
    if ($q && $q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM newsletters WHERE id=$id");

    $message = "Newsletter deleted successfully!";
    $messageType = "success";
}

$newsletters = $conn->query("SELECT * FROM newsletters ORDER BY display_order ASC, id DESC");
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
.form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
.action-buttons{display:flex;gap:5px;justify-content:center}
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    <h2 class="h4 mb-4"><i class="fas fa-newspaper me-2"></i>Newsletter Management</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#newsletterModal" onclick="addNewsletter()">
        <i class="fas fa-plus-circle me-2"></i>Add Newsletter
    </button>

    <!-- Newsletter Table -->
    <div class="form-card">
        <table id="newsletterTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>File</th>
                    <th>Remark</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($row = $newsletters->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td>
                        <a href="<?php echo $row['file']; ?>" target="_blank">Download File</a>
                    </td>
                    <td><?php echo nl2br(htmlspecialchars($row['remark'])); ?></td>
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
                                    onclick='editNewsletter(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal" data-bs-target="#newsletterModal">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this newsletter?');">
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
<div class="modal fade" id="newsletterModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Newsletter</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="newsletter_id" id="newsletter_id">

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File (PDF/DOC/DOCX)</label>
                        <input type="file" name="file" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control" rows="4"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_newsletter" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts (load AFTER footer.php which has jQuery) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#newsletterTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']], // Sort by display_order ascending
        language: {
            search: "Search Newsletters:",
            lengthMenu: "Show _MENU_ newsletters per page",
            info: "Showing _START_ to _END_ of _TOTAL_ newsletters",
            infoEmpty: "No newsletters available",
            zeroRecords: "No matching newsletters found"
        }
    });
});
</script>

<!-- JS Functions -->
<script>
function addNewsletter() {
    document.getElementById("modalTitle").innerText = "Add Newsletter";
    document.getElementById("newsletter_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("remark").value = "";
}

function editNewsletter(data) {
    document.getElementById("modalTitle").innerText = "Update Newsletter";
    document.getElementById("newsletter_id").value = data.id;
    document.getElementById("title").value = data.title;
    document.getElementById("display_order").value = data.display_order;
    document.getElementById("remark").value = data.remark;
}
</script>

</body>
</html>