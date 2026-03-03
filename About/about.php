<!DOCTYPE html>
<html lang="en">
<?php $page_title = "About Institute - K.D. Polytechnic"; ?>
<?php include '../assets/preload/head.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="about-pages.css">
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
                    <h1 class="page-title">About Institute</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">About Institute</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- About Content Section -->
    <?php
    $college_query = "SELECT * FROM college_details LIMIT 1";
    $college_result = $conn->query($college_query);
    $college = $college_result->fetch_assoc();
    ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="content-box">
                <h2 class="section-title text-start">About <?php echo $college['college_name']; ?></h2>
                <?php if (!empty($college['college_photo']) && file_exists('../Admin/' . $college['college_photo'])): ?>
                <div class="row align-items-start g-4">
                    <div class="col-lg-5 col-md-5">
                        <img src="../Admin/<?php echo htmlspecialchars($college['college_photo']); ?>"
                             alt="<?php echo htmlspecialchars($college['college_name']); ?>"
                             class="img-fluid rounded shadow-sm w-100"
                             style="object-fit: cover; max-height: 400px; cursor: zoom-in;"
                             onclick="openAboutPhoto(this.src, this.alt)">
                    </div>
                    <div class="col-lg-7 col-md-7">
                        <div class="content-text">
                            <?php echo $college['college_description']; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="content-text">
                    <?php echo $college['college_description']; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- College Info Cards -->
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Address</h5>
                        <p><?php echo nl2br($college['address']); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Contact</h5>
                        <p><a href="tel:<?php echo $college['contact_no']; ?>"><?php echo $college['contact_no']; ?></a></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email</h5>
                        <p><a href="mailto:<?php echo $college['email']; ?>"><?php echo $college['email']; ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- College Photo Lightbox Modal -->
    <div class="modal fade" id="aboutPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 bg-dark">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title text-white fw-semibold" id="aboutPhotoModalLabel"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img id="aboutPhotoModalImg" src="" alt="" class="img-fluid rounded" style="max-height: 82vh; width: auto;">
                </div>
            </div>
        </div>
    </div>

    <script>
    function openAboutPhoto(src, alt) {
        document.getElementById('aboutPhotoModalImg').src = src;
        document.getElementById('aboutPhotoModalImg').alt = alt;
        document.getElementById('aboutPhotoModalLabel').textContent = alt;
        new bootstrap.Modal(document.getElementById('aboutPhotoModal')).show();
    }
    </script>

    <!-- Footer -->
    <?php include '../assets/preload/footer.php'; ?>
</body>
</html>
