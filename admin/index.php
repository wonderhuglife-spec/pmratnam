<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-middleware.php';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$counts = [
    'pages' => pmr_table_count('pages'),
    'posts' => pmr_table_count('posts'),
    'categories' => pmr_table_count('categories'),
    'media' => pmr_table_count('media'),
    'leads' => pmr_table_count('leads'),
];

$recent = pmr_pdo()->query(
    'SELECT id, title, status, updated_at FROM pages ORDER BY updated_at DESC LIMIT 8'
)->fetchAll();

pmr_admin_start('Dashboard', 'dashboard', $currentAdmin);
?>
<div class="cards">
    <a class="card" href="/admin/pages/index.php" style="text-decoration:none;color:inherit">
        <div class="n"><?= (int) $counts['pages'] ?></div>
        <div class="l">Pages</div>
    </a>
    <a class="card" href="/admin/posts/index.php" style="text-decoration:none;color:inherit">
        <div class="n"><?= (int) $counts['posts'] ?></div>
        <div class="l">Posts</div>
    </a>
    <a class="card" href="/admin/categories/index.php" style="text-decoration:none;color:inherit">
        <div class="n"><?= (int) $counts['categories'] ?></div>
        <div class="l">Categories</div>
    </a>
    <a class="card" href="/admin/media/index.php" style="text-decoration:none;color:inherit">
        <div class="n"><?= (int) $counts['media'] ?></div>
        <div class="l">Media</div>
    </a>
    <a class="card" href="/admin/forms/index.php" style="text-decoration:none;color:inherit">
        <div class="n"><?= (int) $counts['leads'] ?></div>
        <div class="l">Form leads</div>
    </a>
</div>
<div class="card" style="margin-top:20px">
    <h2 style="margin:0 0 12px;font-size:16px">Recent pages</h2>
    <?php if ($recent === []): ?>
        <p class="empty" style="border:0;padding:0">No pages in the database yet. <a href="/admin/pages/edit.php">Add the first page</a>.</p>
    <?php else: ?>
        <table class="data">
            <thead><tr><th>Title</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $row): ?>
                <tr>
                    <td><a href="/admin/pages/edit.php?id=<?= (int) $row['id'] ?>"><?= pmr_h((string) $row['title']) ?></a></td>
                    <td><span class="badge badge-<?= pmr_h((string) $row['status']) ?>"><?= pmr_h((string) $row['status']) ?></span></td>
                    <td><?= pmr_h((string) $row['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php
pmr_admin_end();
