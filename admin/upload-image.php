<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-middleware.php';
require_once dirname(__DIR__) . '/includes/uploads.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo pmr_json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

if (!pmr_csrf_verify($_POST['_csrf'] ?? null)) {
    http_response_code(400);
    echo pmr_json_encode(['ok' => false, 'error' => 'Invalid security token']);
    exit;
}

try {
    $media = pmr_store_image_upload($_FILES['image'] ?? [], (int) $currentAdmin['id']);
    echo pmr_json_encode([
        'ok' => true,
        'url' => pmr_media_public_url($media),
        'id' => (int) $media['id'],
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo pmr_json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
