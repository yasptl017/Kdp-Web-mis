<?php
include('dbconfig.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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

function bind_dynamic_params($stmt, $types, $params) {
    $bindParams = [$types];
    foreach ($params as $index => $param) {
        $bindParams[] = &$params[$index];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bindParams);
}

function parse_pc_used_map($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return [];
    }

    $map = [];
    foreach (csv_tokens($value) as $entry) {
        if (strpos($entry, ':') !== false) {
            [$key, $count] = array_map('trim', explode(':', $entry, 2));
            $key = strtoupper($key);
            if ($key !== '' && $count !== '') {
                $map[$key] = max(0, (int)$count);
            }
        } else {
            $map['TOTAL'] = max(0, (int)$entry);
        }
    }
    return $map;
}

function parse_batch_lab_map($labNoRaw, array $batches) {
    $map = [];
    $labsWithoutBatch = [];

    foreach (csv_tokens($labNoRaw) as $part) {
        if (strpos($part, ':') !== false) {
            [$left, $right] = array_map('trim', explode(':', $part, 2));
            $left = strtoupper($left);
            $right = strtoupper($right);
            if ($left !== '' && $right !== '') {
                $map[$left] = $right;
            }
        } else {
            $lab = strtoupper(trim((string)$part));
            if ($lab !== '') {
                $labsWithoutBatch[] = $lab;
            }
        }
    }

    if (!empty($labsWithoutBatch)) {
        if (count($batches) === 1) {
            $map[$batches[0]] = $labsWithoutBatch[0];
        } elseif (count($labsWithoutBatch) === count($batches)) {
            foreach ($batches as $index => $batch) {
                $map[$batch] = $labsWithoutBatch[$index];
            }
        }
    }

    return $map;
}

