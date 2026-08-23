<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin-lists.php';
pmr_admin_db_list('Settings', 'settings', 'settings', ['id', 'setting_group', 'setting_key', 'setting_value'], 'No settings rows in the database yet.');
