<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/mou/";
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
        "SELECT id, display_order FROM mou WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM mou
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM mou
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE mou SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE mou SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_mou.php");
    exit;
}

// Add / Update MOU
if (isset($_POST['save_mou'])) {

    $id = $_POST['mou_id'];
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $display_order = intval($_POST['display_order']);
    $remark = $conn->real_escape_string($_POST['remark']); // simple text no HTML

    // Get old file if updating
    $oldFile = "";
    if (!empty($id)) {
        $q = $conn->query("SELECT file_path FROM mou WHERE id=$id");
        if ($q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file_path'];
        }
    }

    // Upload new file
    $file_path = $oldFile;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf','doc','docx','jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $file_path = $uploadDir . time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);

            if (!empty($oldFile) && file_exists($oldFile)) unlink($oldFile);
        }
    }

    // INSERT / UPDATE
    if (empty($id)) {
        $sql = "INSERT INTO mou (company_name, display_order, file_path, remark)
                VALUES ('$company_name', $display_order, '$file_path', '$remark')";
        $conn->query($sql);
        $message = "MOU added successfully!";
    } else {
        $sql = "UPDATE mou SET 
                company_name='$company_name',
                display_order=$display_order,
                file_path='$file_path',
                remark='$remark'
                WHERE id=$id";
        $conn->query($sql);
        $message = "MOU updated successfully!";
    }

    $messageType = "success";
}

// DELETE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT file_path FROM mou WHERE id=$id");
    if ($q->num_rows > 0) {
        $file = $q->fetch_assoc()['file_path'];
        if (file_exists($file)) unlink($file);
    }

    $conn->query("DELETE FROM mou WHERE id=$id");
    $message = "MOU deleted!";
    $messageType = "success";
}

// Fetch data
$mous = $conn->query("SELECT * FROM mou ORDER BY display_order ASC, id DESC");

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

    <h2 class="h4 mb-4"><i class="fas fa-file-contract me-2"></i>Manage MOU</h2>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#mouModal"
            onclick="addMou()">
        <i class="fas fa-plus-circle"></i> Add MOU
    </button>

    <div class="form-card">
        <table id="mouTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Company Name</th>
                    <th>Order</th>
                    <th>File</th>
                    <th>Remark</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $mous->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>

                    <td><?php echo $row['company_name']; ?></td>

                    <td class="text-center"><?php echo $row['display_order']; ?></td>

                    <td class="text-center">
                        <?php if ($row['file_path']): ?>
                            <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                View File
                            </a>
                        <?php else: ?>
                            No File
                        <?php endif; ?>
                    </td>

                    <td><?php echo nl2br($row['remark']); ?></td>

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
                                    onclick='editMou(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#mouModal">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this MOU?');"
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

<!-- MOU Modal -->
<div class="modal fade" id="mouModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add MOU</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="mou_id" id="mou_id">

                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" id="company_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload File</label>
                        <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_mou" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(() => {
    $("#mouTable").DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});
</script>

<script>
function addMou() {
    document.getElementById("modalTitle").innerText = "Add MOU";
    document.getElementById("mou_id").value = "";
    document.getElementById("company_name").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("remark").value = "";
}

function editMou(f) {
    document.getElementById("modalTitle").innerText = "Update MOU";
    document.getElementById("mou_id").value = f.id;
    document.getElementById("company_name").value = f.company_name;
    document.getElementById("display_order").value = f.display_order;
    document.getElementById("remark").value = f.remark;
}
</script>

</body>
</html>