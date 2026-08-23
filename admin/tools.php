<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin-lists.php';
pmr_admin_db_list('Tools', 'tools', 'redirects', ['id', 'from_path', 'to_path', 'status_code', 'is_active'], 'No redirects in the database yet. Importer tools ship in a later phase.');
