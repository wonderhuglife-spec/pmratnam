<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/admin-lists.php';
pmr_admin_db_list('Forms / leads', 'forms', 'leads', ['id', 'form_name', 'email', 'status', 'created_at'], 'No form leads in the database yet.');
