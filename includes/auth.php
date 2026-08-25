<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const PMR_LOGIN_MAX_FAILURES = 5;
const PMR_LOGIN_WINDOW_MINUTES = 15;

function pmr_current_admin(): ?array
{
    pmr_start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    try {
        $stmt = pmr_pdo()->prepare(
            'SELECT id, name, email, role, status FROM admins WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        if (!$admin || $admin['status'] !== 'active') {
            return null;
        }
        return $admin;
    } catch (Throwable $e) {
        return null;
    }
}

function pmr_require_admin(): array
{
    pmr_send_security_headers();
    pmr_start_session();

    if (empty($_SESSION['admin_id'])) {
        pmr_redirect('/admin/login.php');
    }

    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > PMR_SESSION_LIFETIME) {
        pmr_logout();
        pmr_redirect('/admin/login.php?timeout=1');
    }

    $_SESSION['last_activity'] = time();
    $admin = pmr_current_admin();
    if ($admin === null) {
        pmr_logout();
        pmr_redirect('/admin/login.php');
    }
    return $admin;
}

function pmr_logout(): void
{
    pmr_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function pmr_record_login_attempt(PDO $pdo, string $email, bool $success): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (email, ip_address, success, user_agent) VALUES (?, ?, ?, ?)'
    );
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $stmt->execute([$email, pmr_client_ip(), $success ? 1 : 0, $ua]);
}

function pmr_too_many_failures(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE success = 0
           AND attempted_at >= (NOW() - INTERVAL ' . PMR_LOGIN_WINDOW_MINUTES . ' MINUTE)
           AND (email = ? OR ip_address = ?)'
    );
    $stmt->execute([$email, pmr_client_ip()]);
    return (int) $stmt->fetchColumn() >= PMR_LOGIN_MAX_FAILURES;
}

function pmr_audit(PDO $pdo, ?int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, ip_address, user_agent, metadata)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $stmt->execute([
        $adminId,
        $action,
        $entityType,
        $entityId,
        pmr_client_ip(),
        $ua,
        pmr_json_encode($metadata),
    ]);
}

/**
 * @return array{ok:bool, error:?string, admin:?array}
 */
function pmr_attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Enter email and password.', 'admin' => null];
    }

    try {
        $pdo = pmr_pdo();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Database is not available.', 'admin' => null];
    }

    if (pmr_too_many_failures($pdo, $email)) {
        return [
            'ok' => false,
            'error' => 'Too many failed attempts. Wait 15 minutes and try again.',
            'admin' => null,
        ];
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    $hash = is_array($admin) ? (string) $admin['password_hash'] : '';
    $verified = $hash !== '' && password_verify($password, $hash);

    if (!$admin || !$verified) {
        pmr_record_login_attempt($pdo, $email, false);
        if ($admin) {
            $pdo->prepare(
                'UPDATE admins SET failed_login_count = failed_login_count + 1,
                    locked_until = CASE
                        WHEN failed_login_count + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                        ELSE locked_until
                    END
                 WHERE id = ?'
            )->execute([PMR_LOGIN_MAX_FAILURES, PMR_LOGIN_WINDOW_MINUTES, $admin['id']]);
        }
        return ['ok' => false, 'error' => 'Invalid email or password.', 'admin' => null];
    }

    if ($admin['status'] !== 'active') {
        pmr_record_login_attempt($pdo, $email, false);
        return ['ok' => false, 'error' => 'This account is disabled.', 'admin' => null];
    }

    if (!empty($admin['locked_until']) && strtotime((string) $admin['locked_until']) > time()) {
        pmr_record_login_attempt($pdo, $email, false);
        return ['ok' => false, 'error' => 'Too many failed attempts. Wait 15 minutes and try again.', 'admin' => null];
    }

    pmr_start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));

    $pdo->prepare(
        'UPDATE admins SET last_login_at = NOW(), last_login_ip = ?, failed_login_count = 0, locked_until = NULL WHERE id = ?'
    )->execute([pmr_client_ip(), $admin['id']]);

    pmr_record_login_attempt($pdo, $email, true);
    pmr_audit($pdo, (int) $admin['id'], 'login', 'admins', (int) $admin['id']);

    unset($admin['password_hash']);
    return ['ok' => true, 'error' => null, 'admin' => $admin];
}
