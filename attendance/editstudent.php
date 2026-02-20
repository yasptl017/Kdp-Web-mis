<?php
include('dbconfig.php'); // Include database connection


// Fetch Student Details for Editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM students WHERE id = $id");
    $student = $result->fetch_assoc();
}

// Handle Student Update
if (isset($_POST['update_student'])) {
    $term = $_POST['term'];
    $enrollmentNo = $_POST['enrollmentNo'];
    $name = $_POST['name'];
    $sem = $_POST['sem'];
    $class = $_POST['class'];
    $labBatch = $_POST['labBatch'];
    $tutBatch = $_POST['tutBatch'];

    // Update the student data in the database
    $stmt = $conn->prepare("UPDATE students SET term = ?, enrollmentNo = ?, name = ?, sem = ?, class = ?, labBatch = ?, tutBatch = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $term, $enrollmentNo, $name, $sem, $class, $labBatch, $tutBatch, $id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the manage student page with a success message
    header("Location: managestudents.php?msg=Student updated successfully");
    exit();
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
            <h1 class="app-page-title"><i class="bi bi-pencil-square me-2"></i>Edit Student</h1>

            <div class="row mb-4">
                <div class="col-12 col-lg-10">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Edit Student Details</h4>
                            <form method="POST" action="editstudent.php?id=<?php echo $student['id']; ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-2">
                                        <label class="form-label">Term</label>
                                        <input type="text" name="term" class="form-control" value="<?php echo htmlspecialchars($student['term']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Enrollment No</label>
                                        <input type="text" name="enrollmentNo" class="form-control" value="<?php echo htmlspecialchars($student['enrollmentNo']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">Semester</label>
                                        <input type="text" name="sem" class="form-control" value="<?php echo htmlspecialchars($student['sem']); ?>" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">Class</label>
                                        <input type="text" name="class" class="form-control" value="<?php echo htmlspecialchars($student['class']); ?>" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">Lab Batch</label>
                                        <input type="text" name="labBatch" class="form-control" value="<?php echo htmlspecialchars($student['labBatch']); ?>" required>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">Tut Batch</label>
                                        <input type="text" name="tutBatch" class="form-control" value="<?php echo htmlspecialchars($student['tutBatch']); ?>" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                                    <a href="managestudents.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div><!--//container-xl-->
    </div><!--//app-content-->
</div><!--//app-wrapper-->

<?php include('footer.php'); ?>
</body>
</html>

<?php
$conn->close();
?>
