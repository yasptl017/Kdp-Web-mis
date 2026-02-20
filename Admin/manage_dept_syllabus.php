<?php
// manage_dept_syllabus.php
// Department Syllabus Management - All operations on one page

session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

/* -------------------------
   MOVE UP / DOWN
------------------------- */
if (isset($_GET['move'], $_GET['id'])) {

    $id = intval($_GET['id']);
    $direction = $_GET['move']; // up | down

    $current = $conn->query(
        "SELECT id, display_order FROM dept_syllabus WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM dept_syllabus
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM dept_syllabus
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE dept_syllabus SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE dept_syllabus SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_dept_syllabus.php");
    exit;
}

/* ===================================
   ADD / UPDATE SYLLABUS
   =================================== */
if (isset($_POST['save_syllabus'])) {
    
    $id = intval($_POST['syllabus_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $url = $conn->real_escape_string($_POST['url']);
    
    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $message = "Invalid URL format. Please enter a valid URL.";
        $messageType = "danger";
    } else {
        if ($id == 0) {
            // INSERT
            $sql = "INSERT INTO dept_syllabus (department, title, display_order, url)
                    VALUES ('$department', '$title', $display_order, '$url')";
            
            if ($conn->query($sql)) {
                $message = "Syllabus added successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
            
        } else {
            // UPDATE
            $sql = "UPDATE dept_syllabus SET 
                    department='$department',
                    title='$title',
                    display_order=$display_order,
                    url='$url'
                    WHERE id=$id";
            
            if ($conn->query($sql)) {
                $message = "Syllabus updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}

/* ===================================
   DELETE SYLLABUS
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    if ($conn->query("DELETE FROM dept_syllabus WHERE id=$id")) {
        $message = "Syllabus deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting syllabus: " . $conn->error;
        $messageType = "danger";
    }
}

// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $syllabus_list = $conn->query("SELECT * FROM dept_syllabus ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $syllabus_list = $conn->query("SELECT * FROM dept_syllabus WHERE department='$userDept' ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Department Syllabus</title>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    
    <style>
        .form-card {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .url-link {
            display: inline-block;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">
            <i class="fas fa-book me-2"></i>
            Manage Department Syllabus
        </h2>
        <button class="btn btn-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#syllabusModal"
                onclick="addSyllabus()">
            <i class="fas fa-plus-circle"></i> Add Syllabus
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <table id="syllabusTable" class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>URL</th>
                    <th width="220">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $syllabus_list->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($row['url']); ?>" 
                           target="_blank" 
                           class="url-link text-decoration-none"
                           title="<?php echo htmlspecialchars($row['url']); ?>">
                            <i class="fas fa-external-link-alt text-primary"></i>
                            <?php echo htmlspecialchars($row['url']); ?>
                        </a>
                    </td>
                    <td>
                        <div class="action-buttons">

                            <a href="?move=up&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Up">
                               <i class="fas fa-arrow-up"></i>
                            </a>

                            <a href="?move=down&id=<?php echo $row['id']; ?>"
                               class="btn btn-sm btn-secondary" title="Move Down">
                               <i class="fas fa-arrow-down"></i>
                            </a>

                            <button class="btn btn-sm btn-warning"
                                    onclick='editSyllabus(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#syllabusModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this syllabus?');"
                               class="btn btn-sm btn-danger"
                               title="Delete">
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


<!-- SYLLABUS MODAL -->
<div class="modal fade" id="syllabusModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            
            <form method="POST" id="syllabusForm">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-book me-2"></i>Add Syllabus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    
                    <input type="hidden" name="syllabus_id" id="syllabus_id">

                    <div class="row">
                        
                        <!-- Department -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Department <span class="text-danger">*</span>
                            </label>
                            <select name="department" 
                                    id="department" 
                                    class="form-control" 
                                    required>
                                <option value="">-- Select Department --</option>
                                <?php 
                                $departments->data_seek(0);
                                while ($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Syllabus Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control" 
                                   placeholder="e.g., B.Tech Computer Engineering - Semester 1"
                                   required>
                        </div>

                        <!-- Display Order -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Display Order <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   name="display_order" 
                                   id="display_order" 
                                   class="form-control" 
                                   required>
                        </div>

                        <!-- URL -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Syllabus URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" 
                                   name="url" 
                                   id="url" 
                                   class="form-control" 
                                   placeholder="https://example.com/syllabus.pdf"
                                   required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Enter the complete URL including http:// or https://
                            </small>
                            
                            <!-- URL Preview -->
                            <div id="urlPreview" class="mt-2" style="display:none;">
                                <div class="alert alert-info py-2">
                                    <strong>Preview:</strong>
                                    <a href="#" id="urlPreviewLink" target="_blank" class="ms-2">
                                        <i class="fas fa-external-link-alt"></i> Open Link
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_syllabus" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Syllabus
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<?php include "footer.php"; ?>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Department Syllabus Management ===');

// URL preview function
$('#url').on('input', function() {
    const url = $(this).val();
    const urlPreview = $('#urlPreview');
    const urlPreviewLink = $('#urlPreviewLink');
    
    if (url && url.startsWith('http')) {
        urlPreviewLink.attr('href', url);
        urlPreview.show();
    } else {
        urlPreview.hide();
    }
});

// Add Syllabus function
function addSyllabus() {
    console.log('Adding new syllabus');
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-book me-2"></i>Add Syllabus';
    document.getElementById('syllabus_id').value = '';
    document.getElementById('department').value = '';
    document.getElementById('title').value = '';
    document.getElementById('display_order').value = '';
    document.getElementById('url').value = '';
    
    // Hide URL preview
    $('#urlPreview').hide();
}

// Edit Syllabus function
function editSyllabus(syllabus) {
    console.log('Editing syllabus:', syllabus.id);
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Update Syllabus';
    document.getElementById('syllabus_id').value = syllabus.id;
    document.getElementById('department').value = syllabus.department;
    document.getElementById('title').value = syllabus.title;
    document.getElementById('display_order').value = syllabus.display_order;
    document.getElementById('url').value = syllabus.url;
    
    // Show URL preview
    if (syllabus.url) {
        $('#urlPreviewLink').attr('href', syllabus.url);
        $('#urlPreview').show();
    }
}

// Form validation
$('#syllabusForm').on('submit', function(e) {
    const url = $('#url').val();
    
    // Validate URL format
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        e.preventDefault();
        alert('Please enter a valid URL starting with http:// or https://');
        $('#url').focus();
        return false;
    }
});

// Initialize DataTable
$(document).ready(function() {
    console.log('Initializing DataTable');
    
    $('#syllabusTable').DataTable({
        pageLength: 25,
        order: [[3, 'asc']], // Sort by Order column
        language: {
            search: "Search Syllabus:",
            lengthMenu: "Show _MENU_ syllabus per page",
            info: "Showing _START_ to _END_ of _TOTAL_ syllabus",
            infoEmpty: "No syllabus available",
            infoFiltered: "(filtered from _MAX_ total records)",
            zeroRecords: "No matching syllabus found"
        },
        columnDefs: [
            { orderable: false, targets: 5 } // Disable sorting on Actions column
        ]
    });
    
    console.log('✓ DataTable initialized');
});

console.log('✓ Department Syllabus page loaded');
</script>

</body>
</html>