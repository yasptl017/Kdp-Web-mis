<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Student Section - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Student Section</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Students</a></li>
                            <li class="breadcrumb-item active">Student Section</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    
    <?php
    $ss_query = "SELECT * FROM ss WHERE display_order >= 0 ORDER BY display_order ASC, created_at DESC";
    $ss_result = $conn->query($ss_query);
    ?>
    
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($ss_result && $ss_result->num_rows > 0): ?>
                <?php while ($form = $ss_result->fetch_assoc()): ?>
                    <?php 
                    $hasFile = !empty($form['file']);
                    $hasDescription = !empty($form['description']);
                    
                    // Clean description
                    $description = '';
                    if ($hasDescription) {
                        $description = strip_tags($form['description']);
                        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                        $description = trim($description);
                    }
                    
                    // Check if description is long (more than 500 characters)
                    $isLong = strlen($description) > 500;
                    ?>
                    
                    <div class="facility-detail-card mb-4">
                        <h2 class="facility-main-title">
                            <i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($form['title']); ?>
                        </h2>
                        
                        <?php if ($hasDescription && !empty($description)): ?>
                            <div class="facility-description <?php echo $isLong ? 'collapsible-content' : ''; ?>" 
                                 id="desc-<?php echo $form['id']; ?>">
                                <?php 
                                if ($isLong) {
                                    $preview = substr($description, 0, 500) . '...';
                                    echo '<div class="preview-text">' . nl2br(htmlspecialchars($preview)) . '</div>';
                                    echo '<div class="full-text" style="display: none;">' . nl2br(htmlspecialchars($description)) . '</div>';
                                } else {
                                    echo nl2br(htmlspecialchars($description));
                                }
                                ?>
                            </div>
                            
                            <?php if ($isLong): ?>
                                <div class="mt-3">
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="toggleDescription(<?php echo $form['id']; ?>)" 
                                            id="btn-<?php echo $form['id']; ?>">
                                        <i class="fas fa-chevron-down me-2"></i>Read More
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($hasFile): ?>
                            <div class="mt-3">
                                <a href="../Admin/<?php echo htmlspecialchars($form['file']); ?>" 
                                   class="btn btn-accent" 
                                   target="_blank"
                                   download>
                                    <i class="fas fa-download me-2"></i>Download Form
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Student forms and applications will be available soon.
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <script>
    function toggleDescription(id) {
        const descDiv = document.getElementById('desc-' + id);
        const btn = document.getElementById('btn-' + id);
        const previewText = descDiv.querySelector('.preview-text');
        const fullText = descDiv.querySelector('.full-text');
        
        if (fullText.style.display === 'none') {
            previewText.style.display = 'none';
            fullText.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-chevron-up me-2"></i>Read Less';
        } else {
            previewText.style.display = 'block';
            fullText.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Read More';
            descDiv.closest('.facility-detail-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    </script>
    
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>