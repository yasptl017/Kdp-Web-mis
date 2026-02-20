<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="K.D. Polytechnic, Patan - Excellence in Technical Education">

    <title>
        <?php
        echo isset($page_title)
            ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8')
            : 'K.D. Polytechnic, Patan';
        ?>
    </title>
    <link rel="icon" href="data:,">

    <?php
    // ======================================================
    // Load BASE_URL from config.php (PROJECT ROOT)
    // ======================================================
    require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
    ?>

    <!-- ===============================
         BOOTSTRAP CSS
    ================================ -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- ===============================
         FONT AWESOME
    ================================ -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- ===============================
         GOOGLE FONTS
    ================================ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <!-- ===============================
         CUSTOM CSS FILES
    ================================ -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/about-pages.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/academics-pages.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/students-pages.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/campus-pages.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dept-pages.css">

    <!-- ===============================
         BOOTSTRAP JS (DEFERRED)
    ================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            defer></script>
</head>
