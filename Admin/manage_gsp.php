<!DOCTYPE html>
<html lang="en">
<?php
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
        "SELECT id, display_order FROM gsp WHERE id=$id"
    )->fetch_assoc();

    if ($current) {

        if ($direction === 'up') {
            $swap = $conn->query(
                "SELECT id, display_order FROM gsp
                 WHERE display_order < {$current['display_order']}
                 ORDER BY display_order DESC LIMIT 1"
            )->fetch_assoc();
        } else {
            $swap = $conn->query(
                "SELECT id, display_order FROM gsp
                 WHERE display_order > {$current['display_order']}
                 ORDER BY display_order ASC LIMIT 1"
            )->fetch_assoc();
        }

        if ($swap) {
            $conn->query(
                "UPDATE gsp SET display_order={$swap['display_order']}
                 WHERE id={$current['id']}"
            );
            $conn->query(
                "UPDATE gsp SET display_order={$current['display_order']}
                 WHERE id={$swap['id']}"
            );
        }
    }

    header("Location: manage_gsp.php");
    exit;
}

/* ADD / UPDATE */
if (isset($_POST['save_gsp'])) {

    $id = intval($_POST['gsp_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $display_order = intval($_POST['display_order']);
    $url = $conn->real_escape_string($_POST['url']);

    if ($id == 0) {
        $sql = "INSERT INTO gsp (title, display_order, url)
                VALUES ('$title', $display_order, '$url')";
        $conn->query($sql);
        $message = "Link added successfully!";
    } else {
        $sql = "UPDATE gsp SET 
                title='$title',
                display_order=$display_order,
                url='$url'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Link updated successfully!";
    }

    $messageType = "success";
}

/* DELETE */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM gsp WHERE id=$id");
    $message = "Link deleted!";
    $messageType = "success";
}

$records = $conn->query("SELECT * FROM gsp ORDER BY display_order ASC, id DESC");
?>

<head>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <style>
        .form-card{background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}
        .action-buttons{display:flex;gap:5px;justify-content:center}
    </style>
</head>

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content">

<h2 class="h4 mb-4"><i class="fas fa-globe me-2"></i>Manage GSP Links</h2>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <?php echo $message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<button class="btn btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#gspModal"
        onclick="addGSP()">
    <i class="fas fa-plus-circle"></i> Add Link
</button>

<div class="form-card">
    <table id="gspTable" class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th width="60">#</th>
                <th>Title</th>
                <th>Order</th>
                <th>URL</th>
                <th width="200">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php $i = 1; while($row = $records->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>
                <td class="text-center"><?php echo $row['display_order']; ?></td>
                <td><a href="<?php echo $row['url']; ?>" target="_blank"><?php echo $row['url']; ?></a></td>

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
                                onclick='editGSP(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#gspModal">
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Delete this link?');"
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

<!-- MODAL -->
<div class="modal fade" id="gspModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add Link</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="gsp_id" id="gsp_id">

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Display Order *</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL *</label>
                        <input type="text" name="url" id="url" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_gsp" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>

                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(function () {
    $('#gspTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc']]
    });
});

function addGSP() {
    document.getElementById("modalTitle").innerText = "Add Link";
    document.getElementById("gsp_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("display_order").value = "";
    document.getElementById("url").value = "";
}

function editGSP(data) {
    document.getElementById("modalTitle").innerText = "Update Link";
    document.getElementById("gsp_id").value = data.id;
    document.getElementById("title").value = data.title;
    document.getElementById("display_order").value = data.display_order;
    document.getElementById("url").value = data.url;
}
</script>

</body>
</html>