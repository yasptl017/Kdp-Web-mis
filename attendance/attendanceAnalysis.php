<?php
include('dbconfig.php');

function csv_tokens($value) {
    $items = array_map('trim', explode(',', (string)$value));
    return array_values(array_filter($items, function ($item) {
        return $item !== '';
    }));
}

function normalize_tokens($value) {
    $raw = is_array($value) ? $value : csv_tokens($value);
    $out = [];
    foreach ($raw as $token) {
        $token = strtoupper(trim((string)$token));
        if ($token !== '' && !in_array($token, $out, true)) {
            $out[] = $token;
        }
    }
    return $out;
}

function parse_attendance_date($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $formats = ['Y-m-d', 'n/j/Y', 'm/d/Y', 'j/n/Y', 'd/m/Y', 'd-m-Y', 'j-m-Y'];
    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        $ok = $dt && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
        if ($ok) {
            return $dt;
        }
    }

    $ts = strtotime($value);
    if ($ts !== false) {
        return (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return null;
}

function percent_value($present, $total) {
    if ((int)$total <= 0) {
        return null;
    }
    return ((int)$present * 100) / (int)$total;
}

function percent_display($value) {
    if ($value === null) {
        return '-';
    }
    return number_format((float)$value, 2) . '%';
}

function present_enrollment_set($presentNo, $idToEnrollment) {
    $set = [];
    foreach (csv_tokens($presentNo) as $token) {
        if (isset($idToEnrollment[$token])) {
            $token = $idToEnrollment[$token];
        }
        $token = trim((string)$token);
        if ($token !== '') {
            $set[$token] = true;
        }
    }
    return $set;
}

$sem = trim((string)($_GET['sem'] ?? ''));
$class = strtoupper(trim((string)($_GET['class'] ?? '')));
$start_date = trim((string)($_GET['start_date'] ?? ''));
$end_date = trim((string)($_GET['end_date'] ?? ''));

$msg = '';
$rows = [];
$students_count = 0;

$sem_result = $conn->query("SELECT sem FROM semester WHERE status = 1 ORDER BY sem");
$allowed_classes = ['A', 'B', 'C', 'D'];

$hasFilterInput = ($sem !== '' || $class !== '' || $start_date !== '' || $end_date !== '');
if ($hasFilterInput) {
    if ($sem === '' || $class === '' || $start_date === '' || $end_date === '') {
        $msg = 'Please select Semester, Class, Start Date, and End Date.';
    } elseif (!in_array($class, $allowed_classes, true)) {
        $msg = 'Invalid class selected.';
    } else {
        $startDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $start_date);
        $endDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $end_date);

        if (!$startDateObj || !$endDateObj || $startDateObj > $endDateObj) {
            $msg = 'Please provide a valid date range.';
        } else {
            $students = [];
            $studentOrder = [];
            $studentStats = [];
            $idToEnrollment = [];
            $labStudentsByBatch = [];
            $tutStudentsByBatch = [];

            $stuStmt = $conn->prepare("SELECT id, enrollmentNo, name, class, labBatch, tutBatch FROM students WHERE sem = ? AND class = ? ORDER BY enrollmentNo, name");
            $stuStmt->bind_param('ss', $sem, $class);
            $stuStmt->execute();
            $stuRes = $stuStmt->get_result();
            while ($student = $stuRes->fetch_assoc()) {
                $enrollment = trim((string)$student['enrollmentNo']);
                if ($enrollment === '') {
                    continue;
                }

                $students[$enrollment] = [
                    'enrollment' => $enrollment,
                    'name' => trim((string)$student['name']),
                    'class' => trim((string)$student['class']),
                    'labBatch' => strtoupper(trim((string)$student['labBatch'])),
                    'tutBatch' => strtoupper(trim((string)$student['tutBatch'])),
                ];
                $studentOrder[] = $enrollment;
                $studentStats[$enrollment] = [
                    'lec_total' => 0,
                    'lec_present' => 0,
                    'lab_total' => 0,
                    'lab_present' => 0,
                    'tut_total' => 0,
                    'tut_present' => 0,
                ];

                $id = trim((string)$student['id']);
                if ($id !== '') {
                    $idToEnrollment[$id] = $enrollment;
                }

                if ($students[$enrollment]['labBatch'] !== '') {
                    if (!isset($labStudentsByBatch[$students[$enrollment]['labBatch']])) {
                        $labStudentsByBatch[$students[$enrollment]['labBatch']] = [];
                    }
                    $labStudentsByBatch[$students[$enrollment]['labBatch']][] = $enrollment;
                }

                if ($students[$enrollment]['tutBatch'] !== '') {
                    if (!isset($tutStudentsByBatch[$students[$enrollment]['tutBatch']])) {
                        $tutStudentsByBatch[$students[$enrollment]['tutBatch']] = [];
                    }
                    $tutStudentsByBatch[$students[$enrollment]['tutBatch']][] = $enrollment;
                }
            }
            $stuStmt->close();

            $students_count = count($studentOrder);
            if ($students_count === 0) {
                $msg = 'No students found for selected semester and class.';
            } else {
                $lecStmt = $conn->prepare("SELECT date, presentNo FROM lecattendance WHERE sem = ? AND class = ? ORDER BY id ASC");
                $lecStmt->bind_param('ss', $sem, $class);
                $lecStmt->execute();
                $lecRes = $lecStmt->get_result();
                while ($lec = $lecRes->fetch_assoc()) {
                    $sessionDate = parse_attendance_date($lec['date'] ?? '');
                    if (!$sessionDate || $sessionDate < $startDateObj || $sessionDate > $endDateObj) {
                        continue;
                    }

                    $presentSet = present_enrollment_set($lec['presentNo'] ?? '', $idToEnrollment);
                    foreach ($studentOrder as $enrollment) {
                        $studentStats[$enrollment]['lec_total']++;
                        if (isset($presentSet[$enrollment])) {
                            $studentStats[$enrollment]['lec_present']++;
                        }
                    }
                }
                $lecStmt->close();

                $labStmt = $conn->prepare("SELECT date, batch, presentNo FROM labattendance WHERE sem = ? AND COALESCE(TRIM(labNo), '') <> '' ORDER BY id ASC");
                $labStmt->bind_param('s', $sem);
                $labStmt->execute();
                $labRes = $labStmt->get_result();
                while ($lab = $labRes->fetch_assoc()) {
                    $sessionDate = parse_attendance_date($lab['date'] ?? '');
                    if (!$sessionDate || $sessionDate < $startDateObj || $sessionDate > $endDateObj) {
                        continue;
                    }

                    $rowBatches = normalize_tokens($lab['batch'] ?? '');
                    if (empty($rowBatches)) {
                        continue;
                    }

                    $presentSet = present_enrollment_set($lab['presentNo'] ?? '', $idToEnrollment);
                    foreach ($rowBatches as $batch) {
                        if (!isset($labStudentsByBatch[$batch])) {
                            continue;
                        }
                        foreach ($labStudentsByBatch[$batch] as $enrollment) {
                            $studentStats[$enrollment]['lab_total']++;
                            if (isset($presentSet[$enrollment])) {
                                $studentStats[$enrollment]['lab_present']++;
                            }
                        }
                    }
                }
                $labStmt->close();

                $tutStmt = $conn->prepare("SELECT date, batch, presentNo FROM labattendance WHERE sem = ? AND COALESCE(TRIM(labNo), '') = '' ORDER BY id ASC");
                $tutStmt->bind_param('s', $sem);
                $tutStmt->execute();
                $tutRes = $tutStmt->get_result();
                while ($tut = $tutRes->fetch_assoc()) {
                    $sessionDate = parse_attendance_date($tut['date'] ?? '');
                    if (!$sessionDate || $sessionDate < $startDateObj || $sessionDate > $endDateObj) {
                        continue;
                    }

                    $rowBatches = normalize_tokens($tut['batch'] ?? '');
                    if (empty($rowBatches)) {
                        continue;
                    }

                    $presentSet = present_enrollment_set($tut['presentNo'] ?? '', $idToEnrollment);
                    foreach ($rowBatches as $batch) {
                        if (!isset($tutStudentsByBatch[$batch])) {
                            continue;
                        }
                        foreach ($tutStudentsByBatch[$batch] as $enrollment) {
                            $studentStats[$enrollment]['tut_total']++;
                            if (isset($presentSet[$enrollment])) {
                                $studentStats[$enrollment]['tut_present']++;
                            }
                        }
                    }
                }
                $tutStmt->close();

                foreach ($studentOrder as $enrollment) {
                    $s = $students[$enrollment];
                    $st = $studentStats[$enrollment];

                    $lecPct = percent_value($st['lec_present'], $st['lec_total']);
                    $labPct = percent_value($st['lab_present'], $st['lab_total']);
                    $tutPct = percent_value($st['tut_present'], $st['tut_total']);

                    $totalPresent = $st['lec_present'] + $st['lab_present'] + $st['tut_present'];
                    $totalConducted = $st['lec_total'] + $st['lab_total'] + $st['tut_total'];
                    $totalPct = percent_value($totalPresent, $totalConducted);

                    $rows[] = [
                        'enrollment' => $s['enrollment'],
                        'name' => $s['name'],
                        'class' => $s['class'],
                        'lec_pct' => $lecPct,
                        'lab_pct' => $labPct,
                        'tut_pct' => $tutPct,
                        'total_pct' => $totalPct,
                    ];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-bar-chart-line me-2"></i>Attendance Analysis</h1>

            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <form method="GET" action="attendanceAnalysis.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label">Semester</label>
                                <select name="sem" class="form-control" required>
                                    <option value="">Select Semester</option>
                                    <?php while ($semRow = $sem_result->fetch_assoc()) { ?>
                                        <option value="<?= htmlspecialchars($semRow['sem']); ?>" <?= ((string)$semRow['sem'] === $sem) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($semRow['sem']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($allowed_classes as $classOption) { ?>
                                        <option value="<?= $classOption; ?>" <?= ($classOption === $class) ? 'selected' : ''; ?>><?= $classOption; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date); ?>" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date); ?>" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-graph-up me-1"></i>Generate Analysis
                                </button>
                            </div>
                        </div>
                    </form>
                    <p class="text-muted mb-0 mt-3" style="font-size:0.85rem;">
                        Total attendance % is calculated from combined conducted sessions (lecture + lab + tutorial). Components with zero conducted sessions are not treated as 0%.
                    </p>
                </div>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <?php if (!empty($rows)): ?>
                <div class="app-card shadow-sm">
                    <div class="app-card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <h4 class="mb-0">Result</h4>
                            <span class="text-muted" style="font-size:0.875rem;">Students: <?= (int)$students_count; ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Enroll</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Lab Attendance %</th>
                                        <th>Lecture Attendance %</th>
                                        <th>Tutorial Attendance %</th>
                                        <th>Total Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['enrollment']); ?></td>
                                            <td><?= htmlspecialchars($row['name']); ?></td>
                                            <td><?= htmlspecialchars($row['class']); ?></td>
                                            <td><?= htmlspecialchars(percent_display($row['lab_pct'])); ?></td>
                                            <td><?= htmlspecialchars(percent_display($row['lec_pct'])); ?></td>
                                            <td><?= htmlspecialchars(percent_display($row['tut_pct'])); ?></td>
                                            <td><strong><?= htmlspecialchars(percent_display($row['total_pct'])); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($hasFilterInput && $msg === ''): ?>
                <div class="alert alert-info">No attendance records found in selected range.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
