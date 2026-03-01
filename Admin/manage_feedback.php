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
        "SELECT id, display_order FROM feedback WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM feedback
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM feedback
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE feedback SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE feedback SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_feedback.php");
    exit;
}

/* ==================================================
   ADD / UPDATE FEEDBACK
   ================================================== */
if (isset($_POST['save_feedback'])) {

    $id = intval($_POST['feedback_id']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $display_order = intval($_POST['display_order']);
    $feedback = $conn->real_escape_string($_POST['feedback']);
    $department_id = intval($_POST['department_id']);
    $passing_year = $conn->real_escape_string($_POST['passing_year']);

    if ($id == 0) {
        $sql = "INSERT INTO feedback (student_name, display_order, feedback, department_id, passing_year)
                VALUES ('$student_name', $display_order, '$feedback', $department_id, '$passing_year')";
        $conn->query($sql);
        $message = "Feedback added successfully!";
    } else {
        $sql = "UPDATE feedback SET
                student_name='$student_name',
                display_order=$display_order,
                feedback='$feedback',
                department_id=$department_id,
                passing_year='$passing_year'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Feedback updated successfully!";
    }

    $messageType = "success";
}

/* ==================================================
   DELETE FEEDBACK
   ================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM feedback WHERE id=$id");
    $message = "Feedback deleted!";
    $messageType = "success";
}

// Fetch all feedbacks with department names
$feedbacks = $conn->query("
    SELECT f.*, d.department 
    FROM feedback f
    LEFT JOIN departments d ON f.department_id = d.id
    ORDER BY f.display_order ASC, f.id DESC
");

// Fetch departments for dropdown
$departments = $conn->query("SELECT * FROM departments WHERE visibility=1 ORDER BY department ASC");
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
    .action-buttons{display:flex;gap:5px;justify-content:center}
    .feedback-text {
        max-width: 400px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .feedback-badge {
        background: #4e73df;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .year-badge {
        background: #1cc88a;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-comments me-2"></i>Manage Student Feedback</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#feedbackModal" onclick="addFeedback()">
        <i class="fas fa-plus-circle"></i> Add Feedback
    </button>

    <div class="form-card">
        <table id="feedbackTable" class="table table-bordered table-striped align-middle">
            <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Student Name</th>
                <th>Order</th>
                <th>Department</th>
                <th>Passing Year</th>
                <th>Feedback</th>
                <th width="200">Action</th>
            </tr>
            </thead>

            <tbody>
            <?php $i=1; while($fb=$feedbacks->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $i++; ?></td>
                    <td>
                        <strong><?= htmlspecialchars($fb['student_name']); ?></strong>
                    </td>
                    <td class="text-center"><?= $fb['display_order']; ?></td>
                    <td class="text-center">
                        <span class="feedback-badge">
                            <?= htmlspecialchars($fb['department'] ?? 'N/A'); ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="year-badge">
                            <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($fb['passing_year']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="feedback-text" title="<?= htmlspecialchars($fb['feedback']); ?>">
                            <?= htmlspecialchars($fb['feedback']); ?>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">

                            <a href="?move=up&id=<?= $fb['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Up">
                               <i class="fas fa-arrow-up"></i>
                            </a>

                            <a href="?move=down&id=<?= $fb['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Down">
                               <i class="fas fa-arrow-down"></i>
                            </a>

                            <button class="btn btn-sm btn-warning"
                                onclick='editFeedback(<?= json_encode($fb, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#feedbackModal"
                                title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?= $fb['id']; ?>" 
                               onclick="return confirm('Delete this feedback?');"
                               class="btn btn-sm btn-danger"
                               title="Delete">
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

<!-- FEEDBACK MODAL -->
<div class="modal fade" id="feedbackModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Feedback</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="feedback_id" id="feedback_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student Name <span class="text-danger">*</span></label>
                            <input type="text" name="student_name" id="student_name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                            <input type="number" name="display_order" id="display_order" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Passing Year <span class="text-danger">*</span></label>
                            <select name="passing_year" id="passing_year" class="form-control" required>
                                <option value="">Select Year</option>
                                <?php 
                                $currentYear = date('Y');
                                for($year = $currentYear; $year >= $currentYear - 30; $year--): 
                                ?>
                                    <option value="<?= $year; ?>"><?= $year; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php 
                                // Reset departments pointer
                                $departments->data_seek(0);
                                while($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?= $dept['id']; ?>"><?= htmlspecialchars($dept['department']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Feedback <span class="text-danger">*</span></label>
                        <textarea name="feedback" id="feedback" class="form-control" rows="5" required placeholder="Enter student feedback..."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_feedback" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Feedback
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts -->
<script>
console.log('=== Feedback Management ===');

// Initialize DataTable
$(document).ready(() => {
    $('#feedbackTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']],
        language: {
            search: "Search Feedback:",
            lengthMenu: "Show _MENU_ items per page",
            info: "Showing _START_ to _END_ of _TOTAL_ feedbacks",
            infoEmpty: "No feedback available",
            zeroRecords: "No matching feedback found"
        }
    });
    console.log('✓ DataTable initialized');
});

// Add Feedback
function addFeedback() {
    console.log('Adding new feedback');
    
    document.getElementById("modalTitle").innerText = "Add Feedback";
    document.getElementById("feedback_id").value = "";
    document.getElementById("student_name").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("passing_year").value = "";
    document.getElementById("department_id").value = "";
    document.getElementById("feedback").value = "";
}

// Edit Feedback
function editFeedback(fb) {
    console.log('Editing feedback:', fb.id);
    
    document.getElementById("modalTitle").innerText = "Update Feedback";
    document.getElementById("feedback_id").value = fb.id;
    document.getElementById("student_name").value = fb.student_name;
    document.getElementById("display_order").value = fb.display_order;
    document.getElementById("passing_year").value = fb.passing_year;
    document.getElementById("department_id").value = fb.department_id;
    document.getElementById("feedback").value = fb.feedback;
}

console.log('✓ Feedback Management page loaded');
</script>

</body>
</html>