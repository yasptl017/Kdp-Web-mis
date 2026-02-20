<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Create upload directory if not exists
$uploadDir = "uploads/departments/";
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
        "SELECT id, display_order FROM departments WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM departments
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM departments
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE departments SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE departments SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: add_dept.php");
    exit;
}

/* ===========================================
   ADD / UPDATE DEPARTMENT
=========================================== */
if (isset($_POST['save_department'])) {

    $id = $_POST['dept_id'];
    $department = $conn->real_escape_string($_POST['department']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']);
    $visibility = isset($_POST['visibility']) ? 1 : 0;

    // Handle existing photo
    $photoPath = "";
    if ($id != "") {
        $q = $conn->query("SELECT photo FROM departments WHERE id=$id");
        if ($q->num_rows > 0) {
            $photoPath = $q->fetch_assoc()['photo'];
        }
    }

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Delete old photo if exists
            if ($photoPath && file_exists($photoPath)) {
                unlink($photoPath);
            }

            // Upload new photo
            $newName = "dept_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $photoPath = $uploadDir . $newName;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
        } else {
            $message = "Invalid file format. Only JPG, PNG, GIF, WEBP allowed.";
            $messageType = "danger";
        }
    }

    // Handle photo removal
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        if ($photoPath && file_exists($photoPath)) {
            unlink($photoPath);
        }
        $photoPath = "";
    }

    if ($messageType != "danger") {
        $photoPath = $conn->real_escape_string($photoPath);

        if ($id == "") {
            // INSERT
            $sql = "INSERT INTO departments (department, display_order, description, photo, visibility)
                    VALUES ('$department', $display_order, '$description', '$photoPath', '$visibility')";
            $conn->query($sql);
            $message = "Department added successfully!";
            $messageType = "success";

        } else {
            // UPDATE
            $sql = "UPDATE departments SET 
                    department='$department',
                    display_order=$display_order,
                    description='$description',
                    photo='$photoPath',
                    visibility='$visibility'
                    WHERE id=$id";
            $conn->query($sql);
            $message = "Department updated successfully!";
            $messageType = "success";
        }
    }
}

/* ===========================================
   DELETE DEPARTMENT
=========================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Delete photo file
    $q = $conn->query("SELECT photo FROM departments WHERE id=$id");
    if ($q->num_rows > 0) {
        $photo = $q->fetch_assoc()['photo'];
        if ($photo && file_exists($photo)) {
            unlink($photo);
        }
    }
    
    $conn->query("DELETE FROM departments WHERE id=$id");
    $message = "Department deleted successfully!";
    $messageType = "success";
}

/* ===========================================
   FETCH ALL
=========================================== */
$departments = $conn->query("SELECT * FROM departments ORDER BY display_order ASC, id DESC");

?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
    .action-buttons{display:flex;gap:5px;justify-content:center}
    .dept-photo-thumb {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #ddd;
    }
    
    .current-photo-preview {
        position: relative;
        display: inline-block;
        margin-bottom: 15px;
    }
    
    .current-photo-preview img {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        border: 2px solid #ddd;
        object-fit: cover;
    }
    
    .remove-photo-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    .remove-photo-btn:hover {
        background: #c82333;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-3">
        <i class="fas fa-building me-2"></i>Manage Departments
    </h2>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ADD BUTTON -->
    <button class="btn btn-primary mb-3"
            data-bs-toggle="modal"
            data-bs-target="#deptModal"
            onclick="addDept()">
        <i class="fas fa-plus-circle"></i> Add Department
    </button>

    <!-- TABLE LIST -->
    <div class="form-card">
        <table id="deptTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Photo</th>
                    <th>Department</th>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Visibility</th>
                    <th width="200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $departments->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td class="text-center">
                            <?php if ($row['photo'] && file_exists($row['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($row['photo']); ?>" 
                                     alt="Department Photo" 
                                     class="dept-photo-thumb">
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-image" style="font-size: 30px;"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td class="text-center"><?php echo $row['display_order']; ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td class="text-center">
                            <?php if ($row['visibility'] == 1): ?>
                                <span class="badge bg-success">Visible</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Hidden</span>
                            <?php endif; ?>
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

                                <button class="btn btn-warning btn-sm"
                                        onclick='editDept(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                        data-bs-toggle="modal"
                                        data-bs-target="#deptModal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <a href="?delete=<?php echo $row['id']; ?>"
                                   onclick="return confirm('Delete this department and its photo?');"
                                   class="btn btn-danger btn-sm">
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

<!-- MODAL FOR ADD/EDIT -->
<div class="modal fade" id="deptModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Department</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="dept_id" id="dept_id">
                    <input type="hidden" name="remove_photo" id="remove_photo" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <input type="text" name="department" id="department" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                            <input type="number" name="display_order" id="display_order" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <input type="checkbox" name="visibility" id="visibility">
                            <span class="ms-1">Visible</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Short Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department Photo</label>
                        
                        <!-- Current Photo Preview -->
                        <div id="currentPhotoPreview" style="display: none;">
                            <div class="current-photo-preview">
                                <img id="currentPhotoImg" src="" alt="Current Photo">
                                <button type="button" class="remove-photo-btn" onclick="removeCurrentPhoto()" title="Remove Photo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <p class="text-muted small mb-2">Current photo (click × to remove)</p>
                        </div>

                        <!-- Upload New Photo -->
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Supported: JPG, PNG, GIF, WEBP. Max 5MB.</small>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_department" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- DATATABLE -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
console.log('=== Department Management ===');

$(document).ready(() => {
    $("#deptTable").DataTable({
        pageLength: 25,
        order: [[3, 'asc']]
    });
    console.log('✓ DataTable initialized');
});

/* ================================
   Add Department
================================ */
function addDept() {
    console.log('Adding new department');
    
    document.getElementById("modalTitle").innerHTML = "Add Department";
    document.getElementById("dept_id").value = "";
    document.getElementById("department").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("description").value = "";
    document.getElementById("visibility").checked = true;
    document.getElementById("photo").value = "";
    document.getElementById("remove_photo").value = "0";
    
    // Hide photo preview
    document.getElementById("currentPhotoPreview").style.display = "none";
}

/* ================================
   Edit Department
================================ */
function editDept(d) {
    console.log('Editing department:', d.id);
    
    document.getElementById("modalTitle").innerHTML = "Update Department";
    document.getElementById("dept_id").value = d.id;
    document.getElementById("department").value = d.department;
    document.getElementById("display_order").value = d.display_order;
    document.getElementById("description").value = d.description;
    document.getElementById("visibility").checked = (d.visibility == 1);
    document.getElementById("photo").value = "";
    document.getElementById("remove_photo").value = "0";
    
    // Show current photo if exists
    if (d.photo && d.photo !== '') {
        document.getElementById("currentPhotoImg").src = d.photo;
        document.getElementById("currentPhotoPreview").style.display = "block";
    } else {
        document.getElementById("currentPhotoPreview").style.display = "none";
    }
}

/* ================================
   Remove Current Photo
================================ */
function removeCurrentPhoto() {
    if (confirm('Are you sure you want to remove this photo?')) {
        document.getElementById("remove_photo").value = "1";
        document.getElementById("currentPhotoPreview").style.display = "none";
        console.log('Photo marked for removal');
    }
}

console.log('✓ Department Management page loaded');
</script>

</body>
</html>