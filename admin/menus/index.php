<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/admin-lists.php';
pmr_admin_db_list('Menus', 'menus', 'menus', ['id', 'name', 'location', 'status'], 'No menus in the database yet.');
