<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/admin-lists.php';
pmr_admin_db_list('Categories', 'categories', 'categories', ['id', 'name', 'slug', 'status'], 'No categories in the database yet.');
