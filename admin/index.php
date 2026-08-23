<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-middleware.php';
pmr_send_security_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signed in | PMRatnam</title>
    <style>
        body { font-family: Inter, system-ui, sans-serif; margin: 40px; color: #111827; }
        a { color: #0f766e; }
    </style>
</head>
<body>
    <h1>Signed in</h1>
    <p>Hello <?= pmr_h($currentAdmin['name']) ?> (<?= pmr_h($currentAdmin['email']) ?>). Role: <?= pmr_h($currentAdmin['role']) ?>.</p>
    <p>The dashboard UI is not part of this phase.</p>
    <p><a href="/admin/logout.php">Sign out</a></p>
</body>
</html>
