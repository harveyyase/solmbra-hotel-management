<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

function notif_add(?int $uid, string $kind, string $title, string $detail = '', ?string $entity = null, ?string $entityId = null): void {
    db()->prepare("INSERT INTO notifications (user_id,kind,title,detail,entity,entity_id) VALUES (?,?,?,?,?,?)")
        ->execute([$uid, $kind, $title, $detail, $entity, $entityId]);
}

/** Derived notifications: pending reservations + pending payments + today's arrivals/departures. */
function notif_derive(int $uid, string $role): void {
    $pdo = db();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $soon  = (new DateTimeImmutable('today +3 days'))->format('Y-m-d');

    // Check if ANY notification exists for this entity (even if it's already read/dismissed)
    $existing = $pdo->prepare("SELECT 1 FROM notifications WHERE kind = ? AND entity_id = ? LIMIT 1");

    // Pending reservations checking in soon
    $st = $pdo->prepare("SELECT r.id, g.name, r.room, r.check_in FROM reservations r
                         JOIN guests g ON g.id = r.guest_id
                         WHERE r.status='Pending' AND r.archived=0 AND r.check_in BETWEEN ? AND ?");
    $st->execute([$today, $soon]);
    foreach ($st->fetchAll() as $r) {
        $existing->execute(['pending_reservation', $r['id']]);
        if (!$existing->fetch()) {
            notif_add($uid, 'pending_reservation', "{$r['name']} — Room {$r['room']}",
                      "Check-in {$r['check_in']} · Awaiting confirmation", 'reservation', $r['id']);
        }
    }

    // Pending payments
    $st = $pdo->prepare("SELECT p.id, p.amount, p.method, g.name FROM payments p
                         JOIN guests g ON g.id = p.guest_id
                         WHERE p.status='Pending' AND p.archived=0 AND p.voided=0");
    $st->execute();
    foreach ($st->fetchAll() as $p) {
        $existing->execute(['payment_due', $p['id']]);
        if (!$existing->fetch()) {
            notif_add($uid, 'payment_due', "Payment {$p['id']} — {$p['name']}",
                      '₱' . number_format((float)$p['amount']) . " · {$p['method']}", 'payment', $p['id']);
        }
    }
}

function notif_list(int $uid): array {
    $st = db()->prepare("SELECT * FROM notifications
                         WHERE dismissed=0 
                           AND read_at IS NULL 
                           AND (user_id IS NULL OR user_id = ?)
                         ORDER BY id DESC LIMIT 40");
    $st->execute([$uid]);
    return $st->fetchAll();
}

function notif_mark_read(int $uid, ?int $id = null): void {
    if ($id === null) {
        db()->prepare("UPDATE notifications SET read_at = datetime('now') WHERE (user_id IS NULL OR user_id = ?) AND read_at IS NULL")->execute([$uid]);
    } else {
        db()->prepare("UPDATE notifications SET read_at = datetime('now') WHERE id = ? AND (user_id IS NULL OR user_id = ?)")->execute([$id, $uid]);
    }
}
function notif_clear(int $uid): void {
    db()->prepare("UPDATE notifications SET dismissed=1 WHERE user_id = ?")->execute([$uid]);
}