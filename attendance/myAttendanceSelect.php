<?php
include('dbconfig.php');

function compare_terms_desc($left, $right) {
    return strnatcmp((string)$right, (string)$left);
}

$session_faculty_name = $_SESSION['Name'] ?? '';

$fac_id_stmt = $conn->prepare("SELECT id FROM faculty WHERE Name = ?");
$fac_id_stmt->bind_param('s', $session_faculty_name);
$fac_id_stmt->execute();
$fac_row = $fac_id_stmt->get_result()->fetch_assoc();
$fac_id_stmt->close();
$logged_faculty_id = $fac_row ? (string)$fac_row['id'] : '0';

$mappings_stmt = $conn->prepare("SELECT term FROM lecmapping WHERE faculty = ?");
$mappings_stmt->bind_param('s', $logged_faculty_id);
$mappings_stmt->execute();
$mappings_rows = $mappings_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$mappings_stmt->close();

$available_terms = [];
foreach ($mappings_rows as $mapping_row) {
    $term_value = trim((string)($mapping_row['term'] ?? ''));
    if ($term_value !== '') {
        $available_terms[$term_value] = true;
    }
}
$available_terms = array_keys($available_terms);
usort($available_terms, 'compare_terms_desc');
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="app-page-title mb-0"><i class="bi bi-calendar2-check me-2"></i>My Attendance</h1>
                <a href="addLectureMapping.php" class="btn btn-sm mapping-cta-btn">Add / Manage Mappings</a>
            </div>

            <?php if (empty($available_terms)): ?>
                <div class="app-card shadow-sm">
                    <div class="app-card-body">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>No lecture mappings found for your account.
                            <a href="addLectureMapping.php" class="alert-link">Create a mapping</a> to get started.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="app-card shadow-sm">
                    <div class="app-card-body py-4">
                        <div class="attendance-term-picker text-center">
                            <h4 class="mb-2">Select Term</h4>
                            <p class="text-muted mb-4">Choose a term to open its attendance page. Pending will be selected by default.</p>
                            <div class="attendance-term-badges">
                                <?php foreach ($available_terms as $term_option): ?>
                                    <a href="myAttendance.php?<?= htmlspecialchars(http_build_query(['term' => $term_option, 'status' => 'unfilled'])) ?>" class="attendance-term-badge">
                                        <?= htmlspecialchars($term_option) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.mapping-cta-btn {
    color: #fff;
    border: 0;
    border-radius: 0.4rem;
    background: linear-gradient(135deg, #1f7a8c, #2a9d8f);
    box-shadow: 0 10px 24px rgba(31, 122, 140, 0.22);
    font-weight: 600;
    letter-spacing: 0.2px;
    padding: 0.45rem 1rem;
    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}
.mapping-cta-btn:hover,
.mapping-cta-btn:focus {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(31, 122, 140, 0.28);
    filter: saturate(1.05);
}
.attendance-term-picker {
    max-width: 720px;
    margin: 0 auto;
}
.attendance-term-badges {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.attendance-term-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 110px;
    padding: 0.7rem 1.1rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #eef6ff, #dbeafe);
    border: 1px solid #93c5fd;
    color: #0f172a;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.attendance-term-badge:hover,
.attendance-term-badge:focus {
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.14);
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
}
@media (max-width: 767.98px) {
    .mapping-cta-btn {
        width: 100%;
        justify-content: center;
        padding: 0.62rem 0.9rem;
    }
    .attendance-term-badges {
        gap: 0.55rem;
    }
    .attendance-term-badge {
        min-width: 96px;
        padding: 0.62rem 0.9rem;
        font-size: 0.92rem;
    }
}
</style>

<?php include('footer.php'); ?>
</body>
</html>
<?php $conn->close(); ?>
