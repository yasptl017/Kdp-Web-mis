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
$filter_status  = $_GET['status']  ?? 'all';   // all | filled | unfilled
$filter_mapping = (int)($_GET['mapping'] ?? 0); // specific mapping id, 0 = all

// ── Load all mappings for this faculty ───────────────────────────────────────
$mappings_stmt = $conn->prepare("SELECT * FROM lecmapping WHERE faculty = ? ORDER BY start_date, id");
$mappings_stmt->bind_param('s', $logged_faculty_id);
$mappings_stmt->execute();
$mappings_rows = $mappings_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$mappings_stmt->close();

// ── Expand each mapping into individual date slots ───────────────────────────
// slot_list: array of [mapping_id, date, faculty, term, sem, subject, class, slot]
$slot_list = [];
foreach ($mappings_rows as $m) {
    if ($filter_mapping > 0 && $m['id'] !== $filter_mapping) continue;

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
            $slot_list[] = [
                'mapping_id' => $m['id'],
                'date'       => $cur->format('Y-m-d'),
                'faculty'    => $m['faculty'],
                'term'       => $m['term'],
                'sem'        => $m['sem'],
                'subject'    => $m['subject'],
                'class'      => $m['class'],
                'slot'       => $m['slot'],
            ];
        }
        $cur->modify('+1 day');
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

$bulk_candidates = array_values(array_filter($slot_list, fn($s) => !$s['filled']));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autofill_pending_max'])) {
    $redirect_params = [
        'status' => $filter_status,
        'mapping' => $filter_mapping,
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

// ── Apply status filter ───────────────────────────────────────────────────────
if ($filter_status === 'filled') {
    $slot_list = array_values(array_filter($slot_list, fn($s) => $s['filled']));
} elseif ($filter_status === 'unfilled') {
    $slot_list = array_values(array_filter($slot_list, fn($s) => !$s['filled']));
}

// ── Faculty name lookup ───────────────────────────────────────────────────────
$faculty_map = [];
$fres = $conn->query("SELECT id, Name FROM faculty");
while ($fr = $fres->fetch_assoc()) {
    $faculty_map[(string)$fr['id']] = $fr['Name'];
}

// Stats
$total    = count($slot_list);
$filled   = count(array_filter($slot_list, fn($s) => $s['filled']));
$unfilled = $total - $filled;

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

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="app-page-title mb-0"><i class="bi bi-calendar2-check me-2"></i>My Attendance</h1>
                <a href="addMapping.php" class="btn btn-sm mapping-cta-btn">
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
                    <a href="addMapping.php" class="alert-link">Create a mapping</a> to get started.
                </div>
            <?php else: ?>

            <!-- Stats row -->
            <div class="row g-3 mb-3">
                <div class="col-4 col-md-2">
                    <div class="app-card shadow-sm text-center">
                        <div class="app-card-body py-2">
                            <div class="fs-4 fw-bold"><?= $total ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="app-card shadow-sm text-center">
                        <div class="app-card-body py-2">
                            <div class="fs-4 fw-bold text-success"><?= $filled ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">Filled</div>
                        </div>
                    </div>
                </div>
                <div class="col-4 col-md-2">
                    <div class="app-card shadow-sm text-center">
                        <div class="app-card-body py-2">
                            <div class="fs-4 fw-bold text-danger"><?= $unfilled ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">Pending</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body py-2">
                    <form method="GET" action="myAttendance.php" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-semibold me-1" style="font-size:0.85rem;">Filter:</span>

                        <div class="btn-group btn-group-sm" role="group">
                            <a href="?status=all&mapping=<?= $filter_mapping ?>"
                               class="btn <?= $filter_status === 'all'      ? 'btn-secondary' : 'btn-outline-secondary' ?>">All</a>
                            <a href="?status=unfilled&mapping=<?= $filter_mapping ?>"
                               class="btn <?= $filter_status === 'unfilled' ? 'btn-danger'    : 'btn-outline-danger' ?>">
                               Pending</a>
                            <a href="?status=filled&mapping=<?= $filter_mapping ?>"
                               class="btn <?= $filter_status === 'filled'   ? 'btn-success'   : 'btn-outline-success' ?>">
                               Filled</a>
                        </div>

                        <select name="mapping" class="form-select form-select-sm" style="max-width:260px;" onchange="this.form.submit()">
                            <option value="0">All Mappings</option>
                            <?php foreach ($mappings_rows as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($filter_mapping === (int)$m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['subject'] . ' · Class ' . $m['class'] . ' · ' . $m['slot']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                    </form>

                    <?php if (!empty($bulk_candidates)): ?>
                        <form method="POST" action="myAttendance.php?<?= htmlspecialchars(http_build_query(['status' => $filter_status, 'mapping' => $filter_mapping])) ?>" class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <button type="submit" name="autofill_pending_max" class="btn btn-warning btn-sm" onclick="return confirm('Autofill all pending slots using maximum available attendance on each day? Slots without autofill source will be skipped.');">
                                <i class="bi bi-magic me-1"></i>Autofill All Pending (Max by Day)
                            </button>
                        
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Slot list -->
            <div class="app-card shadow-sm">
                <div class="app-card-body p-0">
                    <?php if (empty($slot_list)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
                            No slots match the current filter.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
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

                                    // Build URL to takelecatt.php
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
                                ?>
                                <tr class="<?= !$slot['filled'] && $is_today ? 'table-warning' : (!$slot['filled'] ? 'table-danger-subtle' : '') ?>">
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($slot['date']) ?></strong>
                                        <?php if ($is_today): ?>
                                            <span class="badge bg-warning text-dark ms-1">Today</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $dow_name ?></td>
                                    <td><?= htmlspecialchars($slot['subject']) ?></td>
                                    <td><span class="badge bg-primary-subtle text-dark border"><?= htmlspecialchars($slot['class']) ?></span></td>
                                    <td><?= htmlspecialchars($slot['slot']) ?></td>
                                    <td>
                                        <?php if ($slot['filled']): ?>
                                            <span class="badge bg-success">Filled</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($slot['filled']): ?>
                                            <a href="<?= htmlspecialchars($summary_url) ?>" class="btn btn-outline-success btn-sm me-1" title="View Summary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars($edit_url) ?>" class="btn btn-outline-primary btn-sm" title="Edit Attendance">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($take_url) ?>" class="btn btn-warning btn-sm">
                                                Take Attendance
                                            </a>
                                        <?php endif; ?>
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
        </div>
    </div>
</div>

<style>
.table-danger-subtle {
    background-color: rgba(220, 53, 69, 0.05);
}
.sticky-top {
    top: 0;
    z-index: 1;
}
.mapping-cta-btn {
    color: #fff;
    border: 0;
    border-radius: 0.4rem;
    background: linear-gradient(135deg, #1f7a8c, #2a9d8f);
    box-shadow: 0 10px 24px rgba(31, 122, 140, 0.22);
    font-weight: 600;
    letter-spacing: 0.2px;
    padding: 0.45rem 1rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
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
</style>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
