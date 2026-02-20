<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Alumni Association - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include_once "../Admin/dbconfig.php"; ?>
    
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Alumni Association</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Alumni</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $alumni_query = "SELECT * FROM alumni WHERE display_order >= 0 ORDER BY display_order ASC, created_at DESC";
    $alumni_result = $conn->query($alumni_query);
    ?>
    
    <section class="py-5 bg-white">
        <div class="container">
            <?php if ($alumni_result && $alumni_result->num_rows > 0): ?>
                <?php while ($alumni = $alumni_result->fetch_assoc()): ?>
                    <?php 
                    $content = !empty($alumni['description']) ? $alumni['description'] : '';
                    $contentLength = strlen(strip_tags($content));
                    $isLong = $contentLength > 1000;
                    ?>
                    
                    <div class="facility-detail-card">
                        <h2 class="facility-main-title">
                            <i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($alumni['title']); ?>
                        </h2>
                        
                        <?php if (!empty($content)): ?>
                            <div class="facility-description <?php echo $isLong ? 'collapsible-content' : ''; ?>" 
                                 id="content-<?php echo $alumni['id']; ?>">
                                <?php 
                                if ($isLong) {
                                    $preview = substr(strip_tags($content), 0, 500) . '...';
                                    echo '<div class="preview-text">' . nl2br(htmlspecialchars($preview)) . '</div>';
                                    echo '<div class="full-text" style="display: none;">' . $content . '</div>';
                                } else {
                                    echo $content;
                                }
                                ?>
                            </div>
                            
                            <?php if ($isLong): ?>
                                <div class="mt-3">
                                    <button class="btn btn-primary" 
                                            onclick="toggleContent(<?php echo $alumni['id']; ?>)" 
                                            id="btn-<?php echo $alumni['id']; ?>">
                                        <i class="fas fa-chevron-down me-2"></i>Read More
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Alumni information will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <script>
    function toggleContent(id) {
        const contentDiv = document.getElementById('content-' + id);
        const btn = document.getElementById('btn-' + id);
        const previewText = contentDiv.querySelector('.preview-text');
        const fullText = contentDiv.querySelector('.full-text');
        
        if (fullText.style.display === 'none') {
            previewText.style.display = 'none';
            fullText.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-chevron-up me-2"></i>Read Less';
        } else {
            previewText.style.display = 'block';
            fullText.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Read More';
            contentDiv.closest('.facility-detail-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    </script>
</body>
</html>