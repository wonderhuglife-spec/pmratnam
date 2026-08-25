<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

pmr_send_security_headers();
pmr_start_session();

if (pmr_is_installed()) {
    try {
        $admin = pmr_current_admin();
        if ($admin) {
            pmr_audit(pmr_pdo(), (int) $admin['id'], 'logout', 'admins', (int) $admin['id']);
        }
    } catch (Throwable $e) {
        // Destroy the session even if audit logging fails.
    }
}

pmr_logout();
pmr_redirect('/admin/login.php');
