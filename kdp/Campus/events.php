<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Events & Activities - K.D. Polytechnic"; ?>
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
                    <h1 class="page-title">Events & Activities</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Campus</a></li>
                            <li class="breadcrumb-item active">Events</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <!-- Compact Filter -->
            <div class="filter-compact">
                <form method="GET" action="" id="filterForm">
                    <!-- Categories Row -->
                    <div class="filter-row">
                        <label class="filter-label">
                            <i class="fas fa-tag"></i>
                        </label>
                        <div class="category-tabs-container">
                            <?php
                            $cat_query = "SELECT DISTINCT category FROM event_activities WHERE category IS NOT NULL AND category != '' ORDER BY category";
                            $cat_result = $conn->query($cat_query);
                            $selected_categories = isset($_GET['categories']) ? $_GET['categories'] : [];
                            ?>
                            
                            <label class="cat-tab <?php echo empty($selected_categories) ? 'active' : ''; ?>">
                                <input type="radio" name="category_filter" value="" <?php echo empty($selected_categories) ? 'checked' : ''; ?>>
                                <span>All</span>
                            </label>
                            
                            <?php
                            if ($cat_result && $cat_result->num_rows > 0):
                                while ($cat = $cat_result->fetch_assoc()):
                                    $category = $cat['category'];
                                    $is_active = in_array($category, $selected_categories);
                            ?>
                                <label class="cat-tab <?php echo $is_active ? 'active' : ''; ?>">
                                    <input type="checkbox" name="categories[]" 
                                           value="<?php echo htmlspecialchars($category); ?>" 
                                           <?php echo $is_active ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($category); ?></span>
                                </label>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </div>
                    </div>

                    <!-- Date & Actions Row -->
                    <div class="filter-row">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                        </label>
                        <div class="date-group">
                            <input type="date" class="date-input" name="date_from" 
                                   value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                            <span class="to-text">to</span>
                            <input type="date" class="date-input" name="date_to" 
                                   value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="btn-apply">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="events.php" class="btn-clear">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php
            $where_conditions = ["1=1"];
            
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
                $where_conditions[] = "event_date >= '$date_from'";
            }
            
            if (!empty($_GET['date_to'])) {
                $date_to = $conn->real_escape_string($_GET['date_to']);
                $where_conditions[] = "event_date <= '$date_to'";
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            $events_query = "SELECT * FROM event_activities WHERE $where_clause ORDER BY event_date DESC";
            $events_result = $conn->query($events_query);
            $total_results = $events_result->num_rows;
            ?>

            <div class="results-badge">
                <i class="fas fa-info-circle"></i>
                <strong><?php echo $total_results; ?></strong> <?php echo $total_results == 1 ? 'Event' : 'Events'; ?>
            </div>

            <?php if ($events_result && $events_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($event = $events_result->fetch_assoc()): 
                        $photos = json_decode($event['photos'], true);
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="activity-card" onclick="window.location.href='event-details.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                                <div class="activity-category-badge">
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </div>

                                <?php if (!empty($photos) && is_array($photos) && count($photos) > 0): ?>
                                    <div class="activity-image">
                                        <img src="../Admin/<?php echo $photos[0]; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
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
                                    <h5 class="activity-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    
                                    <div class="activity-date mb-2">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <?php echo date("F d, Y", strtotime($event['event_date'])); ?>
                                    </div>

                                    <p class="activity-description">
                                        <?php 
                                        $desc = strip_tags($event['description']);
                                        echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: #cbd5e1;"></i>
                    <h4 class="text-muted mt-3">No Events Found</h4>
                    <p class="text-muted">Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <style>
    /* Compact Filter */
    .filter-compact {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 15px 20px;
        margin-bottom: 25px;
    }

    .filter-row {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 12px;
    }

    .filter-row:last-child {
        margin-bottom: 0;
    }

    .filter-label {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    /* Category Tabs */
    .category-tabs-container {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        flex: 1;
        padding: 2px 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .category-tabs-container::-webkit-scrollbar {
        display: none;
    }

    .cat-tab {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        font-size: 0.85rem;
        font-weight: 500;
        color: #475569;
        user-select: none;
        margin: 0;
        flex-shrink: 0;
    }

    .cat-tab input {
        display: none;
    }

    .cat-tab span {
        display: block;
    }

    .cat-tab:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .cat-tab.active {
        background: #1e3a8a;
        color: white;
        border-color: #1e3a8a;
        font-weight: 600;
    }

    /* Date Group */
    .date-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .date-input {
        flex: 1;
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        min-width: 0;
    }

    .date-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .to-text {
        color: #64748b;
        font-weight: 500;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    /* Filter Buttons */
    .filter-buttons {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .btn-apply,
    .btn-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-apply {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: white;
    }

    .btn-apply:hover {
        background: linear-gradient(135deg, #1e40af, #2563eb);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-clear {
        background: #f1f5f9;
        color: #64748b;
        padding: 8px 12px;
    }

    .btn-clear:hover {
        background: #e2e8f0;
        color: #475569;
    }

    /* Results Badge */
    .results-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        padding: 10px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
        border: 1px solid #bfdbfe;
    }

    .results-badge i {
        font-size: 1rem;
    }

    /* Activity Cards */
    .activity-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; height: 100%; position: relative; border: 2px solid transparent; }
    .activity-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); border-color: #f97316; }
    .activity-category-badge { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; z-index: 2; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3); }
    .activity-image { width: 100%; height: 240px; overflow: hidden; position: relative; }
    .activity-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .activity-card:hover .activity-image img { transform: scale(1.08); }
    .activity-image-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
    .activity-card:hover .activity-image-overlay { opacity: 1; }
    .activity-image-overlay i { color: white; font-size: 2rem; }
    .activity-no-image { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; }
    .activity-no-image i { font-size: 3rem; margin-bottom: 10px; }
    .activity-content { padding: 20px; }
    .activity-title { color: #1e3a8a; font-weight: 700; margin-bottom: 12px; font-size: 1.1rem; line-height: 1.4; min-height: 55px; }
    .activity-date { color: #6b7280; font-size: 0.85rem; font-weight: 500; }
    .activity-description { color: #6b7280; line-height: 1.6; font-size: 0.9rem; }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-compact {
            padding: 12px 15px;
        }

        .filter-row {
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-label {
            width: 32px;
            height: 32px;
            font-size: 0.85rem;
        }

        .category-tabs-container {
            flex: 1 1 100%;
            order: 2;
        }

        .cat-tab {
            padding: 7px 14px;
            font-size: 0.8rem;
        }

        .date-group {
            flex: 1 1 100%;
            order: 3;
        }

        .date-input {
            font-size: 0.8rem;
            padding: 7px 10px;
        }

        .filter-buttons {
            order: 4;
        }

        .btn-apply,
        .btn-clear {
            padding: 7px 14px;
            font-size: 0.8rem;
        }
    }
    </style>

    <script>
    // Dynamic category filtering
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const input = this.querySelector('input');
            const form = document.getElementById('filterForm');
            
            if (input.type === 'radio') {
                // "All Categories" selected
                document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.cat-tab input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                    cb.closest('.cat-tab').classList.remove('active');
                });
                // Auto-submit for immediate filtering
                form.submit();
            } else {
                // Category checkbox
                input.checked = !input.checked;
                this.classList.toggle('active', input.checked);
                
                // Uncheck "All Categories"
                const radioBtn = document.querySelector('.cat-tab input[type="radio"]');
                if (radioBtn && input.checked) {
                    radioBtn.checked = false;
                    radioBtn.closest('.cat-tab').classList.remove('active');
                }
                
                // If no categories selected, select "All"
                const anyChecked = Array.from(document.querySelectorAll('.cat-tab input[type="checkbox"]'))
                    .some(cb => cb.checked);
                
                if (!anyChecked && radioBtn) {
                    radioBtn.checked = true;
                    radioBtn.closest('.cat-tab').classList.add('active');
                }
                
                // Auto-submit for immediate filtering
                form.submit();
            }
        });
    });

    // Prevent form submit on Enter key in date inputs (optional)
    document.querySelectorAll('.date-input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filterForm').submit();
            }
        });
    });
    </script>
</body>
</html>