<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config/config.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    // Déconnexion automatique après une période d'inactivité.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /login.php');
        exit;
    }
    return $u;
}

function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        http_response_code(403);
        die('Accès refusé : cette page ne correspond pas à votre profil.');
    }
    return $u;
}

/** Protection anti-brute-force simple : 5 échecs max / 15 min pour un couple identifiant+IP. */
function too_many_attempts(string $username, string $ip): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE username = ? AND ip_address = ? AND success = 0
           AND attempted_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$username, $ip]);
    return (int)$stmt->fetchColumn() >= 5;
}

function log_attempt(string $username, string $ip, bool $success): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)');
    $stmt->execute([$username, $ip, $success ? 1 : 0]);
}

function attempt_login(string $username, string $password): ?array
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (too_many_attempts($username, $ip)) {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        log_attempt($username, $ip, true);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'role'      => $user['role'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
        ];
        return $_SESSION['user'];
    }

    log_attempt($username, $ip, false);
    return null;
}

function do_logout(): void
{
    $_SESSION = [];
    session_destroy();
}
