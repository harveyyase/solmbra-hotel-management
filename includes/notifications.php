<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

function notif_add(?int $uid, string $kind, string $title, string $detail = '', ?string $entity = null, ?string $entityId = null): void {
    db()->prepare("INSERT INTO notifications (user_id,kind,title,detail,entity,entity_id) VALUES (?,?,?,?,?,?)")
        ->execute([$uid, $kind, $title, $detail, $entity, $entityId]);
}


function notif_sync(): void {
    $pdo = db();

    // NEW: delete notifications whose reservation/guest was removed or archived
    $pdo->exec("DELETE FROM notifications
                WHERE entity = 'reservation'
                  AND NOT EXISTS (
                    SELECT 1 FROM reservations r
                    JOIN guests g ON g.id = r.guest_id
                    WHERE CAST(r.id AS TEXT) = notifications.entity_id
                      AND r.archived = 0 AND g.archived = 0)");

    // NEW: delete notifications whose payment/guest was removed, archived or voided
    $pdo->exec("DELETE FROM notifications
                WHERE entity = 'payment'
                  AND NOT EXISTS (
                    SELECT 1 FROM payments p
                    JOIN guests g ON g.id = p.guest_id
                    WHERE CAST(p.id AS TEXT) = notifications.entity_id
                      AND p.archived = 0 AND p.voided = 0 AND g.archived = 0)");

    
    $pdo->exec("UPDATE notifications SET read_at = datetime('now'), dismissed = 1
                WHERE kind = 'pending_reservation' AND read_at IS NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM reservations r
                    WHERE CAST(r.id AS TEXT) = notifications.entity_id
                      AND r.status = 'Pending')");

    $pdo->exec("UPDATE notifications SET read_at = datetime('now'), dismissed = 1
                WHERE kind = 'payment_due' AND read_at IS NULL
                  AND NOT EXISTS (
                    SELECT 1 FROM payments p
                    WHERE CAST(p.id AS TEXT) = notifications.entity_id
                      AND p.status IN ('Pending','Overdue'))");

    // drop read notifications older than 30 days
    $pdo->exec("DELETE FROM notifications
                WHERE read_at IS NOT NULL AND read_at < datetime('now','-30 days')");
}

/** Derived notifications: pending reservations + pending/overdue payments. */
function notif_derive(int $uid, string $role): void {
    $pdo = db();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $soon  = (new DateTimeImmutable('today +3 days'))->format('Y-m-d');

    // Check if ANY notification exists for this entity 
    $existing = $pdo->prepare("SELECT 1 FROM notifications WHERE kind = ? AND entity_id = ? LIMIT 1");

    // Pending reservations checking in soon
    $st = $pdo->prepare("SELECT r.id, g.name, r.room, r.check_in FROM reservations r
                         JOIN guests g ON g.id = r.guest_id
                         WHERE r.status='Pending' AND r.archived=0 AND g.archived=0 AND r.check_in BETWEEN ? AND ?");
    $st->execute([$today, $soon]);
    foreach ($st->fetchAll() as $r) {
        $existing->execute(['pending_reservation', $r['id']]);
        if (!$existing->fetch()) {
            notif_add($uid, 'pending_reservation', "{$r['name']} — Room {$r['room']}",
                      "Check-in {$r['check_in']} · Awaiting confirmation", 'reservation', (string)$r['id']);
        }
    }

    $st = $pdo->prepare("SELECT p.id, p.amount, p.method, g.name FROM payments p
                         JOIN guests g ON g.id = p.guest_id
                         WHERE p.status IN ('Pending','Overdue') AND p.archived=0 AND p.voided=0 AND g.archived=0");
    $st->execute();
    foreach ($st->fetchAll() as $p) {
        $existing->execute(['payment_due', $p['id']]);
        if (!$existing->fetch()) {
            notif_add($uid, 'payment_due', "Payment {$p['id']} — {$p['name']}",
                      '₱' . number_format((float)$p['amount']) . " · {$p['method']}", 'payment', (string)$p['id']);
        }
    }
}


function notif_list(int $uid): array {
    notif_sync(); 

    $st = db()->prepare("SELECT n.*,
                                CASE n.entity
                                  WHEN 'payment'     THEN p.status
                                  WHEN 'reservation' THEN r.status
                                END AS live_status
                         FROM notifications n
                         LEFT JOIN payments p     ON n.entity = 'payment'     AND CAST(p.id AS TEXT) = n.entity_id
                         LEFT JOIN reservations r ON n.entity = 'reservation' AND CAST(r.id AS TEXT) = n.entity_id
                         WHERE n.dismissed = 0
                           AND (n.user_id IS NULL OR n.user_id = ?)
                         ORDER BY n.id DESC LIMIT 100");
    $st->execute([$uid]);
    return $st->fetchAll();
}

function notif_mark_read(int $uid, ?int $id = null): void {
    if ($id === null) {
        db()->prepare("UPDATE notifications SET read_at = datetime('now') WHERE (user_id IS NULL OR user_id = ?) AND read_at IS NULL")->execute([$uid]);
    } else {
        db()->prepare("UPDATE notifications SET read_at = datetime('now') WHERE id = ? AND (user_id IS NULL OR user_id = ?) AND read_at IS NULL")->execute([$id, $uid]);
    }
}

function notif_clear(int $uid): void {
    db()->prepare("UPDATE notifications SET dismissed=1 WHERE user_id IS NULL OR user_id = ?")->execute([$uid]);
}