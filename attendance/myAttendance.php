<?php
include('dbconfig.php');

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

function compare_terms_desc($left, $right) {
    return strnatcmp((string)$right, (string)$left);
}

// ── Auto-create lecmapping table if missing ───────────────────────────────────
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
    `repeat_days` VARCHAR(20)  NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Auto-create exceptions table (holiday/skip slots) ─────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `lecmapping_exceptions` (
    `id`         INT  NOT NULL AUTO_INCREMENT,
    `mapping_id` INT  NOT NULL,
    `date`       DATE NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mapping_date` (`mapping_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$session_faculty_name = $_SESSION['Name'] ?? '';

// Get logged-in faculty id
$fac_id_stmt = $conn->prepare("SELECT id FROM faculty WHERE Name = ?");
$fac_id_stmt->bind_param('s', $session_faculty_name);
$fac_id_stmt->execute();
$fac_row = $fac_id_stmt->get_result()->fetch_assoc();
$fac_id_stmt->close();
$logged_faculty_id = $fac_row ? (string)$fac_row['id'] : '0';

$success_msg = trim((string)($_GET['msg'] ?? ''));
$error_msg = trim((string)($_GET['err'] ?? ''));

// ── Filters from GET ──────────────────────────────────────────────────────────
$filter_term    = trim((string)($_GET['term'] ?? ''));
$filter_status  = $_GET['status']  ?? 'unfilled';   // all | filled | unfilled | skipped
$filter_mapping = (int)($_GET['mapping'] ?? 0); // specific mapping id, 0 = all

// ── Load all mappings for this faculty ───────────────────────────────────────
$mappings_stmt = $conn->prepare("SELECT * FROM lecmapping WHERE faculty = ? ORDER BY start_date, id");
$mappings_stmt->bind_param('s', $logged_faculty_id);
$mappings_stmt->execute();
$mappings_rows = $mappings_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$mappings_stmt->close();

$available_terms = [];
foreach ($mappings_rows as $mapping_row) {
    $term_value = trim((string)($mapping_row['term'] ?? ''));
    if ($term_value !== '') {
        $available_terms[$term_value] = true;
    }
}
$available_terms = array_keys($available_terms);
usort($available_terms, 'compare_terms_desc');

if ($filter_term === '') {
    header('Location: myAttendanceSelect.php');
    exit();
}

// ── Load exceptions for this faculty's mappings ───────────────────────────────
$exceptions_set = []; // "mapping_id|date" => true
if (!empty($mappings_rows)) {
    $mapping_ids = array_column($mappings_rows, 'id');
    $exc_placeholders = implode(',', array_fill(0, count($mapping_ids), '?'));
    $exc_types = str_repeat('i', count($mapping_ids));
    $exc_stmt = $conn->prepare("SELECT mapping_id, date FROM lecmapping_exceptions WHERE mapping_id IN ($exc_placeholders)");
    $exc_stmt->bind_param($exc_types, ...$mapping_ids);
    $exc_stmt->execute();
    $exc_res = $exc_stmt->get_result();
    while ($er = $exc_res->fetch_assoc()) {
        $exceptions_set[$er['mapping_id'] . '|' . $er['date']] = true;
    }
    $exc_stmt->close();
}

// ── Expand each mapping into individual date slots ───────────────────────────
// slot_list: array of [mapping_id, date, faculty, term, sem, subject, class, slot, skipped]
$slot_list = [];
if ($filter_term !== '') {
    foreach ($mappings_rows as $m) {
    $mapping_term = trim((string)($m['term'] ?? ''));
    if ($filter_mapping > 0 && $m['id'] !== $filter_mapping) continue;
        if (strcasecmp($mapping_term, $filter_term) !== 0) continue;

        $repeat_days = array_map('intval', explode(',', $m['repeat_days']));
        $cur = new DateTime($m['start_date']);
        $end = new DateTime($m['end_date']);
        $today = new DateTime('today');
        if ($end > $today) {
            $end = $today;
        }
        if ($cur > $end) {
            continue;
        }
        $end->modify('+1 day'); // make end inclusive

        while ($cur < $end) {
        $dow = (int)$cur->format('w'); // 0=Sun … 6=Sat
        if (in_array($dow, $repeat_days, true)) {
            $date_str = $cur->format('Y-m-d');
            $slot_list[] = [
                'mapping_id' => $m['id'],
                'date'       => $date_str,
                'faculty'    => $m['faculty'],
                'term'       => $mapping_term,
                'sem'        => $m['sem'],
                'subject'    => $m['subject'],
                'class'      => $m['class'],
                'slot'       => $m['slot'],
                'skipped'    => isset($exceptions_set[$m['id'] . '|' . $date_str]),
            ];
        }
        $cur->modify('+1 day');
    }
    }
}

// Sort by date descending (newest first)
usort($slot_list, fn($a, $b) => strcmp($b['date'], $a['date']));

// ── Check which slots are already filled ─────────────────────────────────────
// Build a lookup: "term|sem|subject|class|date|slot" => attendance_id
$filled_lookup = [];
if (!empty($slot_list)) {
    // Collect unique term/sem combos to query efficiently
    $unique_terms = array_values(array_unique(array_column($slot_list, 'term')));
    $unique_sems  = array_values(array_unique(array_column($slot_list, 'sem')));

    if (!empty($unique_terms) && !empty($unique_sems)) {
        $t_placeholders = implode(',', array_fill(0, count($unique_terms), '?'));
        $s_placeholders = implode(',', array_fill(0, count($unique_sems),  '?'));
        $types = str_repeat('s', count($unique_terms) + count($unique_sems));
        $params = array_merge($unique_terms, $unique_sems);

        $att_stmt = $conn->prepare("SELECT id, date, time, term, sem, subject, class FROM lecattendance WHERE term IN ($t_placeholders) AND sem IN ($s_placeholders)");
        $att_stmt->bind_param($types, ...$params);
        $att_stmt->execute();
        $att_res = $att_stmt->get_result();
        while ($ar = $att_res->fetch_assoc()) {
            $key = $ar['term'] . '|' . $ar['sem'] . '|' . $ar['subject'] . '|' . $ar['class'] . '|' . $ar['date'] . '|' . $ar['time'];
            $filled_lookup[$key] = (int)$ar['id'];
        }
        $att_stmt->close();
    }
}

// ── Annotate each slot with filled status ─────────────────────────────────────
foreach ($slot_list as &$slot) {
    $key = $slot['term'] . '|' . $slot['sem'] . '|' . $slot['subject'] . '|' . $slot['class'] . '|' . $slot['date'] . '|' . $slot['slot'];
    $slot['filled']        = isset($filled_lookup[$key]);
    $slot['attendance_id'] = $filled_lookup[$key] ?? null;
}
unset($slot);

$bulk_candidates = array_values(array_filter($slot_list, fn($s) => !$s['filled'] && !$s['skipped']));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autofill_pending_max'])) {
    $redirect_params = [
        'status' => $filter_status,
        'mapping' => $filter_mapping,
        'term' => $filter_term,
    ];

    if (empty($bulk_candidates)) {
        $redirect_params['err'] = 'No pending lecture slots found for autofill.';
        header('Location: myAttendance.php?' . http_build_query($redirect_params));
        exit();
    }

    $class_students_stmt = $conn->prepare("SELECT enrollmentNo FROM students WHERE term = ? AND sem = ? AND class = ? AND enrollmentNo IS NOT NULL AND TRIM(enrollmentNo) <> ''");
    $lec_auto_stmt = $conn->prepare("SELECT presentNo FROM lecattendance WHERE term = ? AND sem = ? AND class = ? AND date = ?");
    $lab_auto_stmt = $conn->prepare("SELECT presentNo FROM labattendance WHERE term = ? AND sem = ? AND date = ? AND COALESCE(TRIM(labNo), '') <> ''");
    $tut_auto_stmt = $conn->prepare("SELECT presentNo FROM tutattendance WHERE term = ? AND sem = ? AND date = ?");
    $exists_stmt = $conn->prepare("SELECT id FROM lecattendance WHERE date = ? AND time = ? AND term = ? AND sem = ? AND subject = ? AND class = ? LIMIT 1");
    $insert_stmt = $conn->prepare("INSERT INTO lecattendance (date, logdate, time, term, faculty, sem, subject, class, presentNo, absentNo, description) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$class_students_stmt || !$lec_auto_stmt || !$lab_auto_stmt || !$tut_auto_stmt || !$exists_stmt || !$insert_stmt) {
        $redirect_params['err'] = 'Bulk autofill is unavailable right now. Please try again.';
        header('Location: myAttendance.php?' . http_build_query($redirect_params));
        exit();
    }

    $parse_present_tokens = static function (string $csv): array {
        $tokens = [];
        foreach (explode(',', $csv) as $raw) {
            $token = trim($raw);
            if ($token !== '') {
                $tokens[$token] = true;
            }
        }
        return array_keys($tokens);
    };

    $class_cache = [];
    $best_cache = [];
    $processed_slot_keys = [];

    $created = 0;
    $autofilled = 0;
    $skipped_no_autofill = 0;
    $skipped_existing = 0;
    $skipped_duplicate = 0;
    $failed = 0;

    foreach ($bulk_candidates as $slot) {
        $slot_key = $slot['term'] . '|' . $slot['sem'] . '|' . $slot['subject'] . '|' . $slot['class'] . '|' . $slot['date'] . '|' . $slot['slot'];
        if (isset($processed_slot_keys[$slot_key])) {
            $skipped_duplicate++;
            continue;
        }
        $processed_slot_keys[$slot_key] = true;

        $date = (string)$slot['date'];
        $time = (string)$slot['slot'];
        $term = (string)$slot['term'];
        $faculty = (string)$slot['faculty'];
        $sem = (string)$slot['sem'];
        $subject = (string)$slot['subject'];
        $class = (string)$slot['class'];

        $exists_stmt->bind_param('ssssss', $date, $time, $term, $sem, $subject, $class);
        $exists_stmt->execute();
        $existing_row = $exists_stmt->get_result()->fetch_assoc();
        if ($existing_row) {
            $skipped_existing++;
            continue;
        }

        $class_key = $term . '|' . $sem . '|' . $class;
        if (!isset($class_cache[$class_key])) {
            $class_students_stmt->bind_param('sss', $term, $sem, $class);
            $class_students_stmt->execute();
            $student_res = $class_students_stmt->get_result();
            $enrollment_set = [];
            while ($sr = $student_res->fetch_assoc()) {
                $enrollment = trim((string)($sr['enrollmentNo'] ?? ''));
                if ($enrollment !== '') {
                    $enrollment_set[$enrollment] = true;
                }
            }
            $class_cache[$class_key] = $enrollment_set;
        }

        $best_key = $term . '|' . $sem . '|' . $class . '|' . $date;
        if (!isset($best_cache[$best_key])) {
            $class_set = $class_cache[$class_key];
            $best_present = [];
            $best_count = 0;

            $consider_present = static function (string $csv, array $class_set, callable $parser): array {
                $tokens = $parser($csv);
                if (empty($tokens)) {
                    return [];
                }
                $filtered = [];
                foreach ($tokens as $token) {
                    if (isset($class_set[$token])) {
                        $filtered[$token] = true;
                    }
                }
                return array_keys($filtered);
            };

            if (!empty($class_set)) {
                $lec_auto_stmt->bind_param('ssss', $term, $sem, $class, $date);
                $lec_auto_stmt->execute();
                $lec_res = $lec_auto_stmt->get_result();
                while ($row = $lec_res->fetch_assoc()) {
                    $present = $consider_present((string)($row['presentNo'] ?? ''), $class_set, $parse_present_tokens);
                    if (count($present) > $best_count) {
                        $best_count = count($present);
                        $best_present = $present;
                    }
                }

                $lab_auto_stmt->bind_param('sss', $term, $sem, $date);
                $lab_auto_stmt->execute();
                $lab_res = $lab_auto_stmt->get_result();
                while ($row = $lab_res->fetch_assoc()) {
                    $present = $consider_present((string)($row['presentNo'] ?? ''), $class_set, $parse_present_tokens);
                    if (count($present) > $best_count) {
                        $best_count = count($present);
                        $best_present = $present;
                    }
                }

                $tut_auto_stmt->bind_param('sss', $term, $sem, $date);
                $tut_auto_stmt->execute();
                $tut_res = $tut_auto_stmt->get_result();
                while ($row = $tut_res->fetch_assoc()) {
                    $present = $consider_present((string)($row['presentNo'] ?? ''), $class_set, $parse_present_tokens);
                    if (count($present) > $best_count) {
                        $best_count = count($present);
                        $best_present = $present;
                    }
                }
            }

            $best_cache[$best_key] = $best_present;
        }

        $present_list = $best_cache[$best_key];
        if (empty($present_list)) {
            $skipped_no_autofill++;
            continue;
        }

        $class_set = $class_cache[$class_key];
        $present_set = [];
        foreach ($present_list as $enrollment_no) {
            $present_set[$enrollment_no] = true;
        }

        $absent_list = [];
        foreach ($class_set as $enrollment_no => $_exists) {
            if (!isset($present_set[$enrollment_no])) {
                $absent_list[] = $enrollment_no;
            }
        }

        $present_csv = implode(',', $present_list);
        $absent_csv = implode(',', $absent_list);
        $description = null;
        $insert_stmt->bind_param('ssssssssss', $date, $time, $term, $faculty, $sem, $subject, $class, $present_csv, $absent_csv, $description);
        if ($insert_stmt->execute()) {
            $created++;
            $autofilled++;
        } else {
            $failed++;
        }
    }

    $class_students_stmt->close();
    $lec_auto_stmt->close();
    $lab_auto_stmt->close();
    $tut_auto_stmt->close();
    $exists_stmt->close();
    $insert_stmt->close();

    if ($created === 0 && $failed === 0) {
        $redirect_params['err'] = 'No pending slots were inserted. Existing entries may already be present or no autofill source had students.';
    } else {
        $summary = "Autofill complete: created {$created}, autofilled {$autofilled}, skipped no source {$skipped_no_autofill}, skipped existing {$skipped_existing}";
        if ($skipped_duplicate > 0) {
            $summary .= ", skipped duplicate {$skipped_duplicate}";
        }
        if ($failed > 0) {
            $summary .= ", failed {$failed}";
        }
        $summary .= '.';
        $redirect_params['msg'] = $summary;
    }

    header('Location: myAttendance.php?' . http_build_query($redirect_params));
    exit();
}

