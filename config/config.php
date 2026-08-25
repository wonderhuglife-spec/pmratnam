<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

pmr_load_env(dirname(__DIR__));

define('PMR_BASE_PATH', dirname(__DIR__));
define('PMR_APP_NAME', pmr_env('APP_NAME', 'PMRatnam') ?? 'PMRatnam');
define('PMR_APP_ENV', pmr_env('APP_ENV', 'production') ?? 'production');
define('PMR_APP_DEBUG', strtolower((string) pmr_env('APP_DEBUG', 'false')) === 'true');
define('PMR_APP_URL', rtrim((string) pmr_env('APP_URL', ''), '/'));
define('PMR_SESSION_NAME', pmr_env('SESSION_NAME', 'pmratnam_admin') ?? 'pmratnam_admin');
define('PMR_SESSION_LIFETIME', (int) (pmr_env('SESSION_LIFETIME', '28800') ?? '28800'));
define('PMR_INSTALL_LOCK', PMR_BASE_PATH . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'installed.lock');
define('PMR_MIN_PHP', '8.1.0');

if (!PMR_APP_DEBUG) {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
