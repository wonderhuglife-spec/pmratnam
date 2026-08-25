<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/admin-lists.php';
pmr_admin_db_list('Posts', 'posts', 'posts', ['id', 'title', 'slug', 'status', 'updated_at'], 'No posts in the database yet.');
