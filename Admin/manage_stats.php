<!DOCTYPE html>
<html lang="en">
<?php
session_start();
include "dbconfig.php";
include "head.php";

$message = "";
$messageType = "";

/* ==================================================
   ADD / UPDATE STATS
   ================================================== */
if (isset($_POST['save_stats'])) {

    $id = intval($_POST['stats_id']);
    $students = $conn->real_escape_string($_POST['students']);
    $faculty = $conn->real_escape_string($_POST['faculty']);
    $departments = $conn->real_escape_string($_POST['departments']);
    $placement = $conn->real_escape_string($_POST['placement']);

    if ($id == 0) {
        // Check if any stats already exist
        $check = $conn->query("SELECT COUNT(*) as count FROM stats");
        $count = $check->fetch_assoc()['count'];
        
        if ($count > 0) {
            $message = "Stats already exist! Please update the existing record.";
            $messageType = "warning";
        } else {
            $sql = "INSERT INTO stats (students, faculty, departments, placement)
                    VALUES ('$students', '$faculty', '$departments', '$placement')";
            $conn->query($sql);
            $message = "Stats added successfully!";
            $messageType = "success";
        }
    } else {
        $sql = "UPDATE stats SET
                students='$students',
                faculty='$faculty',
                departments='$departments',
                placement='$placement'
                WHERE id=$id";
        $conn->query($sql);
        $message = "Stats updated successfully!";
        $messageType = "success";
    }
}

// Fetch current stats
$stats_result = $conn->query("SELECT * FROM stats LIMIT 1");
$current_stats = $stats_result->fetch_assoc();
?>

<!-- Page-specific CSS -->
<style>
    .stats-display-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .stat-preview-box {
        text-align: center;
        padding: 30px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .stat-preview-box:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-5px);
    }
    
    .stat-preview-icon {
        font-size: 50px;
        margin-bottom: 15px;
        opacity: 0.9;
    }
    
    .stat-preview-number {
        font-size: 42px;
        font-weight: 900;
        display: block;
        margin-bottom: 10px;
    }
    
    .stat-preview-label {
        font-size: 16px;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .last-updated {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        opacity: 0.8;
    }
</style>

<body>

<?php include "sidebar.php"; ?>
<?php include "header.php"; ?>

<main class="main-content" id="mainContent">

    <h2 class="h4 mb-4"><i class="fas fa-chart-bar me-2"></i>Manage Website Statistics</h2>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($current_stats): ?>
        <!-- Current Stats Display -->
        <div class="stats-display-card">
            <h4 class="text-center mb-4">
                <i class="fas fa-chart-line me-2"></i>Current Statistics Preview
            </h4>
            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-preview-box">
                        <div class="stat-preview-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="stat-preview-number"><?= htmlspecialchars($current_stats['students']); ?></span>
                        <span class="stat-preview-label">Students</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-preview-box">
                        <div class="stat-preview-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <span class="stat-preview-number"><?= htmlspecialchars($current_stats['faculty']); ?></span>
                        <span class="stat-preview-label">Faculty Members</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-preview-box">
                        <div class="stat-preview-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <span class="stat-preview-number"><?= htmlspecialchars($current_stats['departments']); ?></span>
                        <span class="stat-preview-label">Departments</span>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-preview-box">
                        <div class="stat-preview-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <span class="stat-preview-number"><?= htmlspecialchars($current_stats['placement']); ?></span>
                        <span class="stat-preview-label">Placement Rate</span>
                    </div>
                </div>
            </div>
            <div class="last-updated">
                <i class="fas fa-clock me-2"></i>Last Updated: 
                <?= date('F j, Y - g:i A', strtotime($current_stats['updated_at'])); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No statistics found. Please add the initial statistics below.
        </div>
    <?php endif; ?>

    <!-- Update Form -->
    <div class="form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                <?= $current_stats ? 'Update Statistics' : 'Add Statistics'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="stats_id" value="<?= $current_stats['id'] ?? 0; ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-users text-primary me-2"></i>
                            Students <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="students" 
                            class="form-control" 
                            value="<?= htmlspecialchars($current_stats['students'] ?? ''); ?>" 
                            placeholder="e.g., 2000+" 
                            required>
                        <small class="text-muted">Example: 2000+ or 1500</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-chalkboard-teacher text-success me-2"></i>
                            Faculty Members <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="faculty" 
                            class="form-control" 
                            value="<?= htmlspecialchars($current_stats['faculty'] ?? ''); ?>" 
                            placeholder="e.g., 50+" 
                            required>
                        <small class="text-muted">Example: 50+ or 45</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-building text-info me-2"></i>
                            Departments <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="departments" 
                            class="form-control" 
                            value="<?= htmlspecialchars($current_stats['departments'] ?? ''); ?>" 
                            placeholder="e.g., 4" 
                            required>
                        <small class="text-muted">Example: 4 or 6</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-trophy text-warning me-2"></i>
                            Placement Rate <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="placement" 
                            class="form-control" 
                            value="<?= htmlspecialchars($current_stats['placement'] ?? ''); ?>" 
                            placeholder="e.g., 95%" 
                            required>
                        <small class="text-muted">Example: 95% or 90%</small>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> These statistics will be displayed on the homepage. 
                    You can use formats like "2000+", "50+", "95%" etc.
                </div>

                <div class="text-end">
                    <button type="submit" name="save_stats" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>
                        <?= $current_stats ? 'Update Statistics' : 'Save Statistics'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

<?php include "footer.php"; ?>

<script>
console.log('=== Stats Management ===');
console.log('✓ Stats Management page loaded');
</script>

</body>
</html>