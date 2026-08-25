<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin-lists.php';
pmr_admin_db_list('Users', 'users', 'admins', ['id', 'name', 'email', 'role', 'status'], 'No admin users found.');
