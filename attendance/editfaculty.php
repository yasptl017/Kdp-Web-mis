<?php
include('dbconfig.php'); // Include database connection

// Check if user is logged in
if (!isset($_SESSION['Name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch Faculty Details for Editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM faculty WHERE id = $id");
    $faculty = $result->fetch_assoc();
}

// Handle Faculty Update
if (isset($_POST['update_faculty'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $initial = $_POST['initial'];
    $name = $_POST['name'];

    // Update the faculty data in the database
    $stmt = $conn->prepare("UPDATE faculty SET username = ?, password = ?, initial = ?, Name = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $username, $password, $initial, $name, $id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the manage faculty page with a success message
    header("Location: managefaculty.php?msg=Faculty updated successfully");
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
            <h1 class="app-page-title"><i class="bi bi-pencil-square me-2"></i>Edit Faculty</h1>

            <div class="row mb-4">
                <div class="col-12 col-lg-8">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Edit Faculty Details</h4>
                            <form method="POST" action="editfaculty.php?id=<?php echo $faculty['id']; ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($faculty['username']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($faculty['password']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Initial</label>
                                        <input type="text" name="initial" class="form-control" value="<?php echo htmlspecialchars($faculty['initial']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($faculty['Name']); ?>" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_faculty" class="btn btn-primary">Update Faculty</button>
                                    <a href="managefaculty.php" class="btn btn-outline-secondary">Cancel</a>
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
