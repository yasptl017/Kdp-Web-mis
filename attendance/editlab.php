<?php
include('dbconfig.php'); // Include database connection

// Check if user is logged in
if (!isset($_SESSION['Name'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch Lab Details for Editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM labs WHERE id = $id");
    $lab = $result->fetch_assoc();
}

// Handle Lab Update
if (isset($_POST['update_lab'])) {
    $labNo = $_POST['labNo'];
    $labName = $_POST['labName'];

    // Update the lab data in the database
    $stmt = $conn->prepare("UPDATE labs SET labNo = ?, labName = ? WHERE id = ?");
    $stmt->bind_param("ssi", $labNo, $labName, $id);
    $stmt->execute();
    $stmt->close();

    // Redirect to the manage lab page with a success message
    header("Location: managelabs.php?msg=Lab updated successfully");
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
            <h1 class="app-page-title"><i class="bi bi-pencil-square me-2"></i>Edit Lab</h1>

            <div class="row mb-4">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Edit Lab Details</h4>
                            <form method="POST" action="editlab.php?id=<?php echo $lab['id']; ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label">Lab Number</label>
                                        <input type="text" name="labNo" class="form-control" value="<?php echo htmlspecialchars($lab['labNo']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <label class="form-label">Lab Name</label>
                                        <input type="text" name="labName" class="form-control" value="<?php echo htmlspecialchars($lab['labName']); ?>" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_lab" class="btn btn-primary">Update Lab</button>
                                    <a href="managelabs.php" class="btn btn-outline-secondary">Cancel</a>
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
