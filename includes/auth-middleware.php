<?php
declare(strict_types=1);

/**
 * Include at the top of every /admin page except login.php.
 */
require_once __DIR__ . '/auth.php';

if (!pmr_is_installed()) {
    pmr_redirect('/install/');
}

$currentAdmin = pmr_require_admin();
