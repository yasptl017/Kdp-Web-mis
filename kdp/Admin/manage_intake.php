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
        "SELECT id, display_order FROM intake WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM intake
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM intake
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE intake SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE intake SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_intake.php");
    exit;
}

// Add / Update Intake
if (isset($_POST['save_intake'])) {

    $id = $_POST['intake_id'];
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $display_order = intval($_POST['display_order']);
    $intek = $conn->real_escape_string($_POST['intek']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $remark = $conn->real_escape_string($_POST['remark']);

    if ($id == "") {
        // Insert
        $sql = "INSERT INTO intake (course_name, display_order, intek, duration, remark)
                VALUES ('$course_name', $display_order, '$intek', '$duration', '$remark')";
        $conn->query($sql);

        $message = "Intake added successfully!";
        $messageType = "success";

    } else {
        // Update
        $sql = "UPDATE intake SET
                    course_name='$course_name',
                    display_order=$display_order,
                    intek='$intek',
                    duration='$duration',
                    remark='$remark'
                WHERE id=$id";

        $conn->query($sql);

        $message = "Intake updated successfully!";
        $messageType = "success";
    }
}

// Delete Intake
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM intake WHERE id=$id");

    $message = "Intake deleted!";
    $messageType = "success";
}

$intakes = $conn->query("SELECT * FROM intake ORDER BY display_order ASC, id DESC");
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

    <h2 class="h4 mb-4"><i class="fas fa-graduation-cap me-2"></i>Manage Intake</h2>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#intakeModal" onclick="addIntake()">
        <i class="fas fa-plus-circle"></i> Add Intake
    </button>

    <div class="form-card">
        <table id="intakeTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="50">#</th>
                    <th>Course Name</th>
                    <th>Order</th>
                    <th>Intek</th>
                    <th>Duration</th>
                    <th>Remark</th>
                    <th width="200">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php $i=1; while ($row = $intakes->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td><?php echo $row['intek']; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td><?php echo $row['remark']; ?></td>

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
                                    onclick='editIntake(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal" data-bs-target="#intakeModal">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this intake?');"
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

<!-- Intake Modal -->
<div class="modal fade" id="intakeModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Intake</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="intake_id" id="intake_id">

                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" name="course_name" id="course_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intek *</label>
                        <input type="text" name="intek" id="intek" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duration *</label>
                        <input type="text" name="duration" id="duration" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <input type="text" name="remark" id="remark" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_intake" class="btn btn-success">
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
    $("#intakeTable").DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});

// Reset form for Add new
function addIntake() {
    document.getElementById("modalTitle").innerText = "Add Intake";
    document.getElementById("intake_id").value = "";
    document.getElementById("course_name").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("intek").value = "";
    document.getElementById("duration").value = "";
    document.getElementById("remark").value = "";
}

// Load record for edit
function editIntake(data) {
    document.getElementById("modalTitle").innerText = "Update Intake";
    document.getElementById("intake_id").value = data.id;
    document.getElementById("course_name").value = data.course_name;
    document.getElementById("display_order").value = data.display_order;
    document.getElementById("intek").value = data.intek;
    document.getElementById("duration").value = data.duration;
    document.getElementById("remark").value = data.remark;
}
</script>

</body>
</html>