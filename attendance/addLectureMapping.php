<?php
include('dbconfig.php');

$session_faculty_name = $_SESSION['Name'] ?? '';
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$is_embedded = isset($_GET['embedded']) && $_GET['embedded'] === '1';
$base_self_url = 'addLectureMapping.php' . ($is_embedded ? '?embedded=1' : '');
$default_date = date('Y-m-d');
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

$conn->query("CREATE TABLE IF NOT EXISTS `lecmapping` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `faculty`     VARCHAR(50)  NOT NULL,
    `term`        VARCHAR(20)  NOT NULL,
    `sem`         VARCHAR(10)  NOT NULL,
    `subject`     VARCHAR(100) NOT NULL,
    `class`       VARCHAR(5)   NOT NULL,
    `slot`        VARCHAR(50)  NOT NULL,
    `start_date`  DATE         NOT NULL,
    `end_date`    DATE         NOT NULL,
    `repeat_days` VARCHAR(20)  NOT NULL COMMENT '0=Sun,1=Mon,...,6=Sat comma-separated',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function normalize_repeat_days($input) {
    $days = array_values(array_unique(array_filter(
        array_map('intval', (array)$input),
        static fn($d) => $d >= 0 && $d <= 6
    )));
    sort($days);
    return $days;
}

function parse_repeat_days_csv($csv) {
    return normalize_repeat_days(explode(',', (string)$csv));
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mapping'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM lecmapping WHERE id = ? AND faculty = ?");
        $stmt->bind_param('is', $del_id, $logged_faculty_id);
        $stmt->execute();
        $stmt->close();
        $success_msg = 'Lecture mapping deleted.';
        if ($edit_id === $del_id) {
            $edit_id = 0;
        }
    }
}

$faculty_rows = [];
$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1 ORDER BY Name");
if ($faculty_result) {
    while ($row = $faculty_result->fetch_assoc()) {
        $faculty_rows[] = $row;
    }
}

$sem_rows = [];
$sem_result = $conn->query("SELECT sem FROM semester WHERE status = 1 ORDER BY sem");
if ($sem_result) {
    while ($row = $sem_result->fetch_assoc()) {
        $sem_rows[] = $row;
    }
}

