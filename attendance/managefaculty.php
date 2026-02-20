<?php
include('dbconfig.php'); // Include database connection

// optional message from add/update/delete redirects
$msg = '';
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Add Faculty (Form handling)
if (isset($_POST['add_faculty'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $initial = $_POST['initial'];
    $name = $_POST['name'];
    $status = 1; // Default status as active

    // Insert the faculty data into the database
    $stmt = $conn->prepare("INSERT INTO faculty (username, password, initial, Name, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $password, $initial, $name, $status);
    $stmt->execute();
    $stmt->close();
}

// Toggle Faculty Status (Change status between 0 and 1)
if (isset($_GET['toggle_status_id'])) {
    $id = $_GET['toggle_status_id'];
    
    // Get current status
    $result = $conn->query("SELECT status FROM faculty WHERE id = $id");
    $row = $result->fetch_assoc();
    $new_status = ($row['status'] == 1) ? 0 : 1; // Toggle the status
    
    // Update the faculty status
    $stmt = $conn->prepare("UPDATE faculty SET status = ? WHERE id = ?");
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
$total_result = $conn->query("SELECT COUNT(*) AS total FROM faculty WHERE username LIKE '%$search%' OR Name LIKE '%$search%'");
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

// Fetch the faculty records with pagination and search
$query = "SELECT * FROM faculty WHERE username LIKE '%$search%' OR Name LIKE '%$search%' LIMIT $limit OFFSET $offset";
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
            <h1 class="app-page-title"><i class="bi bi-person-badge me-2"></i>Manage Faculty</h1>

            <!-- Add Faculty Form -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                            <h4>Add Faculty</h4>
                            <form method="POST" action="managefaculty.php">
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label class="form-label">Initial</label>
                                        <input type="text" name="initial" class="form-control" placeholder="e.g. ABC" required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Full name" required>
                                    </div>
                                    <div class="col-12 col-md-1 d-flex align-items-end">
                                        <button type="submit" name="add_faculty" class="btn btn-primary w-100">Add</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Faculty List -->
            <div class="row">
                <div class="col-12">
                    <div class="app-card shadow-sm">
                        <div class="app-card-body">
                        <h4>Faculty List</h4>

                        <!-- Search Bar -->
                        <form method="GET" action="managefaculty.php">
                            <div class="input-group mb-4">
                                <input type="text" class="form-control" name="search" value="<?php echo $search; ?>" placeholder="Search by username or name">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </form>

                        <!-- Table for Faculty -->
                        <div class="table-responsive">
                            <table id="facultyTable" class="table table-striped table-bordered display">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-center">Username</th>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Initial</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="text-center"><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['username']; ?></td>
                                            <td><?php echo $row['Name']; ?></td>
                                            <td><?php echo $row['initial']; ?></td>
                                            <td class="text-center"><?php echo $row['status'] == 1 ? 'Active' : 'Disabled'; ?></td>
                                            <td class="text-center">
                                                <a href="editfaculty.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="managefaculty.php?toggle_status_id=<?php echo $row['id']; ?>" class="btn btn-<?php echo $row['status'] == 1 ? 'danger' : 'success'; ?> btn-sm">
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
