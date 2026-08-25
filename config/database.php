<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * PDO singleton. Never uses mysql_* / mysqli query strings concatenated with input.
 */
function pmr_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = pmr_env('DB_HOST', 'localhost') ?? 'localhost';
    $port = pmr_env('DB_PORT', '3306') ?? '3306';
    $name = pmr_env('DB_NAME', '');
    $user = pmr_env('DB_USER', '');
    $pass = pmr_env('DB_PASS', '');
    $charset = pmr_env('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4';
    $socket = pmr_env('DB_SOCKET');

    if ($name === null || $name === '' || $user === null || $user === '') {
        throw new RuntimeException('Database credentials are missing. Copy .env.example to .env or run /install/.');
    }

    if ($socket) {
        $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $socket, $name, $charset);
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset . ' COLLATE utf8mb4_unicode_ci',
    ]);

    return $pdo;
}

function pmr_pdo_connect_without_db(string $host, string $port, string $user, string $pass, ?string $socket = null): PDO
{
    if ($socket) {
        $dsn = 'mysql:unix_socket=' . $socket . ';charset=utf8mb4';
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
    }

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
