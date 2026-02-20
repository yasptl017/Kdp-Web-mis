<?php
include('dbconfig.php');

$session_faculty_name = $_SESSION['Name'] ?? '';
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$is_embedded = isset($_GET['embedded']) && $_GET['embedded'] === '1';
$base_self_url = 'addTutMapping.php' . ($is_embedded ? '?embedded=1' : '');
$default_date = date('Y-m-d');

// Auto-create table if missing
$conn->query("CREATE TABLE IF NOT EXISTS `tutmapping` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `faculty`     VARCHAR(50)  NOT NULL,
    `term`        VARCHAR(20)  NOT NULL,
    `sem`         VARCHAR(10)  NOT NULL,
    `subject`     VARCHAR(100) NOT NULL,
    `tutBatch`    VARCHAR(30)  NOT NULL,
    `slot`        VARCHAR(50)  NOT NULL,
    `start_date`  DATE         NOT NULL,
    `end_date`    DATE         NOT NULL,
    `repeat_days` VARCHAR(20)  NOT NULL COMMENT '0=Sun,1=Mon,...,6=Sat comma-separated',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$success_msg = '';
$error_msg = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mapping'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM tutmapping WHERE id = ? AND faculty = ?");
        $stmt->bind_param('is', $del_id, $logged_faculty_id);
        $stmt->execute();
        $stmt->close();
        $success_msg = 'Tutorial mapping deleted.';

        if ($edit_id === $del_id) {
            $edit_id = 0;
        }
    }
}

// Load data for form
$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1 ORDER BY Name");
$sem_result = $conn->query("SELECT sem FROM semester WHERE status = 1 ORDER BY sem");
$subject_result = $conn->query("SELECT subjectName, subjectCode, sem FROM subjects WHERE status = 1 ORDER BY sem, subjectName");
$slot_result = $conn->query("SELECT timeslot FROM timeslot WHERE status = 1 ORDER BY sequence");
$tut_batch_result = $conn->query("SELECT DISTINCT TRIM(tutBatch) AS tutBatch FROM students WHERE tutBatch IS NOT NULL AND TRIM(tutBatch) <> '' ORDER BY tutBatch");

$subjects = [];
while ($row = $subject_result->fetch_assoc()) {
    $subjects[] = $row;
}

$term_rows = [];
$term_res = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
while ($tr = $term_res->fetch_assoc()) {
    $term_rows[] = $tr['term'];
}
$default_term = $term_rows[0] ?? '';

// Get logged in faculty id
$fac_id_stmt = $conn->prepare("SELECT id FROM faculty WHERE Name = ?");
$fac_id_stmt->bind_param('s', $session_faculty_name);
$fac_id_stmt->execute();
$fac_row = $fac_id_stmt->get_result()->fetch_assoc();
$fac_id_stmt->close();
$logged_faculty_id = $fac_row ? (string)$fac_row['id'] : '';

// Load selected mapping for edit mode
$editing_mapping = null;
if ($edit_id > 0) {
    $edit_stmt = $conn->prepare("SELECT * FROM tutmapping WHERE id = ? AND faculty = ?");
    $edit_stmt->bind_param('is', $edit_id, $logged_faculty_id);
    $edit_stmt->execute();
    $editing_mapping = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();

    if (!$editing_mapping) {
        $error_msg = 'Selected mapping not found.';
        $edit_id = 0;
    }
}

$form_defaults = [
    'faculty' => $logged_faculty_id,
    'term' => $default_term,
    'sem' => '',
    'subject' => '',
    'tutBatch' => '',
    'slot' => '',
    'start_date' => $default_date,
    'end_date' => $default_date,
    'repeat_days' => [],
];

