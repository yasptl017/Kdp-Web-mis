<?php
include('dbconfig.php');

// ── Auto-create tutattendance table if missing ────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `tutattendance` (
    `id`        INT          NOT NULL AUTO_INCREMENT,
    `date`      DATE         NOT NULL,
    `logdate`   DATE         NOT NULL,
    `time`      VARCHAR(50)  NOT NULL,
    `term`      VARCHAR(20)  NOT NULL,
    `faculty`   VARCHAR(50)  NOT NULL,
    `sem`       VARCHAR(10)  NOT NULL,
    `subject`   VARCHAR(100) NOT NULL,
    `batch`     VARCHAR(100) NOT NULL COMMENT 'comma-separated tutorial batch names',
    `presentNo` TEXT         NOT NULL COMMENT 'comma-separated enrollment numbers',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

function render_hidden_inputs($name, $value) {
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            render_hidden_inputs($name . '[' . $key . ']', $item);
        }
        return;
    }

    echo '<input type="hidden" name="' . htmlspecialchars((string)$name, ENT_QUOTES) . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES) . '">' . PHP_EOL;
}

function normalize_batch_values($batch_input) {
    if (is_array($batch_input)) {
        $raw_batches = $batch_input;
    } elseif (!empty($batch_input)) {
        $raw_batches = explode(',', (string)$batch_input);
    } else {
        $raw_batches = [];
    }

    $normalized = [];
    foreach ($raw_batches as $batch) {
        $batch = trim((string)$batch);
        if ($batch !== '' && !in_array($batch, $normalized, true)) {
            $normalized[] = $batch;
        }
    }

    return $normalized;
}

$data = $_POST ?: $_GET;
$selected_tut_batches = normalize_batch_values($data['tutBatch'] ?? ($data['batch'] ?? []));
$selected_tut_batches_normalized = array_values(array_unique(array_map(function ($batch_name) {
    return strtoupper(trim((string)$batch_name));
}, $selected_tut_batches)));
$tutorial_batch_csv = implode(',', $selected_tut_batches);
$data['tutBatch'] = $selected_tut_batches;
unset($data['batch']);
$attendance_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $date = $_POST['date'];
    $time = $_POST['slot'];
    $term = $_POST['term'];
    $faculty = $_POST['faculty'];
    $sem = $_POST['sem'];
    $subject = $_POST['subject'];
    $submitted_batches = normalize_batch_values($_POST['tutBatch'] ?? ($_POST['batch'] ?? []));
    $batch = implode(',', $submitted_batches);
    $present = isset($_POST['present']) ? implode(',', $_POST['present']) : '';

    if (empty($submitted_batches)) {
        $attendance_error = 'Please select at least one tutorial batch.';
    } else {
        $stmt = $conn->prepare("INSERT INTO tutattendance (date, logdate, time, term, faculty, sem, subject, batch, presentNo) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssss', $date, $time, $term, $faculty, $sem, $subject, $batch, $present);
        $stmt->execute();
        $attendance_id = (int)$conn->insert_id;
        $stmt->close();
        header('Location: attendanceSummary.php?type=tutorial&id=' . $attendance_id);
        exit();
    }
}

$faculty_id = $data['faculty'];
$faculty_query = "SELECT Name FROM faculty WHERE id = '$faculty_id'";
$faculty_result = $conn->query($faculty_query);
$faculty_name = ($faculty_result->num_rows > 0) ? $faculty_result->fetch_assoc()['Name'] : $faculty_id;

$escaped_term = $conn->real_escape_string($data['term']);
$escaped_sem = $conn->real_escape_string($data['sem']);
$students_result = false;
if (!empty($selected_tut_batches_normalized)) {
    $escaped_batches = array_map(function ($batch_name) use ($conn) {
        return "'" . $conn->real_escape_string($batch_name) . "'";
    }, $selected_tut_batches_normalized);
    $students_query = "SELECT id, enrollmentNo, name, TRIM(tutBatch) AS tutBatch FROM students WHERE term = '{$escaped_term}' AND sem = '{$escaped_sem}' AND UPPER(TRIM(tutBatch)) IN (" . implode(',', $escaped_batches) . ") ORDER BY UPPER(TRIM(tutBatch)), enrollmentNo, name";
    $students_result = $conn->query($students_query);
}

if (!$students_result) {
    $students_result = $conn->query("SELECT id, enrollmentNo, name, tutBatch FROM students WHERE 1 = 0");
}
$total_students = $students_result->num_rows;

// ── Autofill: today's related attendance records ──────────────────────────────
$tut_enrollments = [];
if ($total_students > 0) {
    $students_result->data_seek(0);
    while ($s = $students_result->fetch_assoc()) {
        if (!empty($s['enrollmentNo'])) $tut_enrollments[] = $s['enrollmentNo'];
    }
    $students_result->data_seek(0);
}