// ── Handle skip (add exception) ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skip_slot'])) {
    $skip_mapping_id = (int)($_POST['skip_mapping_id'] ?? 0);
    $skip_date       = trim((string)($_POST['skip_date'] ?? ''));
    $redirect_params = ['status' => $filter_status, 'mapping' => $filter_mapping, 'term' => $filter_term];

    if ($skip_mapping_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $skip_date)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO lecmapping_exceptions (mapping_id, date) VALUES (?, ?)");
        $stmt->bind_param('is', $skip_mapping_id, $skip_date);
        $stmt->execute();
        $stmt->close();
        $redirect_params['msg'] = "Slot on {$skip_date} removed (marked as holiday/skip).";
    } else {
        $redirect_params['err'] = 'Invalid skip request.';
    }
    header('Location: myAttendance.php?' . http_build_query($redirect_params));
    exit();
}

// ── Handle restore (remove exception) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_slot'])) {
    $restore_mapping_id = (int)($_POST['restore_mapping_id'] ?? 0);
    $restore_date       = trim((string)($_POST['restore_date'] ?? ''));
    $redirect_params = ['status' => $filter_status, 'mapping' => $filter_mapping, 'term' => $filter_term];

    if ($restore_mapping_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $restore_date)) {
        $stmt = $conn->prepare("DELETE FROM lecmapping_exceptions WHERE mapping_id = ? AND date = ?");
        $stmt->bind_param('is', $restore_mapping_id, $restore_date);
        $stmt->execute();
        $stmt->close();
        $redirect_params['msg'] = "Slot on {$restore_date} restored.";
    } else {
        $redirect_params['err'] = 'Invalid restore request.';
    }
    header('Location: myAttendance.php?' . http_build_query($redirect_params));
    exit();
}

