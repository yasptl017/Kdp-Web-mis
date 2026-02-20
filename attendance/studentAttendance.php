<?php
include('dbconfig.php');

function parse_rows($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function normalize_subject($subject) {
    $subject = trim((string)$subject);
    return $subject === '' ? 'Unknown Subject' : $subject;
}

function add_subject_totals(&$summary, $rows, $mode) {
    foreach ($rows as $row) {
        $subject = normalize_subject($row['subject'] ?? '');
        if (!isset($summary[$subject])) {
            $summary[$subject] = [
                'subject' => $subject,
                'lecture_total' => 0,
                'lecture_present' => 0,
                'lab_total' => 0,
                'lab_present' => 0,
                'tutorial_total' => 0,
                'tutorial_present' => 0
            ];
        }

        $totalKey = "{$mode}_total";
        $presentKey = "{$mode}_present";
        $summary[$subject][$totalKey]++;
        if (strtoupper((string)($row['status'] ?? '')) === 'P') {
            $summary[$subject][$presentKey]++;
        }
    }
}

function percentage_display($present, $total) {
    if ((int)$total <= 0) {
        return '0.00%';
    }
    return number_format(($present / $total) * 100, 2) . '%';
}

$enrollment = trim((string)($_GET['enrollment'] ?? ''));
$msg = '';
$student = null;
$lectureRows = [];
$labRows = [];
$tutorialRows = [];
$summaryRows = [];
$overallTotals = [
    'lecture_total' => 0,
    'lecture_present' => 0,
    'lab_total' => 0,
    'lab_present' => 0,
    'tutorial_total' => 0,
    'tutorial_present' => 0
];

if ($enrollment !== '') {
    $studentStmt = $conn->prepare("SELECT id, enrollmentNo, name, term, sem, class, labBatch, tutBatch FROM students WHERE enrollmentNo = ? LIMIT 1");
    $studentStmt->bind_param('s', $enrollment);
    $studentStmt->execute();
    $studentRes = $studentStmt->get_result();
    $student = $studentRes->fetch_assoc();
    $studentStmt->close();

    if (!$student) {
        $msg = "Student not found for enrollment number: {$enrollment}";
    } else {
        $studentIdToken = (string)$student['id'];
        $term = (string)$student['term'];
        $sem = (string)$student['sem'];
        $class = (string)$student['class'];
        $labBatch = strtoupper(trim((string)$student['labBatch']));
        $tutBatch = strtoupper(trim((string)$student['tutBatch']));
        $semesterSubjects = [];

        $subStmt = $conn->prepare("SELECT DISTINCT subjectName FROM subjects WHERE sem = ? AND status = 1 ORDER BY subjectName ASC");
        $subStmt->bind_param('s', $sem);
        $subStmt->execute();
        $subRes = $subStmt->get_result();
        while ($subRow = $subRes->fetch_assoc()) {
            $semesterSubjects[] = normalize_subject($subRow['subjectName'] ?? '');
        }
        $subStmt->close();

        $lecSql = "SELECT subject,
                   CASE WHEN (FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0 OR FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0) THEN 'P' ELSE 'A' END AS status
                   FROM lecattendance
                   WHERE term = ? AND sem = ? AND class = ?
                   ORDER BY COALESCE(logdate, '0000-00-00') ASC, id ASC";
        $lecStmt = $conn->prepare($lecSql);
        $lecStmt->bind_param('sssss', $enrollment, $studentIdToken, $term, $sem, $class);
        $lectureRows = parse_rows($lecStmt);

        if ($labBatch !== '') {
            $labSql = "SELECT subject,
                       CASE WHEN (FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0 OR FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0) THEN 'P' ELSE 'A' END AS status
                       FROM labattendance
                       WHERE term = ? AND sem = ? AND COALESCE(TRIM(labNo), '') <> ''
                         AND FIND_IN_SET(?, REPLACE(UPPER(batch), ' ', '')) > 0
                       ORDER BY COALESCE(logdate, '0000-00-00') ASC, id ASC";
            $labStmt = $conn->prepare($labSql);
            $labStmt->bind_param('sssss', $enrollment, $studentIdToken, $term, $sem, $labBatch);
            $labRows = parse_rows($labStmt);
        }

        if ($tutBatch !== '') {
            $tutSql = "SELECT subject,
                       CASE WHEN (FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0 OR FIND_IN_SET(?, REPLACE(presentNo, ' ', '')) > 0) THEN 'P' ELSE 'A' END AS status
                       FROM labattendance
                       WHERE term = ? AND sem = ? AND COALESCE(TRIM(labNo), '') = ''
                         AND FIND_IN_SET(?, REPLACE(UPPER(batch), ' ', '')) > 0
                       ORDER BY COALESCE(logdate, '0000-00-00') ASC, id ASC";
            $tutStmt = $conn->prepare($tutSql);
            $tutStmt->bind_param('sssss', $enrollment, $studentIdToken, $term, $sem, $tutBatch);
            $tutorialRows = parse_rows($tutStmt);
        }

        $subjectSummary = [];
        add_subject_totals($subjectSummary, $lectureRows, 'lecture');
        add_subject_totals($subjectSummary, $labRows, 'lab');
        add_subject_totals($subjectSummary, $tutorialRows, 'tutorial');
        foreach ($semesterSubjects as $subjectName) {
            if (!isset($subjectSummary[$subjectName])) {
                $subjectSummary[$subjectName] = [
                    'subject' => $subjectName,
                    'lecture_total' => 0,
                    'lecture_present' => 0,
                    'lab_total' => 0,
                    'lab_present' => 0,
                    'tutorial_total' => 0,
                    'tutorial_present' => 0
                ];
            }
        }
        if (!empty($subjectSummary)) {
            ksort($subjectSummary, SORT_NATURAL | SORT_FLAG_CASE);
            $summaryRows = array_values($subjectSummary);
            foreach ($summaryRows as $summaryRow) {
                $overallTotals['lecture_total'] += $summaryRow['lecture_total'];
                $overallTotals['lecture_present'] += $summaryRow['lecture_present'];
                $overallTotals['lab_total'] += $summaryRow['lab_total'];
                $overallTotals['lab_present'] += $summaryRow['lab_present'];
                $overallTotals['tutorial_total'] += $summaryRow['tutorial_total'];
                $overallTotals['tutorial_present'] += $summaryRow['tutorial_present'];
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
            <h1 class="app-page-title"><i class="bi bi-search me-2"></i>Student Attendance Lookup</h1>

            <div class="app-card shadow-sm mb-3">
                <div class="app-card-body">
                    <form method="GET" action="studentAttendance.php">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label">Enrollment Number</label>
                                <input type="text" name="enrollment" class="form-control" value="<?= htmlspecialchars($enrollment); ?>" placeholder="Enter Enrollment No" required>
                            </div>
                            <div class="col-12 col-md-3 col-lg-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <?php if ($student): ?>
                <div class="app-card shadow-sm mb-3">
                    <div class="app-card-body">
                        <h4>Student Details</h4>
                        <div class="row g-2">
                            <div class="col-12 col-md-4"><strong>Name:</strong> <?= htmlspecialchars($student['name']); ?></div>
                            <div class="col-12 col-md-4"><strong>Enrollment:</strong> <?= htmlspecialchars($student['enrollmentNo']); ?></div>
                            <div class="col-12 col-md-4"><strong>Term:</strong> <?= htmlspecialchars($student['term']); ?></div>
                            <div class="col-12 col-md-4"><strong>Semester:</strong> <?= htmlspecialchars($student['sem']); ?></div>
                            <div class="col-12 col-md-4"><strong>Class:</strong> <?= htmlspecialchars($student['class']); ?></div>
                            <div class="col-12 col-md-4"><strong>Lab Batch:</strong> <?= htmlspecialchars($student['labBatch']); ?></div>
                            <div class="col-12 col-md-4"><strong>Tutorial Batch:</strong> <?= htmlspecialchars($student['tutBatch']); ?></div>
                        </div>
                    </div>
                </div>

                <div class="app-card shadow-sm mb-3">
                    <div class="app-card-body">
                        <h4>Attendance Summary (Subject-wise)</h4>
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-2">
                                    <strong>Lecture:</strong>
                                    <?= (int)$overallTotals['lecture_present']; ?>/<?= (int)$overallTotals['lecture_total']; ?>
                                    (<?= percentage_display($overallTotals['lecture_present'], $overallTotals['lecture_total']); ?>)
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-2">
                                    <strong>Lab:</strong>
                                    <?= (int)$overallTotals['lab_present']; ?>/<?= (int)$overallTotals['lab_total']; ?>
                                    (<?= percentage_display($overallTotals['lab_present'], $overallTotals['lab_total']); ?>)
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="border rounded p-2">
                                    <strong>Tutorial:</strong>
                                    <?= (int)$overallTotals['tutorial_present']; ?>/<?= (int)$overallTotals['tutorial_total']; ?>
                                    (<?= percentage_display($overallTotals['tutorial_present'], $overallTotals['tutorial_total']); ?>)
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Lecture (P/T)</th>
                                        <th>Lecture %</th>
                                        <th>Lab (P/T)</th>
                                        <th>Lab %</th>
                                        <th>Tutorial (P/T)</th>
                                        <th>Tutorial %</th>
                                        <th>Total (P/T)</th>
                                        <th>Total %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($summaryRows)): ?>
                                        <?php foreach ($summaryRows as $row): ?>
                                            <?php
                                                $subjectPresent = (int)$row['lecture_present'] + (int)$row['lab_present'] + (int)$row['tutorial_present'];
                                                $subjectTotal = (int)$row['lecture_total'] + (int)$row['lab_total'] + (int)$row['tutorial_total'];
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['subject']); ?></td>
                                                <td><?= (int)$row['lecture_present']; ?>/<?= (int)$row['lecture_total']; ?></td>
                                                <td><?= percentage_display($row['lecture_present'], $row['lecture_total']); ?></td>
                                                <td><?= (int)$row['lab_present']; ?>/<?= (int)$row['lab_total']; ?></td>
                                                <td><?= percentage_display($row['lab_present'], $row['lab_total']); ?></td>
                                                <td><?= (int)$row['tutorial_present']; ?>/<?= (int)$row['tutorial_total']; ?></td>
                                                <td><?= percentage_display($row['tutorial_present'], $row['tutorial_total']); ?></td>
                                                <td><?= $subjectPresent; ?>/<?= $subjectTotal; ?></td>
                                                <td><?= percentage_display($subjectPresent, $subjectTotal); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center text-muted">No attendance records found for this student.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
