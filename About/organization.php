<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Organization - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="about-pages.css">
<style>
    .organization-charts-row {
        row-gap: 1.5rem;
    }

    .organization-chart-col {
        width: 100%;
    }

    .organization-chart-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        padding: 1rem;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
    }

    .organization-chart-frame {
        width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        padding: 0.5rem;
    }

    .organization-chart-image {
        display: block;
        width: 100%;
        height: auto;
        object-fit: contain;
        cursor: zoom-in;
        background: #fff;
    }

    .org-chart-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0, 0, 0, 0.95);
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .org-chart-modal-content {
        position: relative;
        width: min(95vw, 1400px);
        height: min(88vh, 900px);
        overflow: auto;
        background: #111827;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .org-chart-modal img {
        display: block;
        transition: width 0.15s ease;
        margin: 0 auto;
        max-width: none;
        width: 100%;
        height: auto;
    }

    .org-chart-modal-close {
        position: absolute;
        top: 16px;
        right: 20px;
        color: #fff;
        font-size: 34px;
        line-height: 1;
        cursor: pointer;
        z-index: 10002;
    }

    .org-chart-toolbar {
        position: absolute;
        top: 16px;
        left: 20px;
        display: flex;
        gap: 8px;
        align-items: center;
        z-index: 10002;
    }

    .org-chart-toolbar button {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: rgba(17, 24, 39, 0.7);
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 14px;
        min-width: 40px;
    }

    .org-chart-toolbar button:hover {
        background: rgba(31, 41, 55, 0.95);
    }

    .org-chart-zoom-label {
        color: #fff;
        font-size: 14px;
        min-width: 52px;
        text-align: center;
    }
