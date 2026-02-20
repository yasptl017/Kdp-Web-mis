<?php
include('dbconfig.php');

function short_name($full_name) {
    $full_name = trim((string)$full_name);
    if ($full_name === '') return '';
    $parts = preg_split('/\s+/', $full_name);
    return (count($parts) >= 2) ? $parts[0] . ' ' . $parts[1] : $full_name;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: editAttendance.php?type=tutorial');
    exit();
}

// Fetch the record from dedicated tutattendance table
$stmt = $conn->prepare("SELECT id, date, time, term, faculty, sem, subject, batch, presentNo FROM tutattendance WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    http_response_code(404);
    echo 'Record not found.';
    exit();
}

$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_attendance'])) {
    $stmt = $conn->prepare("DELETE FROM tutattendance WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($ok && $affected > 0) {
        header('Location: editAttendance.php?type=tutorial&success=' . urlencode('Attendance record deleted successfully.'));
    } else {
        header('Location: editAttendance.php?type=tutorial&error=' . urlencode('Failed to delete attendance record.'));
    }
    exit();
}

// Parse existing batches
$selected_tut_batches = array_filter(array_map('trim', explode(',', (string)$record['batch'])));
$selected_tut_batches = array_values(array_unique($selected_tut_batches));
$batches_normalized   = array_values(array_unique(array_map('strtoupper', $selected_tut_batches)));

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $new_present = isset($_POST['present']) ? implode(',', $_POST['present']) : '';
    $stmt = $conn->prepare("UPDATE tutattendance SET presentNo = ? WHERE id = ?");
    $stmt->bind_param('si', $new_present, $id);
    if ($stmt->execute()) {
        $record['presentNo'] = $new_present;
        $success_msg = 'Tutorial attendance updated successfully.';
    } else {
        $error_msg = 'Failed to update attendance. Please try again.';
    }
    $stmt->close();
}

$present_list = array_filter(array_map('trim', explode(',', (string)$record['presentNo'])));
$present_set  = array_flip($present_list);

// Faculty name
$faculty_display = $record['faculty'];
if (ctype_digit((string)$record['faculty'])) {
    $fstmt = $conn->prepare("SELECT Name FROM faculty WHERE id = ?");
    $fid   = (int)$record['faculty'];
    $fstmt->bind_param('i', $fid);
    $fstmt->execute();
    $frow = $fstmt->get_result()->fetch_assoc();
    if ($frow) $faculty_display = $frow['Name'];
    $fstmt->close();
}

// Students for tutorial batches
$students_result = false;
if (!empty($batches_normalized)) {
    $escaped_term    = $conn->real_escape_string($record['term']);
    $escaped_sem     = $conn->real_escape_string($record['sem']);
    $escaped_batches = array_map(fn($b) => "'" . $conn->real_escape_string($b) . "'", $batches_normalized);
    $students_result = $conn->query("SELECT id, enrollmentNo, name, TRIM(tutBatch) AS tutBatch FROM students WHERE term = '{$escaped_term}' AND sem = '{$escaped_sem}' AND UPPER(TRIM(tutBatch)) IN (" . implode(',', $escaped_batches) . ") ORDER BY UPPER(TRIM(tutBatch)), enrollmentNo, name");
}
if (!$students_result) {
    $students_result = $conn->query("SELECT id, enrollmentNo, name, tutBatch FROM students WHERE 1=0");
}
$total_students = $students_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="app-page-title mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Tutorial Attendance</h1>
                <a href="editAttendance.php?type=tutorial" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to List
                </a>
            </div>

            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            <?php if ($error_msg !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <!-- Record Details -->
            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <h4>Tutorial Details</h4>
                    <div class="row g-2" style="font-size:0.9rem;">
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Faculty</span>
                            <strong><?= htmlspecialchars($faculty_display) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Term</span>
                            <strong><?= htmlspecialchars($record['term']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Semester</span>
                            <strong><?= htmlspecialchars($record['sem']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Subject</span>
                            <strong><?= htmlspecialchars($record['subject']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Tutorial Batch(es)</span>
                            <strong><?= htmlspecialchars(implode(', ', $selected_tut_batches)) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Date</span>
                            <strong><?= htmlspecialchars($record['date']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Slot</span>
                            <strong><?= htmlspecialchars($record['time']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Attendance Form -->
            <form method="POST" action="edittutatt.php?id=<?= $id ?>">
                <div class="app-card shadow-sm">
                    <div class="app-card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h4 class="mb-0">Update Attendance
                                <span class="text-muted fw-normal" style="font-size:0.875rem;">(<?= $total_students ?> students)</span>
                            </h4>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" id="markAllBtn">
                                    <i class="bi bi-check-all me-1"></i>Mark All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllBtn">
                                    <i class="bi bi-x-lg me-1"></i>Clear All
                                </button>
                            </div>
                        </div>

                        <?php if ($total_students > 0): ?>
                            <div class="row g-2" id="student-cards">
                                <?php while ($student = $students_result->fetch_assoc()):
                                    $roll         = !empty($student['enrollmentNo']) ? $student['enrollmentNo'] : $student['id'];
                                    $display_name = short_name($student['name']);
                                    $is_present   = isset($present_set[(string)$roll]);
                                ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                        <label class="card shadow-sm p-2 text-center student-card <?= $is_present ? 'bg-success-subtle border-success' : '' ?>" style="cursor:pointer;">
                                            <input type="checkbox" name="present[]" value="<?= htmlspecialchars((string)$roll) ?>" class="d-none attendance-checkbox" <?= $is_present ? 'checked' : '' ?>>
                                            <div class="student-info">
                                                <strong><?= htmlspecialchars((string)$roll) ?></strong>
                                                <span class="d-block text-truncate" title="<?= htmlspecialchars($display_name) ?>">
                                                    <?= htmlspecialchars($display_name) ?>
                                                </span>
                                                <span class="d-block text-muted"><?= htmlspecialchars($student['tutBatch']) ?></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                                <button type="submit" name="update_attendance" class="btn btn-primary px-4">
                                    <i class="bi bi-floppy me-1"></i>Save Changes
                                </button>
                                <button type="submit" name="delete_attendance" class="btn btn-outline-danger" formnovalidate onclick="return confirm('Delete this attendance record? This cannot be undone.');">
                                    <i class="bi bi-trash me-1"></i>Delete Attendance
                                </button>
                                <span class="text-muted" id="present-count" style="font-size:0.875rem;"></span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>No students found for the selected criteria.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    const cards   = document.querySelectorAll('.student-card');
    const countEl = document.getElementById('present-count');

    function updateCount() {
        const checked = document.querySelectorAll('.attendance-checkbox:checked').length;
        if (countEl) countEl.textContent = checked + ' marked present';
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = !cb.checked;
            card.classList.toggle('bg-success-subtle', cb.checked);
            card.classList.toggle('border-success', cb.checked);
            updateCount();
        });
    });

    document.getElementById('markAllBtn')?.addEventListener('click', function () {
        cards.forEach(card => {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = true;
            card.classList.add('bg-success-subtle', 'border-success');
        });
        updateCount();
    });

    document.getElementById('clearAllBtn')?.addEventListener('click', function () {
        cards.forEach(card => {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = false;
            card.classList.remove('bg-success-subtle', 'border-success');
        });
        updateCount();
    });

    updateCount();
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
