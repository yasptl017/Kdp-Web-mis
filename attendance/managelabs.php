<?php
include('dbconfig.php'); // Include database connection

// optional message from add/update/delete redirects
$msg = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Add Lab (Form handling)
if (isset($_POST['add_lab'])) {
    $labNo = $_POST['labNo'];
    $labName = $_POST['labName'];
    $status = 1; // Default status as active

    // Insert the lab data into the database
    $stmt = $conn->prepare("INSERT INTO labs (labNo, labName, status) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $labNo, $labName, $status);
    $stmt->execute();
    $stmt->close();
}

// Toggle Lab Status (Change status between 0 and 1)
if (isset($_GET['toggle_status_id'])) {
    $id = $_GET['toggle_status_id'];

    // Get current status
    $result = $conn->query("SELECT status FROM labs WHERE id = $id");
    $row = $result->fetch_assoc();
    $new_status = ($row['status'] == 1) ? 0 : 1; // Toggle the status

    // Update the lab status
    $stmt = $conn->prepare("UPDATE labs SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    $stmt->close();
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Pagination functionality
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query to get total records for pagination
$total_result = $conn->query("SELECT COUNT(*) AS total FROM labs WHERE labNo LIKE '%$search%' OR labName LIKE '%$search%'");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

// Fetch the lab records with pagination and search
$query = "SELECT * FROM labs WHERE labNo LIKE '%$search%' OR labName LIKE '%$search%' LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <h1 class="app-page-title"><i class="bi bi-building me-2"></i>Manage Labs</h1>

            <!-- Add Lab Form -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Add Lab</h4>
                            <form method="POST" action="managelabs.php">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Lab Number</label>
                                        <input type="text" name="labNo" class="form-control" placeholder="e.g. L-101" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Lab Name</label>
                                        <input type="text" name="labName" class="form-control" placeholder="Lab name" required>
                                    </div>
                                    <div class="col-12 col-md-4 d-flex align-items-end">
                                        <button type="submit" name="add_lab" class="btn btn-primary w-100">Add Lab</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab List -->
            <div class="row">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                        <h4>Lab List</h4>

                        <!-- Search Bar -->
                        <form method="GET" action="managelabs.php">
                            <div class="input-group mb-4">
                                <input type="text" class="form-control" name="search" value="<?php echo $search; ?>" placeholder="Search by lab number or name">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <!-- Table for Labs -->
                        <div class="table-responsive">
                            <table id="labsTable" class="table table-striped table-bordered display">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Lab Number</th>
                                        <th class="text-center">Lab Name</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="text-center"><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['labNo']; ?></td>
                                            <td><?php echo $row['labName']; ?></td>
                                            <td class="text-center"><?php echo $row['status'] == 1 ? 'Active' : 'Disabled'; ?></td>
                                            <td class="text-center">
                                                <a href="editlab.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="managelabs.php?toggle_status_id=<?php echo $row['id']; ?>" class="btn btn-<?php echo $row['status'] == 1 ? 'danger' : 'success'; ?> btn-sm">
                                                    <?php echo $row['status'] == 1 ? 'Disable' : 'Enable'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php } ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
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
