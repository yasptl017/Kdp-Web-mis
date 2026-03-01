<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/organization/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ===================================
   ADD / UPDATE
   =================================== */
if (isset($_POST['save_org'])) {

    $id = intval($_POST['org_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $description = $conn->real_escape_string($_POST['description']); // Summernote HTML content (optional)

    // Get old image
    $oldImage = "";
    if ($id > 0) {
        $q = $conn->query("SELECT image FROM organization_structure WHERE id=$id LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $oldImage = $q->fetch_assoc()['image'];
        }
    }

    $imagePath = $oldImage;

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $fileName = "org_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $imagePath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);

            if (!empty($oldImage) && file_exists($oldImage)) unlink($oldImage);
        } else {
            $message = "Invalid image format!";
            $messageType = "danger";
        }
    }

    if (empty($message)) {
        if ($id == 0) {
            $sql = "INSERT INTO organization_structure (title, image, description, display_order)
                    VALUES ('$title', '$imagePath', '$description', $display_order)";
            $conn->query($sql);
            $message = "Organization item added successfully!";
        } else {
            $sql = "UPDATE organization_structure SET 
                    title='$title',
                    image='$imagePath',
                    description='$description',
                    display_order=$display_order
                    WHERE id=$id";
            $conn->query($sql);
            $message = "Organization item updated successfully!";
        }

        $messageType = "success";
    }
}

/* ===================================
   DELETE
   =================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $q = $conn->query("SELECT image FROM organization_structure WHERE id=$id LIMIT 1");
    if ($q && $q->num_rows > 0) {
        $image = $q->fetch_assoc()['image'];
        if (file_exists($image)) unlink($image);
    }

    $conn->query("DELETE FROM organization_structure WHERE id=$id");
    $message = "Organization item deleted!";
    $messageType = "success";
}

// Fetch all records
$records = $conn->query("SELECT * FROM organization_structure ORDER BY display_order ASC, id DESC");
?>

<head>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <!-- Summernote -->
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-sitemap me-2"></i>Manage Organization Structure</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<button class="btn btn-primary mb-3" data-bs-toggle="modal"
        data-bs-target="#orgModal" onclick="addOrg()">
    <i class="fas fa-plus-circle"></i> Add Item
</button>

<div class="form-card">
    <table id="orgTable" class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th width="120">Image</th>
                <th width="90">Order</th>
                <th width="140">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>

                <td class="text-center">
                    <?php if ($row['image'] && file_exists($row['image'])): ?>
                        <a href="<?php echo $row['image']; ?>" target="_blank" class="btn btn-info btn-sm">View</a>
                    <?php else: ?>
                        <span class="text-muted">No Image</span>
                    <?php endif; ?>
                </td>

                <td class="text-center"><?php echo $row['display_order']; ?></td>

                <td class="text-center">
                    <button class="btn btn-warning btn-sm"
                            onclick='editOrg(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                            data-bs-toggle="modal"
                            data-bs-target="#orgModal">
                        <i class="fas fa-edit"></i>
                    </button>

                    <a href="?delete=<?php echo $row['id']; ?>"
                       onclick="return confirm('Delete this item?');"
                       class="btn btn-danger btn-sm">
                       <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

</main>

<!-- MODAL -->
<div class="modal fade" id="orgModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Item</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="org_id" id="org_id">

                    <label>Title *</label>
                    <input type="text" name="title" id="title"
                           class="form-control mb-3" required>

                    <label>Image</label>
                    <input type="file" name="image" id="image"
                           class="form-control mb-3" accept=".jpg,.jpeg,.png,.webp,.gif">

                    <label>Order *</label>
                    <input type="number" name="display_order" id="display_order"
                           class="form-control mb-3" required>

                    <label>Description (Optional)</label>
                    <textarea name="description" id="description"
                              class="form-control"></textarea>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_org" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- JS -->
<script>
$(document).ready(function () {
    $('#orgTable').DataTable({
        order: [[3, 'asc']]
    });
});

// Summernote config
// Summernote config moved to summernote-config.js

// Single shown.bs.modal handler: init Summernote, then load pending content
$('#orgModal').on('shown.bs.modal', function () {
    if (!$('#description').next('.note-editor').length) {
        initSummernote('#description', {height: 250, placeholder: "Enter description (optional)..."});
    }
    if (window.pendingDescription !== undefined) {
        $('#description').summernote('code', window.pendingDescription || '');
        window.pendingDescription = undefined;
    }
});

// Destroy on close
$('#orgModal').on('hidden.bs.modal', function () {
    if ($('#description').next('.note-editor').length) {
        $('#description').summernote('destroy');
    }
    window.pendingDescription = undefined;
});

// Add function
function addOrg() {
    document.getElementById("modalTitle").innerText = "Add Item";
    document.getElementById("org_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("image").value = "";
    document.getElementById("display_order").value = "";
    window.pendingDescription = undefined;
}

// Edit function
function editOrg(f) {
    document.getElementById("modalTitle").innerText = "Update Item";
    document.getElementById("org_id").value = f.id;
    document.getElementById("title").value = f.title;
    document.getElementById("display_order").value = f.display_order;

    window.pendingDescription = f.description;
}
</script>

</body>
</html>
