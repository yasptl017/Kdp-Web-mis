<?php
include('dbconfig.php');

function normalize_batch_values($batchInput) {
    if (is_array($batchInput)) {
        $rawBatches = $batchInput;
    } elseif (!empty($batchInput)) {
        $rawBatches = explode(',', (string)$batchInput);
    } else {
        $rawBatches = [];
    }

    $normalized = [];
    foreach ($rawBatches as $batch) {
        $batch = trim((string)$batch);
        if ($batch !== '' && !in_array($batch, $normalized, true)) {
            $normalized[] = $batch;
        }
    }

    return $normalized;
}

function normalize_batch_lab_map($batchLabInput, array $selectedBatches, $fallbackLab = '') {
    $map = [];
    if (is_array($batchLabInput)) {
        foreach ($batchLabInput as $batch => $labNo) {
            $batch = trim((string)$batch);
            $labNo = trim((string)$labNo);
            if ($batch !== '' && $labNo !== '' && in_array($batch, $selectedBatches, true)) {
                $map[$batch] = $labNo;
            }
        }
    }

    // Backward compatibility for old single-batch form submissions.
    if (empty($map) && count($selectedBatches) === 1 && !empty($fallbackLab)) {
        $map[$selectedBatches[0]] = trim((string)$fallbackLab);
    }

    return $map;
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

$data = $_POST ?: $_GET;
$selected_batches = normalize_batch_values($data['batch'] ?? []);
$batch_lab_map = normalize_batch_lab_map($data['batch_lab_map'] ?? [], $selected_batches, $data['lab'] ?? '');

$data['batch'] = $selected_batches;
$data['batch_lab_map'] = $batch_lab_map;
unset($data['lab']);

$batch_csv = implode(',', $selected_batches);
$batch_lab_pairs = [];
foreach ($selected_batches as $batch_name) {
    if (!empty($batch_lab_map[$batch_name])) {
        $batch_lab_pairs[] = $batch_name . ':' . $batch_lab_map[$batch_name];
    }
}
$batch_lab_csv = implode(', ', $batch_lab_pairs);
$selected_labs = array_values(array_unique(array_values($batch_lab_map)));
$selected_batches_normalized = array_values(array_unique(array_map(function ($batch_name) {
    return strtoupper(trim((string)$batch_name));
}, $selected_batches)));
$batch_lab_map_normalized = [];
foreach ($batch_lab_map as $batch_name => $lab_name) {
    $normalized_batch = strtoupper(trim((string)$batch_name));
    if ($normalized_batch !== '') {
        $batch_lab_map_normalized[$normalized_batch] = $lab_name;
    }
}

// Handle form submission
$attendance_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $date = $_POST['date'];
    $time = $_POST['slot'];
    $term = $_POST['term'];
    $faculty = $_POST['faculty'];
    $sem = $_POST['sem'];
    $subject = $_POST['subject'];
    $batch = $batch_csv;
    $labNo = $batch_lab_csv;
    $present = isset($_POST['present']) ? implode(",", $_POST['present']) : '';
    $totalPcUsedInput = $_POST['totalPcUsedByLab'] ?? [];
    $totalPcUsedMap = [];

    if (is_array($totalPcUsedInput)) {
        foreach ($totalPcUsedInput as $lab_name => $pc_used) {
            $lab_name = trim((string)$lab_name);
            if ($lab_name === '' || !in_array($lab_name, $selected_labs, true)) {
                continue;
            }
            $pc_used = max(0, (int)$pc_used);
            $totalPcUsedMap[$lab_name] = $pc_used;
        }
    }

    // Backward compatibility for old single-value form.
    if (empty($totalPcUsedMap) && count($selected_labs) === 1 && isset($_POST['totalPcUsed'])) {
        $totalPcUsedMap[$selected_labs[0]] = max(0, (int)$_POST['totalPcUsed']);
    }

    if (empty($selected_batches) || count($batch_lab_map) !== count($selected_batches)) {
        $attendance_error = 'Please select at least one batch and a lab number for every selected batch.';
    } elseif (empty($selected_labs) || count($totalPcUsedMap) !== count($selected_labs)) {
        $attendance_error = 'Please enter total PC used for every selected lab.';
    } else {
        $total_pc_pairs = [];
        foreach ($selected_labs as $lab_name) {
            $total_pc_pairs[] = $lab_name . ':' . $totalPcUsedMap[$lab_name];
        }
        $totalPcUsed = implode(', ', $total_pc_pairs);

        $stmt = $conn->prepare("INSERT INTO labattendance (date, logdate, time, term, faculty, sem, subject, batch, labNo, presentNo, totalPcUsed) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $date, $time, $term, $faculty, $sem, $subject, $batch, $labNo, $present, $totalPcUsed);
        $stmt->execute();
        $attendance_id = (int)$conn->insert_id;
        header("Location: attendanceSummary.php?type=lab&id=" . $attendance_id);
        exit();
    }
}

$faculty_id = $data['faculty'];
$faculty_query = "SELECT Name FROM faculty WHERE id = '$faculty_id'";
$faculty_result = $conn->query($faculty_query);
$faculty_name = ($faculty_result->num_rows > 0) ? $faculty_result->fetch_assoc()['Name'] : $faculty_id;

$students_result = false;
if (!empty($selected_batches_normalized)) {
    $escaped_term = $conn->real_escape_string($data['term']);
    $escaped_sem = $conn->real_escape_string($data['sem']);
    $escaped_batches = array_map(function ($batch_name) use ($conn) {
        return "'" . $conn->real_escape_string($batch_name) . "'";
    }, $selected_batches_normalized);

    $students_query = "SELECT id, enrollmentNo, name, TRIM(labBatch) AS labBatch FROM students WHERE term = '{$escaped_term}' AND sem = '{$escaped_sem}' AND UPPER(TRIM(labBatch)) IN (" . implode(',', $escaped_batches) . ") ORDER BY UPPER(TRIM(labBatch)), enrollmentNo, name";
    $students_result = $conn->query($students_query);
}

if (!$students_result) {
    $students_result = $conn->query("SELECT id, enrollmentNo, name, labBatch FROM students WHERE 1 = 0");
}

$missing_batches = [];
if (!empty($selected_batches_normalized)) {
    $escaped_term = $conn->real_escape_string($data['term']);
    $escaped_sem = $conn->real_escape_string($data['sem']);
    $escaped_batches = array_map(function ($batch_name) use ($conn) {
        return "'" . $conn->real_escape_string($batch_name) . "'";
    }, $selected_batches_normalized);

    $batch_count_query = "SELECT UPPER(TRIM(labBatch)) AS batch_name, COUNT(*) AS total FROM students WHERE term = '{$escaped_term}' AND sem = '{$escaped_sem}' AND UPPER(TRIM(labBatch)) IN (" . implode(',', $escaped_batches) . ") GROUP BY UPPER(TRIM(labBatch))";
    $batch_count_result = $conn->query($batch_count_query);
    $batch_counts = [];
    if ($batch_count_result) {
        while ($batch_count_row = $batch_count_result->fetch_assoc()) {
            $batch_counts[$batch_count_row['batch_name']] = (int)$batch_count_row['total'];
        }
    }

    foreach ($selected_batches_normalized as $batch_name) {
        if (!isset($batch_counts[$batch_name]) || $batch_counts[$batch_name] === 0) {
            $missing_batches[] = $batch_name;
        }
    }
}

// ── Autofill: today's related attendance records ──────────────────────────────
// Collect enrollment numbers of students in the selected batches
$batch_enrollments = [];
if ($students_result && $students_result->num_rows > 0) {
    $students_result->data_seek(0);
    while ($s = $students_result->fetch_assoc()) {
        if (!empty($s['enrollmentNo'])) $batch_enrollments[] = $s['enrollmentNo'];
    }
    $students_result->data_seek(0);
}

$autofill_records = [];
if (!empty($batch_enrollments) && !empty($escaped_term)) {
    $att_date_esc = $conn->real_escape_string($data['date']);

    // Lecture records today (same term, sem, date) — filter to students in selected batches
    $lec_res = $conn->query("SELECT id, subject, class, time, presentNo FROM lecattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' ORDER BY id DESC");
    if ($lec_res) {
        while ($row = $lec_res->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $batch_enrollments));
            if (!empty($filt)) {
                $autofill_records[] = ['type' => 'Lecture', 'label' => 'Lecture · ' . $row['subject'] . ' · Class ' . $row['class'] . ' · ' . $row['time'], 'present' => $filt];
            }
        }
    }

    // Other lab records today (same term, sem, date) — filter to students in selected batches
    $lab_res = $conn->query("SELECT id, subject, batch, presentNo FROM labattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' AND labNo IS NOT NULL AND labNo!='' ORDER BY id DESC");
    if ($lab_res) {
        while ($row = $lab_res->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $batch_enrollments));
            if (!empty($filt)) {
                $autofill_records[] = ['type' => 'Lab', 'label' => 'Lab · ' . $row['subject'] . ' · Batch ' . $row['batch'], 'present' => $filt];
            }
        }
    }

    // Tutorial records today — from tutattendance table
    $tut_res = $conn->query("SELECT id, subject, batch, presentNo FROM tutattendance WHERE term='{$escaped_term}' AND sem='{$escaped_sem}' AND date='{$att_date_esc}' ORDER BY id DESC");
    if ($tut_res) {
        while ($row = $tut_res->fetch_assoc()) {
            $all  = array_filter(array_map('trim', explode(',', (string)$row['presentNo'])));
            $filt = array_values(array_intersect($all, $batch_enrollments));
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
            <h1 class="app-page-title">Take Lab Attendance</h1>

            <?php if ($attendance_error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($attendance_error); ?></div>
            <?php endif; ?>
            <?php if (!empty($missing_batches)): ?>
                <div class="alert alert-warning">
                    No students found for selected batch(es): <?= htmlspecialchars(implode(', ', $missing_batches)); ?>.
                </div>
            <?php endif; ?>

            <div class="app-card app-card-body shadow-sm mb-4">
                <h4>Lab Details</h4>
                <p><strong>Faculty:</strong> <?= htmlspecialchars($faculty_name) ?></p>
                <p><strong>Term:</strong> <?= htmlspecialchars($data['term']) ?></p>
                <p><strong>Semester:</strong> <?= htmlspecialchars($data['sem']) ?></p>
                <p><strong>Subject:</strong> <?= htmlspecialchars($data['subject']) ?></p>
                <p><strong>Batches:</strong> <?= htmlspecialchars(!empty($selected_batches) ? implode(', ', $selected_batches) : '-') ?></p>
                <p><strong>Lab No (Batch-wise):</strong> <?= htmlspecialchars($batch_lab_csv !== '' ? $batch_lab_csv : '-') ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($data['date']) ?></p>
                <p><strong>Slot:</strong> <?= htmlspecialchars($data['slot']) ?></p>
            </div>

            <!-- Autofill Panel -->
            <?php if (!empty($autofill_records)): ?>
            <div class="app-card app-card-body shadow-sm mb-4">
                <h5 class="mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Today's Attendance — Click to Autofill</h5>
                <p class="text-muted mb-2" style="font-size:0.82rem;">Only students in batch(es) <strong><?= htmlspecialchars(implode(', ', $selected_batches)) ?></strong> will be marked.</p>
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

            <form method="POST" action="takelabatt.php">
                <?php foreach ($data as $key => $value): ?>
                    <?php render_hidden_inputs($key, $value); ?>
                <?php endforeach; ?>

                <div class="app-card app-card-body shadow-sm">
                    <h4>Mark Attendance</h4>
                    <?php if ($students_result->num_rows > 0): ?>
                        <div class="row" id="student-cards">
                            <?php
                            while ($student = $students_result->fetch_assoc()):
                                $student_roll = !empty($student['enrollmentNo']) ? $student['enrollmentNo'] : $student['id'];
                                $student_batch = strtoupper(trim((string)$student['labBatch']));
                                $student_lab = $batch_lab_map_normalized[$student_batch] ?? '';
                                $full_name = trim((string)$student['name']);
                                $name_parts = preg_split('/\s+/', $full_name);
                                if (count($name_parts) >= 2) {
                                    $display_name = $name_parts[0] . ' ' . $name_parts[1];
                                } else {
                                    $display_name = $full_name;
                                }
                            ?>
                                <div class="col-md-3 mb-3">
                                    <label class="card shadow-sm p-2 text-center student-card" style="cursor: pointer;">
                                        <input type="checkbox" name="present[]" value="<?= htmlspecialchars((string)$student_roll); ?>" class="d-none attendance-checkbox" data-lab="<?= htmlspecialchars($student_lab); ?>">
                                        <div class="student-info">
                                            <strong><?= htmlspecialchars((string)$student_roll); ?></strong><br>
                                            <?= htmlspecialchars($display_name); ?>
                                            <span class="d-block text-muted"><?= htmlspecialchars($student_batch . ', ' . ($student_lab !== '' ? $student_lab : '-')); ?></span>
                                        </div>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="row g-3 mt-3" id="pc-used-container">
                            <?php foreach ($selected_labs as $lab_name): ?>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Total PC Used in Lab <?= htmlspecialchars($lab_name); ?></label>
                                    <input type="number" name="totalPcUsedByLab[<?= htmlspecialchars($lab_name); ?>]" class="form-control pc-used-input" data-lab="<?= htmlspecialchars($lab_name); ?>" value="0" min="0" required>
                                    <small class="text-muted">Default = half of marked present students in this lab.</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" name="submit_attendance" class="btn btn-success w-100 mt-3">✅ Submit Attendance</button>
                    <?php else: ?>
                        <p>No students found for the selected criteria.</p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const cards = document.querySelectorAll('.student-card');
    const attendanceCheckboxes = document.querySelectorAll('.attendance-checkbox');
    const pcUsedInputs = document.querySelectorAll('.pc-used-input');

    function updatePcDefaultsByLab() {
        const presentCountByLab = {};

        attendanceCheckboxes.forEach(function (checkbox) {
            if (!checkbox.checked) return;
            const labName = checkbox.dataset.lab || '';
            if (!labName) return;
            presentCountByLab[labName] = (presentCountByLab[labName] || 0) + 1;
        });

        pcUsedInputs.forEach(function (input) {
            const labName = input.dataset.lab || '';
            const presentCount = presentCountByLab[labName] || 0;
            input.value = Math.ceil(presentCount / 2);
        });
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            const checkbox = card.querySelector('.attendance-checkbox');
            checkbox.checked = !checkbox.checked;
            card.classList.toggle('bg-success-subtle', checkbox.checked);
            card.classList.toggle('border-success', checkbox.checked);
            updatePcDefaultsByLab();
        });
    });

    updatePcDefaultsByLab();

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
            updatePcDefaultsByLab();
        });
    });
</script>

<?php include('footer.php'); ?>
</body>
</html>

<?php $conn->close(); ?>
