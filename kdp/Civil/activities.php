<!DOCTYPE html>
<html lang="en">
<?php 
include 'dptname.php';
$page_title = "Activities - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
?>
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
                    <h1 class="page-title">Department Activities</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="aboutdpt.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Activities</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php include 'dptnavigation.php'; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Activities
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <label class="form-label fw-bold text-primary">
                                    <i class="fas fa-tags me-2"></i>Select Categories
                                </label>
                                <?php
                                $cat_query = "SELECT DISTINCT category FROM activities WHERE department = '" . $conn->real_escape_string($DEPARTMENT_NAME) . "' AND category IS NOT NULL AND category != '' ORDER BY category";
                                $cat_result = $conn->query($cat_query);
                                $selected_categories = isset($_GET['categories']) ? $_GET['categories'] : [];
                                ?>
                                
                                <div class="category-dropdown-wrapper">
                                    <button type="button" class="category-dropdown-btn" id="categoryDropdownBtn">
                                        <span id="categorySelectedText">All Categories</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="category-dropdown-menu" id="categoryDropdownMenu">
                                        <?php
                                        if ($cat_result && $cat_result->num_rows > 0):
                                            while ($cat = $cat_result->fetch_assoc()):
                                                $category = $cat['category'];
                                                $checked = in_array($category, $selected_categories) ? 'checked' : '';
                                        ?>
                                            <label class="category-checkbox-item">
                                                <input type="checkbox" name="categories[]" 
                                                       value="<?php echo htmlspecialchars($category); ?>" 
                                                       <?php echo $checked; ?>>
                                                <span><?php echo htmlspecialchars($category); ?></span>
                                            </label>
                                        <?php 
                                            endwhile;
                                        else: 
                                        ?>
                                            <div class="text-muted px-3 py-2">No categories found</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label fw-bold text-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>Date Range
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">From Date</label>
                                        <input type="date" class="form-control" name="date_from" 
                                               value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">To Date</label>
                                        <input type="date" class="form-control" name="date_to" 
                                               value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="activities.php" class="btn btn-secondary px-4">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $where_conditions = ["department = '$DEPARTMENT_NAME'"];
            
            if (!empty($_GET['categories'])) {
                $category_conditions = [];
                foreach ($_GET['categories'] as $cat) {
                    $cat_escaped = $conn->real_escape_string($cat);
                    $category_conditions[] = "category = '$cat_escaped'";
                }
                $where_conditions[] = "(" . implode(" OR ", $category_conditions) . ")";
            }
            
            if (!empty($_GET['date_from'])) {
                $date_from = $conn->real_escape_string($_GET['date_from']);
                $where_conditions[] = "date >= '$date_from'";
            }
            
            if (!empty($_GET['date_to'])) {
                $date_to = $conn->real_escape_string($_GET['date_to']);
                $where_conditions[] = "date <= '$date_to'";
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            $activities_query = "SELECT * FROM activities WHERE $where_clause ORDER BY date DESC";
            $activities_result = $conn->query($activities_query);
            $total_results = $activities_result->num_rows;
            ?>

            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="fas fa-info-circle me-3 fs-4"></i>
                <div>
                    Showing <strong><?php echo $total_results; ?></strong> 
                    <?php echo $total_results == 1 ? 'activity' : 'activities'; ?>
                </div>
            </div>

            <?php if ($activities_result && $activities_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($activity = $activities_result->fetch_assoc()): 
                        $photos = json_decode($activity['photos'], true);
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="activity-card" onclick="window.location.href='activity-details.php?id=<?php echo $activity['id']; ?>'" style="cursor: pointer;">
                                <div class="activity-category-badge">
                                    <?php echo htmlspecialchars($activity['category']); ?>
                                </div>

                                <?php if (!empty($photos) && is_array($photos) && count($photos) > 0): ?>
                                    <div class="activity-image">
                                        <img src="../Admin/<?php echo $photos[0]; ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>">
                                        <div class="activity-image-overlay">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="activity-image activity-no-image">
                                        <i class="fas fa-image"></i>
                                        <p>No Image</p>
                                    </div>
                                <?php endif; ?>

                                <div class="activity-content">
                                    <h5 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h5>
                                    
                                    <div class="activity-date mb-3">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?php echo date("F d, Y", strtotime($activity['date'])); ?>
                                    </div>

                                    <p class="activity-description">
                                        <?php 
                                        $desc = strip_tags($activity['description']);
                                        echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                        ?>
                                    </p>

                                    <button class="btn btn-primary btn-sm w-100 mt-3" onclick="event.stopPropagation(); window.location.href='activity-details.php?id=<?php echo $activity['id']; ?>'">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: #cbd5e1;"></i>
                    <h4 class="text-muted mt-3">No Activities Found</h4>
                    <p class="text-muted">Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <style>
    .category-dropdown-wrapper {
        position: relative;
        width: 100%;
    }
    
    .category-dropdown-btn {
        width: 100%;
        padding: 12px 16px;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
        font-size: 1rem;
    }
    
    .category-dropdown-btn:hover {
        border-color: #3b82f6;
    }
    
    .category-dropdown-btn i {
        transition: transform 0.3s;
        color: #6b7280;
    }
    
    .category-dropdown-btn.active i {
        transform: rotate(180deg);
    }
    
    .category-dropdown-menu {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }
    
    .category-dropdown-menu.show {
        display: block;
    }
    
    .category-checkbox-item {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        cursor: pointer;
        transition: background-color 0.2s;
        margin: 0;
    }
    
    .category-checkbox-item:hover {
        background-color: #f3f4f6;
    }
    
    .category-checkbox-item input[type="checkbox"] {
        margin-right: 10px;
        cursor: pointer;
        width: 18px;
        height: 18px;
    }
    
    .category-checkbox-item span {
        flex: 1;
        user-select: none;
    }
    
    .category-dropdown-menu::-webkit-scrollbar {
        width: 8px;
    }
    
    .category-dropdown-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .category-dropdown-menu::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .category-dropdown-menu::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .activity-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; height: 100%; position: relative; border: 2px solid transparent; }
    .activity-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); border-color: #f97316; }
    .activity-category-badge { position: absolute; top: 15px; right: 15px; background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; z-index: 2; box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3); }
    .activity-image { width: 100%; height: 250px; overflow: hidden; position: relative; }
    .activity-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .activity-card:hover .activity-image img { transform: scale(1.1); }
    .activity-image-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
    .activity-card:hover .activity-image-overlay { opacity: 1; }
    .activity-image-overlay i { color: white; font-size: 2rem; }
    .activity-no-image { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; }
    .activity-no-image i { font-size: 3rem; margin-bottom: 10px; }
    .activity-content { padding: 25px; }
    .activity-title { color: #1e3a8a; font-weight: 700; margin-bottom: 15px; font-size: 1.2rem; line-height: 1.4;}
    .activity-date { color: #6b7280; font-size: 0.9rem; font-weight: 500; }
    .activity-description { color: #6b7280; line-height: 1.7; min-height: 100px; }
    </style>

    <script>
    // Category dropdown functionality
    const dropdownBtn = document.getElementById('categoryDropdownBtn');
    const dropdownMenu = document.getElementById('categoryDropdownMenu');
    const selectedText = document.getElementById('categorySelectedText');
    
    // Toggle dropdown
    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
        dropdownBtn.classList.toggle('active');
    });
    
    // Prevent dropdown from closing when clicking inside
    dropdownMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        dropdownMenu.classList.remove('show');
        dropdownBtn.classList.remove('active');
    });
    
    // Update selected text
    function updateSelectedText() {
        const checkboxes = dropdownMenu.querySelectorAll('input[type="checkbox"]:checked');
        const count = checkboxes.length;
        
        if (count === 0) {
            selectedText.textContent = 'All Categories';
        } else if (count === 1) {
            selectedText.textContent = checkboxes[0].nextElementSibling.textContent;
        } else {
            selectedText.textContent = count + ' categories selected';
        }
    }
    
    // Update text when checkboxes change
    dropdownMenu.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedText);
    });
    
    // Initialize text on page load
    updateSelectedText();
    </script>
</body>
</html>