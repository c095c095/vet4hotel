<?php

require_once 'cores/init.php';

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
    <link rel="stylesheet" href="<?php echo assets('index.css'); ?>">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="min-vh-100">
        <?php
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo "<div class='container mt-5'>";
                echo "<div class='alert alert-danger text-center'>";
                echo "<h3>พบข้อผิดพลาด 404</h3>";
                echo "<p>ไม่พบไฟล์: <strong>" . htmlspecialchars($page_file) . "</strong> ในระบบ</p>";
                echo "</div>";
                echo "</div>";
            }
        ?>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>