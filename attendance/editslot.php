<?php
include('dbconfig.php');

// Fetch slot data to edit
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM timeslot WHERE id = $id");
    $row = $result->fetch_assoc();
} else {
    header("Location: manageslot.php");
    exit();
}

// Update Slot (Form handling)
if (isset($_POST['update_slot'])) {
    $timeslot = $_POST['timeslot'];
    $sequence = $_POST['sequence'];

    // Update the slot data in the database
    $stmt = $conn->prepare("UPDATE timeslot SET timeslot = ?, sequence = ? WHERE id = ?");
    $stmt->bind_param("sii", $timeslot, $sequence, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: manageslot.php?msg=Slot updated successfully");
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
            <h1 class="app-page-title"><i class="bi bi-pencil-square me-2"></i>Edit Slot</h1>

            <div class="row mb-4">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Edit Slot Details</h4>
                            <form method="POST" action="editslot.php?id=<?php echo $id; ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-7">
                                        <label class="form-label">Timeslot</label>
                                        <input type="text" name="timeslot" class="form-control" value="<?php echo htmlspecialchars($row['timeslot']); ?>" required>
                                    </div>
                                    <div class="col-12 col-md-5">
                                        <label class="form-label">Sequence</label>
                                        <input type="number" name="sequence" class="form-control" value="<?php echo htmlspecialchars($row['sequence']); ?>" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="update_slot" class="btn btn-primary">Update Slot</button>
                                    <a href="manageslot.php" class="btn btn-outline-secondary">Cancel</a>
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
