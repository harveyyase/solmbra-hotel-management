<?php
declare(strict_types=1);

// ---- Bootstrap -------------------------------------------------------------
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT . '/storage');
define('DB_PATH', STORAGE_DIR . '/solmbra.sqlite');
define('APP_TIMEZONE', 'Asia/Manila');

date_default_timezone_set(APP_TIMEZONE);

// ---- Session hardening -----------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),  // false on local HTTP
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('solmbra_sid');
    session_start();
}

// ---- Security headers ------------------------------------------------------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
// Adjust if you add CDNs; self-hosted assets allow a strict CSP.
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; script-src 'self'; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
// ---- Ensure storage exists -------------------------------------------------
if (!is_dir(STORAGE_DIR)) {
    @mkdir(STORAGE_DIR, 0775, true);
}