<?php
declare(strict_types=1);

/**
 * @return list<array{id:string,label:string,href:string,match:string}>
 */
function pmr_admin_nav(): array
{
    return [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/admin/index.php', 'match' => 'dashboard'],
        ['id' => 'pages', 'label' => 'Pages', 'href' => '/admin/pages/index.php', 'match' => 'pages'],
        ['id' => 'posts', 'label' => 'Posts', 'href' => '/admin/posts/index.php', 'match' => 'posts'],
        ['id' => 'categories', 'label' => 'Categories', 'href' => '/admin/categories/index.php', 'match' => 'categories'],
        ['id' => 'media', 'label' => 'Media', 'href' => '/admin/media/index.php', 'match' => 'media'],
        ['id' => 'menus', 'label' => 'Menus', 'href' => '/admin/menus/index.php', 'match' => 'menus'],
        ['id' => 'seo', 'label' => 'SEO', 'href' => '/admin/seo.php', 'match' => 'seo'],
        ['id' => 'forms', 'label' => 'Forms', 'href' => '/admin/forms/index.php', 'match' => 'forms'],
        ['id' => 'users', 'label' => 'Users', 'href' => '/admin/users.php', 'match' => 'users'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => '/admin/settings.php', 'match' => 'settings'],
        ['id' => 'tools', 'label' => 'Tools', 'href' => '/admin/tools.php', 'match' => 'tools'],
    ];
}

function pmr_admin_start(string $title, string $active, array $admin): void
{
    pmr_send_security_headers();
    $flash = pmr_flash_get();
    $nav = pmr_admin_nav();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= pmr_h($title) ?> | PMRatnam admin</title>
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="admin-body">
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="brand">
        <span class="brand-mark">PM</span>
        <span class="brand-text">PMRatnam</span>
    </div>
    <nav>
        <?php foreach ($nav as $item): ?>
            <a class="<?= $active === $item['match'] ? 'is-active' : '' ?>" href="<?= pmr_h($item['href']) ?>"><?= pmr_h($item['label']) ?></a>
        <?php endforeach; ?>
        <a href="/" target="_blank" rel="noopener">View Website</a>
        <a href="/admin/logout.php">Logout</a>
    </nav>
</aside>
<div class="admin-shell">
    <header class="admin-top">
        <button type="button" class="menu-btn" id="sidebar-toggle" aria-label="Toggle menu">☰</button>
        <h1><?= pmr_h($title) ?></h1>
        <div class="admin-user"><?= pmr_h($admin['name']) ?> · <?= pmr_h($admin['email']) ?></div>
    </header>
    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="flash flash-<?= pmr_h((string) $flash['type']) ?>"><?= pmr_h((string) $flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function pmr_admin_end(?string $extraJs = null): void
{
    ?>
    </main>
</div>
<script>
document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
    document.getElementById('admin-sidebar')?.classList.toggle('is-open');
});
</script>
<?php if ($extraJs): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
    <?php
}
