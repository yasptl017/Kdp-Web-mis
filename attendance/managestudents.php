<?php
include('dbconfig.php'); // Include database connection

// optional message from add/update/delete redirects
$msg = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Add Student (Form handling)
if (isset($_POST['add_student'])) {
    $term = $_POST['term'];
    $enrollmentNo = $_POST['enrollmentNo'];
    $name = $_POST['name'];
    $sem = $_POST['sem'];
    $class = $_POST['class'];
    $labBatch = $_POST['labBatch'];
    $tutBatch = $_POST['tutBatch'];
    $status = 1; // Default status as active

    $stmt = $conn->prepare("INSERT INTO students (term, enrollmentNo, name, sem, class, labBatch, tutBatch, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $term, $enrollmentNo, $name, $sem, $class, $labBatch, $tutBatch, $status);
    $stmt->execute();
    $stmt->close();
}

// Toggle Student Status
if (isset($_GET['toggle_status_id'])) {
    $id = $_GET['toggle_status_id'];
    $result = $conn->query("SELECT status FROM students WHERE id = $id");
    $row = $result->fetch_assoc();
    $new_status = ($row['status'] == 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    $stmt->close();
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Fetch all students (client-side DataTable handles pagination, sorting)
$query = "SELECT * FROM students WHERE enrollmentNo LIKE '%$search%' OR name LIKE '%$search%'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<!-- Include DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-people me-2"></i>Manage Students</h1>

            <!-- Add Student Form -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Add Student</h4>
                            <form method="POST" action="managestudents.php">
                                <div class="row g-3 mb-3">
                                    <div class="col-6 col-md-2">
                                        <label class="form-label">Term</label>
                                        <input type="text" name="term" class="form-control" placeholder="Term" required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Enrollment No</label>
                                        <input type="text" name="enrollmentNo" class="form-control" placeholder="Enrollment No" required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Full name" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label">Sem</label>
                                        <input type="text" name="sem" class="form-control" placeholder="Sem" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label">Class</label>
                                        <input type="text" name="class" class="form-control" placeholder="A-D" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label">Lab</label>
                                        <input type="text" name="labBatch" class="form-control" placeholder="Batch" required>
                                    </div>
                                    <div class="col-6 col-md-1">
                                        <label class="form-label">Tut</label>
                                        <input type="text" name="tutBatch" class="form-control" placeholder="Batch" required>
                                    </div>
                                </div>
                                <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div class="row">
                <div class="col-12">
                    <div class="app-card app-card-body shadow-sm">
                        <h4 class="mb-4">Student List</h4>

                       <!-- Table for Students -->
                        <div class="table-responsive">
                            <table id="studentsTable" class="table table-striped table-bordered display">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Enrollment No</th>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Semester</th>
                                        <th class="text-center">Class</th>
                                        <th class="text-center">Lab Batch</th>
                                        <th class="text-center">Tut Batch</th>
                                        <th class="text-center">Term</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="text-center"><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['enrollmentNo']; ?></td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['sem']; ?></td>
                                            <td><?php echo $row['class']; ?></td>
                                            <td><?php echo $row['labBatch']; ?></td>
                                            <td><?php echo $row['tutBatch']; ?></td>
                                            <td class="text-center"><?php echo $row['term']; ?></td>
                                            <td class="text-center">
                                                <a href="editstudent.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="managestudents.php?toggle_status_id=<?php echo $row['id']; ?>" class="btn btn-<?php echo $row['status'] == 1 ? 'danger' : 'success'; ?> btn-sm">
                                                    <?php echo $row['status'] == 1 ? 'Disable' : 'Enable'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

        </div><!--//container-xl-->
    </div><!--//app-content-->
</div><!--//app-wrapper-->

<?php include('footer.php'); ?>

<!-- jQuery & DataTables Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        $('#studentsTable').DataTable({
            "order": [[0, "asc"]],
            "columnDefs": [
                { "orderable": false, "targets": 8 } // Disable sort on Actions
            ]
        });
    });
</script>

</body>
</html>

<?php $conn->close(); ?>
