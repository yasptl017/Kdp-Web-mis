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

function csv_values($value) {
    $items = array_map('trim', explode(',', (string)$value));
    $items = array_filter($items, function ($item) {
        return $item !== '';
    });
    return array_values(array_unique($items));
}

function bind_dynamic_params($stmt, $types, $params) {
    $bind_params = [$types];
    foreach ($params as $index => $param) {
        $bind_params[] = &$params[$index];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

function parse_pc_used_map($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return [];
    }

    $map = [];
    $parts = array_map('trim', explode(',', $value));
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if (strpos($part, ':') !== false) {
            [$key, $pc] = array_map('trim', explode(':', $part, 2));
            if ($key !== '' && $pc !== '') {
                $map[$key] = max(0, (int)$pc);
            }
        } else {
            $map['Total'] = max(0, (int)$part);
        }
    }

    return $map;
}

$type = strtolower(trim((string)($_GET['type'] ?? '')));
$id = (int)($_GET['id'] ?? 0);

$allowed_types = ['lecture', 'lab', 'tutorial'];
if (!in_array($type, $allowed_types, true) || $id <= 0) {
    http_response_code(400);
    echo 'Invalid summary request.';
    exit();
}

if ($type === 'lecture') {
    ensure_lecture_attendance_columns($conn);
    $stmt = $conn->prepare("SELECT id, date, logdate, time, term, faculty, sem, subject, class, presentNo, COALESCE(absentNo, '') AS absentNo, COALESCE(description, '') AS description FROM lecattendance WHERE id = ?");
    $stmt->bind_param('i', $id);
} elseif ($type === 'tutorial') {
    $stmt = $conn->prepare("SELECT id, date, CURDATE() AS logdate, time, term, faculty, sem, subject, batch, '' AS labNo, presentNo, '' AS totalPcUsed, '' AS absentNo, '' AS description FROM tutattendance WHERE id = ?");
    $stmt->bind_param('i', $id);
} else {
    $stmt = $conn->prepare("SELECT id, date, logdate, time, term, faculty, sem, subject, batch, labNo, presentNo, totalPcUsed, '' AS absentNo, '' AS description FROM labattendance WHERE id = ?");
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$record_result = $stmt->get_result();
$record = $record_result->fetch_assoc();
$stmt->close();

if (!$record) {
    http_response_code(404);
    echo 'Attendance record not found.';
    exit();
}

$faculty_display = $record['faculty'];
if (ctype_digit((string)$record['faculty'])) {
    $faculty_id = (int)$record['faculty'];
    $faculty_stmt = $conn->prepare("SELECT Name FROM faculty WHERE id = ?");
    $faculty_stmt->bind_param('i', $faculty_id);
    $faculty_stmt->execute();
    $faculty_result = $faculty_stmt->get_result();
    if ($faculty_row = $faculty_result->fetch_assoc()) {
        $faculty_display = $faculty_row['Name'];
    }
    $faculty_stmt->close();
}

$present_enrollments = csv_values($record['presentNo'] ?? '');
$absent_enrollments = ($type === 'lecture') ? csv_values($record['absentNo'] ?? '') : [];
$present_students = [];

if (!empty($present_enrollments)) {
    $placeholders = implode(',', array_fill(0, count($present_enrollments), '?'));
    $query = "SELECT enrollmentNo, name, class, labBatch, tutBatch FROM students WHERE term = ? AND sem = ? AND enrollmentNo IN ($placeholders)";
    $students_stmt = $conn->prepare($query);

    $params = array_merge([(string)$record['term'], (string)$record['sem']], $present_enrollments);
    $types = 'ss' . str_repeat('s', count($present_enrollments));
    bind_dynamic_params($students_stmt, $types, $params);

    $students_stmt->execute();
    $students_result = $students_stmt->get_result();

    $students_by_enrollment = [];
    while ($student_row = $students_result->fetch_assoc()) {
        $students_by_enrollment[(string)$student_row['enrollmentNo']] = $student_row;
    }
    $students_stmt->close();

    foreach ($present_enrollments as $enrollment) {
        if (isset($students_by_enrollment[$enrollment])) {
            $student_row = $students_by_enrollment[$enrollment];
            if ($type === 'lecture') {
                $group_value = $student_row['class'];
            } elseif ($type === 'tutorial') {
                $group_value = $student_row['tutBatch'];
            } else {
                $group_value = $student_row['labBatch'];
            }

            $present_students[] = [
                'enrollmentNo' => $enrollment,
                'name' => short_name($student_row['name']),
                'group' => $group_value,
            ];
        } else {
            $present_students[] = [
                'enrollmentNo' => $enrollment,
                'name' => '-',
                'group' => '-',
            ];
        }
    }
}

$group_counts = [];
foreach ($present_students as $student) {
    $group_key = trim((string)$student['group']) !== '' ? $student['group'] : '-';
    if (!isset($group_counts[$group_key])) {
        $group_counts[$group_key] = 0;
    }
    $group_counts[$group_key]++;
}
ksort($group_counts);

$pc_map = [];
$pc_total = 0;
if ($type !== 'lecture') {
    $pc_map = parse_pc_used_map($record['totalPcUsed'] ?? '');
    foreach ($pc_map as $pc_value) {
        $pc_total += (int)$pc_value;
    }
}

$group_label = ($type === 'lecture') ? 'Class' : (($type === 'tutorial') ? 'Tutorial Batch' : 'Batch');
$page_title = ($type === 'lecture') ? 'Lecture Attendance Summary' : (($type === 'tutorial') ? 'Tutorial Attendance Summary' : 'Lab Attendance Summary');
$back_url = ($type === 'lecture') ? 'lecAttendance.php' : (($type === 'tutorial') ? 'tutAttendance.php' : 'labAttendance.php');
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
                <h1 class="app-page-title mb-0"><i class="bi bi-journal-check me-2"></i><?= htmlspecialchars($page_title); ?></h1>
                <div class="d-flex gap-2">
                    <?php
                    $edit_page = ['lecture' => 'editlecatt.php', 'lab' => 'editlabatt.php', 'tutorial' => 'edittutatt.php'];
                    ?>
                    <a href="<?= $edit_page[$type] ?>?id=<?= $id ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Edit Attendance
                    </a>
                    <a href="<?= htmlspecialchars($back_url); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>

            <div class="alert alert-success mb-3">
                Attendance saved successfully.
            </div>

            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <h4>Recorded Details</h4>
                    <div class="row g-2">
                        <div class="col-6 col-md-3"><strong>Faculty:</strong> <?= htmlspecialchars($faculty_display); ?></div>
                        <div class="col-6 col-md-3"><strong>Term:</strong> <?= htmlspecialchars($record['term']); ?></div>
                        <div class="col-6 col-md-3"><strong>Semester:</strong> <?= htmlspecialchars($record['sem']); ?></div>
                        <div class="col-6 col-md-3"><strong>Subject:</strong> <?= htmlspecialchars($record['subject']); ?></div>
                        <?php if ($type === 'lecture'): ?>
                            <div class="col-6 col-md-3"><strong>Class:</strong> <?= htmlspecialchars($record['class']); ?></div>
                        <?php elseif ($type === 'tutorial'): ?>
                            <div class="col-6 col-md-3"><strong>Tutorial Batch:</strong> <?= htmlspecialchars($record['batch']); ?></div>
                        <?php else: ?>
                            <div class="col-6 col-md-3"><strong>Batch(es):</strong> <?= htmlspecialchars($record['batch']); ?></div>
                            <div class="col-6 col-md-3"><strong>Lab(s):</strong> <?= htmlspecialchars($record['labNo']); ?></div>
                        <?php endif; ?>
                        <div class="col-6 col-md-3"><strong>Date:</strong> <?= htmlspecialchars($record['date']); ?></div>
                        <div class="col-6 col-md-3"><strong>Slot:</strong> <?= htmlspecialchars($record['time']); ?></div>
                        <div class="col-6 col-md-3"><strong>Log Date:</strong> <?= htmlspecialchars((string)$record['logdate']); ?></div>
                        <?php if ($type === 'lecture'): ?>
                            <div class="col-12 col-md-6">
                                <strong>Description:</strong>
                                <?= ($record['description'] !== '') ? htmlspecialchars($record['description']) : '<span class="text-muted">-</span>'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="app-card shadow-sm h-100">
                        <div class="app-card-body">
                            <h5>Total Present</h5>
                            <div class="display-6 mb-0"><?= count($present_students); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="app-card shadow-sm h-100">
                        <div class="app-card-body">
                            <h5><?= htmlspecialchars($group_label); ?> Wise</h5>
                            <?php if (!empty($group_counts)): ?>
                                <?php foreach ($group_counts as $group_name => $count): ?>
                                    <div><?= htmlspecialchars($group_name); ?>: <strong><?= (int)$count; ?></strong></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-muted">No present students marked.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="app-card shadow-sm h-100">
                        <div class="app-card-body">
                            <?php if ($type === 'lecture'): ?>
                                <h5>Total Absent</h5>
                                <div class="display-6 mb-0"><?= count($absent_enrollments); ?></div>
                            <?php else: ?>
                                <h5>Total PC Used</h5>
                            <?php endif; ?>
                            <?php if ($type === 'tutorial'): ?>
                                <div class="text-muted">Not applicable</div>
                            <?php elseif (!empty($pc_map)): ?>
                                <?php foreach ($pc_map as $pc_key => $pc_value): ?>
                                    <div><?= htmlspecialchars($pc_key); ?>: <strong><?= (int)$pc_value; ?></strong></div>
                                <?php endforeach; ?>
                                <hr>
                                <div>Total: <strong><?= (int)$pc_total; ?></strong></div>
                            <?php elseif ($type === 'lecture'): ?>
                                <div class="text-muted">From saved absent list.</div>
                            <?php else: ?>
                                <div class="text-muted">No PC usage data.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card shadow-sm">
                <div class="app-card-body">
                    <h4>Present Students</h4>
                    <?php if (!empty($present_students)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Enrollment No</th>
                                        <th>Name</th>
                                        <th><?= htmlspecialchars($group_label); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($present_students as $index => $student): ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= htmlspecialchars($student['enrollmentNo']); ?></td>
                                            <td><?= htmlspecialchars($student['name']); ?></td>
                                            <td><?= htmlspecialchars($student['group']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">No students marked present in this attendance entry.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
