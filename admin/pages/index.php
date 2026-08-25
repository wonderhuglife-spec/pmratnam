<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth-middleware.php';
require_once dirname(__DIR__, 2) . '/includes/admin-layout.php';
require_once dirname(__DIR__, 2) . '/includes/pages.php';

$q = pmr_request_string('q');
$status = pmr_request_string('status');
$pageNum = max(1, pmr_request_int('p', 1));
$result = pmr_pages_search($q, $status, $pageNum, 20);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pmr_require_csrf();
    $id = pmr_request_int('id');
    $action = pmr_request_string('action');
    try {
        if ($action === 'delete') {
            pmr_delete_page($id);
            pmr_audit(pmr_pdo(), (int) $currentAdmin['id'], 'page.delete', 'pages', $id);
            pmr_flash_set('ok', 'Page deleted.');
        } elseif ($action === 'duplicate') {
            $newId = pmr_duplicate_page($id, (int) $currentAdmin['id']);
            pmr_audit(pmr_pdo(), (int) $currentAdmin['id'], 'page.duplicate', 'pages', $newId, ['from' => $id]);
            pmr_flash_set('ok', 'Page duplicated as a draft.');
            pmr_redirect('/admin/pages/edit.php?id=' . $newId);
        } elseif ($action === 'toggle') {
            $next = pmr_toggle_page_status($id, (int) $currentAdmin['id']);
            pmr_audit(pmr_pdo(), (int) $currentAdmin['id'], 'page.toggle', 'pages', $id, ['status' => $next]);
            pmr_flash_set('ok', 'Page is now ' . $next . '.');
        }
    } catch (Throwable $e) {
        pmr_flash_set('err', $e->getMessage());
    }
    pmr_redirect('/admin/pages/index.php');
}

pmr_admin_start('Pages', 'pages', $currentAdmin);
?>
<div class="toolbar">
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;flex:1">
        <div class="field" style="margin:0;min-width:220px">
            <label for="q">Search</label>
            <input id="q" name="q" value="<?= pmr_h($q) ?>" placeholder="Title or slug">
        </div>
        <div class="field" style="margin:0">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                <?php foreach (['draft', 'published', 'archived'] as $st): ?>
                    <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn secondary" type="submit">Filter</button>
    </form>
    <a class="btn" href="/admin/pages/edit.php">Add page</a>
</div>

<?php if ($result['total'] === 0): ?>
    <p class="empty">No pages match these filters. The database currently has <?= (int) pmr_table_count('pages') ?> page(s) total.</p>
<?php else: ?>
    <p class="help"><?= (int) $result['total'] ?> page(s)</p>
    <table class="data">
        <thead>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Updated</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($result['rows'] as $row): ?>
            <tr>
                <td>
                    <a href="/admin/pages/edit.php?id=<?= (int) $row['id'] ?>"><?= pmr_h((string) $row['title']) ?></a>
                    <?php if ((int) $row['is_homepage'] === 1): ?><span class="badge">home</span><?php endif; ?>
                </td>
                <td><code><?= pmr_h((string) $row['slug']) ?></code></td>
                <td><span class="badge badge-<?= pmr_h((string) $row['status']) ?>"><?= pmr_h((string) $row['status']) ?></span></td>
                <td><?= pmr_h((string) $row['updated_at']) ?></td>
                <td class="row-actions">
                    <a class="btn ghost" href="/admin/pages/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                    <form method="post">
                        <?= pmr_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="action" value="toggle">
                        <button class="btn secondary" type="submit"><?= $row['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
                    </form>
                    <form method="post">
                        <?= pmr_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="action" value="duplicate">
                        <button class="btn ghost" type="submit">Duplicate</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete this page permanently?');">
                        <?= pmr_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($result['pages'] > 1): ?>
        <div class="pager">
            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                <a class="btn <?= $i === $result['page'] ? '' : 'ghost' ?>" href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&p=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php
pmr_admin_end();