$form_values = $form_defaults;
if ($editing_mapping) {
    $form_values = [
        'faculty' => (string)$editing_mapping['faculty'],
        'term' => (string)$editing_mapping['term'],
        'sem' => (string)$editing_mapping['sem'],
        'subject' => (string)$editing_mapping['subject'],
        'tutBatch' => (string)$editing_mapping['tutBatch'],
        'slot' => (string)$editing_mapping['slot'],
        'start_date' => (string)$editing_mapping['start_date'],
        'end_date' => (string)$editing_mapping['end_date'],
        'repeat_days' => array_values(array_unique(array_filter(
            array_map('intval', explode(',', (string)$editing_mapping['repeat_days'])),
            static fn($day) => $day >= 0 && $day <= 6
        ))),
    ];
}

// Handle save (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mapping'])) {
    $mapping_id  = (int)($_POST['mapping_id'] ?? 0);
    $faculty     = trim((string)($_POST['faculty'] ?? ''));
    $term        = trim((string)($_POST['term'] ?? ''));
    $sem         = trim((string)($_POST['sem'] ?? ''));
    $subject     = trim((string)($_POST['subject'] ?? ''));
    $tutBatch    = trim((string)($_POST['tutBatch'] ?? ''));
    $slot        = trim((string)($_POST['slot'] ?? ''));
    $start_date  = trim((string)($_POST['start_date'] ?? ''));
    $end_date    = trim((string)($_POST['end_date'] ?? ''));
    $repeat_days = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['repeat_days'] ?? [])),
        static fn($day) => $day >= 0 && $day <= 6
    )));

    $form_values = [
        'faculty' => $faculty,
        'term' => $term,
        'sem' => $sem,
        'subject' => $subject,
        'tutBatch' => $tutBatch,
        'slot' => $slot,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'repeat_days' => $repeat_days,
    ];

    if ($faculty === '' || $term === '' || $sem === '' || $subject === '' || $tutBatch === '' || $slot === '' || $start_date === '' || $end_date === '' || empty($repeat_days)) {
        $error_msg = 'All fields are required including at least one repeat day.';
    } elseif ($end_date < $start_date) {
        $error_msg = 'End date must be on or after start date.';
    } else {
        $repeat_days_csv = implode(',', $repeat_days);

        if ($mapping_id > 0) {
            $check_stmt = $conn->prepare("SELECT id FROM tutmapping WHERE id = ? AND faculty = ?");
            $check_stmt->bind_param('is', $mapping_id, $logged_faculty_id);
            $check_stmt->execute();
            $mapping_exists = (bool)$check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if (!$mapping_exists) {
                $error_msg = 'Mapping not found for update.';
            } else {
                $stmt = $conn->prepare("UPDATE tutmapping SET faculty = ?, term = ?, sem = ?, subject = ?, tutBatch = ?, slot = ?, start_date = ?, end_date = ?, repeat_days = ? WHERE id = ? AND faculty = ?");
                $stmt->bind_param('sssssssssis', $faculty, $term, $sem, $subject, $tutBatch, $slot, $start_date, $end_date, $repeat_days_csv, $mapping_id, $logged_faculty_id);
                if ($stmt->execute()) {
                    $success_msg = 'Tutorial mapping updated successfully.';
                    $edit_id = $mapping_id;
                } else {
                    $error_msg = 'Failed to update tutorial mapping.';
                }
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO tutmapping (faculty, term, sem, subject, tutBatch, slot, start_date, end_date, repeat_days) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssss', $faculty, $term, $sem, $subject, $tutBatch, $slot, $start_date, $end_date, $repeat_days_csv);
            if ($stmt->execute()) {
                $success_msg = 'Tutorial mapping saved successfully.';
                $form_values = $form_defaults;
            } else {
                $error_msg = 'Failed to save tutorial mapping.';
            }
            $stmt->close();
        }
    }
}

$is_edit_mode = $edit_id > 0;
$form_action = $base_self_url;
if ($is_edit_mode) {
    $form_action .= ($is_embedded ? '&' : '?') . 'edit_id=' . $edit_id;
}

