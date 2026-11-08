<?php
declare(strict_types=1);

function v_str(?string $v, int $max = 255): ?string {
    $v = trim((string)$v);
    if ($v === '') return null;
    return mb_substr($v, 0, $max);
}
function v_email(?string $v): ?string {
    $v = trim((string)$v);
    return filter_var($v, FILTER_VALIDATE_EMAIL) ?: null;
}
function v_date(?string $v): ?string {
    $v = trim((string)$v);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return null;
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    return $d ? $d->format('Y-m-d') : null;
}
function v_float($v, float $min = 0): ?float {
    if (!is_numeric($v)) return null;
    $n = (float)$v;
    return $n >= $min ? $n : null;
}
function v_int($v, int $min = 0): ?int {
    if (!is_numeric($v)) return null;
    $n = (int)$v;
    return $n >= $min ? $n : null;
}
function v_enum(?string $v, array $allowed): ?string {
    return in_array($v, $allowed, true) ? $v : null;
}

/** Business rule: no overlapping active reservations for the same room. */
function room_available(string $room, string $checkIn, string $checkOut, ?string $ignoreId = null): bool {
    $sql = "SELECT 1 FROM reservations
            WHERE room = ? AND archived = 0
              AND status NOT IN ('Cancelled','Checked Out')
              AND (check_in < ? AND check_out > ?)";
    $params = [$room, $checkOut, $checkIn];
    if ($ignoreId) { $sql .= " AND id <> ?"; $params[] = $ignoreId; }
    $st = db()->prepare($sql . " LIMIT 1");
    $st->execute($params);
    return !$st->fetch();
}