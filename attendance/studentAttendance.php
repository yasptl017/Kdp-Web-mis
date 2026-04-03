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

function empty_subject_totals($subject) {
    return [
        'subject' => $subject,
        'lecture_total' => 0,
        'lecture_present' => 0,
        'lab_total' => 0,
        'lab_present' => 0,
        'tutorial_total' => 0,
        'tutorial_present' => 0
    ];
}

function add_subject_totals(&$summary, $rows, $mode) {
    foreach ($rows as $row) {
        $subject = normalize_subject($row['subject'] ?? '');
        if (!isset($summary[$subject])) {
            $summary[$subject] = empty_subject_totals($subject);
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

function compare_terms_desc($left, $right) {
    return strnatcmp((string)$right, (string)$left);
}

function build_term_summary(mysqli $conn, array $studentRecord) {
    $enrollment = (string)$studentRecord['enrollmentNo'];
    $studentIdToken = (string)$studentRecord['id'];
    $term = (string)$studentRecord['term'];
    $sem = (string)$studentRecord['sem'];
    $class = (string)$studentRecord['class'];
    $labBatch = strtoupper(trim((string)$studentRecord['labBatch']));
    $tutBatch = strtoupper(trim((string)$studentRecord['tutBatch']));

    $semesterSubjects = [];
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
            $subjectSummary[$subjectName] = empty_subject_totals($subjectName);
        }
    }

    if (!empty($subjectSummary)) {
        ksort($subjectSummary, SORT_NATURAL | SORT_FLAG_CASE);
        $summaryRows = array_values($subjectSummary);
        foreach ($summaryRows as $summaryRow) {
            $overallTotals['lecture_total'] += (int)$summaryRow['lecture_total'];
            $overallTotals['lecture_present'] += (int)$summaryRow['lecture_present'];
            $overallTotals['lab_total'] += (int)$summaryRow['lab_total'];
            $overallTotals['lab_present'] += (int)$summaryRow['lab_present'];
            $overallTotals['tutorial_total'] += (int)$summaryRow['tutorial_total'];
            $overallTotals['tutorial_present'] += (int)$summaryRow['tutorial_present'];
        }
    }

    return [
        'term' => $term,
        'sem' => $sem,
        'class' => $class,
        'labBatch' => (string)$studentRecord['labBatch'],
        'tutBatch' => (string)$studentRecord['tutBatch'],
        'summaryRows' => $summaryRows,
        'overallTotals' => $overallTotals
    ];
}

$enrollment = trim((string)($_GET['enrollment'] ?? ''));
$msg = '';
$student = null;
$studentRecords = [];
$termSummaries = [];
$availableTerms = [];

if ($enrollment !== '') {
    $studentStmt = $conn->prepare("SELECT id, enrollmentNo, name, term, sem, class, labBatch, tutBatch FROM students WHERE enrollmentNo = ? ORDER BY id DESC");
    $studentStmt->bind_param('s', $enrollment);
    $studentStmt->execute();
    $studentRes = $studentStmt->get_result();
    while ($studentRow = $studentRes->fetch_assoc()) {
        $studentRecords[] = $studentRow;
    }
    $studentStmt->close();

    if (empty($studentRecords)) {
        $msg = "Student not found for enrollment number: {$enrollment}";
    } else {
        usort($studentRecords, function ($left, $right) {
            $termCompare = compare_terms_desc($left['term'] ?? '', $right['term'] ?? '');
            if ($termCompare !== 0) {
                return $termCompare;
            }
            return strnatcmp((string)($right['sem'] ?? ''), (string)($left['sem'] ?? ''));
        });

        $student = $studentRecords[0];
        foreach ($studentRecords as $studentRecord) {
            $termLabel = trim((string)($studentRecord['term'] ?? ''));
            if ($termLabel !== '') {
                $availableTerms[$termLabel] = true;
            }
            $termSummaries[] = build_term_summary($conn, $studentRecord);
        }

        $availableTerms = array_keys($availableTerms);
        usort($availableTerms, 'compare_terms_desc');
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
                            <div class="col-12 col-md-4"><strong>Latest Term:</strong> <?= htmlspecialchars($student['term']); ?></div>
                            <div class="col-12 col-md-4"><strong>Latest Semester:</strong> <?= htmlspecialchars($student['sem']); ?></div>
                            <div class="col-12 col-md-4"><strong>Term Records:</strong> <?= count($termSummaries); ?></div>
                            <div class="col-12">
                                <strong>Available Terms:</strong>
                                <?= !empty($availableTerms) ? htmlspecialchars(implode(', ', $availableTerms)) : 'None'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php foreach ($termSummaries as $termSummary): ?>
                    <?php $overallTotals = $termSummary['overallTotals']; ?>
                    <div class="app-card shadow-sm mb-3">
                        <div class="app-card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                                <div>
                                    <h4 class="mb-1">Attendance Summary - Term <?= htmlspecialchars($termSummary['term']); ?></h4>
                                    <div class="text-muted small">
                                        Semester <?= htmlspecialchars($termSummary['sem']); ?> |
                                        Class <?= htmlspecialchars($termSummary['class']); ?> |
                                        Lab Batch <?= htmlspecialchars((string)$termSummary['labBatch'] === '' ? '-' : $termSummary['labBatch']); ?> |
                                        Tutorial Batch <?= htmlspecialchars((string)$termSummary['tutBatch'] === '' ? '-' : $termSummary['tutBatch']); ?>
                                    </div>
                                </div>
                            </div>
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
                                        <?php if (!empty($termSummary['summaryRows'])): ?>
                                            <?php foreach ($termSummary['summaryRows'] as $row): ?>
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
                                            <tr><td colspan="9" class="text-center text-muted">No attendance records found for term <?= htmlspecialchars($termSummary['term']); ?>.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