$mappings_stmt = $conn->prepare("SELECT m.*, f.Name AS faculty_name FROM tutmapping m LEFT JOIN faculty f ON f.id = m.faculty WHERE m.faculty = ? ORDER BY m.start_date DESC, m.id DESC");
$mappings_stmt->bind_param('s', $logged_faculty_id);
$mappings_stmt->execute();
$mappings_result = $mappings_stmt->get_result();
$mappings_stmt->close();
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<?php if ($is_embedded): ?>
<style>
    .app-header,
    #app-sidepanel,
    .app-footer {
        display: none !important;
    }
    .app-wrapper {
        margin-left: 0 !important;
        padding-top: 0 !important;
    }
    .app-content {
        padding-top: 0 !important;
    }
    .app-content .container-xl {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .app-content .row.g-4 > .col-12.col-lg-5,
    .app-content .row.g-4 > .col-12.col-lg-7 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
</style>
<?php endif; ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-journal-check me-2"></i>Tutorial Mapping</h1>

            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success_msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error_msg !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><?= $is_edit_mode ? 'Edit Tutorial Mapping' : 'New Tutorial Mapping' ?></h4>
                                <?php if ($is_edit_mode): ?>
                                    <a href="<?= htmlspecialchars($base_self_url) ?>" class="btn btn-sm btn-outline-secondary">Cancel Edit</a>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="<?= htmlspecialchars($form_action) ?>">
                                <input type="hidden" name="mapping_id" value="<?= $is_edit_mode ? (int)$edit_id : 0 ?>">

                                <div class="mb-3">
                                    <label class="form-label">Faculty Name</label>
                                    <select name="faculty" class="form-control" required>
                                        <option value="">Select Faculty</option>
                                        <?php if ($faculty_result): ?>
                                            <?php $faculty_result->data_seek(0); while ($fac = $faculty_result->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars((string)$fac['id']) ?>" <?= ((string)$fac['id'] === (string)$form_values['faculty']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($fac['Name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <select name="term" class="form-control" required>
                                        <option value="">Select Term</option>
                                        <?php foreach ($term_rows as $tr): ?>
                                            <option value="<?= htmlspecialchars($tr) ?>" <?= ((string)$tr === (string)$form_values['term']) ? 'selected' : '' ?>><?= htmlspecialchars($tr) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Semester</label>
                                    <select name="sem" class="form-control" id="semSelect" required>
                                        <option value="">Select Semester</option>
                                        <?php if ($sem_result): ?>
                                            <?php while ($sem = $sem_result->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars((string)$sem['sem']) ?>" <?= ((string)$sem['sem'] === (string)$form_values['sem']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string)$sem['sem']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <select name="subject" class="form-control" id="subjectSelect" required>
                                        <option value="">Select Semester first</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tutorial Batch</label>
                                    <select name="tutBatch" class="form-control" required>
                                        <option value="">Select Tutorial Batch</option>
                                        <?php if ($tut_batch_result): ?>
                                            <?php while ($tut = $tut_batch_result->fetch_assoc()): ?>
                                                <?php $tut_batch = (string)$tut['tutBatch']; ?>
                                                <option value="<?= htmlspecialchars($tut_batch) ?>" <?= ($tut_batch === (string)$form_values['tutBatch']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tut_batch) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slot</label>
                                    <select name="slot" class="form-control" required>
                                        <option value="">Select Slot</option>
                                        <?php if ($slot_result): ?>
                                            <?php while ($slot = $slot_result->fetch_assoc()): ?>
                                                <?php $slot_name = (string)$slot['timeslot']; ?>
                                                <option value="<?= htmlspecialchars($slot_name) ?>" <?= ($slot_name === (string)$form_values['slot']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($slot_name) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Repeat Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string)$form_values['start_date']) ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Repeat End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string)$form_values['end_date']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Repeat on Day(s)</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $num => $name): ?>
                                            <?php $checked = in_array((int)$num, (array)$form_values['repeat_days'], true); ?>
                                            <label class="btn <?= $checked ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm px-3 day-toggle" style="cursor:pointer;">
                                                <input type="checkbox" name="repeat_days[]" value="<?= $num ?>" class="d-none" <?= $checked ? 'checked' : '' ?>>
                                                <?= $name ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">Select all days this tutorial repeats weekly.</small>
                                </div>

                                <button type="submit" name="save_mapping" class="btn btn-primary w-100">
                                    <i class="bi <?= $is_edit_mode ? 'bi-check2-circle' : 'bi-plus-circle' ?> me-1"></i><?= $is_edit_mode ? 'Update Tutorial Mapping' : 'Save Tutorial Mapping' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4 class="mb-3">Existing Tutorial Mappings</h4>
                            <?php if ($mappings_result && $mappings_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.82rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Faculty</th>
                                                <th>Term / Sem</th>
                                                <th>Subject</th>
                                                <th>Tut Batch</th>
                                                <th>Slot</th>
                                                <th>Period</th>
                                                <th>Days</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($m = $mappings_result->fetch_assoc()): ?>
                                            <?php
                                                $days_arr = array_values(array_unique(array_filter(
                                                    array_map('intval', explode(',', (string)$m['repeat_days'])),
                                                    static fn($day) => $day >= 0 && $day <= 6
                                                )));
                                                $days_str = implode(', ', array_map(static fn($day) => $day_names[$day] ?? (string)$day, $days_arr));
                                            ?>
                                            <tr class="<?= ($is_edit_mode && $edit_id === (int)$m['id']) ? 'table-warning' : '' ?>">
                                                <td><?= htmlspecialchars($m['faculty_name'] ?? $m['faculty']) ?></td>
                                                <td><?= htmlspecialchars($m['term']) ?><br><small class="text-muted">Sem <?= htmlspecialchars($m['sem']) ?></small></td>
                                                <td><?= htmlspecialchars($m['subject']) ?></td>
                                                <td><span class="badge bg-primary-subtle text-dark border"><?= htmlspecialchars($m['tutBatch']) ?></span></td>
                                                <td><?= htmlspecialchars($m['slot']) ?></td>
                                                <td style="white-space:nowrap;"><?= htmlspecialchars($m['start_date']) ?><br><?= htmlspecialchars($m['end_date']) ?></td>
                                                <td><?= htmlspecialchars($days_str) ?></td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="addTutMapping.php?edit_id=<?= (int)$m['id'] ?><?= $is_embedded ? '&embedded=1' : '' ?>" class="btn btn-outline-warning btn-sm" title="Edit tutorial mapping">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form method="POST" action="<?= htmlspecialchars($base_self_url) ?>" onsubmit="return confirm('Delete this tutorial mapping?')">
                                                            <input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>">
                                                            <button type="submit" name="delete_mapping" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-1"></i>No tutorial mappings yet. Create one on the left.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const allSubjects = <?= json_encode($subjects) ?>;
    const semSelect = document.getElementById('semSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const selectedSem = <?= json_encode((string)$form_values['sem']) ?>;
    const selectedSubject = <?= json_encode((string)$form_values['subject']) ?>;

    function populateSubjects(sem, selectedValue = '') {
        if (!sem) {
            subjectSelect.innerHTML = '<option value="">Select Semester first</option>';
            return;
        }

        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        allSubjects
            .filter(s => String(s.sem) === String(sem))
            .forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.subjectName;
                opt.textContent = s.subjectName + (s.subjectCode ? ' (' + s.subjectCode + ')' : '');
                if (String(s.subjectName) === String(selectedValue)) {
                    opt.selected = true;
                }
                subjectSelect.appendChild(opt);
            });
    }

    semSelect.addEventListener('change', function () {
        populateSubjects(this.value);
    });

    populateSubjects(selectedSem || semSelect.value, selectedSubject);

    document.querySelectorAll('.day-toggle').forEach(function (label) {
        const cb = label.querySelector('input[type=checkbox]');

        function syncState() {
            label.classList.toggle('btn-primary', cb.checked);
            label.classList.toggle('btn-outline-primary', !cb.checked);
        }

        syncState();
        cb.addEventListener('change', syncState);
    });
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