</style>
<body>
    <?php include_once "../Admin/dbconfig.php"; ?>
    
    <!-- Top Info Bar -->
    <?php include '../assets/preload/topbar.php'; ?>

    <!-- Header -->
    <?php include '../assets/preload/header.php'; ?>

    <!-- Navigation -->
    <?php include '../assets/preload/navigation.php'; ?>

    <!-- Mobile Navigation -->
    <?php include '../assets/preload/mobilenav.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Organization</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Organization</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Organization Content -->
    <?php
    $org_query = "SELECT * FROM organization_structure ORDER BY display_order ASC, id DESC";
    $org_result = $conn->query($org_query);
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <!-- Organization Structure -->
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="section-title text-start">Organizational Structure</h2>
                    <p class="lead text-center">Our institute operates under a well-defined organizational structure ensuring efficient administration and academic excellence.</p>
                </div>
            </div>

            <!-- Organization Items -->
            <?php if ($org_result && $org_result->num_rows > 0): ?>
                <div class="row mb-5 organization-charts-row justify-content-center">
                    <?php while ($org = $org_result->fetch_assoc()): ?>
                        <div class="col-12 organization-chart-col">
                            <div class="organization-chart-card text-center">
                                <div class="organization-chart-frame mb-3">
                                    <?php if (!empty($org['image']) && file_exists("../Admin/" . $org['image'])): ?>
                                        <img src="../Admin/<?php echo $org['image']; ?>"
                                             alt="<?php echo htmlspecialchars($org['title']); ?>"
                                             class="organization-chart-image"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="placeholder-photo">
                                            <i class="fas fa-sitemap"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="org-info text-center">
                                    <h4><?php echo htmlspecialchars($org['title']); ?></h4>
                                    <?php if (!empty($org['description'])): ?>
                                        <div class="org-desc text-center">
                                            <?php echo $org['description']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <!-- Departments Section -->
            <?php
            $departments_query = "SELECT * FROM departments ORDER BY department ASC";
            $departments_result = $conn->query($departments_query);
            ?>
            <?php if ($departments_result && $departments_result->num_rows > 0): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="section-subtitle text-start mb-4">Academic Departments</h3>
                    </div>
                </div>
                <div class="row g-4">
                    <?php while ($dept = $departments_result->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="dept-card">
                                <div class="dept-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h5><?php echo $dept['department']; ?></h5>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <!-- Administrative Structure -->
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="section-subtitle text-start mb-4">Administrative Structure</h3>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5>Academic Section</h5>
                        <p>Manages academic activities, curriculum, examinations, and student records</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h5>Administration</h5>
                        <p>Handles administrative functions, HR, finance, and general operations</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h5>Training & Placement</h5>
                        <p>Coordinates industrial training, internships, and placement activities</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5>Library</h5>
                        <p>Manages library resources, digital content, and reading facilities</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Maintenance</h5>
                        <p>Responsible for infrastructure maintenance and facility management</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="admin-card">
                        <div class="admin-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5>Student Affairs</h5>
                        <p>Handles student welfare, grievances, and extracurricular activities</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>

    <div id="orgChartModal" class="org-chart-modal" onclick="closeOrgChartModal()">
        <span class="org-chart-modal-close" onclick="closeOrgChartModal()">&times;</span>
        <div class="org-chart-toolbar" onclick="event.stopPropagation()">
            <button type="button" onclick="zoomOutOrgChart()">-</button>
            <span id="orgChartZoomLabel" class="org-chart-zoom-label">100%</span>
            <button type="button" onclick="zoomInOrgChart()">+</button>
            <button type="button" onclick="resetOrgChartZoom()">Reset</button>
        </div>
        <div id="orgChartModalContent" class="org-chart-modal-content" onclick="event.stopPropagation()">
            <img id="orgChartModalImage" src="" alt="Organization Chart">
        </div>
    </div>

    <script>
        let orgChartZoom = 1;
        const orgChartMinZoom = 0.5;
        const orgChartMaxZoom = 4;
        const orgChartStep = 0.25;

        function updateOrgChartZoom() {
            const img = document.getElementById('orgChartModalImage');
            const label = document.getElementById('orgChartZoomLabel');

            if (!img || !label) {
                return;
            }

            img.style.width = (orgChartZoom * 100) + '%';
            label.textContent = Math.round(orgChartZoom * 100) + '%';
        }

        function openOrgChartModal(src, alt) {
            const modal = document.getElementById('orgChartModal');
            const img = document.getElementById('orgChartModalImage');
            const content = document.getElementById('orgChartModalContent');

            if (!modal || !img || !content) {
                return;
            }

            img.src = src;
            img.alt = alt || 'Organization Chart';
            orgChartZoom = 1;
            updateOrgChartZoom();

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            content.scrollTop = 0;
            content.scrollLeft = 0;
        }

        function closeOrgChartModal() {
            const modal = document.getElementById('orgChartModal');
            const img = document.getElementById('orgChartModalImage');
            if (!modal || !img) {
                return;
            }

            modal.style.display = 'none';
            img.src = '';
            document.body.style.overflow = 'auto';
        }

        function zoomInOrgChart() {
            orgChartZoom = Math.min(orgChartMaxZoom, orgChartZoom + orgChartStep);
            updateOrgChartZoom();
        }

        function zoomOutOrgChart() {
            orgChartZoom = Math.max(orgChartMinZoom, orgChartZoom - orgChartStep);
            updateOrgChartZoom();
        }

        function resetOrgChartZoom() {
            orgChartZoom = 1;
            updateOrgChartZoom();
        }

        document.querySelectorAll('.organization-chart-image').forEach(function (image) {
            image.addEventListener('click', function () {
                openOrgChartModal(this.src, this.alt);
            });
        });

        document.getElementById('orgChartModalContent').addEventListener('wheel', function (event) {
            event.preventDefault();
            if (event.deltaY < 0) {
                zoomInOrgChart();
            } else {
                zoomOutOrgChart();
            }
        }, { passive: false });

        document.addEventListener('keydown', function (event) {
            const modal = document.getElementById('orgChartModal');
            if (!modal || modal.style.display !== 'flex') {
                return;
            }

            if (event.key === 'Escape') {
                closeOrgChartModal();
            } else if (event.key === '+' || event.key === '=' || event.key === 'ArrowUp') {
                zoomInOrgChart();
            } else if (event.key === '-' || event.key === 'ArrowDown') {
                zoomOutOrgChart();
            } else if (event.key === '0') {
                resetOrgChartZoom();
            }
        });
    </script>
</body>
</html>
