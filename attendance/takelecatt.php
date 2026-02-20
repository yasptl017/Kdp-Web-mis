<?php
include('dbconfig.php');

function short_name($full_name) {
    $full_name = trim((string)$full_name);
    if ($full_name === '') {
        return '';
    }
    $parts = preg_split('/\s+/', $full_name);
    if (count($parts) >= 2) {
        return $parts[0] . ' ' . $parts[1];
    }
    return $full_name;
}

function lecture_column_exists(mysqli $conn, string $column): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lecattendance' AND COLUMN_NAME = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function ensure_lecture_attendance_columns(mysqli $conn): void {
    if (!lecture_column_exists($conn, 'absentNo')) {
        $conn->query("ALTER TABLE lecattendance ADD COLUMN absentNo TEXT NULL AFTER presentNo");
    }
    if (!lecture_column_exists($conn, 'description')) {
        $conn->query("ALTER TABLE lecattendance ADD COLUMN description VARCHAR(255) NULL AFTER absentNo");
    }
}

ensure_lecture_attendance_columns($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $date    = $_POST['date'];
    $time    = $_POST['slot'];
    $term    = $_POST['term'];
    $faculty = $_POST['faculty'];
    $sem     = $_POST['sem'];
    $subject = $_POST['subject'];
    $class   = trim((string)($_POST['class'] ?? ''));
    $mark_mode = trim((string)($_POST['mark_mode'] ?? 'normal'));
    $description = trim((string)($_POST['description'] ?? ''));

    $present_tokens = isset($_POST['present']) ? array_map('trim', (array)$_POST['present']) : [];
    $present_tokens = array_values(array_unique(array_filter($present_tokens, function ($value) {
        return $value !== '';
    })));
    if ($mark_mode === 'all_absent') {
        $present_tokens = [];
    }

    $class_students_stmt = $conn->prepare("SELECT enrollmentNo FROM students WHERE term = ? AND sem = ? AND class = ? AND enrollmentNo IS NOT NULL AND TRIM(enrollmentNo) <> '' ORDER BY enrollmentNo");
    $class_students_stmt->bind_param('sss', $term, $sem, $class);
    $class_students_stmt->execute();
    $students_res = $class_students_stmt->get_result();
    $class_enrollments = [];
    while ($row = $students_res->fetch_assoc()) {
        $enrollment_no = trim((string)($row['enrollmentNo'] ?? ''));
        if ($enrollment_no !== '') {
            $class_enrollments[] = $enrollment_no;
        }
    }
    $class_students_stmt->close();

    $class_enrollment_set = array_flip($class_enrollments);
    $present_filtered = [];
    foreach ($present_tokens as $enrollment_no) {
        if (isset($class_enrollment_set[$enrollment_no])) {
            $present_filtered[] = $enrollment_no;
        }
    }

    $present_set = array_flip($present_filtered);
    $absent_tokens = [];
    foreach ($class_enrollments as $enrollment_no) {
        if (!isset($present_set[$enrollment_no])) {
            $absent_tokens[] = $enrollment_no;
        }
    }

    $present = implode(',', $present_filtered);
    $absent = implode(',', $absent_tokens);
    $description_or_null = ($description !== '') ? $description : null;

    $stmt = $conn->prepare("INSERT INTO lecattendance (date, logdate, time, term, faculty, sem, subject, class, presentNo, absentNo, description) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $date, $time, $term, $faculty, $sem, $subject, $class, $present, $absent, $description_or_null);
    $stmt->execute();
    $attendance_id = (int)$conn->insert_id;
    $stmt->close();
    header("Location: attendanceSummary.php?type=lecture&id=" . $attendance_id);
    exit();
}

$data = $_POST ?: $_GET;

$faculty_id     = $data['faculty'];
$faculty_result = $conn->query("SELECT Name FROM faculty WHERE id = '$faculty_id'");
$faculty_name   = ($faculty_result->num_rows > 0) ? $faculty_result->fetch_assoc()['Name'] : $faculty_id;

$escaped_term = $conn->real_escape_string($data['term']);
$escaped_sem = $conn->real_escape_string($data['sem']);
$escaped_class = $conn->real_escape_string($data['class']);
$students_result = $conn->query("SELECT id, enrollmentNo, name, class FROM students WHERE term = '{$escaped_term}' AND sem = '{$escaped_sem}' AND class = '{$escaped_class}' ORDER BY enrollmentNo, name");
$total_students  = $students_result->num_rows;

// â”€â”€ Autofill: today's related attendance records â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$class_enrollments = [];
if ($total_students > 0) {
    $students_result->data_seek(0);
    while ($s = $students_result->fetch_assoc()) {
        if (!empty($s['enrollmentNo'])) $class_enrollments[] = $s['enrollmentNo'];
    }
    $students_result->data_seek(0);
}

$autofill_records = [];
$att_date_esc = $conn->real_escape_string($data['date']);

// Other lecture records today (same class, different subject/slot)
$lec_res = $conn->query("SELECT id, subject, time, presentNo FROM lecattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND class='{$escaped_class}' AND date='{$att_date_esc}' ORDER BY id DESC");
if ($lec_res) {
    while ($row = $lec_res->fetch_assoc()) {
        $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
        $filt = array_values(array_intersect($all, $class_enrollments));
        if (!empty($filt)) {
            $autofill_records[] = ['type' => 'Lecture', 'label' => 'Lecture Â· ' . $row['subject'] . ' Â· ' . $row['time'], 'present' => $filt];
        }
    }
}

// Lab records today (same term, sem, date) â€” filter to students of this class
$lab_res = $conn->query("SELECT id, subject, batch, presentNo FROM labattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' AND labNo IS NOT NULL AND labNo!='' ORDER BY id DESC");
if ($lab_res) {
    while ($row = $lab_res->fetch_assoc()) {
        $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
        $filt = array_values(array_intersect($all, $class_enrollments));
        if (!empty($filt)) {
            $autofill_records[] = ['type' => 'Lab', 'label' => 'Lab Â· ' . $row['subject'] . ' Â· Batch ' . $row['batch'], 'present' => $filt];
        }
    }
}

// Tutorial records today â€” from tutattendance table
$tut_res = $conn->query("SELECT id, subject, batch, presentNo FROM tutattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' ORDER BY id DESC");
if ($tut_res) {
    while ($row = $tut_res->fetch_assoc()) {
        $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
        $filt = array_values(array_intersect($all, $class_enrollments));
        if (!empty($filt)) {
            $autofill_records[] = ['type' => 'Tutorial', 'label' => 'Tutorial Â· ' . $row['subject'] . ' Â· Batch ' . $row['batch'], 'present' => $filt];
        }
    }
}
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-check2-square me-2"></i>Take Lecture Attendance</h1>

            <!-- Lecture Details Card -->
            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <h4>Lecture Details</h4>
                    <div class="row g-2" style="font-size:0.9rem;">
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Faculty</span>
                            <strong><?= htmlspecialchars($faculty_name) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Term</span>
                            <strong><?= htmlspecialchars($data['term']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Semester</span>
                            <strong><?= htmlspecialchars($data['sem']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Subject</span>
                            <strong><?= htmlspecialchars($data['subject']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Class / Date</span>
                            <strong><?= htmlspecialchars($data['class']) ?> &bull; <?= htmlspecialchars($data['date']) ?></strong>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <span class="text-muted d-block" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;">Slot</span>
                            <strong><?= htmlspecialchars($data['slot']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autofill Panel -->
            <?php if (!empty($autofill_records)): ?>
            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <h5 class="mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Today's Attendance â€” Click to Autofill</h5>
                    <p class="text-muted mb-2" style="font-size:0.82rem;">Only students belonging to Class <strong><?= htmlspecialchars($data['class']) ?></strong> will be marked.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $badge_colors = ['Lecture' => 'primary', 'Lab' => 'danger', 'Tutorial' => 'success'];
                        foreach ($autofill_records as $idx => $rec):
                            $color = $badge_colors[$rec['type']] ?? 'secondary';
                        ?>
                        <button type="button"
                                class="btn btn-outline-<?= $color ?> btn-sm autofill-btn"
                                data-present="<?= htmlspecialchars(json_encode($rec['present'])) ?>"
                                title="<?= htmlspecialchars($rec['label']) ?> (<?= count($rec['present']) ?> students from this class)">
                            
                            <?= htmlspecialchars($rec['label']) ?>
                            <span class="badge bg-<?= $color ?> ms-1"><?= count($rec['present']) ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Attendance Form -->
            <form method="POST" action="takelecatt.php" id="lectureAttendanceForm">
                <?php foreach ($data as $key => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endforeach; ?>
                <input type="hidden" name="mark_mode" id="markModeField" value="normal">
                <input type="hidden" name="description" id="attendanceDescriptionField" value="">

                <div class="app-card shadow-sm">
                    <div class="app-card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h4 class="mb-0">Mark Attendance
                                <span class="text-muted fw-normal" style="font-size:0.875rem;">(<?= $total_students ?> students)</span>
                            </h4>
                            <div class="attendance-actions mb-0">
                                <button type="button" class="btn btn-sm btn-outline-success" id="markAllBtn">
                                    <i class="bi bi-check-all me-1"></i>Mark All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllBtn">
                                    <i class="bi bi-x-lg me-1"></i>Clear All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="allAbsentBtn">
                                    <i class="bi bi-x-octagon me-1"></i>All Ab
                                </button>
                            </div>
                        </div>

                        <?php if ($total_students > 0): ?>
                            <div class="row g-2" id="student-cards">
                                <?php
                                $students_result->data_seek(0);
                                while ($student = $students_result->fetch_assoc()):
                                    $student_roll = !empty($student['enrollmentNo']) ? $student['enrollmentNo'] : $student['id'];
                                    $display_name = short_name($student['name']);
                                    ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                        <label class="card shadow-sm p-2 text-center student-card" style="cursor:pointer;">
                                            <input type="checkbox" name="present[]" value="<?= htmlspecialchars((string)$student_roll); ?>" class="d-none attendance-checkbox">
                                            <div class="student-info">
                                                <strong><?= htmlspecialchars((string)$student_roll); ?></strong>
                                                <span class="d-block text-truncate" title="<?= htmlspecialchars($display_name); ?>">
                                                    <?= htmlspecialchars($display_name); ?>
                                                </span>
                                                <span class="d-block text-muted"><?= htmlspecialchars($student['class']); ?></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                                <button type="submit" name="submit_attendance" class="btn btn-success px-4">
                                    Submit Attendance
                                </button>
                                <span class="text-muted" id="present-count" style="font-size:0.875rem;">0 marked present</span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>No students found for the selected criteria.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="modal fade" id="allAbsentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Mark All Absent</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Optional description (example: No teaching due to Holi):</p>
                            <textarea class="form-control" id="allAbsentDescription" rows="3" maxlength="255" placeholder="Description (optional)"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmAllAbsentBtn">Save All Absent</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const attendanceForm = document.getElementById('lectureAttendanceForm');
    const submitAttendanceBtn = attendanceForm?.querySelector('button[name="submit_attendance"]');
    const cards = document.querySelectorAll('.student-card');
    const countEl = document.getElementById('present-count');
    const markModeField = document.getElementById('markModeField');
    const descriptionField = document.getElementById('attendanceDescriptionField');
    const allAbsentBtn = document.getElementById('allAbsentBtn');
    const allAbsentDescription = document.getElementById('allAbsentDescription');
    const confirmAllAbsentBtn = document.getElementById('confirmAllAbsentBtn');
    const allAbsentModalEl = document.getElementById('allAbsentModal');
    const allAbsentModal = (window.bootstrap && allAbsentModalEl) ? bootstrap.Modal.getOrCreateInstance(allAbsentModalEl) : null;

    function setNormalMode() {
        if (markModeField) markModeField.value = 'normal';
        if (descriptionField) descriptionField.value = '';
    }

    function submitAttendanceForm() {
        if (!attendanceForm || !submitAttendanceBtn) return;
        if (typeof attendanceForm.requestSubmit === 'function') {
            attendanceForm.requestSubmit(submitAttendanceBtn);
        } else {
            submitAttendanceBtn.click();
        }
    }

    function updateCount() {
        const checked = document.querySelectorAll('.attendance-checkbox:checked').length;
        if (countEl) countEl.textContent = checked + ' marked present';
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            const checkbox = card.querySelector('.attendance-checkbox');
            checkbox.checked = !checkbox.checked;
            card.classList.toggle('bg-success-subtle', checkbox.checked);
            card.classList.toggle('border-success', checkbox.checked);
            setNormalMode();
            updateCount();
        });
    });

    document.getElementById('markAllBtn')?.addEventListener('click', function () {
        cards.forEach(card => {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = true;
            card.classList.add('bg-success-subtle', 'border-success');
        });
        setNormalMode();
        updateCount();
    });

    document.getElementById('clearAllBtn')?.addEventListener('click', function () {
        cards.forEach(card => {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = false;
            card.classList.remove('bg-success-subtle', 'border-success');
        });
        setNormalMode();
        updateCount();
    });

    // Autofill buttons
    document.querySelectorAll('.autofill-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const presentSet = new Set(JSON.parse(this.dataset.present));
            cards.forEach(function (card) {
                const cb = card.querySelector('.attendance-checkbox');
                if (presentSet.has(cb.value)) {
                    cb.checked = true;
                    card.classList.add('bg-success-subtle', 'border-success');
                }
            });
            setNormalMode();
            updateCount();
        });
    });

    allAbsentBtn?.addEventListener('click', function () {
        if (allAbsentDescription) allAbsentDescription.value = '';
        if (allAbsentModal) {
            allAbsentModal.show();
            return;
        }

        const note = window.prompt('Optional description (example: No teaching due to Holi):', '') || '';
        if (!window.confirm('Save attendance as all absent?')) {
            return;
        }
        if (markModeField) markModeField.value = 'all_absent';
        if (descriptionField) descriptionField.value = note.trim();
        submitAttendanceForm();
    });

    confirmAllAbsentBtn?.addEventListener('click', function () {
        cards.forEach(card => {
            const cb = card.querySelector('.attendance-checkbox');
            cb.checked = false;
            card.classList.remove('bg-success-subtle', 'border-success');
        });
        updateCount();

        if (markModeField) markModeField.value = 'all_absent';
        if (descriptionField) descriptionField.value = (allAbsentDescription?.value || '').trim();
        if (allAbsentModal) allAbsentModal.hide();

        submitAttendanceForm();
    });

    attendanceForm?.addEventListener('submit', function () {
        if (markModeField?.value !== 'all_absent') {
            setNormalMode();
        }
    });

    updateCount();
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
