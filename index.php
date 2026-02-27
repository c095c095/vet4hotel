<?php require_once 'cores/init.php'; ?>
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
    <link rel="stylesheet" href="<?php echo assets('output.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/theme-change@2.0.2/index.js"></script>
</head>

<body>
    <?php if (!in_array($current_page, ['login', 'register'])): ?>
        <?php include 'navbar.php'; ?>
    <?php endif; ?>
    <main class="min-vh-100">
        <?php
        if (file_exists($page_file)) {
            include $page_file;

            $msg_success = $_SESSION['msg_success'] ?? null;
            $msg_error = $_SESSION['msg_error'] ?? null;
            unset($_SESSION['msg_success'], $_SESSION['msg_error']);

            if ($msg_success) {
                ?>
                <div class="toast toast-top toast-center z-9999" id="flash-toast">
                    <div class="alert alert-success shadow-lg">
                        <i data-lucide="check-circle" class="size-5"></i>
                        <span><?php echo sanitize($msg_success); ?></span>
                    </div>
                </div>
                <script>setTimeout(() => { const t = document.getElementById('flash-toast'); if (t) t.remove(); }, 4000);</script>
                <?php
            }

            if ($msg_error) {
                ?>
                <div class="toast toast-top toast-center z-9999" id="flash-toast-err">
                    <div class="alert alert-error shadow-lg">
                        <i data-lucide="alert-circle" class="size-5"></i>
                        <span><?php echo sanitize($msg_error); ?></span>
                    </div>
                </div>
                <script>setTimeout(() => { const t = document.getElementById('flash-toast-err'); if (t) t.remove(); }, 5000);</script>
                <?php
            }
        } else {
            echo "<div class='container mt-5'>";
            echo "<div class='alert alert-danger text-center'>";
            echo "<h3>พบข้อผิดพลาด 404</h3>";
            echo "<p>ไม่พบไฟล์: <strong>" . sanitize($page_file) . "</strong> ในระบบ</p>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </main>
    <?php if (!in_array($current_page, ['login', 'register'])): ?>
        <?php include 'footer.php'; ?>
    <?php endif; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>