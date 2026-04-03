<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<?php include('head.php'); ?>
<head>
    <style>
        /* Enhanced Dashboard Styling */
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 0.75rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .dashboard-header h1 {
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .dashboard-header .welcome-text {
            font-size: 0.95rem;
            opacity: 0.95;
            margin: 0;
        }

        .dashboard-header .user-name {
            font-weight: 600;
            opacity: 1;
        }

        /* Enhanced Quick Access Cards */
        .quick-access-card {
            background: white;
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }

        .quick-access-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .quick-access-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .quick-access-card:hover::before {
            transform: scaleX(1);
        }

        .quick-access-card-body {
            padding: 2rem 1.5rem;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            transition: all 0.3s ease;
        }

        .quick-access-card:nth-child(1) .icon-wrapper {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .quick-access-card:nth-child(2) .icon-wrapper {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .quick-access-card:nth-child(3) .icon-wrapper {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .quick-access-card:nth-child(4) .icon-wrapper {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .quick-access-card:hover .icon-wrapper {
            transform: scale(1.1);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .card-description {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
        }

        /* Quick Links Section */
        .quick-links-section {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
        }

        .quick-links-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .quick-links-section h3::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }

        .quick-link-btn {
            background: white;
            border: 2px solid #e2e8f0;
            color: #667eea;
            padding: 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-link-btn i {
            font-size: 1.5rem;
        }

        .quick-link-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }

            .dashboard-header h1 {
                font-size: 1.5rem;
            }

            .quick-access-card-body {
                padding: 1.5rem 1rem;
            }

            .quick-links-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 0.75rem;
            }

            .quick-link-btn {
                padding: 0.75rem;
                font-size: 0.8rem;
            }

            .quick-link-btn i {
                font-size: 1.25rem;
            }
        }

        /* Make card links fully clickable */
        .quick-access-card {
            position: relative;
        }

        .quick-access-card .app-card-link-mask {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 10;
            display: block;
            text-decoration: none;
            pointer-events: auto;
            cursor: pointer;
        }

        .quick-access-card-body {
            position: relative;
            z-index: 5;
            pointer-events: none;
        }

        .quick-access-card {
            position: relative;
            overflow: visible;
        }
    </style>
</head>
<body class="app">
<?php include('header.php'); ?>

<div class="app-wrapper">
    <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="container-xl">

            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <p class="welcome-text">
                    Welcome back, <span class="user-name"><?php echo isset($_SESSION['Name']) ? htmlspecialchars($_SESSION['Name']) : 'User'; ?></span>
                </p>
            </div>

            <!-- Quick Access Cards -->
            <div class="row g-3 g-md-4 mb-5">

                <div class="col-6 col-lg-3">
                    <div class="quick-access-card">
                        <div class="quick-access-card-body">
                            <div class="icon-wrapper">
                                <i class="bi bi-calendar2-check"></i>
                            </div>
                            <h4 class="card-title">My Attendance</h4>
                        </div>
                        <a class="app-card-link-mask" href="myAttendanceSelect.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="quick-access-card">
                        <div class="quick-access-card-body">
                            <div class="icon-wrapper">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <h4 class="card-title">Edit Attendance</h4>
                           
                        </div>
                        <a class="app-card-link-mask" href="editAttendance.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="quick-access-card">
                        <div class="quick-access-card-body">
                            <div class="icon-wrapper">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <h4 class="card-title">Add Mapping</h4>
         
                        </div>
                        <a class="app-card-link-mask" href="addLectureMapping.php"></a>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="quick-access-card">
                        <div class="quick-access-card-body">
                            <div class="icon-wrapper">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                            </div>
                            <h4 class="card-title">Muster Report</h4>
                            
                        </div>
                        <a class="app-card-link-mask" href="lecmuster.php"></a>
                    </div>
                </div>

            </div>

            <!-- Quick Manage Links -->
            <div class="quick-links-section">
                <h3>Quick Links</h3>
                <div class="quick-links-grid">
                    <a href="managefaculty.php" class="quick-link-btn">
                        <i class="bi bi-person-badge"></i>
                        <span>Faculty</span>
                    </a>
                    <a href="managestudents.php" class="quick-link-btn">
                        <i class="bi bi-people"></i>
                        <span>Students</span>
                    </a>
                    <a href="managesubjects.php" class="quick-link-btn">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>Subjects</span>
                    </a>
                    <a href="managesemester.php" class="quick-link-btn">
                        <i class="bi bi-calendar3"></i>
                        <span>Semester</span>
                    </a>
                    <a href="manageslot.php" class="quick-link-btn">
                        <i class="bi bi-clock"></i>
                        <span>Slots</span>
                    </a>
                    <a href="managelabs.php" class="quick-link-btn">
                        <i class="bi bi-building"></i>
                        <span>Labs</span>
                    </a>
                    <a href="bulkupload.php" class="quick-link-btn">
                        <i class="bi bi-upload"></i>
                        <span>Bulk Upload</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
