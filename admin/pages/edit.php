<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth-middleware.php';
require_once dirname(__DIR__, 2) . '/includes/admin-layout.php';
require_once dirname(__DIR__, 2) . '/includes/pages.php';

$id = pmr_request_int('id');
$page = $id > 0 ? pmr_page_by_id($id) : pmr_page_empty();
if ($id > 0 && $page === null) {
    pmr_flash_set('err', 'Page not found.');
    pmr_redirect('/admin/pages/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    pmr_require_csrf();
    try {
        $savedId = pmr_save_page(
            $_POST,
            (int) $currentAdmin['id'],
            $_FILES['featured_image'] ?? null,
            $_FILES['og_image'] ?? null
        );
        pmr_audit(pmr_pdo(), (int) $currentAdmin['id'], $id > 0 ? 'page.update' : 'page.create', 'pages', $savedId);
        pmr_flash_set('ok', 'Page saved.');
        pmr_redirect('/admin/pages/edit.php?id=' . $savedId);
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $page = array_merge($page, $_POST);
        $page['id'] = $id;
    }
}

$featured = pmr_media_by_id((int) ($page['featured_image_id'] ?? 0));
$og = pmr_media_by_id((int) ($page['og_image_id'] ?? 0));
$publishedLocal = '';
if (!empty($page['published_at'])) {
    $ts = strtotime((string) $page['published_at']);
    if ($ts) {
        $publishedLocal = date('Y-m-d\TH:i', $ts);
    }
}

pmr_admin_start($id > 0 ? 'Edit page' : 'Add page', 'pages', $currentAdmin);
if ($error) {
    echo '<div class="flash flash-err">' . pmr_h($error) . '</div>';
}
?>
<form method="post" enctype="multipart/form-data">
    <?= pmr_csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($page['id'] ?? 0) ?>">
    <input type="hidden" name="featured_image_id" value="<?= (int) ($page['featured_image_id'] ?? 0) ?>">
    <input type="hidden" name="og_image_id" value="<?= (int) ($page['og_image_id'] ?? 0) ?>">
    <div class="grid-2">
        <div>
            <div class="field">
                <label for="title">Title</label>
                <input id="title" name="title" required value="<?= pmr_h((string) ($page['title'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="<?= pmr_h((string) ($page['slug'] ?? '')) ?>">
                <div class="help">Leave blank on the homepage. Otherwise used as /your-slug</div>
            </div>
            <div class="field">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?= pmr_h((string) ($page['excerpt'] ?? '')) ?></textarea>
            </div>
            <div class="field">
                <label>Content</label>
                <div id="editor"></div>
                <div class="html-toggle">
                    <button class="btn ghost" type="button" id="toggle-html">Raw HTML</button>
                </div>
                <textarea id="content-html" name="content" hidden><?= pmr_h((string) ($page['content'] ?? '')) ?></textarea>
            </div>
            <div class="field">
                <label for="schema_markup">Schema markup (JSON-LD)</label>
                <textarea id="schema_markup" name="schema_markup" rows="6" placeholder='{"@context":"https://schema.org","@type":"WebPage"}'><?= pmr_h((string) ($page['schema_markup'] ?? '')) ?></textarea>
            </div>
        </div>
        <div>
            <div class="card">
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (['draft', 'published', 'archived'] as $st): ?>
                            <option value="<?= $st ?>" <?= (($page['status'] ?? '') === $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="published_at">Publish date</label>
                    <input id="published_at" name="published_at" type="datetime-local" value="<?= pmr_h($publishedLocal) ?>">
                </div>
                <div class="field">
                    <label for="template">Template</label>
                    <select id="template" name="template">
                        <?php foreach (pmr_page_templates() as $tpl): ?>
                            <option value="<?= pmr_h($tpl) ?>" <?= (($page['template'] ?? '') === $tpl) ? 'selected' : '' ?>><?= pmr_h($tpl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label><input type="checkbox" name="is_homepage" value="1" <?= !empty($page['is_homepage']) ? 'checked' : '' ?>> Homepage</label>
                </div>
                <button class="btn" type="submit">Save page</button>
                <a class="btn ghost" href="/admin/pages/index.php">Back to list</a>
            </div>
            <div class="card" style="margin-top:16px">
                <div class="field">
                    <label for="featured_image">Featured image</label>
                    <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if ($featured): ?>
                        <img class="thumb" src="<?= pmr_h(pmr_media_public_url($featured) ?? '') ?>" alt="">
                        <div class="help">Current media #<?= (int) $featured['id'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="meta_title">Meta title</label>
                    <input id="meta_title" name="meta_title" value="<?= pmr_h((string) ($page['meta_title'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="meta_description">Meta description</label>
                    <textarea id="meta_description" name="meta_description" rows="3"><?= pmr_h((string) ($page['meta_description'] ?? '')) ?></textarea>
                </div>
                <div class="field">
                    <label for="canonical_url">Canonical URL</label>
                    <input id="canonical_url" name="canonical_url" type="url" value="<?= pmr_h((string) ($page['canonical_url'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="og_title">OG title</label>
                    <input id="og_title" name="og_title" value="<?= pmr_h((string) ($page['og_title'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="og_description">OG description</label>
                    <textarea id="og_description" name="og_description" rows="3"><?= pmr_h((string) ($page['og_description'] ?? '')) ?></textarea>
                </div>
                <div class="field">
                    <label for="og_image">OG image</label>
                    <input id="og_image" name="og_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if ($og): ?>
                        <img class="thumb" src="<?= pmr_h(pmr_media_public_url($og) ?? '') ?>" alt="">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="/admin/assets/page-editor.js"></script>
<?php
pmr_admin_end();
