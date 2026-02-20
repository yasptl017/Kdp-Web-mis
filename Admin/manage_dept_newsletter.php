<?php
// manage_dept_newsletter.php
// Department Newsletter Management - All operations on one page

session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/newsletters/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* -------------------------
   MOVE UP / DOWN
------------------------- */
if (isset($_GET['move'], $_GET['id'])) {

    $id = intval($_GET['id']);
    $direction = $_GET['move']; // up | down

    $current = $conn->query(
        "SELECT id, display_order FROM dept_newsletter WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM dept_newsletter
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM dept_newsletter
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE dept_newsletter SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE dept_newsletter SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_dept_newsletter.php");
    exit;
}

/* ===================================
   ADD / UPDATE NEWSLETTER
   =================================== */
if (isset($_POST['save_newsletter'])) {
    
    $id = intval($_POST['newsletter_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $remark = $conn->real_escape_string($_POST['remark']);
    
    // Handle file upload
    $oldFile = '';
    if ($id > 0) {
        $q = $conn->query("SELECT file FROM dept_newsletter WHERE id=$id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $oldFile = $q->fetch_assoc()['file'];
        }
    }
    
    $filePath = $oldFile;
    
    // Check if new file uploaded
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $fileName = 'newsletter_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                // Delete old file
                if (!empty($oldFile) && file_exists($oldFile)) {
                    unlink($oldFile);
                }
                $filePath = $target;
            }
        } else {
            $message = "Invalid file format. Allowed: PDF, DOC, DOCX, JPG, PNG";
            $messageType = "danger";
        }
    }
    
    if (empty($message)) {
        if ($id == 0) {
            // INSERT
            $sql = "INSERT INTO dept_newsletter (department, title, display_order, file, remark)
                    VALUES ('$department', '$title', $display_order, " . 
                    ($filePath ? "'$filePath'" : "NULL") . ", '$remark')";
            
            if ($conn->query($sql)) {
                $message = "Newsletter added successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
            
        } else {
            // UPDATE
            $sql = "UPDATE dept_newsletter SET 
                    department='$department',
                    title='$title',
                    display_order=$display_order,
                    file=" . ($filePath ? "'$filePath'" : "NULL") . ",
                    remark='$remark'
                    WHERE id=$id";
            
            if ($conn->query($sql)) {
                $message = "Newsletter updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}

/* ===================================
   DELETE NEWSLETTER
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get file path
    $result = $conn->query("SELECT file FROM dept_newsletter WHERE id=$id LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filePath = $row['file'];
        
        // Delete from database
        if ($conn->query("DELETE FROM dept_newsletter WHERE id=$id")) {
            // Delete file
            if (!empty($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            $message = "Newsletter deleted successfully!";
            $messageType = "success";
        }
    }
}

// Filter by role
if ($_SESSION['role'] == 'Admin') {
    $newsletters = $conn->query("SELECT * FROM dept_newsletter ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 ORDER BY department ASC");
} else {
    $userDept = $conn->real_escape_string($_SESSION['user_name']);
    $newsletters = $conn->query("SELECT * FROM dept_newsletter WHERE department='$userDept' ORDER BY display_order ASC, id DESC");
    $departments = $conn->query("SELECT department FROM departments WHERE visibility = 1 AND department='$userDept' ORDER BY department ASC");
}
?>

<!-- Page-specific CSS -->
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
    .file-icon {
        font-size: 24px;
        color: #dc3545;
    }
</style>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">
            <i class="fas fa-newspaper me-2"></i>
            Manage Department Newsletters
        </h2>
        <button class="btn btn-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#newsletterModal"
                onclick="addNewsletter()">
            <i class="fas fa-plus-circle"></i> Add Newsletter
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <table id="newslettersTable" class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Department</th>
                    <th>Title</th>
                    <th>Order</th>
                    <th>File</th>
                    <th>Remark</th>
                    <th width="220">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $newsletters->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="text-center"><?php echo $row['display_order']; ?></td>
                    <td class="text-center">
                        <?php if (!empty($row['file']) && file_exists($row['file'])): ?>
                            <a href="<?php echo $row['file']; ?>" 
                               target="_blank" 
                               class="text-decoration-none"
                               title="View/Download File">
                                <i class="fas fa-file-pdf file-icon"></i>
                                <br>
                                <small><?php echo basename($row['file']); ?></small>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">No file</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($row['remark']); ?>
                        </div>
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
                                    onclick='editNewsletter(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                    data-bs-toggle="modal"
                                    data-bs-target="#newsletterModal"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <a href="?delete=<?php echo $row['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this newsletter?');"
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


<!-- NEWSLETTER MODAL -->
<div class="modal fade" id="newsletterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            
            <form method="POST" enctype="multipart/form-data" id="newsletterForm">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-newspaper me-2"></i>Add Newsletter
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    
                    <input type="hidden" name="newsletter_id" id="newsletter_id">

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
                                // Reset pointer for departments
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
                                Newsletter Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control" 
                                   placeholder="e.g., Monthly Newsletter - January 2024"
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

                        <!-- File Upload -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Newsletter File <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="file" 
                                   id="file" 
                                   class="form-control" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">
                                Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)
                            </small>
                            
                            <!-- Current File Display -->
                            <div id="currentFileContainer" style="display:none;" class="mt-2 p-2 bg-light rounded">
                                <label class="text-muted">Current File:</label><br>
                                <a href="#" id="currentFileLink" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-file-pdf text-danger"></i>
                                    <span id="currentFileName"></span>
                                </a>
                            </div>
                        </div>

                        <!-- Remark -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Remark</label>
                            <textarea name="remark" 
                                      id="remark" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Optional remarks or description"></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_newsletter" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Newsletter
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

<!-- Page-specific Scripts (load AFTER footer.php which has jQuery) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Department Newsletters Management ===');

// Add Newsletter function
function addNewsletter() {
    console.log('Adding new newsletter');
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-newspaper me-2"></i>Add Newsletter';
    document.getElementById('newsletter_id').value = '';
    document.getElementById('department').value = '';
    document.getElementById('title').value = '';
    document.getElementById('display_order').value = '';
    document.getElementById('file').value = '';
    document.getElementById('remark').value = '';
    
    // Hide current file display
    document.getElementById('currentFileContainer').style.display = 'none';
    
    // Make file input required for new entries
    document.getElementById('file').required = true;
}

// Edit Newsletter function
function editNewsletter(newsletter) {
    console.log('Editing newsletter:', newsletter.id);
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Update Newsletter';
    document.getElementById('newsletter_id').value = newsletter.id;
    document.getElementById('department').value = newsletter.department;
    document.getElementById('title').value = newsletter.title;
    document.getElementById('display_order').value = newsletter.display_order;
    document.getElementById('remark').value = newsletter.remark;
    
    // Show current file if exists
    const currentFileContainer = document.getElementById('currentFileContainer');
    const currentFileLink = document.getElementById('currentFileLink');
    const currentFileName = document.getElementById('currentFileName');
    
    if (newsletter.file) {
        currentFileLink.href = newsletter.file;
        currentFileName.textContent = newsletter.file.split('/').pop();
        currentFileContainer.style.display = 'block';
        
        // File not required when editing (keep existing file)
        document.getElementById('file').required = false;
    } else {
        currentFileContainer.style.display = 'none';
        document.getElementById('file').required = true;
    }
    
    // Clear file input
    document.getElementById('file').value = '';
}

// Initialize DataTable
$(document).ready(function() {
    console.log('Initializing DataTable');
    
    $('#newslettersTable').DataTable({
        pageLength: 25,
        order: [[3, 'asc']], // Sort by Order column
        language: {
            search: "Search Newsletters:",
            lengthMenu: "Show _MENU_ newsletters per page",
            info: "Showing _START_ to _END_ of _TOTAL_ newsletters",
            infoEmpty: "No newsletters available",
            infoFiltered: "(filtered from _MAX_ total records)",
            zeroRecords: "No matching newsletters found"
        },
        columnDefs: [
            { orderable: false, targets: [4, 6] } // Disable sorting on File and Actions columns
        ]
    });
    
    console.log('✓ DataTable initialized');
});

console.log('✓ Department Newsletters page loaded');
</script>

</body>
</html>