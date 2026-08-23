<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/uploads.php';

/**
 * @return list<string>
 */
function pmr_page_templates(): array
{
    return ['page', 'home', 'service', 'about', 'contact', 'legal'];
}

function pmr_page_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = pmr_pdo()->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function pmr_unique_page_slug(string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $n = 2;
    while (true) {
        $sql = 'SELECT id FROM pages WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = pmr_pdo()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
        if ($n > 200) {
            return $base . '-' . bin2hex(random_bytes(3));
        }
    }
}

/**
 * @return array{rows:list<array>, total:int, page:int, pages:int, per:int}
 */
function pmr_pages_search(string $q, string $status, int $page, int $per = 20): array
{
    $page = max(1, $page);
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = '(title LIKE ? OR slug LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (in_array($status, ['draft', 'published', 'archived'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $sqlWhere = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

    $count = pmr_pdo()->prepare('SELECT COUNT(*) FROM pages ' . $sqlWhere);
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $per));
    if ($total === 0) {
        $pages = 1;
        $page = 1;
    } else {
        $page = min($page, $pages);
    }
    $offset = ($page - 1) * $per;

    $stmt = pmr_pdo()->prepare(
        'SELECT id, title, slug, status, is_homepage, published_at, updated_at, created_at
         FROM pages ' . $sqlWhere . ' ORDER BY updated_at DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $offset
    );
    $stmt->execute($params);

    return [
        'rows' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per' => $per,
    ];
}

function pmr_page_empty(): array
{
    return [
        'id' => 0,
        'title' => '',
        'slug' => '',
        'content' => '',
        'excerpt' => '',
        'featured_image_id' => null,
        'template' => 'page',
        'status' => 'draft',
        'is_homepage' => 0,
        'published_at' => '',
        'meta_title' => '',
        'meta_description' => '',
        'canonical_url' => '',
        'og_title' => '',
        'og_description' => '',
        'og_image_id' => null,
        'schema_markup' => '',
    ];
}

/**
 * @param array<string,mixed> $input
 */
