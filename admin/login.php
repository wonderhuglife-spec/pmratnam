<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

pmr_send_security_headers();
pmr_start_session();

if (!pmr_is_installed()) {
    pmr_redirect('/install/');
}

if (pmr_current_admin()) {
    pmr_redirect('/admin/index.php');
}

$error = null;
$emailValue = '';

if (isset($_GET['timeout'])) {
    $error = 'Your session expired. Sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!pmr_csrf_verify($_POST['_csrf'] ?? null)) {
        $error = 'Invalid security token. Reload the page and try again.';
    } else {
        $result = pmr_attempt_login($emailValue, (string) ($_POST['password'] ?? ''));
        if ($result['ok']) {
            pmr_redirect('/admin/index.php');
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin sign in | PMRatnam</title>
    <style>
        body { font-family: Inter, system-ui, sans-serif; background: #111827; color: #111827; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        form { background: #fff; width: min(420px, 92vw); padding: 32px; border-radius: 12px; }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #6b7280; }
        label { display: block; font-weight: 600; margin: 14px 0 6px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { width: 100%; margin-top: 18px; background: #0f766e; color: #fff; border: 0; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .err { background: #fef2f2; color: #b91c1c; padding: 10px 12px; border-radius: 8px; }
    </style>
</head>
<body>
<form method="post" autocomplete="off">
    <h1>PMRatnam admin</h1>
    <p>Sign in to manage pages, media, and site settings.</p>
    <?php if ($error): ?><p class="err"><?= pmr_h($error) ?></p><?php endif; ?>
    <?= pmr_csrf_field() ?>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required value="<?= pmr_h($emailValue) ?>">
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>
    <button type="submit">Sign in</button>
</form>
</body>
</html>
