<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/admin-lists.php';
pmr_admin_db_list('Media', 'media', 'media', ['id', 'filename', 'mime_type', 'byte_size', 'created_at'], 'No media files in the database yet. Uploading an image on a page creates a row here.');
