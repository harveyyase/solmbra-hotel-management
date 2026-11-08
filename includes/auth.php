<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/audit.php';

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_SESSION['uid']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            json_out(['ok' => false, 'error' => 'unauth'], 401);
        }
        header('Location: index.php');
        exit;
    }
    return $u;
}

function require_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            json_out(['ok' => false, 'error' => 'forbidden'], 403);
        }
        http_response_code(403);
        exit('Forbidden');
    }
    return $u;
}

function attempt_login(string $username, string $password, string $ip): array {
    $pdo = db();
    $key = "login:$ip:$username";

    if (!rate_limit($key, 5, 900)) {
        $pdo->prepare("INSERT INTO sessions_audit (user_id, ip, ua, event) VALUES (NULL,?,?,'lockout')")
            ->execute([$ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
        return ['ok' => false, 'error' => 'Too many attempts. Try again in 15 minutes.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
        $pdo->prepare("INSERT INTO sessions_audit (user_id, ip, ua, event) VALUES (?,?,?,'login_fail')")
            ->execute([$u['id'] ?? null, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
        return ['ok' => false, 'error' => 'Invalid username or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['uid']   = (int)$u['id'];
    $_SESSION['role']  = $u['role'];
    $_SESSION['name']  = $u['display_name'];
    $_SESSION['since'] = time();

    rate_reset($key);
    $pdo->prepare("INSERT INTO sessions_audit (user_id, ip, ua, event) VALUES (?,?,?,'login_ok')")
        ->execute([(int)$u['id'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    return ['ok' => true, 'user' => ['id' => (int)$u['id'], 'name' => $u['display_name'], 'role' => $u['role']]];
}

function logout(): void {
    $uid = $_SESSION['uid'] ?? null;
    db()->prepare("INSERT INTO sessions_audit (user_id, ip, ua, event) VALUES (?,?,?,'logout')")
        ->execute([$uid, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}