function pmr_save_page(array $input, int $adminId, ?array $featuredUpload = null, ?array $ogUpload = null): int
{
    $id = (int) ($input['id'] ?? 0);
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('Title is required.');
    }

    $isHomepage = !empty($input['is_homepage']) ? 1 : 0;
    $slug = trim((string) ($input['slug'] ?? ''));
    if ($isHomepage === 1) {
        $slug = '';
    } elseif ($slug === '') {
        $slug = pmr_slugify($title);
    } else {
        $slug = pmr_slugify($slug);
    }
    if ($isHomepage === 0 && $slug === '') {
        throw new InvalidArgumentException('Slug is required unless this is the homepage.');
    }
    if ($isHomepage === 0) {
        $slug = pmr_unique_page_slug($slug, $id > 0 ? $id : null);
    }

    $status = (string) ($input['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }
    $template = (string) ($input['template'] ?? 'page');
    if (!in_array($template, pmr_page_templates(), true)) {
        $template = 'page';
    }

    $publishedAt = trim((string) ($input['published_at'] ?? ''));
    if ($status === 'published' && $publishedAt === '') {
        $publishedAt = date('Y-m-d H:i:s');
    } elseif ($publishedAt !== '') {
        $ts = strtotime(str_replace('T', ' ', $publishedAt));
        $publishedAt = $ts ? date('Y-m-d H:i:s', $ts) : null;
    } else {
        $publishedAt = null;
    }

    $featuredId = (int) ($input['featured_image_id'] ?? 0) ?: null;
    $ogId = (int) ($input['og_image_id'] ?? 0) ?: null;
    if ($featuredUpload && ($featuredUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $media = pmr_store_image_upload($featuredUpload, $adminId, $title);
        $featuredId = (int) $media['id'];
    }
    if ($ogUpload && ($ogUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $media = pmr_store_image_upload($ogUpload, $adminId, $title . ' OG');
        $ogId = (int) $media['id'];
    }

    $fields = [
        'title' => $title,
        'slug' => $slug,
        'content' => (string) ($input['content'] ?? ''),
        'excerpt' => (string) ($input['excerpt'] ?? ''),
        'featured_image_id' => $featuredId,
        'template' => $template,
        'status' => $status,
        'is_homepage' => $isHomepage,
        'published_at' => $publishedAt,
        'meta_title' => (string) ($input['meta_title'] ?? '') ?: null,
        'meta_description' => (string) ($input['meta_description'] ?? '') ?: null,
        'canonical_url' => (string) ($input['canonical_url'] ?? '') ?: null,
        'og_title' => (string) ($input['og_title'] ?? '') ?: null,
        'og_description' => (string) ($input['og_description'] ?? '') ?: null,
        'og_image_id' => $ogId,
        'schema_markup' => (string) ($input['schema_markup'] ?? '') ?: null,
        'updated_by' => $adminId,
    ];

    $pdo = pmr_pdo();
    $pdo->beginTransaction();
    try {
        if ($isHomepage === 1) {
            $ignoreId = $id > 0 ? $id : 0;
            $others = $pdo->prepare('SELECT id, title FROM pages WHERE slug = \'\' AND id <> ?');
            $others->execute([$ignoreId]);
            foreach ($others->fetchAll() as $row) {
                $fallback = pmr_slugify((string) $row['title']);
                if ($fallback === '') {
                    $fallback = 'page-' . (int) $row['id'];
                }
                $newSlug = pmr_unique_page_slug($fallback, (int) $row['id']);
                $pdo->prepare('UPDATE pages SET slug = ?, is_homepage = 0 WHERE id = ?')->execute([
                    $newSlug,
                    (int) $row['id'],
                ]);
            }
            $pdo->exec('UPDATE pages SET is_homepage = 0');
        }

        if ($id > 0) {
            $sets = [];
            $params = [];
            foreach ($fields as $col => $val) {
                $sets[] = '`' . $col . '` = ?';
                $params[] = $val;
            }
            $params[] = $id;
            $pdo->prepare('UPDATE pages SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
            $pdo->commit();
            return $id;
        }

        $fields['created_by'] = $adminId;
        $cols = array_keys($fields);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO pages (`' . implode('`, `', $cols) . '`) VALUES (' . $placeholders . ')';
        $pdo->prepare($sql)->execute(array_values($fields));
        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $newId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function pmr_duplicate_page(int $id, int $adminId): int
{
    $page = pmr_page_by_id($id);
    if ($page === null) {
        throw new RuntimeException('Page not found.');
    }
    $copy = [
        'title' => $page['title'] . ' (copy)',
        'slug' => $page['slug'] !== '' ? $page['slug'] : pmr_slugify((string) $page['title']),
        'content' => $page['content'],
        'excerpt' => $page['excerpt'],
        'featured_image_id' => $page['featured_image_id'],
        'template' => $page['template'],
        'status' => 'draft',
        'is_homepage' => 0,
        'published_at' => null,
        'meta_title' => $page['meta_title'],
        'meta_description' => $page['meta_description'],
        'canonical_url' => $page['canonical_url'],
        'og_title' => $page['og_title'],
        'og_description' => $page['og_description'],
        'og_image_id' => $page['og_image_id'],
        'schema_markup' => $page['schema_markup'],
    ];
    return pmr_save_page($copy, $adminId);
}

function pmr_delete_page(int $id): void
{
    $stmt = pmr_pdo()->prepare('DELETE FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Page not found.');
    }
}

function pmr_toggle_page_status(int $id, int $adminId): string
{
    $page = pmr_page_by_id($id);
    if ($page === null) {
        throw new RuntimeException('Page not found.');
    }
    $next = $page['status'] === 'published' ? 'draft' : 'published';
    $publishedAt = $next === 'published'
        ? ($page['published_at'] ?: date('Y-m-d H:i:s'))
        : $page['published_at'];
    pmr_pdo()->prepare(
        'UPDATE pages SET status = ?, published_at = ?, updated_by = ? WHERE id = ?'
    )->execute([$next, $publishedAt, $adminId, $id]);
    return $next;
}
