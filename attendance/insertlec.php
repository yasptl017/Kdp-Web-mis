<?php
require_once __DIR__ . '/auth.php';
require_login();

$mysqli = new mysqli("localhost", "root", "", "kdpmis");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Function to generate next 45 weekdays
function getNextWeekdays($startDate, $count) {
    $weekdays = [];
    $current = strtotime($startDate);
    while (count($weekdays) < $count) {
        $day = date('N', $current); // 1 (Mon) to 7 (Sun)
        if ($day < 6) { // Mon–Fri only
            $weekdays[] = date('Y-m-d', $current);
        }
        $current = strtotime('+1 day', $current);
    }
    return $weekdays;
}

// Parameters
$roll_start = 206310307001;
$roll_end = 206310307075;
$term = "Term1";
$faculty = "Prof. Sharma";
$sem = "4";
$subject = "IWD";
$class = "A";
$time = "10:00 - 11:00";

// SQL statement
$sql = "INSERT INTO lecattendance 
    (`date`, `time`, `term`, `faculty`, `sem`, `subject`, `class`, `presentNo`, `description`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);

// Generate dates
$dates = getNextWeekdays("2025-06-23", 45);

foreach ($dates as $date) {
    // Generate random presentNos
    $range = range($roll_start, $roll_end);
    shuffle($range);
    $total = rand(25, 40); // number of students present
    $selected = array_slice($range, 0, $total);
    $presentNo = implode(",", $selected);

    $description = NULL;

    $stmt->bind_param("sssssssss", $date, $time, $term, $faculty, $sem, $subject, $class, $presentNo, $description);

    if ($stmt->execute()) {
        echo "Inserted for $date<br>";
    } else {
        echo "Error on $date: " . $stmt->error . "<br>";
    }
}

$stmt->close();
$mysqli->close();
?>
