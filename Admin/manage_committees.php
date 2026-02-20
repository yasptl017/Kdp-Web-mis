<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/committees/";
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
        "SELECT id, display_order FROM committees WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM committees
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM committees
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE committees SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE committees SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_committees.php");
    exit;
}

// Add / Update Committee
if (isset($_POST['save_committee'])) {

    $id       = $_POST['committee_id'];
    $title    = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $remark   = $conn->real_escape_string($_POST['remark']);

    // Fetch old file if editing
    $oldFile = "";
    if ($id != "") {
        $qry = $conn->query("SELECT file FROM committees WHERE id=$id");
        if ($qry && $qry->num_rows > 0) {
            $oldFile = $qry->fetch_assoc()['file'];
        }
    }

    // Handle File Upload
    $filePath = $oldFile; // Keep existing file by default
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {

        $allowed = ['pdf','jpg','jpeg','png','doc','docx'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {

            $filePath = $uploadDir . time() . "_" . rand(1000,9999) . "." . $ext;

            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

            // Delete old file
            if (!empty($oldFile) && file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    }

    // Insert or Update
    if ($id == "") {
        $sql = "INSERT INTO committees (title, display_order, file, remark)
                VALUES ('$title', $display_order, '$filePath', '$remark')";
        $conn->query($sql);
        $message = "Committee added successfully!";
        $messageType = "success";
    } else {
        $sql = "UPDATE committees SET 
                title='$title',
                display_order=$display_order,
                file='$filePath',
                remark='$remark'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Committee updated successfully!";
        $messageType = "success";
    }
}

// Delete Committee
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file FROM committees WHERE id=$id");
    if ($q && $q->num_rows > 0) {
        $file = $q->fetch_assoc()['file'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM committees WHERE id=$id");

    $message = "Committee deleted successfully!";
    $messageType = "success";
}

$committees = $conn->query("SELECT * FROM committees ORDER BY display_order ASC, id DESC");
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
.form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
.action-buttons{display:flex;gap:5px;justify-content:center}
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-users me-2"></i>Manage Committees</h2>

    <?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#committeeModal" onclick="addCommittee()">
        <i class="fas fa-plus-circle me-2"></i>Add Committee
    </button>

    <div class="form-card">
        <table id="committeeTable" class="table table-bordered table-striped align-middle">
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
                <?php $i = 1; while($row = $committees->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>

                    <td><?php echo htmlspecialchars($row['title']); ?></td>

                    <td class="text-center"><?php echo $row['display_order']; ?></td>

                    <td class="text-center">
                        <?php if (!empty($row['file'])): ?>
                            <a href="<?php echo $row['file']; ?>" target="_blank" class="btn btn-sm btn-info">
                                View File
                            </a>
                        <?php else: ?>
                            <span class="text-muted">No File</span>
                        <?php endif; ?>
                    </td>

                    <td><?php echo htmlspecialchars($row['remark']); ?></td>

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
                                    onclick='editCommittee(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal" data-bs-target="#committeeModal">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this committee?');"
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

<!-- Committee Modal -->
<div class="modal fade" id="committeeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Committee</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="committee_id" id="committee_id">

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="file" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control" rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_committee" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(() => {
    $('#committeeTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});
</script>

<!-- Modal Logic -->
<script>
function addCommittee() {
    document.getElementById("modalTitle").innerText = "Add Committee";
    document.getElementById("committee_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("remark").value = "";
}

function editCommittee(c) {
    document.getElementById("modalTitle").innerText = "Update Committee";
    document.getElementById("committee_id").value = c.id;
    document.getElementById("title").value = c.title;
    document.getElementById("display_order").value = c.display_order;
    document.getElementById("remark").value = c.remark;
}
</script>

</body>
</html>