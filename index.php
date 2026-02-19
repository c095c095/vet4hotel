<?php

require_once 'cores/init.php';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> | <?php echo $currentPage['title']; ?></title>
    <link rel="icon" type="image/png" href="<?php echo assets('favicon/favicon-96x96.png'); ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?php echo assets('favicon/favicon.svg'); ?>" />
    <link rel="shortcut icon" href="<?php echo assets('favicon/favicon.ico'); ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo assets('favicon/apple-touch-icon.png'); ?>" />
    <link rel="manifest" href="<?php echo assets('favicon/site.webmanifest'); ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Outfit:wght@700&family=Sarabun:wght@400;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap & Custom CSS -->
    <link rel="stylesheet" href="<?php echo assets('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo assets('css/index.css'); ?>">
    <script src="<?php echo assets('js/bootstrap.bundle.min.js'); ?>"></script>
    <link rel="stylesheet" href="<?php echo assets('bootstrap-icons-1.13.1/bootstrap-icons.min.css'); ?>">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main>
        <?php include $currentPage['file']; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>