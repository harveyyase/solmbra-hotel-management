<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

// ---- Rate limiting (per key + 15 min window) -------------------------------
function rate_limit(string $key, int $max = 5, int $windowSec = 900): bool {
    $pdo = db();
    $now = time();
    $stmt = $pdo->prepare("SELECT window_start,count FROM rate_limit WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row || ($now - (int)$row['window_start']) > $windowSec) {
        $pdo->prepare("INSERT INTO rate_limit (key,window_start,count) VALUES (?,?,1)
                       ON CONFLICT(key) DO UPDATE SET window_start=excluded.window_start,count=1")
            ->execute([$key, $now]);
        return true;
    }
    if ((int)$row['count'] >= $max) return false;
    $pdo->prepare("UPDATE rate_limit SET count = count + 1 WHERE key = ?")->execute([$key]);
    return true;
}
function rate_reset(string $key): void {
    db()->prepare("DELETE FROM rate_limit WHERE key = ?")->execute([$key]);
}

// ---- CSRF ------------------------------------------------------------------