$autofill_records = [];
if (!empty($tut_enrollments)) {
    $att_date_esc = $conn->real_escape_string($data['date']);

    // Lecture records today — filter to students in selected tutorial batches
    $lec_res = $conn->query("SELECT id, subject, class, time, presentNo FROM lecattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' ORDER BY id DESC");
    if ($lec_res) {
        while ($row = $lec_res->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $tut_enrollments));
            if (!empty($filt)) {
                $autofill_records[] = ['type' => 'Lecture', 'label' => 'Lecture · ' . $row['subject'] . ' · Class ' . $row['class'] . ' · ' . $row['time'], 'present' => $filt];
            }
        }
    }

    // Lab records today — filter to students in selected tutorial batches
    $lab_res = $conn->query("SELECT id, subject, batch, presentNo FROM labattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' AND labNo IS NOT NULL AND labNo!='' ORDER BY id DESC");
    if ($lab_res) {
        while ($row = $lab_res->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $tut_enrollments));
            if (!empty($filt)) {
                $autofill_records[] = ['type' => 'Lab', 'label' => 'Lab · ' . $row['subject'] . ' · Batch ' . $row['batch'], 'present' => $filt];
            }
        }
    }

    // Other tutorial records today — from tutattendance table
    $tut_res2 = $conn->query("SELECT id, subject, batch, presentNo FROM tutattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' ORDER BY id DESC");
    if ($tut_res2) {
        while ($row = $tut_res2->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $tut_enrollments));
            if (!empty($filt)) {
                $autofill_records[] = ['type' => 'Tutorial', 'label' => 'Tutorial · ' . $row['subject'] . ' · Batch ' . $row['batch'], 'present' => $filt];
            }
        }
    }
}
// ─────────────────────────────────────────────────────────────────────────────
?>

<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-check2-square me-2"></i>Take Tutorial Attendance</h1>

            <?php if ($attendance_error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($attendance_error); ?></div>
            <?php endif; ?>

            <div class="app-card app-card-body shadow-sm mb-4">
                <h4>Tutorial Details</h4>
                <p><strong>Faculty:</strong> <?= htmlspecialchars($faculty_name) ?></p>
                <p><strong>Term:</strong> <?= htmlspecialchars($data['term']) ?></p>
                <p><strong>Semester:</strong> <?= htmlspecialchars($data['sem']) ?></p>
                <p><strong>Subject:</strong> <?= htmlspecialchars($data['subject']) ?></p>
                <p><strong>Tutorial Batch(es):</strong> <?= htmlspecialchars(!empty($selected_tut_batches) ? implode(', ', $selected_tut_batches) : '-') ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($data['date']) ?></p>
                <p><strong>Slot:</strong> <?= htmlspecialchars($data['slot']) ?></p>
            </div>

            <!-- Autofill Panel -->
            <?php if (!empty($autofill_records)): ?>
            <div class="app-card app-card-body shadow-sm mb-4">
                <h5 class="mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Today's Attendance — Click to Autofill</h5>
                <p class="text-muted mb-2" style="font-size:0.82rem;">Only students in tutorial batch(es) <strong><?= htmlspecialchars(implode(', ', $selected_tut_batches)) ?></strong> will be marked.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php
                    $badge_colors = ['Lecture' => 'primary', 'Lab' => 'danger', 'Tutorial' => 'success'];
                    foreach ($autofill_records as $rec):
                        $color = $badge_colors[$rec['type']] ?? 'secondary';
                    ?>
                    <button type="button"
                            class="btn btn-outline-<?= $color ?> btn-sm autofill-btn"
                            data-present="<?= htmlspecialchars(json_encode($rec['present'])) ?>"
                            title="<?= htmlspecialchars($rec['label']) ?> (<?= count($rec['present']) ?> students from these batches)">
                        
                        <?= htmlspecialchars($rec['label']) ?>
                        <span class="badge bg-<?= $color ?> ms-1"><?= count($rec['present']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="taketutatt.php">
                <?php foreach ($data as $key => $value): ?>
                    <?php render_hidden_inputs($key, $value); ?>
                <?php endforeach; ?>

                <div class="app-card app-card-body shadow-sm">
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
                        </div>
                    </div>

                    <?php if ($total_students > 0): ?>
                        <div class="row g-2" id="student-cards">
                            <?php while ($student = $students_result->fetch_assoc()):
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
                                            <span class="d-block text-muted"><?= htmlspecialchars($student['tutBatch']); ?></span>
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
                        <div class="alert alert-warning mb-0">No students found for the selected criteria.</div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const cards = document.querySelectorAll('.student-card');
    const countEl = document.getElementById('present-count');

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
            updateCount();
        });
    });

    updateCount();
</script>

<?php include('footer.php'); ?>
</body>
</html>

<?php $conn->close(); ?>
