<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

// Add & Update User
if (isset($_POST['save_user'])) {
    $id = $_POST['user_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $passwordInput = $_POST['password'];

    // Duplicate email validation
    $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id <> '$id'");
    if ($check->num_rows > 0) {
        $message = "Email already exists! Please use another.";
        $messageType = "danger";
    } else {

        // Add or Update
        if ($id == "") { // Add
            if (empty($passwordInput)) {
                $message = "Password is required for new user!";
                $messageType = "danger";
            } else {
                $password = password_hash($passwordInput, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password, role)
                        VALUES ('$name', '$email', '$password', '$role')";
                if ($conn->query($sql) === TRUE) {
                    $message = "User added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "danger";
                }
            }
        } else { // Update
            if (!empty($passwordInput)) {
                $password = password_hash($passwordInput, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET 
                            name='$name', 
                            email='$email', 
                            password='$password',
                            role='$role' 
                        WHERE id=$id";
            } else {
                $sql = "UPDATE users SET 
                            name='$name', 
                            email='$email', 
                            role='$role' 
                        WHERE id=$id";
            }
            
            if ($conn->query($sql) === TRUE) {
                $message = "User updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $message = "User deleted successfully!";
    $messageType = "success";
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<body>
<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">
    <h2 class="h4 mb-4"><i class="fas fa-users me-2"></i>User Management</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#userModal" onclick="addUser()">
        <i class="fas fa-plus-circle me-2"></i>Add User
    </button>

    <!-- Users Table -->
    <div class="form-card">
        <table id="userTable" class="table table-bordered table-striped align-middle">
            <thead>
                <tr class="text-center">
                    <th width="60">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th width="160">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $users->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['role']; ?></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm"
                            onclick='editUser(<?php echo json_encode($row); ?>)'
                            data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <a href="?delete=<?php echo $row['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure to delete this user?');">
                           <i class="fas fa-trash me-1"></i>Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- User Modal -->
<div class="modal fade" id="userModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <input type="hidden" name="user_id" id="user_id">

                    <label class="form-label">Name *</label>
                    <input type="text" name="name" id="name" class="form-control mb-3" required>

                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control mb-3" required>

                    <label class="form-label" id="passwordLabel">
                        Password * 
                    </label>
                    <input type="password" name="password" id="password" class="form-control mb-3">

                    <label class="form-label">Role *</label>
                    <select name="role" id="role" class="form-control mb-3" required>
                        <option value="Admin">Admin</option>
                        <option value="Faculty">Faculty</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save
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

<!-- Page-specific Scripts (Load AFTER footer.php which has jQuery) -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
// Initialize DataTable
$(document).ready(function(){
    $('#userTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search Users:",
            lengthMenu: "Show _MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users available",
            zeroRecords: "No matching users found"
        }
    });
});

// Modal Functions
function addUser() {
    document.getElementById("modalTitle").innerText = "Add User";
    document.getElementById("passwordLabel").innerHTML = "Password *";
    document.getElementById("password").setAttribute("required", "required");
    document.getElementById("user_id").value = "";
    document.getElementById("name").value = "";
    document.getElementById("email").value = "";
    document.getElementById("password").value = "";
    document.getElementById("role").value = "Faculty";
}

function editUser(user) {
    document.getElementById("modalTitle").innerText = "Update User";
    document.getElementById("passwordLabel").innerHTML = 'Password <small class="text-muted">(Leave empty to keep old)</small>';
    document.getElementById("password").removeAttribute("required");
    document.getElementById("user_id").value = user.id;
    document.getElementById("name").value = user.name;
    document.getElementById("email").value = user.email;
    document.getElementById("password").value = "";
    document.getElementById("role").value = user.role;
}
</script>

</body>
</html>