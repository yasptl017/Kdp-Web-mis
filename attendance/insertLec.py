<?php
$mysqli = new mysqli("localhost", "username", "password", "kdpmis");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Generate random presentNos
$roll_start = 206310307001;
$roll_end = 206310307075;
$total = rand(25, 40); // you can adjust how many random numbers you want
$range = range($roll_start, $roll_end);
shuffle($range);
$selected = array_slice($range, 0, $total);
$presentNo = implode(",", $selected);

// Insert data
$sql = "INSERT INTO lecattendance 
    (`date`, `time`, `term`, `faculty`, `sem`, `subject`, `class`, `presentNo`, `description`) 
    VALUES 
    (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
$date = "2025-06-23";
$time = "10:00 - 11:00";
$term = "Term1";
$faculty = "Prof. Sharma";
$sem = "4";
$subject = "IWD";
$class = "A";
$description = "Teachers day";

$stmt->bind_param("sssssssss", $date, $time, $term, $faculty, $sem, $subject, $class, $presentNo, $description);

if ($stmt->execute()) {
    echo "Record inserted successfully.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
