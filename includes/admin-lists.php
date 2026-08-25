<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth-middleware.php';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

/**
 * Read-only list of real DB rows for sections whose full CRUD is a later phase.
 *
 * @param list<string> $columns
 */
function pmr_admin_db_list(string $title, string $nav, string $table, array $columns, string $empty): void
{
    global $currentAdmin;
    $allowed = [
        'posts' => true,
        'categories' => true,
        'media' => true,
        'menus' => true,
        'leads' => true,
        'admins' => true,
        'settings' => true,
        'redirects' => true,
        'seo_settings' => true,
    ];
    if (!isset($allowed[$table])) {
        throw new InvalidArgumentException('Unknown table');
    }
    $safeCols = [];
    foreach ($columns as $col) {
        if (!preg_match('/^[a-z_]+$/', $col)) {
            continue;
        }
        $safeCols[] = '`' . $col . '`';
    }
    $sql = 'SELECT ' . implode(', ', $safeCols) . ' FROM `' . $table . '` ORDER BY 1 DESC LIMIT 50';
    try {
        $rows = pmr_pdo()->query($sql)->fetchAll();
        $total = (int) pmr_pdo()->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    } catch (Throwable $e) {
        $rows = [];
        $total = 0;
    }

    pmr_admin_start($title, $nav, $currentAdmin);
    echo '<p class="help">' . (int) $total . ' row(s) in <code>' . pmr_h($table) . '</code>. Full editors for this section ship in a later phase; these figures are live.</p>';
    if ($rows === []) {
        echo '<p class="empty">' . pmr_h($empty) . '</p>';
        pmr_admin_end();
        return;
    }
    echo '<table class="data"><thead><tr>';
    foreach ($columns as $col) {
        echo '<th>' . pmr_h($col) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($columns as $col) {
            $val = $row[$col] ?? '';
            $str = is_scalar($val) ? (string) $val : '';
            if (strlen($str) > 160) {
                $str = substr($str, 0, 157) . '...';
            }
            echo '<td>' . pmr_h($str) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    pmr_admin_end();
}