function extract_lab_names($labNoRaw) {
    $labs = [];
    foreach (csv_tokens($labNoRaw) as $part) {
        if (strpos($part, ':') !== false) {
            [, $lab] = array_map('trim', explode(':', $part, 2));
        } else {
            $lab = trim((string)$part);
        }
        $lab = strtoupper($lab);
        if ($lab !== '' && !in_array($lab, $labs, true)) {
            $labs[] = $lab;
        }
    }
    return $labs;
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

function safe_sheet_title($baseTitle, array &$usedTitles) {
    $title = preg_replace('/[\\\\\/?*\[\]:]/', '-', (string)$baseTitle);
    $title = trim($title);
    if ($title === '') {
        $title = 'Sheet';
    }

    $title = mb_substr($title, 0, 31);
    $candidate = $title;
    $i = 1;

    while (in_array($candidate, $usedTitles, true)) {
        $suffix = '-' . $i;
        $candidate = mb_substr($title, 0, 31 - mb_strlen($suffix)) . $suffix;
        $i++;
    }

    $usedTitles[] = $candidate;
    return $candidate;
}

function write_muster_sheet($sheet, $students, $sessions, $term, $sem, $batch, $subjectDisplay, $partNo, $partTotal) {
    $totalCols = max(2, 2 + count($sessions));
    $lastCol = Coordinate::stringFromColumnIndex($totalCols);

    $sheet->mergeCells("A1:{$lastCol}1");
    $sheet->setCellValue('A1', 'K. D. POLYTECHNIC, PATAN');
    $sheet->mergeCells("A2:{$lastCol}2");
    $sheet->setCellValue('A2', 'Department of Computer Engineering');
    $sheet->mergeCells("A3:{$lastCol}3");
    $sheet->setCellValue('A3', 'Faculty Name: __________________________    Subject Name (Subject Code): ' . $subjectDisplay);
    $sheet->mergeCells("A4:{$lastCol}4");
    $sheet->setCellValue('A4', 'Term: ' . $term . '    W.E.F.: ____________________');
    $sheet->mergeCells("A5:{$lastCol}5");
    $partText = ($partTotal > 1) ? ('    Part: ' . $partNo . '/' . $partTotal) : '';
    $sheet->setCellValue('A5', 'Semester: ' . $sem . ' - Batch: ' . $batch . $partText);

    $headerRow = 6;
    $sheet->setCellValue('A' . $headerRow, 'Enrollment');
    $sheet->setCellValue('B' . $headerRow, 'Name');

    foreach ($sessions as $index => $session) {
        $col = $index + 3;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $headerRow, $session['date_label']);
    }

    $row = $headerRow + 1;
    foreach ($students as $student) {
        $enrollment = $student['enrollmentNo'];
        $sheet->setCellValue("A{$row}", $enrollment);
        $sheet->setCellValue("B{$row}", $student['name']);

        foreach ($sessions as $index => $session) {
            $col = $index + 3;
            $present = isset($session['present_set'][$enrollment]);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $present ? 'P' : 'A');
        }
        $row++;
    }

    $lastDataRow = max($headerRow, $row - 1);

    $sheet->getStyle("A1:{$lastCol}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A1:{$lastCol}5")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A2")->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle("A3:{$lastCol}5")->getFont()->setBold(true);
    $sheet->getStyle("A1:{$lastCol}5")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
    $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
    $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    if ($totalCols >= 3) {
        $sheet->getStyle("C{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()->setWrapText(true);
    }

    $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$headerRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B{$headerRow}:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    if ($totalCols >= 3) {
        $sheet->getStyle("C{$headerRow}:{$lastCol}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    $sheet->getColumnDimension('A')->setWidth(16);
    $sheet->getColumnDimension('B')->setWidth(30);
    for ($colIndex = 3; $colIndex <= $totalCols; $colIndex++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(11);
    }

    $sheet->getRowDimension($headerRow)->setRowHeight(32);
    $sheet->freezePane('C7');

    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageSetup()->setHorizontalCentered(true);
    $sheet->getPageSetup()->setVerticalCentered(false);
}

function write_lab_utilization_sheet($sheet, $rows, $filters) {
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'Lab Utilization Report');
    $sheet->setCellValue('A2', 'Date Range: ' . $filters['start_label'] . ' to ' . $filters['end_label']);
    $sheet->setCellValue('A3', 'Lab No: ' . $filters['lab_label']);

    $headerRow = 5;
    $sheet->setCellValue("A{$headerRow}", 'Date');
    $sheet->setCellValue("B{$headerRow}", 'Slot');
    $sheet->setCellValue("C{$headerRow}", 'Sem');
    $sheet->setCellValue("D{$headerRow}", 'Subject');
    $sheet->setCellValue("E{$headerRow}", 'Lab Batch');
    $sheet->setCellValue("F{$headerRow}", 'Total Student Present');
    $sheet->setCellValue("G{$headerRow}", 'Total PC Used');

    $rowNum = $headerRow + 1;
    foreach ($rows as $row) {
        $sheet->setCellValue("A{$rowNum}", $row['date']);
        $sheet->setCellValue("B{$rowNum}", $row['slot']);
        $sheet->setCellValue("C{$rowNum}", $row['sem']);
        $sheet->setCellValue("D{$rowNum}", $row['subject']);
        $sheet->setCellValue("E{$rowNum}", $row['batch']);
        $sheet->setCellValue("F{$rowNum}", (int)$row['present_count']);
        $sheet->setCellValue("G{$rowNum}", (int)$row['pc_used']);
        $rowNum++;
    }

    if ($rowNum === $headerRow + 1) {
        $sheet->mergeCells("A{$rowNum}:G{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'No lab utilization data found for selected filters.');
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $rowNum++;
    }

    $lastDataRow = $rowNum - 1;
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A3')->getFont()->setBold(true);
    $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFont()->setBold(true);
    $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
    $sheet->getStyle("A{$headerRow}:G{$lastDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$headerRow}:G{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A{$headerRow}:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("E{$headerRow}:G{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(18);
    $sheet->getColumnDimension('C')->setWidth(8);
    $sheet->getColumnDimension('D')->setWidth(26);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(22);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->freezePane('A6');

    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->getPageSetup()->setHorizontalCentered(true);
}

function selected_attr($data, $key, $value) {
    return (isset($data[$key]) && (string)$data[$key] === (string)$value) ? 'selected' : '';
}

$msg = '';
$msgType = 'warning';
$formData = $_POST;

// Fetch dropdown data
$faculty_result = $conn->query("SELECT id, Name FROM faculty WHERE status = 1 ORDER BY Name");
$term_result = $conn->query("SELECT DISTINCT term FROM students ORDER BY term DESC");
$sem_result = $conn->query("SELECT sem FROM semester WHERE status = 1 ORDER BY sem");
$subject_result = $conn->query("SELECT DISTINCT subjectName FROM subjects WHERE status = 1 ORDER BY subjectName");
$lab_no_options = [];
$labNoResult = $conn->query("SELECT DISTINCT labNo FROM labattendance WHERE COALESCE(TRIM(labNo), '') <> ''");
if ($labNoResult) {
    while ($labNoRow = $labNoResult->fetch_assoc()) {
        foreach (extract_lab_names($labNoRow['labNo'] ?? '') as $labName) {
            if (!in_array($labName, $lab_no_options, true)) {
                $lab_no_options[] = $labName;
            }
        }
    }
}
usort($lab_no_options, 'strnatcasecmp');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strtolower(trim((string)($_POST['report_mode'] ?? 'muster'))) === 'lab_utilization') {
    $lu_lab_no = strtoupper(trim((string)($_POST['lu_lab_no'] ?? '')));
    $lu_start_date = trim((string)($_POST['lu_start_date'] ?? ''));
    $lu_end_date = trim((string)($_POST['lu_end_date'] ?? ''));

    if ($lu_lab_no === '' || $lu_start_date === '' || $lu_end_date === '') {
        $msg = 'Please fill Lab No, Start Date, and End Date for lab utilization report.';
    } else {
        $startDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $lu_start_date);
        $endDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $lu_end_date);
        if (!$startDateObj || !$endDateObj || $startDateObj > $endDateObj) {
            $msg = 'Please provide a valid date range for lab utilization report.';
        } else {
            $idToEnrollment = [];
            $batchBySemEnrollment = [];
            $batchByEnrollment = [];
            $stuStmt = $conn->prepare("SELECT id, enrollmentNo, sem, UPPER(TRIM(labBatch)) AS labBatch FROM students");
            $stuStmt->execute();
            $stuRes = $stuStmt->get_result();
            while ($student = $stuRes->fetch_assoc()) {
                $enrollment = trim((string)($student['enrollmentNo'] ?? ''));
                $studentSem = trim((string)($student['sem'] ?? ''));
                $batch = strtoupper(trim((string)($student['labBatch'] ?? '')));
                if ($enrollment !== '') {
                    $batchBySemEnrollment[$studentSem . '|' . $enrollment] = $batch;
                    if (!isset($batchByEnrollment[$enrollment])) {
                        $batchByEnrollment[$enrollment] = $batch;
                    }
                }
                $studentId = trim((string)($student['id'] ?? ''));
                if ($studentId !== '' && $enrollment !== '') {
                    $idToEnrollment[$studentId] = $enrollment;
                }
            }
            $stuStmt->close();

            $sql = "SELECT id, date, time, sem, subject, batch, labNo, presentNo, totalPcUsed
                    FROM labattendance
                    WHERE COALESCE(TRIM(labNo), '') <> ''
                      AND UPPER(labNo) LIKE ?";
            $types = 's';
            $params = ['%' . $lu_lab_no . '%'];
            $sql .= " ORDER BY id ASC";

            $attStmt = $conn->prepare($sql);
            if (!$attStmt) {
                $msg = 'Unable to prepare lab utilization query.';
            } else {
                bind_dynamic_params($attStmt, $types, $params);
                $attStmt->execute();
                $attRes = $attStmt->get_result();
                $utilRows = [];

                while ($att = $attRes->fetch_assoc()) {
                    $sessionDate = parse_attendance_date($att['date'] ?? '');
                    if (!$sessionDate || $sessionDate < $startDateObj || $sessionDate > $endDateObj) {
                        continue;
                    }

                    $rowBatches = normalize_tokens($att['batch'] ?? '');
                    if (empty($rowBatches)) {
                        $single = strtoupper(trim((string)($att['batch'] ?? '')));
                        if ($single !== '') {
                            $rowBatches = [$single];
                        }
                    }
                    if (empty($rowBatches)) {
                        $rowBatches = ['-'];
                    }

                    $batchLabMap = parse_batch_lab_map($att['labNo'] ?? '', $rowBatches);
                    $targetBatches = [];
                    if (!empty($batchLabMap)) {
                        foreach ($batchLabMap as $mappedBatch => $mappedLab) {
                            if (strtoupper(trim((string)$mappedLab)) === $lu_lab_no) {
                                $targetBatches[] = strtoupper(trim((string)$mappedBatch));
                            }
                        }
                    } else {
                        $rawLabs = extract_lab_names($att['labNo'] ?? '');
                        if (in_array($lu_lab_no, $rawLabs, true)) {
                            $targetBatches = $rowBatches;
                        }
                    }
                    $targetBatches = array_values(array_unique(array_filter($targetBatches)));
                    if (empty($targetBatches)) {
                        continue;
                    }

                    $pcMap = parse_pc_used_map($att['totalPcUsed'] ?? '');

                    $presentEnrollmentSet = [];
                    foreach (csv_tokens($att['presentNo'] ?? '') as $token) {
                        if (isset($idToEnrollment[$token])) {
                            $token = $idToEnrollment[$token];
                        }
                        $token = trim((string)$token);
                        if ($token !== '') {
                            $presentEnrollmentSet[$token] = true;
                        }
                    }

                    $presentByBatch = [];
                    foreach (array_keys($presentEnrollmentSet) as $enrollment) {
                        $studentSem = trim((string)($att['sem'] ?? ''));
                        $studentBatch = strtoupper(trim((string)($batchBySemEnrollment[$studentSem . '|' . $enrollment] ?? ($batchByEnrollment[$enrollment] ?? ''))));
                        if (!isset($presentByBatch[$studentBatch])) {
                            $presentByBatch[$studentBatch] = 0;
                        }
                        $presentByBatch[$studentBatch]++;
                    }

                    foreach ($targetBatches as $rowBatch) {
                        $presentCount = (int)($presentByBatch[$rowBatch] ?? 0);
                        if ($presentCount === 0 && count($targetBatches) === 1) {
                            $presentCount = count($presentEnrollmentSet);
                        }

                        $pcUsed = 0;
                        if (isset($pcMap[$lu_lab_no])) {
                            $pcUsed = (int)$pcMap[$lu_lab_no];
                        } elseif (isset($pcMap[$rowBatch])) {
                            $pcUsed = (int)$pcMap[$rowBatch];
                        } elseif (count($pcMap) === 1) {
                            $pcUsed = (int)reset($pcMap);
                        }

                        $utilRows[] = [
                            'sort_date' => $sessionDate->format('Y-m-d'),
                            'sort_slot' => trim((string)($att['time'] ?? '')),
                            'sort_id' => (int)$att['id'],
                            'date' => $sessionDate->format('d-m-y'),
                            'slot' => trim((string)($att['time'] ?? '')),
                            'sem' => trim((string)($att['sem'] ?? '')),
                            'subject' => trim((string)($att['subject'] ?? '')),
                            'batch' => $rowBatch,
                            'present_count' => $presentCount,
                            'pc_used' => $pcUsed,
                        ];
                    }
                }
                $attStmt->close();

                usort($utilRows, function ($a, $b) {
                    $cmp = strcmp($a['sort_date'], $b['sort_date']);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                    $cmp = strcmp($a['sort_slot'], $b['sort_slot']);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                    return $a['sort_id'] <=> $b['sort_id'];
                });
                foreach ($utilRows as &$row) {
                    unset($row['sort_date'], $row['sort_slot'], $row['sort_id']);
                }
                unset($row);

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Lab Utilization');

                write_lab_utilization_sheet($sheet, $utilRows, [
                    'start_label' => $startDateObj->format('d-m-y'),
                    'end_label' => $endDateObj->format('d-m-y'),
                    'lab_label' => $lu_lab_no,
                ]);

                $filename = 'lab_utilization_' . $lu_lab_no . '_' . $lu_start_date . '_to_' . $lu_end_date . '.xlsx';
                $filename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strtolower(trim((string)($_POST['report_mode'] ?? 'muster'))) !== 'lab_utilization') {
    $attendance_type = strtolower(trim((string)($_POST['attendance_type'] ?? 'lecture')));
    if (!in_array($attendance_type, ['lecture', 'lab', 'tutorial'], true)) {
        $attendance_type = 'lecture';
    }

    $faculty_id = trim((string)($_POST['faculty'] ?? ''));
    $term = trim((string)($_POST['term'] ?? ''));
    $sem = trim((string)($_POST['sem'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $class = trim((string)($_POST['class'] ?? ''));
    $start_date = trim((string)($_POST['start_date'] ?? ''));
    $end_date = trim((string)($_POST['end_date'] ?? ''));

    $requiredValid = $faculty_id !== '' && $term !== '' && $sem !== '' && $subject !== '' && $class !== '' && $start_date !== '' && $end_date !== '';
    if (!$requiredValid) {
        $msg = 'Please fill all required fields.';
    } else {
        $startDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $start_date);
        $endDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $end_date);

        if (!$startDateObj || !$endDateObj || $startDateObj > $endDateObj) {
            $msg = 'Please provide a valid date range.';
        } else {
            $faculty_name = '';
            $facultyStmt = $conn->prepare("SELECT Name FROM faculty WHERE id = ?");
            $facultyStmt->bind_param('s', $faculty_id);
            $facultyStmt->execute();
            $facultyRes = $facultyStmt->get_result();
            if ($frow = $facultyRes->fetch_assoc()) {
                $faculty_name = trim((string)$frow['Name']);
            }
            $facultyStmt->close();

            $subject_code = '';
            $subjectStmt = $conn->prepare("SELECT subjectCode FROM subjects WHERE subjectName = ? AND sem = ? LIMIT 1");
            $subjectStmt->bind_param('ss', $subject, $sem);
            $subjectStmt->execute();
            $subjectRes = $subjectStmt->get_result();
            if ($srow = $subjectRes->fetch_assoc()) {
                $subject_code = trim((string)$srow['subjectCode']);
            }
            $subjectStmt->close();

            $subjectDisplay = $subject;
            if ($subject_code !== '') {
                $subjectDisplay .= ' (' . $subject_code . ')';
            }

            $groupField = ($attendance_type === 'tutorial') ? 'tutBatch' : 'labBatch';
            $studentsByBatch = [];
            $idToEnrollment = [];
            $allSelectedEnrollments = [];
            $batchKeyByNormalized = [];

            $stuStmt = $conn->prepare("SELECT id, enrollmentNo, name, labBatch, tutBatch FROM students WHERE term = ? AND sem = ? AND class = ? ORDER BY enrollmentNo, name");
            $stuStmt->bind_param('sss', $term, $sem, $class);
            $stuStmt->execute();
            $stuRes = $stuStmt->get_result();
            while ($student = $stuRes->fetch_assoc()) {
                $enrollment = trim((string)$student['enrollmentNo']);
                if ($enrollment === '') {
                    continue;
                }

                $batch = trim((string)$student[$groupField]);
                if ($batch === '') {
                    $batch = 'UNASSIGNED';
                }

                if (!isset($studentsByBatch[$batch])) {
                    $studentsByBatch[$batch] = [];
                }

                $studentsByBatch[$batch][] = [
                    'enrollmentNo' => $enrollment,
                    'name' => trim((string)$student['name']),
                ];

                $idToEnrollment[(string)$student['id']] = $enrollment;
                $allSelectedEnrollments[$enrollment] = true;
                $batchKeyByNormalized[strtoupper($batch)] = $batch;
            }
            $stuStmt->close();

            if (empty($studentsByBatch)) {
                $msg = 'No students found for selected term/semester/class.';
            } else {
                ksort($studentsByBatch, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($studentsByBatch as $batchName => &$batchStudents) {
                    usort($batchStudents, function ($a, $b) {
                        return strnatcasecmp($a['enrollmentNo'], $b['enrollmentNo']);
                    });
                }
                unset($batchStudents);

                $sessionsByBatch = [];
                foreach (array_keys($studentsByBatch) as $batchName) {
                    $sessionsByBatch[$batchName] = [];
                }

                if ($attendance_type === 'lecture') {
                    $attSql = "SELECT id, date, time, presentNo FROM lecattendance WHERE term = ? AND sem = ? AND subject = ? AND class = ? AND (faculty = ? OR faculty = ?)";
                    $attStmt = $conn->prepare($attSql);
                    $attStmt->bind_param('ssssss', $term, $sem, $subject, $class, $faculty_id, $faculty_name);
                } else {
                    $typeCondition = ($attendance_type === 'lab')
                        ? "AND COALESCE(TRIM(labNo), '') <> ''"
                        : "AND COALESCE(TRIM(labNo), '') = ''";
                    $attSql = "SELECT id, date, time, batch, presentNo FROM labattendance WHERE term = ? AND sem = ? AND subject = ? AND (faculty = ? OR faculty = ?) {$typeCondition}";
                    $attStmt = $conn->prepare($attSql);
                    $attStmt->bind_param('sssss', $term, $sem, $subject, $faculty_id, $faculty_name);
                }

                $attStmt->execute();
                $attRes = $attStmt->get_result();

                while ($att = $attRes->fetch_assoc()) {
                    $sessionDate = parse_attendance_date($att['date'] ?? '');
                    if (!$sessionDate) {
                        continue;
                    }
                    if ($sessionDate < $startDateObj || $sessionDate > $endDateObj) {
                        continue;
                    }

                    $presentSet = [];
                    foreach (csv_tokens($att['presentNo'] ?? '') as $token) {
                        if (isset($idToEnrollment[$token])) {
                            $token = $idToEnrollment[$token];
                        }
                        if (isset($allSelectedEnrollments[$token])) {
                            $presentSet[$token] = true;
                        }
                    }

                    $session = [
                        'id' => (int)$att['id'],
                        'date_label' => $sessionDate->format('d-m-y'),
                        'time' => trim((string)($att['time'] ?? '')),
                        'sort_key' => $sessionDate->format('Y-m-d') . ' ' . trim((string)($att['time'] ?? '')),
                        'present_set' => $presentSet,
                    ];

                    if ($attendance_type === 'lecture') {
                        foreach (array_keys($studentsByBatch) as $batchName) {
                            $sessionsByBatch[$batchName][] = $session;
                        }
                    } else {
                        $rowBatches = normalize_tokens($att['batch'] ?? '');
                        foreach ($rowBatches as $rowBatchNormalized) {
                            if (isset($batchKeyByNormalized[$rowBatchNormalized])) {
                                $batchName = $batchKeyByNormalized[$rowBatchNormalized];
                                $sessionsByBatch[$batchName][] = $session;
                            }
                        }
                    }
                }
                $attStmt->close();

                foreach ($sessionsByBatch as $batchName => &$batchSessions) {
                    usort($batchSessions, function ($a, $b) {
                        $cmp = strcmp($a['sort_key'], $b['sort_key']);
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                        return $a['id'] <=> $b['id'];
                    });
                }
                unset($batchSessions);

                $spreadsheet = new Spreadsheet();
                $spreadsheet->removeSheetByIndex(0);
                $usedTitles = [];

                foreach ($studentsByBatch as $batchName => $batchStudents) {
                    $batchSessions = $sessionsByBatch[$batchName] ?? [];
                    $chunks = array_chunk($batchSessions, 25);
                    if (empty($chunks)) {
                        $chunks = [[]];
                    }

                    $totalParts = count($chunks);
                    foreach ($chunks as $partIndex => $chunkSessions) {
                        $sheet = $spreadsheet->createSheet();
                        $sheetTitleBase = $batchName . ($totalParts > 1 ? ('-P' . ($partIndex + 1)) : '');
                        $sheet->setTitle(safe_sheet_title($sheetTitleBase, $usedTitles));

                        write_muster_sheet(
                            $sheet,
                            $batchStudents,
                            $chunkSessions,
                            $term,
                            $sem,
                            $batchName,
                            $subjectDisplay,
                            $partIndex + 1,
                            $totalParts
                        );
                    }
                }

                if ($spreadsheet->getSheetCount() === 0) {
                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle('Muster');
                    $sheet->setCellValue('A1', 'No attendance data found for selected filters.');
                }

                $spreadsheet->setActiveSheetIndex(0);

                $filename = 'muster_' . $attendance_type . '_' . $term . '_sem' . $sem . '.xlsx';
                $filename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
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
            <h1 class="app-page-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Generate Muster Report</h1>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-<?= htmlspecialchars($msgType); ?>"><?= htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-12 col-lg-8">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Filter Options</h4>
                            <form method="POST">
                                <input type="hidden" name="report_mode" value="muster">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Attendance Type</label>
                                        <select name="attendance_type" class="form-control" required>
                                            <option value="lecture" <?= selected_attr($formData, 'attendance_type', 'lecture'); ?>>Lecture</option>
                                            <option value="lab" <?= selected_attr($formData, 'attendance_type', 'lab'); ?>>Lab</option>
                                            <option value="tutorial" <?= selected_attr($formData, 'attendance_type', 'tutorial'); ?>>Tutorial</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Faculty</label>
                                        <select name="faculty" class="form-control" required>
                                            <option value="">Select Faculty</option>
                                            <?php while ($f = $faculty_result->fetch_assoc()) { ?>
                                                <option value="<?= htmlspecialchars($f['id']); ?>" <?= selected_attr($formData, 'faculty', $f['id']); ?>><?= htmlspecialchars($f['Name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Term</label>
                                        <select name="term" class="form-control" required>
                                            <option value="">Select Term</option>
                                            <?php while ($t = $term_result->fetch_assoc()) { ?>
                                                <option value="<?= htmlspecialchars($t['term']); ?>" <?= selected_attr($formData, 'term', $t['term']); ?>><?= htmlspecialchars($t['term']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Semester</label>
                                        <select name="sem" class="form-control" required>
                                            <option value="">Select Semester</option>
                                            <?php while ($s = $sem_result->fetch_assoc()) { ?>
                                                <option value="<?= htmlspecialchars($s['sem']); ?>" <?= selected_attr($formData, 'sem', $s['sem']); ?>><?= htmlspecialchars($s['sem']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Class</label>
                                        <select name="class" class="form-control" required>
                                            <option value="">Select Class</option>
                                            <option value="A" <?= selected_attr($formData, 'class', 'A'); ?>>A</option>
                                            <option value="B" <?= selected_attr($formData, 'class', 'B'); ?>>B</option>
                                            <option value="C" <?= selected_attr($formData, 'class', 'C'); ?>>C</option>
                                            <option value="D" <?= selected_attr($formData, 'class', 'D'); ?>>D</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Subject</label>
                                        <select name="subject" class="form-control" required>
                                            <option value="">Select Subject</option>
                                            <?php while ($sub = $subject_result->fetch_assoc()) { ?>
                                                <option value="<?= htmlspecialchars($sub['subjectName']); ?>" <?= selected_attr($formData, 'subject', $sub['subjectName']); ?>><?= htmlspecialchars($sub['subjectName']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($formData['start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($formData['end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <button type="submit" class="btn btn-primary w-100">
                                            Generate &amp; Download Excel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4 mt-3 mt-lg-0">
                    <div class="app-card shadow-sm h-100" style="background:linear-gradient(135deg,#e8eaf6,#f3f4fd);">
                        <div class="app-card-body">
                            <h4>Output Format</h4>
                            <ol style="font-size:0.875rem;padding-left:1.25rem;line-height:1.8;">
                                <li>Attendance grouped batch-wise</li>
                                <li>One batch per page section</li>
                                <li>Maximum 25 attendance date-columns per page</li>
                                <li>Header block repeated on every page section</li>
                            </ol>
                            <div class="alert alert-info mb-0 mt-2" style="font-size:0.82rem;padding:0.6rem 0.9rem;">
                                <i class="bi bi-info-circle me-1"></i>If there are more than 25 sessions, additional pages are created automatically for that batch.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Lab Utilization Report</h4>
                            <form method="POST">
                                <input type="hidden" name="report_mode" value="lab_utilization">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Lab No</label>
                                        <input type="text" name="lu_lab_no" class="form-control" value="<?= htmlspecialchars($formData['lu_lab_no'] ?? ''); ?>" list="lu-lab-no-options" placeholder="e.g. F108" required>
                                        <datalist id="lu-lab-no-options">
                                            <?php foreach ($lab_no_options as $lab_name) { ?>
                                                <option value="<?= htmlspecialchars($lab_name); ?>"></option>
                                            <?php } ?>
                                        </datalist>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="lu_start_date" class="form-control" value="<?= htmlspecialchars($formData['lu_start_date'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="lu_end_date" class="form-control" value="<?= htmlspecialchars($formData['lu_end_date'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-4">
                                        <button type="submit" class="btn btn-success w-100">
                                            Generate Lab Utilization
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
