<?php
// manage_faculty.php
// Faculty listing and management page

session_start();
include "dbconfig.php";
include "head.php";

$msg = "";
$msgType = "";

/* ---------------------------
   Handle Copy
   --------------------------- */
if (isset($_GET['copy'])) {
    $copyid = intval($_GET['copy']);
    
    // Fetch the original faculty data
    $result = $conn->query("SELECT * FROM faculty WHERE id=$copyid LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $original = $result->fetch_assoc();
        
        // Prepare all fields for copying
        $department = $conn->real_escape_string($original['department']);
        $idx = $original['idx'] !== null ? intval($original['idx']) : 'NULL';
        $name = $conn->real_escape_string("Copy of " . $original['name']);
        $photo = $original['photo'] ? $conn->real_escape_string($original['photo']) : 'NULL';
        $email = $conn->real_escape_string($original['email']);
        $phone = $conn->real_escape_string($original['phone']);
        $designation = $conn->real_escape_string($original['designation']);
        $date_of_joining = $original['date_of_joining'] ? "'" . $conn->real_escape_string($original['date_of_joining']) . "'" : 'NULL';
        $experience_years = $conn->real_escape_string($original['experience_years']);
        
        $education = $conn->real_escape_string($original['education']);
        $work_experience = $conn->real_escape_string($original['work_experience']);
        $skills = $conn->real_escape_string($original['skills']);
        $course_taught = $conn->real_escape_string($original['course_taught']);
        $training = $conn->real_escape_string($original['training']);
        $portfolio = $conn->real_escape_string($original['portfolio']);
        $research_projects = $conn->real_escape_string($original['research_projects']);
        $publications = $conn->real_escape_string($original['publications']);
        $academic_projects = $conn->real_escape_string($original['academic_projects']);
        $patents = $conn->real_escape_string($original['patents']);
        $memberships = $conn->real_escape_string($original['memberships']);
        $awards = $conn->real_escape_string($original['awards']);
        $expert_lectures = $conn->real_escape_string($original['expert_lectures']);
        $extra_fields = $original['extra_fields'] ? "'" . $conn->real_escape_string($original['extra_fields']) . "'" : 'NULL';
        
        // Prepare idx for SQL
        $idx_sql = ($idx === 'NULL') ? "NULL" : "'$idx'";
        $photo_sql = ($photo === 'NULL') ? "NULL" : "'$photo'";
        
        // Insert the copy
        $sql = "INSERT INTO faculty
            (department, idx, name, photo, email, phone, designation, date_of_joining, experience_years,
             education, work_experience, skills, course_taught, training, portfolio, research_projects,
             publications, academic_projects, patents, memberships, awards, expert_lectures, extra_fields)
         VALUES (
            '$department', $idx_sql, '$name', $photo_sql, '$email', '$phone', '$designation', $date_of_joining,
            '$experience_years', '$education', '$work_experience', '$skills', '$course_taught', '$training', 
            '$portfolio', '$research_projects', '$publications', '$academic_projects', '$patents', 
            '$memberships', '$awards', '$expert_lectures', $extra_fields
         )";
        
        if ($conn->query($sql)) {
            $msg = "Faculty copied successfully! You can now edit the copy.";
            $msgType = "success";
            header("Location: manage_faculty.php?msg=copied");
            exit;
        } else {
            $msg = "Error copying faculty: " . $conn->error;
            $msgType = "danger";
        }
    } else {
        $msg = "Faculty not found!";
        $msgType = "danger";
    }
}

/* ---------------------------
   Handle Delete
   --------------------------- */
if (isset($_GET['delete'])) {
    $delid = intval($_GET['delete']);
    
    // Get photo path before deleting
    $result = $conn->query("SELECT photo FROM faculty WHERE id=$delid LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $photoPath = $row['photo'];
        
        // Delete from database
        if ($conn->query("DELETE FROM faculty WHERE id=$delid")) {
            // Delete photo file if exists
            if (!empty($photoPath) && file_exists($photoPath)) {
                unlink($photoPath);
            }
            header("Location: manage_faculty.php?msg=deleted");
            exit;
        }
    }
}

// Check for success messages
if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'added':
            $msg = "Faculty added successfully!";
            $msgType = "success";
            break;
        case 'updated':
            $msg = "Faculty updated successfully!";
            $msgType = "success";
            break;
        case 'deleted':
            $msg = "Faculty deleted successfully!";
            $msgType = "success";
            break;
        case 'copied':
            $msg = "Faculty copied successfully! The copy is now in the list.";
            $msgType = "success";
            break;
    }
}

/* ---------------------------
   Fetch Listing (for table)
   --------------------------- */
if ($_SESSION['role'] == 'Admin') {
    $list = $conn->query("SELECT id, department, name, email, phone, designation FROM faculty ORDER BY department ASC, name ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $list = $conn->query("SELECT id, department, name, email, phone, designation FROM faculty WHERE department='$userDept' ORDER BY department ASC, name ASC");
}
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .action-buttons .btn {
        min-width: 35px;
    }
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Manage Faculty</h2>
        <a href="add_faculty.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Faculty
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show">
            <?php echo $msg; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <table id="facultyTable" class="table table-striped table-bordered table-hover">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th width="180">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($row = $list->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['designation']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="add_faculty.php?id=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-warning"
                               title="Edit Faculty"
                               data-bs-toggle="tooltip">
                                <i class="fas fa-edit"></i>
                            </a>

                            <a href="?copy=<?php echo $row['id']; ?>"
                               onclick="return confirm('Create a copy of this faculty member?');"
                               class="btn btn-sm btn-info"
                               title="Copy Faculty"
                               data-bs-toggle="tooltip">
                               <i class="fas fa-copy"></i>
                            </a>

                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this faculty member? This action cannot be undone.');"
                               class="btn btn-sm btn-danger"
                               title="Delete Faculty"
                               data-bs-toggle="tooltip">
                               <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include "footer.php"; ?>

<!-- Page-specific Scripts (load AFTER footer.php which has jQuery) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Initializing Faculty DataTable...');
    
    // Initialize DataTable
    $("#facultyTable").DataTable({
        pageLength: 25,
        order: [[1, 'asc'], [2, 'asc']], // Sort by department, then name
        language: {
            search: "Search Faculty:",
            lengthMenu: "Show _MENU_ faculty per page",
            info: "Showing _START_ to _END_ of _TOTAL_ faculty",
            infoEmpty: "No faculty records available",
            infoFiltered: "(filtered from _MAX_ total records)",
            zeroRecords: "No matching faculty found"
        },
        columnDefs: [
            { orderable: false, targets: 6 } // Disable sorting on Action column
        ]
    });
    
    console.log('✓ DataTable initialized');
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    console.log('✓ Tooltips initialized');
});
</script>

</body>
</html>