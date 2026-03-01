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
        "SELECT id, display_order FROM accreditation WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM accreditation
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM accreditation
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE accreditation SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE accreditation SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_accreditation.php");
    exit;
}

// ADD or UPDATE Accreditation
if (isset($_POST['save_accreditation'])) {

    $id = $_POST['accreditation_id'];
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $display_order = intval($_POST['display_order']);
    $status = $conn->real_escape_string($_POST['status']);
    $remark = $conn->real_escape_string($_POST['remark']);

    if ($id == "") {
        // INSERT
        $sql = "INSERT INTO accreditation (course_name, display_order, accreditation_status, remark)
                VALUES ('$course_name', $display_order, '$status', '$remark')";
        $conn->query($sql);
        $message = "Accreditation added successfully!";
    } else {
        // UPDATE
        $sql = "UPDATE accreditation SET 
                course_name='$course_name',
                display_order=$display_order,
                accreditation_status='$status',
                remark='$remark'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Accreditation updated successfully!";
    }

    $messageType = "success";
}

// DELETE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM accreditation WHERE id=$id");
    $message = "Accreditation deleted!";
    $messageType = "success";
}

// FETCH DATA
$records = $conn->query("SELECT * FROM accreditation ORDER BY display_order ASC, id DESC");

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

    <h2 class="h4 mb-4"><i class="fas fa-certificate me-2"></i>Manage Accreditation</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#accModal" onclick="addAcc()">
        <i class="fas fa-plus-circle"></i> Add Accreditation
    </button>

    <div class="form-card">
        <table id="accTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Course</th>
                    <th>Order</th>
                    <th>Accreditation Status</th>
                    <th>Remark</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($row = $records->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td><?php echo $row['accreditation_status']; ?></td>
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
                                    onclick='editAcc(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal" data-bs-target="#accModal">
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

<!-- Modal -->
<div class="modal fade" id="accModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Accreditation</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="accreditation_id" id="accreditation_id">

                    <div class="mb-3">
                        <label class="form-label">Course *</label>
                        <input type="text" name="course_name" id="course_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Accreditation Status *</label>
                        <input type="text" name="status" id="status" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" id="remark" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_accreditation" class="btn btn-success">
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

<script>
$(document).ready(() => {
    $("#accTable").DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});
</script>

<!-- JS -->
<script>
function addAcc() {
    document.getElementById("modalTitle").innerText = "Add Accreditation";
    document.getElementById("accreditation_id").value = "";
    document.getElementById("course_name").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("status").value = "";
    document.getElementById("remark").value = "";
}

function editAcc(row) {
    document.getElementById("modalTitle").innerText = "Update Accreditation";
    document.getElementById("accreditation_id").value = row.id;
    document.getElementById("course_name").value = row.course_name;
    document.getElementById("display_order").value = row.display_order;
    document.getElementById("status").value = row.accreditation_status;
    document.getElementById("remark").value = row.remark;
}
</script>

</body>
</html>