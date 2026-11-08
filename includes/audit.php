<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

function audit(?int $uid, string $action, ?string $entity = null, ?string $entityId = null, array $before = [], array $after = []): void {
    db()->prepare("INSERT INTO audit_log (user_id,action,entity,entity_id,before,after) VALUES (?,?,?,?,?,?)")
        ->execute([$uid, $action, $entity, $entityId,
                   $before ? json_encode($before) : null,
                   $after ? json_encode($after) : null]);
}

function log_activity(?int $uid, string $type, string $message): void {
    db()->prepare("INSERT INTO activity (user_id,type,message) VALUES (?,?,?)")
        ->execute([$uid, $type, $message]);
}