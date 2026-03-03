<!DOCTYPE html>
<html lang="en">
<?php 
include_once "../Admin/dbconfig.php";

// Get notice ID from URL
$notice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch notice details
$notice_query = "SELECT * FROM cnb WHERE id = $notice_id";
$notice_result = $conn->query($notice_query);
$notice = $notice_result->fetch_assoc();

if (!$notice) {
    header("Location: notice-board.php");
    exit();
}

$page_title = $notice['title'] . " - K.D. Polytechnic";
?>
<?php include '../assets/preload/head.php'; ?>
<style>
.notice-details-section {
    padding: 2.75rem 0;
}
.notice-detail-shell {
    width: 100%;
    max-width: none;
    margin: 0;
}
.page-header .page-title {
    text-align: left;
    margin-bottom: 8px;
}
.notice-detail-card {
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    width: 100%;
}
.notice-detail-card h1,
.notice-detail-card h2,
.notice-detail-card h3,
.notice-detail-card h4,
.notice-detail-card h5,
.notice-detail-card h6 {
    text-align: left;
}
.notice-detail-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    border: 1px solid #1d4ed8;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 24px;
    text-align: left;
}
.notice-meta {
    margin-bottom: 8px;
}
.notice-date {
    color: #dbeafe;
    font-weight: 600;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
}
.notice-detail-title {
    color: #ffffff;
    font-weight: 700;
    font-size: clamp(1.45rem, 2vw, 1.85rem);
    line-height: 1.35;
    margin: 0;
}
.section-subtitle {
    color: #1e3a8a;
    font-weight: 700;
    margin: 0 0 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e7eb;
    font-size: 1.08rem;
}
.notice-detail-description {
    margin-bottom: 26px;
}
/* Description content wrapper */
.description-content {
    text-align: left;
    margin: 0;
}
/* Force consistent typography inside Summernote/Word-pasted content */
.description-content *,
.description-content p,
.description-content span,
.description-content div,
.description-content li,
.description-content td,
.description-content th {
    font-family: 'Poppins', 'Open Sans', sans-serif !important;
    color: #212529 !important;
    font-size: 1rem !important;
    line-height: 1.75 !important;
    text-align: left !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}
/* Headings: slightly larger, left-aligned */
.description-content h1,
.description-content h2,
.description-content h3,
.description-content h4,
.description-content h5,
.description-content h6 {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    margin: 14px 0 8px !important;
    padding: 0 !important;
    text-align: left !important;
    color: #1e3a8a !important;
}
/* Paragraphs */
.description-content p {
    margin: 0 0 10px 0 !important;
    padding: 0 !important;
}
/* Links */
.description-content a,
.description-content a span {
    color: #1e3a8a !important;
    word-break: break-all;
}
.description-content a:hover,
.description-content a:hover span {
    color: #f97316 !important;
}
/* Word/MsoNormal reset */
.description-content .MsoNormal,
.description-content [class^="Mso"] {
    margin: 0 0 8px 0 !important;
    padding: 0 !important;
}
/* Strip ALL inline color/background */
.description-content *[style] {
    color: #212529 !important;
    background-color: transparent !important;
    background: transparent !important;
}
.description-content a[style],
.description-content a *[style] {
    color: #1e3a8a !important;
}
/* Lists */
.description-content ul,
.description-content ol {
    padding-left: 1.5rem !important;
    margin: 0 0 10px 0 !important;
}
/* Tables */
.description-content table {
    width: auto !important;
    border-collapse: collapse !important;
    margin: 10px 0 !important;
}
.description-content td,
.description-content th {
    padding: 6px 10px !important;
    border: 1px solid #dee2e6 !important;
}
/* Empty paragraph gaps */
.description-content p:empty,
.description-content p br:only-child {
    margin: 0 !important;
    line-height: 1 !important;
}
.notice-detail-file {
    margin-bottom: 26px;
}
.file-preview-box {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f8fafc;
    border: 1px solid #dbe2ea;
    border-radius: 12px;
    padding: 16px 18px;
    flex-wrap: wrap;
}
.file-icon { font-size: 2.2rem; color: #dc3545; }
.file-info { flex: 1; min-width: 120px; }
.file-name { font-weight: 600; margin: 0; color: #1e3a8a; }
.file-size { margin: 4px 0 0 0; color: #6b7280; font-size: 0.9rem; }
.file-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
.notice-detail-footer {
    border-top: 2px solid #f1f5f9;
    padding-top: 20px;
    margin-top: 4px;
    text-align: left;
}
@media (max-width: 768px) {
    .notice-details-section { padding: 1.75rem 0; }
    .notice-detail-card { padding: 20px; border-radius: 14px; }
    .notice-detail-header { padding: 16px; margin-bottom: 18px; border-radius: 12px; }
    .notice-detail-title { font-size: 1.35rem; }
    .notice-detail-description,
    .notice-detail-file { margin-bottom: 20px; }
    .file-preview-box { flex-direction: column; align-items: flex-start; padding: 14px; gap: 12px; }
    .file-actions { width: 100%; margin-left: 0; }
    .file-actions .btn { width: 100%; }
}
</style>
<body>
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Notice Details</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item"><a href="notice-board.php">Notice Board</a></li>
                            <li class="breadcrumb-item active">Notice Details</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="notice-details-section bg-light">
        <div class="container-fluid px-3 px-md-4 px-lg-5 notice-detail-shell">
            <div class="row">
                <div class="col-12">
                    <div class="notice-detail-card">
                        <!-- Notice Header -->
                        <div class="notice-detail-header">
                            <div class="notice-meta">
                                <span class="notice-date">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <?php echo date("d F Y", strtotime($notice['notice_date'])); ?>
                                </span>
                            </div>
                            <h2 class="notice-detail-title"><?php echo $notice['title']; ?></h2>
                        </div>

                        <!-- Notice Description -->
                        <?php if (!empty($notice['description'])): ?>
                            <div class="notice-detail-description">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </h5>
                                <div class="description-content">
                                    <?php echo $notice['description']; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Notice File -->
                        <?php if (!empty($notice['file'])): ?>
                            <div class="notice-detail-file">
                                <h5 class="section-subtitle">
                                    <i class="fas fa-file-pdf me-2"></i>Attached Document
                                </h5>
                                <div class="file-preview-box">
                                    <div class="file-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="file-info">
                                        <p class="file-name">Notice Document</p>
                                        <p class="file-size">PDF File</p>
                                    </div>
                                    <div class="file-actions">
                                        <a href="../Admin/<?php echo $notice['file']; ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="../Admin/<?php echo $notice['file']; ?>" class="btn btn-accent" download>
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Back Button -->
                        <div class="notice-detail-footer">
                            <a href="notice-board.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Notice Board
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
