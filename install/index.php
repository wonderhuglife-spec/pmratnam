<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/installer.php';
require_once dirname(__DIR__) . '/includes/auth.php';

pmr_send_security_headers();
pmr_start_session();

$blocked = pmr_is_installed();
$checks = pmr_php_requirements();
$ready = pmr_requirements_ok($checks);
$errors = [];
$done = false;

if (!$blocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pmr_csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Reload the page and try again.';
    } elseif (!$ready) {
        $errors[] = 'Fix the failed server checks before continuing.';
    } else {
        $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
        $port = trim((string) ($_POST['db_port'] ?? '3306'));
        $name = trim((string) ($_POST['db_name'] ?? ''));
        $user = trim((string) ($_POST['db_user'] ?? ''));
        $pass = (string) ($_POST['db_pass'] ?? '');
        $socket = trim((string) ($_POST['db_socket'] ?? ''));
        $appUrl = trim((string) ($_POST['app_url'] ?? ''));
        $adminName = trim((string) ($_POST['admin_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $adminPass = (string) ($_POST['admin_password'] ?? '');
        $adminPass2 = (string) ($_POST['admin_password_confirm'] ?? '');

        if ($appUrl === '') {
            $scheme = pmr_is_https() ? 'https' : 'http';
            $appUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $appUrl = rtrim($appUrl, '/');

        if ($name === '' || $user === '') {
            $errors[] = 'Database name and user are required (copy them from Hostinger hPanel → Databases).';
        }
        if ($adminPass !== $adminPass2) {
            $errors[] = 'Admin passwords do not match.';
        }

        if ($errors === []) {
            try {
                $pdo = pmr_test_db_credentials($host, $port, $name, $user, $pass, $socket !== '' ? $socket : null);
                pmr_write_env_file([
                    'APP_NAME' => 'PMRatnam',
                    'APP_URL' => $appUrl,
                    'DB_HOST' => $host,
                    'DB_PORT' => $port !== '' ? $port : '3306',
                    'DB_NAME' => $name,
                    'DB_USER' => $user,
                    'DB_PASS' => $pass,
                    'DB_SOCKET' => $socket,
                ]);
                pmr_load_env(PMR_BASE_PATH, true);
                pmr_run_schema($pdo);
                $adminId = pmr_create_first_admin($pdo, $adminName, $adminEmail, $adminPass);
                pmr_mark_installed($pdo);
                pmr_audit($pdo, $adminId, 'install.complete', 'system', null, ['php' => PHP_VERSION]);
                $done = true;
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

function pmr_post_val(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? (string) $_POST[$key] : $default;
}

$defaultUrl = (pmr_is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install PMRatnam CMS</title>
    <style>
        :root { --teal:#0f766e; --err:#b91c1c; }
        body { font-family: Inter, system-ui, sans-serif; background: #f3f4f6; margin: 0; }
        main { max-width: 720px; margin: 40px auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
        label { display: block; font-weight: 600; margin: 12px 0 6px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { margin-top: 20px; background: var(--teal); color: #fff; border: 0; padding: 12px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .ok { color: #047857; } .bad { color: var(--err); }
        .err { background: #fef2f2; color: var(--err); padding: 12px; border-radius: 8px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } main { margin: 16px; } }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
        .note { font-size: 14px; color: #6b7280; }
    </style>
</head>
<body>
<main>
    <h1>PMRatnam CMS installer</h1>
    <?php if ($blocked): ?>
        <p>Installation is already complete.</p>
        <p><a href="/admin/login.php">Go to admin login</a></p>
    <?php elseif ($done): ?>
        <p class="ok">Installation finished. The first admin was created and the installer is locked.</p>
        <p><a href="/admin/login.php">Sign in</a></p>
        <p class="note">On Hostinger keep <code>.env</code> in public_html at permission 600. Never commit it.</p>
    <?php else: ?>
        <p class="note">Hostinger: create an empty MySQL database in hPanel first. Paste the <strong>prefixed</strong> database name and user. Host is almost always <code>localhost</code>. Select PHP 8.1+ in hPanel → PHP Configuration.</p>
        <h2>Server checks</h2>
        <ul>
            <?php foreach ($checks as $check): ?>
                <li class="<?= $check['ok'] ? 'ok' : 'bad' ?>"><?= $check['ok'] ? 'Pass' : 'Fail' ?> — <?= pmr_h($check['label']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php foreach ($errors as $error): ?>
            <p class="err"><?= pmr_h($error) ?></p>
        <?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= pmr_csrf_field() ?>
            <h2>Site</h2>
            <label for="app_url">Public site URL</label>
            <input id="app_url" name="app_url" type="url" value="<?= pmr_h(pmr_post_val('app_url', $defaultUrl)) ?>">
            <h2>Database (hPanel → Databases)</h2>
            <div class="grid">
                <div>
                    <label for="db_host">Host</label>
                    <input id="db_host" name="db_host" value="<?= pmr_h(pmr_post_val('db_host', 'localhost')) ?>">
                </div>
                <div>
                    <label for="db_port">Port</label>
                    <input id="db_port" name="db_port" value="<?= pmr_h(pmr_post_val('db_port', '3306')) ?>">
                </div>
            </div>
            <label for="db_name">Database name</label>
            <input id="db_name" name="db_name" required value="<?= pmr_h(pmr_post_val('db_name')) ?>" placeholder="u123456789_pmratnam">
            <label for="db_user">Database user</label>
            <input id="db_user" name="db_user" required value="<?= pmr_h(pmr_post_val('db_user')) ?>" placeholder="u123456789_admin">
            <label for="db_pass">Database password</label>
            <input id="db_pass" name="db_pass" type="password" required>
            <label for="db_socket">Unix socket (optional, leave empty on Hostinger)</label>
            <input id="db_socket" name="db_socket" value="<?= pmr_h(pmr_post_val('db_socket')) ?>">
            <h2>First admin</h2>
            <label for="admin_name">Name</label>
            <input id="admin_name" name="admin_name" required value="<?= pmr_h(pmr_post_val('admin_name')) ?>">
            <label for="admin_email">Email</label>
            <input id="admin_email" name="admin_email" type="email" required value="<?= pmr_h(pmr_post_val('admin_email')) ?>">
            <label for="admin_password">Password (min 12 characters)</label>
            <input id="admin_password" name="admin_password" type="password" required minlength="12">
            <label for="admin_password_confirm">Confirm password</label>
            <input id="admin_password_confirm" name="admin_password_confirm" type="password" required minlength="12">
            <button type="submit" <?= $ready ? '' : 'disabled' ?>>Install</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
