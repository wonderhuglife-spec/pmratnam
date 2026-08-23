<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

function pmr_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strtolower((string) $forwarded) === 'https';
}

function pmr_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function pmr_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pmr_send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (pmr_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function pmr_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = pmr_is_https();
    session_name(PMR_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) PMR_SESSION_LIFETIME);
    session_start();
}

function pmr_csrf_token(): string
{
    pmr_start_session();
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function pmr_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . pmr_h(pmr_csrf_token()) . '">';
}

function pmr_csrf_verify(mixed $token): bool
{
    pmr_start_session();
    $expected = $_SESSION['_csrf'] ?? '';
    if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($expected, $token);
}

function pmr_is_installed(): bool
{
    if (is_file(PMR_INSTALL_LOCK)) {
        return true;
    }
    try {
        $stmt = pmr_pdo()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute(['install.complete']);
        $row = $stmt->fetch();
        return $row !== false && (string) $row['setting_value'] === '1';
    } catch (Throwable $e) {
        return false;
    }
}

function pmr_redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

function pmr_json_encode(array $data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    return $json === false ? '{}' : $json;
}
