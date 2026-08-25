<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function pmr_store_image_upload(array $file, int $adminId, string $alt = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('No file was uploaded.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $original = (string) ($file['name'] ?? 'image');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, GIF, and WebP images are allowed.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException('Image must be 8 MB or smaller.');
    }

    $ext = $allowed[$mime];
    $subdir = 'uploads/' . date('Y') . '/' . date('m');
    $absDir = PMR_BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
    if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
        throw new RuntimeException('Could not create the uploads directory.');
    }

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $absPath = $absDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $absPath)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    $width = null;
    $height = null;
    $info = @getimagesize($absPath);
    if (is_array($info)) {
        $width = (int) $info[0];
        $height = (int) $info[1];
    }

    $diskPath = $subdir . '/' . $filename;
    $stmt = pmr_pdo()->prepare(
        'INSERT INTO media (disk_path, filename, original_filename, mime_type, extension, byte_size, width, height, alt_text, title, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $diskPath,
        $filename,
        $original,
        $mime,
        $ext,
        $size,
        $width,
        $height,
        $alt !== '' ? $alt : null,
        pathinfo($original, PATHINFO_FILENAME),
        $adminId,
    ]);

    $id = (int) pmr_pdo()->lastInsertId();
    return pmr_media_by_id($id) ?? ['id' => $id, 'disk_path' => $diskPath];
}

function pmr_media_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = pmr_pdo()->prepare('SELECT * FROM media WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