// ── Apply status filter ───────────────────────────────────────────────────────
// Stats computed before filter (on all slots including skipped)
$stats_slot_list = $slot_list;
$total_skipped = count(array_filter($stats_slot_list, fn($s) => $s['skipped']));

if ($filter_status === 'filled') {
    $slot_list = array_values(array_filter($slot_list, fn($s) => $s['filled']));
} elseif ($filter_status === 'unfilled') {
    $slot_list = array_values(array_filter($slot_list, fn($s) => !$s['filled'] && !$s['skipped']));
} elseif ($filter_status === 'skipped') {
    $slot_list = array_values(array_filter($slot_list, fn($s) => $s['skipped']));
}
// 'all' shows everything including skipped

// ── Faculty name lookup ───────────────────────────────────────────────────────
$faculty_map = [];
$fres = $conn->query("SELECT id, Name FROM faculty");
while ($fr = $fres->fetch_assoc()) {
    $faculty_map[(string)$fr['id']] = $fr['Name'];
}

// Stats (computed on the full unfiltered list)
$total    = count($stats_slot_list);
$filled   = count(array_filter($stats_slot_list, fn($s) => $s['filled']));
$skipped  = count(array_filter($stats_slot_list, fn($s) => $s['skipped']));
$unfilled = $total - $filled - $skipped;

