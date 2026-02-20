<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Upload directory
$uploadDir = "uploads/slider/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Delete slider
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $q = $conn->query("SELECT slider_image FROM slider WHERE id=$id");
    if ($q && $q->num_rows > 0) {
        $data = $q->fetch_assoc();
        if (file_exists($data['slider_image'])) unlink($data['slider_image']);
    }
    $conn->query("DELETE FROM slider WHERE id=$id");
    $message = "Slider deleted successfully!";
    $messageType = "success";
}

// Add slider
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);

    $imageName = "";
    if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $imageName = $uploadDir . time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['slider_image']['tmp_name'], $imageName);
        }
    }

    $sql = "INSERT INTO slider (slider_image, title, description)
            VALUES ('$imageName', '$title', '$description')";
    if ($conn->query($sql) === TRUE) {
        $message = "Slider added successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}

$result = $conn->query("SELECT * FROM slider ORDER BY id DESC");
?>
<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    <h2 class="h4 mb-4"><i class="fas fa-sliders-h me-2"></i>Slider Management</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Slider Form -->
    <form method="POST" enctype="multipart/form-data" class="form-card mb-4">
        <h5><i class="fas fa-plus-circle me-2"></i>Add New Slider</h5>

        <div class="mb-3">
            <label class="form-label">Slider Image *</label>
            <input type="file" name="slider_image" class="form-control" accept="image/*" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" placeholder="Enter slider title" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description *</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Enter description here..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Save Slider
        </button>
    </form>

    <!-- Slider Table -->
    <div class="form-card">
        <h5><i class="fas fa-table me-2"></i>Slider List</h5>
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th width="140">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="text-center">
                                <img src="<?php echo $row['slider_image']; ?>" style="height:60px;border-radius:4px;">
                            </td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
                            <td class="text-center">
                                <a href="?delete=<?php echo $row['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure to delete this slider?');">
                                   <i class="fas fa-trash me-1"></i>Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No sliders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include "footer.php"; ?>
</body>
</html>