<?php
include('dbconfig.php'); // Include database connection

// Check if user is logged in
if (!isset($_SESSION['Name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch Semester Details for Editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM semester WHERE id = $id");
    $semester = $result->fetch_assoc();
}

// Handle Semester Update
if (isset($_POST['update_semester'])) {
    $sem = $_POST['sem'];

    // Update the semester data in the database
    $stmt = $conn->prepare("UPDATE semester SET sem = ? WHERE id = ?");
    $stmt->bind_param("si", $sem, $id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the manage semester page with a success message
    header("Location: managesemester.php?msg=Semester updated successfully");
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
            <h1 class="app-page-title"><i class="bi bi-pencil-square me-2"></i>Edit Semester</h1>

            <div class="row mb-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Edit Semester Details</h4>
                            <form method="POST" action="editsemester.php?id=<?php echo $semester['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Semester</label>
                                    <input type="text" name="sem" class="form-control" value="<?php echo htmlspecialchars($semester['sem']); ?>" required>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_semester" class="btn btn-primary">Update</button>
                                    <a href="managesemester.php" class="btn btn-outline-secondary">Cancel</a>
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