$subjects = [];
$subject_result = $conn->query("SELECT subjectName, subjectCode, sem FROM subjects WHERE status = 1 ORDER BY sem, subjectName");
if ($subject_result) {
    while ($row = $subject_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

$slot_rows = [];
$slot_result = $conn->query("SELECT timeslot FROM timeslot WHERE status = 1 ORDER BY sequence");
if ($slot_result) {
    while ($row = $slot_result->fetch_assoc()) {
        $slot_rows[] = $row;
    }
}

$term_rows = [];
$term_result = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
if ($term_result) {
    while ($row = $term_result->fetch_assoc()) {
        $term_rows[] = (string)$row['term'];
    }
}
$default_term = $term_rows[0] ?? '';

$fac_id_stmt = $conn->prepare("SELECT id FROM faculty WHERE Name = ?");
$fac_id_stmt->bind_param('s', $session_faculty_name);
$fac_id_stmt->execute();
$fac_row = $fac_id_stmt->get_result()->fetch_assoc();
$fac_id_stmt->close();
$logged_faculty_id = $fac_row ? (string)$fac_row['id'] : '';

$form_defaults = [
    'faculty' => $logged_faculty_id,
    'term' => $default_term,
    'sem' => '',
    'subject' => '',
    'class' => '',
    'slot' => '',
    'start_date' => $default_date,
    'end_date' => $default_date,
    'repeat_days' => [],
];
$form_values = $form_defaults;

if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM lecmapping WHERE id = ? AND faculty = ?");
    $stmt->bind_param('is', $edit_id, $logged_faculty_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $form_values = [
            'faculty' => (string)$row['faculty'],
            'term' => (string)$row['term'],
            'sem' => (string)$row['sem'],
            'subject' => (string)$row['subject'],
            'class' => (string)$row['class'],
            'slot' => (string)$row['slot'],
            'start_date' => (string)$row['start_date'],
            'end_date' => (string)$row['end_date'],
            'repeat_days' => parse_repeat_days_csv($row['repeat_days']),
        ];
    } else {
        $error_msg = 'Selected lecture mapping not found.';
        $edit_id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mapping'])) {
    $mapping_id = (int)($_POST['mapping_id'] ?? 0);
    if ($mapping_id > 0) {
        $edit_id = $mapping_id;
    }

    $faculty = trim((string)($_POST['faculty'] ?? ''));
    $term = trim((string)($_POST['term'] ?? ''));
    $sem = trim((string)($_POST['sem'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $class = trim((string)($_POST['class'] ?? ''));
    $slot = trim((string)($_POST['slot'] ?? ''));
    $start_date = trim((string)($_POST['start_date'] ?? ''));
    $end_date = trim((string)($_POST['end_date'] ?? ''));
    $repeat_days = normalize_repeat_days($_POST['repeat_days'] ?? []);

    $form_values = [
        'faculty' => $faculty,
        'term' => $term,
        'sem' => $sem,
        'subject' => $subject,
        'class' => $class,
        'slot' => $slot,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'repeat_days' => $repeat_days,
    ];

    if ($faculty === '' || $term === '' || $sem === '' || $subject === '' || $class === '' || $slot === '' || $start_date === '' || $end_date === '' || empty($repeat_days)) {
        $error_msg = 'All fields are required including at least one repeat day.';
    } elseif ($end_date < $start_date) {
        $error_msg = 'End date must be on or after start date.';
    } else {
        $repeat_days_csv = implode(',', $repeat_days);
        if ($mapping_id > 0) {
            $stmt = $conn->prepare("UPDATE lecmapping SET faculty = ?, term = ?, sem = ?, subject = ?, class = ?, slot = ?, start_date = ?, end_date = ?, repeat_days = ? WHERE id = ? AND faculty = ?");
            $stmt->bind_param('sssssssssis', $faculty, $term, $sem, $subject, $class, $slot, $start_date, $end_date, $repeat_days_csv, $mapping_id, $logged_faculty_id);
            if ($stmt->execute()) {
                $success_msg = 'Lecture mapping updated successfully.';
            } else {
                $error_msg = 'Failed to update lecture mapping.';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO lecmapping (faculty, term, sem, subject, class, slot, start_date, end_date, repeat_days) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssss', $faculty, $term, $sem, $subject, $class, $slot, $start_date, $end_date, $repeat_days_csv);
            if ($stmt->execute()) {
                $success_msg = 'Lecture mapping saved successfully.';
                $form_values = $form_defaults;
                $edit_id = 0;
            } else {
                $error_msg = 'Failed to save lecture mapping.';
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

$mappings = [];
$res_stmt = $conn->prepare("SELECT m.*, f.Name AS faculty_name FROM lecmapping m LEFT JOIN faculty f ON f.id = m.faculty WHERE m.faculty = ? ORDER BY m.start_date DESC, m.id DESC");
$res_stmt->bind_param('s', $logged_faculty_id);
$res_stmt->execute();
$res = $res_stmt->get_result();
$res_stmt->close();
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $mappings[] = $row;
    }
}
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
            <h1 class="app-page-title"><i class="bi bi-calendar-week me-2"></i>Lecture Mapping</h1>
            <?php if ($success_msg !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div><?php endif; ?>
            <?php if ($error_msg !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="app-card shadow-sm"><div class="app-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><?= $is_edit_mode ? 'Edit Lecture Mapping' : 'New Lecture Mapping' ?></h4>
                            <?php if ($is_edit_mode): ?><a href="<?= htmlspecialchars($base_self_url) ?>" class="btn btn-sm btn-outline-secondary">Cancel Edit</a><?php endif; ?>
                        </div>
                        <form method="POST" action="<?= htmlspecialchars($form_action) ?>">
                            <input type="hidden" name="mapping_id" value="<?= $is_edit_mode ? (int)$edit_id : 0 ?>">
                            <div class="mb-3"><label class="form-label">Faculty Name</label><select name="faculty" class="form-control" required><option value="">Select Faculty</option><?php foreach ($faculty_rows as $f): ?><option value="<?= htmlspecialchars((string)$f['id']) ?>" <?= ((string)$f['id'] === (string)$form_values['faculty']) ? 'selected' : '' ?>><?= htmlspecialchars($f['Name']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Term</label><select name="term" class="form-control" required><option value="">Select Term</option><?php foreach ($term_rows as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= ((string)$t === (string)$form_values['term']) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Semester</label><select name="sem" class="form-control" id="semSelect" required><option value="">Select Semester</option><?php foreach ($sem_rows as $s): ?><option value="<?= htmlspecialchars((string)$s['sem']) ?>" <?= ((string)$s['sem'] === (string)$form_values['sem']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$s['sem']) ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Subject</label><select name="subject" class="form-control" id="subjectSelect" required><option value="">Select Semester first</option></select></div>
                            <div class="mb-3"><label class="form-label">Class</label><select name="class" class="form-control" required><option value="">Select Class</option><?php foreach (['A','B','C','D'] as $c): ?><option value="<?= $c ?>" <?= ($c === (string)$form_values['class']) ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
                            <div class="mb-3"><label class="form-label">Slot</label><select name="slot" class="form-control" required><option value="">Select Slot</option><?php foreach ($slot_rows as $sl): $sn=(string)$sl['timeslot']; ?><option value="<?= htmlspecialchars($sn) ?>" <?= ($sn === (string)$form_values['slot']) ? 'selected' : '' ?>><?= htmlspecialchars($sn) ?></option><?php endforeach; ?></select></div>
                            <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Repeat Start Date</label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string)$form_values['start_date']) ?>" required></div><div class="col-6"><label class="form-label">Repeat End Date</label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string)$form_values['end_date']) ?>" required></div></div>
                            <div class="mb-4"><label class="form-label">Repeat on Day(s)</label><div class="d-flex flex-wrap gap-2"><?php foreach ([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',0=>'Sun'] as $num=>$name): $checked=in_array((int)$num,(array)$form_values['repeat_days'],true); ?><label class="btn <?= $checked ? 'btn-primary':'btn-outline-primary' ?> btn-sm px-3 day-toggle" style="cursor:pointer;"><input type="checkbox" name="repeat_days[]" value="<?= $num ?>" class="d-none" <?= $checked ? 'checked':'' ?>><?= $name ?></label><?php endforeach; ?></div></div>
                            <button type="submit" name="save_mapping" class="btn btn-primary w-100"><?= $is_edit_mode ? 'Update Lecture Mapping' : 'Save Lecture Mapping' ?></button>
                        </form>
                    </div></div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="app-card shadow-sm"><div class="app-card-body">
                        <h4 class="mb-3">Existing Lecture Mappings</h4>
                        <?php if (!empty($mappings)): ?>
                        <div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0" style="font-size:0.82rem;"><thead class="table-light"><tr><th>Faculty</th><th>Term / Sem</th><th>Subject</th><th>Class</th><th>Slot</th><th>Period</th><th>Days</th><th></th></tr></thead><tbody>
                        <?php foreach ($mappings as $m): $days = parse_repeat_days_csv($m['repeat_days']); $days_text = implode(', ', array_map(fn($d)=>$day_names[$d] ?? (string)$d, $days)); ?>
                        <tr class="<?= ($is_edit_mode && $edit_id === (int)$m['id']) ? 'table-warning' : '' ?>"><td><?= htmlspecialchars($m['faculty_name'] ?? $m['faculty']) ?></td><td><?= htmlspecialchars($m['term']) ?><br><small class="text-muted">Sem <?= htmlspecialchars($m['sem']) ?></small></td><td><?= htmlspecialchars($m['subject']) ?></td><td><span class="badge bg-primary-subtle text-dark border"><?= htmlspecialchars($m['class']) ?></span></td><td><?= htmlspecialchars($m['slot']) ?></td><td style="white-space:nowrap;"><?= htmlspecialchars($m['start_date']) ?><br><?= htmlspecialchars($m['end_date']) ?></td><td><?= htmlspecialchars($days_text) ?></td><td><div class="d-flex gap-1"><a href="addLectureMapping.php?edit_id=<?= (int)$m['id'] ?><?= $is_embedded ? '&embedded=1' : '' ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i></a><form method="POST" action="<?= htmlspecialchars($base_self_url) ?>" onsubmit="return confirm('Delete this lecture mapping?')"><input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>"><button type="submit" name="delete_mapping" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button></form></div></td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                        <?php else: ?><div class="alert alert-info mb-0">No lecture mappings yet.</div><?php endif; ?>
                    </div></div>
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
    if (!sem) { subjectSelect.innerHTML = '<option value="">Select Semester first</option>'; return; }
    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
    allSubjects.filter(s => String(s.sem) === String(sem)).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.subjectName;
        opt.textContent = s.subjectName + (s.subjectCode ? ' (' + s.subjectCode + ')' : '');
        if (String(s.subjectName) === String(selectedValue)) opt.selected = true;
        subjectSelect.appendChild(opt);
    });
}
semSelect.addEventListener('change', function () { populateSubjects(this.value); });
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