$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 attendance-toolbar">
                <h1 class="app-page-title mb-0"><i class="bi bi-calendar2-check me-2"></i>My Attendance</h1>
                <a href="addLectureMapping.php" class="btn btn-sm mapping-cta-btn">
                    Add / Manage Mappings
                </a>
            </div>

            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            <?php if ($error_msg !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <?php if (empty($mappings_rows)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>No lecture mappings found for your account.
                    <a href="addLectureMapping.php" class="alert-link">Create a mapping</a> to get started.
                </div>
            <?php else: ?>

            <?php if ($filter_term === ''): ?>
            <div class="app-card shadow-sm">
                <div class="app-card-body py-4">
                    <div class="attendance-term-picker text-center">
                        <h4 class="mb-2">Select Term</h4>
                        <p class="text-muted mb-4">Choose a term to open its attendance page. Pending will be selected by default.</p>
                        <div class="attendance-term-badges">
                            <?php foreach ($available_terms as $term_option): ?>
                                <a href="myAttendance.php?<?= htmlspecialchars(http_build_query(['term' => $term_option, 'status' => 'unfilled'])) ?>" class="attendance-term-badge">
                                    <?= htmlspecialchars($term_option) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>

            <div class="app-card shadow-sm mb-1 attendance-shell-card">
                <div class="app-card-body py-1 px-2 d-flex justify-content-between align-items-center flex-wrap gap-1 attendance-top-strip">
                    <div class="attendance-current-term">
                        <span class="attendance-current-term-label">Term</span>
                        <span class="attendance-current-term-value"><?= htmlspecialchars($filter_term) ?></span>
                    </div>
                    <a href="myAttendanceSelect.php" class="btn btn-sm attendance-compact-btn attendance-change-btn">Change Term</a>
                </div>
            </div>

            <div class="row g-2 mb-1 attendance-stats-row">
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="app-card shadow-sm text-center attendance-stat-card attendance-stat-card-compact attendance-shell-card">
                        <div class="app-card-body py-2">
                            <div class="attendance-stat-value"><?= $total ?></div>
                            <div class="attendance-stat-label">Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="app-card shadow-sm text-center attendance-stat-card attendance-stat-card-compact attendance-shell-card">
                        <div class="app-card-body py-2">
                            <div class="attendance-stat-value text-success"><?= $filled ?></div>
                            <div class="attendance-stat-label">Filled</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="app-card shadow-sm text-center attendance-stat-card attendance-stat-card-compact attendance-shell-card">
                        <div class="app-card-body py-2">
                            <div class="attendance-stat-value text-danger"><?= $unfilled ?></div>
                            <div class="attendance-stat-label">Pending</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="app-card shadow-sm text-center attendance-stat-card attendance-stat-card-compact attendance-shell-card">
                        <div class="app-card-body py-2">
                            <div class="attendance-stat-value text-secondary"><?= $skipped ?></div>
                            <div class="attendance-stat-label">Skipped</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card shadow-sm mb-1 attendance-shell-card">
                <div class="app-card-body py-1 px-2">
                    <div class="attendance-filter-panel">
                        <div class="attendance-filter-row">
                            <span class="attendance-filter-label">Filter</span>
                            <div class="attendance-filter-pills" role="group" aria-label="Attendance filters">
                                <a href="?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => 'all', 'mapping' => $filter_mapping])) ?>"
                                   class="btn btn-sm <?= $filter_status === 'all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">All</a>
                                <a href="?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => 'unfilled', 'mapping' => $filter_mapping])) ?>"
                                   class="btn btn-sm <?= $filter_status === 'unfilled' ? 'btn-danger' : 'btn-outline-danger' ?>">Pending</a>
                                <a href="?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => 'filled', 'mapping' => $filter_mapping])) ?>"
                                   class="btn btn-sm <?= $filter_status === 'filled' ? 'btn-success' : 'btn-outline-success' ?>">Filled</a>
                                <a href="?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => 'skipped', 'mapping' => $filter_mapping])) ?>"
                                   class="btn btn-sm <?= $filter_status === 'skipped' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Skipped</a>
                            </div>
                        </div>

                        <?php if (!empty($bulk_candidates)): ?>
                            <div class="attendance-filter-actions">
                                <form method="POST" action="myAttendance.php?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => $filter_status, 'mapping' => $filter_mapping])) ?>" class="attendance-bulk-form">
                                    <button type="submit" name="autofill_pending_max" class="btn btn-warning btn-sm attendance-bulk-btn" title="Autofill all pending slots (max by day)" onclick="return confirm('Autofill all pending slots using maximum available attendance on each day? Slots without autofill source will be skipped.');">
                                        <i class="bi bi-stars me-1"></i>Autofill Pending
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="app-card shadow-sm attendance-shell-card">
                <div class="app-card-body p-0">
                    <?php if (empty($slot_list)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
                            No slots match the current filter for term <?= htmlspecialchars($filter_term) ?>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive attendance-table-wrap">
                            <table id="attendanceDataTable" class="table table-hover align-middle mb-0 attendance-table" style="font-size:0.875rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width:36px;">#</th>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Slot</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($slot_list as $i => $slot):
                                    $date_obj = new DateTime($slot['date']);
                                    $dow_name = $day_names[(int)$date_obj->format('w')];
                                    $is_today = ($slot['date'] === date('Y-m-d'));
                                    $params = http_build_query([
                                        'faculty' => $slot['faculty'],
                                        'term'    => $slot['term'],
                                        'sem'     => $slot['sem'],
                                        'subject' => $slot['subject'],
                                        'class'   => $slot['class'],
                                        'date'    => $slot['date'],
                                        'slot'    => $slot['slot'],
                                    ]);
                                    $take_url = 'takelecatt.php?' . $params;
                                    $edit_url = $slot['filled'] ? 'editlecatt.php?id=' . $slot['attendance_id'] : null;
                                    $summary_url = $slot['filled'] ? 'attendanceSummary.php?type=lecture&id=' . $slot['attendance_id'] : null;
                                    $row_class = '';
                                    if ($slot['skipped']) $row_class = 'table-secondary skip-row';
                                    elseif (!$slot['filled'] && $is_today) $row_class = 'table-warning';
                                    elseif (!$slot['filled']) $row_class = 'table-danger-subtle';
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td class="text-muted" data-label="No."><?= $i + 1 ?></td>
                                    <td data-label="Date">
                                        <strong><?= htmlspecialchars($slot['date']) ?></strong>
                                        <?php if ($is_today): ?>
                                            <span class="badge bg-warning text-dark ms-1">Today</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Day"><?= $dow_name ?></td>
                                    <td data-label="Subject"><?= htmlspecialchars($slot['subject']) ?></td>
                                    <td data-label="Class"><span class="badge bg-primary-subtle text-dark border"><?= htmlspecialchars($slot['class']) ?></span></td>
                                    <td data-label="Slot"><?= htmlspecialchars($slot['slot']) ?></td>
                                    <td data-label="Status" class="attendance-status-cell">
                                        <?php if ($slot['skipped']): ?>
                                            <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Skipped</span>
                                        <?php elseif ($slot['filled']): ?>
                                            <span class="badge bg-success">Filled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap attendance-action-cell" data-label="Action">
                                        <div class="attendance-actions">
                                        <?php if ($slot['skipped']): ?>
                                            <form method="POST" action="myAttendance.php?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => $filter_status, 'mapping' => $filter_mapping])) ?>" class="d-inline-flex">
                                                <input type="hidden" name="restore_mapping_id" value="<?= (int)$slot['mapping_id'] ?>">
                                                <input type="hidden" name="restore_date" value="<?= htmlspecialchars($slot['date']) ?>">
                                                <button type="submit" name="restore_slot" class="btn btn-outline-secondary btn-sm" title="Restore this slot" onclick="return confirm('Restore this slot on <?= htmlspecialchars($slot['date']) ?>?')">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            </form>
                                        <?php elseif ($slot['filled']): ?>
                                            <a href="<?= htmlspecialchars($summary_url) ?>" class="btn btn-outline-success btn-sm me-1" title="View Summary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-outline-primary btn-sm" title="Edit Attendance">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($take_url) ?>" class="btn btn-warning btn-sm me-1">
                                                Take Attendance
                                            </a>
                                            <form method="POST" action="myAttendance.php?<?= htmlspecialchars(http_build_query(['term' => $filter_term, 'status' => $filter_status, 'mapping' => $filter_mapping])) ?>" class="d-inline-flex">
                                                <input type="hidden" name="skip_mapping_id" value="<?= (int)$slot['mapping_id'] ?>">
                                                <input type="hidden" name="skip_date" value="<?= htmlspecialchars($slot['date']) ?>">
                                                <button type="submit" name="skip_slot" class="btn btn-outline-secondary btn-sm" title="Skip this slot (holiday/no class)" onclick="return confirm('Skip slot on <?= htmlspecialchars($slot['date']) ?>? It will be removed from pending.')">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table-danger-subtle {
    background-color: rgba(220, 53, 69, 0.05);
}
.skip-row td {
    opacity: 0.55;
    text-decoration: line-through;
    text-decoration-color: #888;
}
.skip-row td:last-child {
    text-decoration: none;
    opacity: 1;
}
.sticky-top {
    top: 0;
    z-index: 1;
}
.mapping-cta-btn {
    color: #fff;
    border: 0;
    border-radius: 0.6rem;
    background: linear-gradient(135deg, #1f7a8c, #2a9d8f);
    box-shadow: 0 12px 28px rgba(31, 122, 140, 0.28);
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 0.65rem 1.2rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    font-size: 1rem;
}
.mapping-cta-btn:hover,
.mapping-cta-btn:focus {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(31, 122, 140, 0.28);
    filter: saturate(1.05);
}
.mapping-cta-btn:active {
    transform: translateY(0);
}
.attendance-term-picker {
    max-width: 720px;
    margin: 0 auto;
}
.attendance-term-badges {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.attendance-term-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 130px;
    padding: 0.85rem 1.25rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #eef6ff, #dbeafe);
    border: 2px solid #93c5fd;
    color: #0f172a;
    font-weight: 700;
    text-decoration: none;
    font-size: 1rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.attendance-term-badge:hover,
.attendance-term-badge:focus {
    color: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.22);
    background: linear-gradient(135deg, #bfdbfe, #7dd3fc);
}
.attendance-filters {
    display: block;
}
.attendance-top-strip {
    min-height: 0;
    gap: 0.35rem;
    flex-wrap: nowrap;
}
.attendance-shell-card {
    border-radius: 0.7rem;
}
.attendance-shell-card .app-card-body {
    padding: 0.3rem 0.45rem !important;
}
.attendance-current-term {
    display: inline-flex;
    align-items: center;
    gap: 0.32rem;
    flex-wrap: wrap;
}
.attendance-current-term-label {
    font-size: 0.62rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.attendance-current-term-value {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 46px;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: #eef6ff;
    border: 1px solid #bfdbfe;
    color: #0f172a;
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.1;
}
.attendance-filter-panel {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #f0f4f8 0%, #f8fafc 100%);
    border: 2px solid #e2e8f0;
}
.attendance-filter-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.attendance-filter-label {
    font-size: 0.9rem;
    color: #1e293b;
    white-space: nowrap;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.attendance-filter-pills {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    overflow-x: auto;
    scrollbar-width: thin;
    padding-bottom: 0.15rem;
    flex: 1 1 auto;
}
.attendance-filter-pills .btn {
    padding: 0.5rem 0.85rem;
    font-size: 0.92rem;
    line-height: 1.2;
    border-radius: 999px;
    font-weight: 700;
    white-space: nowrap;
    border: 2px solid;
    transition: all 0.2s ease;
}
.attendance-bulk-form {
    margin: 0;
}
.attendance-filter-actions {
    display: flex;
    justify-content: flex-start;
}
.attendance-compact-btn {
    padding: 0.5rem 0.85rem;
    font-size: 0.85rem;
    line-height: 1.2;
    border-radius: 0.6rem;
    font-weight: 700;
    border-width: 2px;
}
.attendance-change-btn {
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border: 0;
    font-weight: 700;
    border-radius: 0.6rem;
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.22);
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
}
.attendance-change-btn:hover,
.attendance-change-btn:focus {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
    filter: brightness(1.03);
}
.attendance-change-btn:active {
    transform: translateY(0);
}
.attendance-bulk-btn {
    min-height: 38px;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    font-weight: 700;
    white-space: nowrap;
    border-radius: 0.6rem;
    box-shadow: 0 6px 14px rgba(245, 158, 11, 0.24);
    border-width: 2px;
    transition: all 0.2s ease;
}
.attendance-stat-card {
    border-radius: 1rem;
    border: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}
.attendance-stat-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}
.attendance-stat-card .app-card-body {
    padding: 0.6rem 0.3rem !important;
}
.attendance-stat-card-compact {
    border-radius: 0.85rem;
}
.attendance-stat-card-compact .app-card-body {
    padding: 0.5rem 0.25rem !important;
}
.attendance-stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1;
}
.attendance-stat-label {
    margin-top: 0.35rem;
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.2;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.attendance-table-wrap {
    overflow-x: auto;
}
.attendance-table-wrap .dataTables_wrapper {
    padding: 0.35rem 0.45rem 0.45rem;
}
.attendance-table-wrap .dataTables_filter {
    margin-bottom: 0.35rem;
}
.attendance-table-wrap .dataTables_filter input {
    margin-left: 0.4rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 0.22rem 0.45rem;
}
.attendance-table-wrap .dataTables_length,
.attendance-table-wrap .dataTables_info,
.attendance-table-wrap .dataTables_paginate {
    font-size: 0.82rem;
}
.attendance-table {
    min-width: 800px;
}
.attendance-table thead th {
    font-size: 0.85rem;
    padding: 0.85rem 0.75rem;
    white-space: nowrap;
    font-weight: 700;
    background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ff 100%);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #cbd5e1;
}
.attendance-table td {
    padding: 0.85rem 0.75rem;
    vertical-align: middle;
    font-weight: 500;
}
.attendance-table .badge {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.5rem 0.65rem;
    border-radius: 0.5rem;
}
.attendance-status-cell .badge {
    min-width: 90px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
}
.attendance-actions {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.65rem;
    flex-wrap: wrap;
}
.attendance-actions .btn,
.attendance-actions form {
    margin: 0 !important;
}
.attendance-actions .btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
    line-height: 1.3;
    border-radius: 0.5rem;
    font-weight: 600;
    border-width: 2px;
    transition: all 0.2s ease;
    min-height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}
@media (max-width: 767.98px) {
    .attendance-toolbar {
        align-items: stretch !important;
    }
    .attendance-toolbar .app-page-title {
        font-size: 1.4rem;
        font-weight: 700;
    }
    .attendance-top-strip {
        align-items: center !important;
        flex-wrap: nowrap !important;
    }
    .attendance-current-term {
        width: 100%;
    }
    .mapping-cta-btn {
        width: 100%;
        justify-content: center;
        padding: 0.8rem 0.9rem;
        font-size: 1rem;
    }
    .attendance-filters {
        width: 100%;
    }
    .attendance-filter-panel {
        padding: 0.85rem 0.75rem;
        gap: 0.6rem;
    }
    .attendance-shell-card .app-card-body {
        padding: 0.5rem 0.5rem !important;
    }
    .attendance-filter-row {
        align-items: flex-start;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .attendance-filter-pills {
        width: 100%;
        gap: 0.4rem;
    }
    .attendance-filter-pills .btn {
        flex: 1 1 calc(50% - 0.2rem);
        font-size: 0.9rem;
        padding: 0.5rem 0.6rem;
        min-height: 40px;
    }
    .attendance-filter-actions,
    .attendance-compact-btn {
        width: 100%;
    }
    .attendance-bulk-form {
        width: 100%;
    }
    .attendance-bulk-btn {
        width: 100%;
        justify-content: center;
        min-height: 44px;
        font-size: 1rem;
    }
    .attendance-table {
        min-width: 100%;
        font-size: 0.9rem !important;
    }
    .attendance-table thead th,
    .attendance-table td {
        padding: 0.75rem 0.6rem;
    }
    .attendance-table .badge {
        font-size: 0.78rem;
        padding: 0.4rem 0.55rem;
    }
    .sticky-top {
        position: static;
    }
    .attendance-actions {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .attendance-actions .btn {
        flex: 1 1 calc(50% - 0.25rem);
        min-height: 40px;
        font-size: 0.85rem;
    }
    .attendance-stat-value {
        font-size: 1.5rem;
    }
}

@media (max-width: 1024px) and (min-width: 768px) {
    .attendance-filter-pills {
        gap: 0.35rem;
    }
    .attendance-filter-pills .btn {
        padding: 0.45rem 0.75rem;
        font-size: 0.88rem;
        flex: 0 0 auto;
    }
    .attendance-table {
        min-width: 750px;
    }
    .attendance-table thead th,
    .attendance-table td {
        padding: 0.7rem 0.6rem;
        font-size: 0.9rem;
    }
}

/* Button & Icon Enhancements */
.btn i {
    font-size: 1rem;
    margin-right: 0.3rem;
}
.attendance-bulk-btn i {
    font-size: 1.1rem;
}
.attendance-actions .btn i {
    font-size: 1rem;
}
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableElement = document.getElementById('attendanceDataTable');
    if (!tableElement || typeof window.jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
        return;
    }

    const dataTable = jQuery(tableElement).DataTable({
        pageLength: 25,
        order: [[1, 'desc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false },
            { targets: 7, orderable: false, searchable: false }
        ],
        language: {
            search: 'Search slots:',
            lengthMenu: 'Show _MENU_ slots',
            info: 'Showing _START_ to _END_ of _TOTAL_ slots',
            emptyTable: 'No slots available for the selected term.'
        }
    });

    dataTable.on('order.dt search.dt draw.dt', function () {
        dataTable.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
            cell.textContent = index + 1;
        });
    });

    dataTable.draw();
});
</script>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
