<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/csrf.php';

$u = require_login();
$role = $u['role'];
$uid = (int)$u['id'];

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$in = $method === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?: [])
    : $_GET;

if ($method === 'POST' && !csrf_check($in['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? null))) {
    json_out(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
}

try {
    switch ($action) {

        case 'bootstrap':
            notif_derive($uid, $role);
            $rooms = db()->query("SELECT * FROM rooms WHERE deleted_at IS NULL ORDER BY number")->fetchAll();
            $guests = db()->query("SELECT * FROM guests ORDER BY name")->fetchAll();
            $reservations = db()->query(
                "SELECT r.*, g.name AS guest FROM reservations r
                 JOIN guests g ON g.id = r.guest_id
                 ORDER BY r.created_at DESC"
            )->fetchAll();
            $staff = db()->query("SELECT * FROM staff ORDER BY name")->fetchAll();
            $payments = db()->query(
                "SELECT p.*, g.name AS guest FROM payments p
                 JOIN guests g ON g.id = p.guest_id
                 ORDER BY p.date DESC"
            )->fetchAll();
            $activity = db()->query("SELECT * FROM activity ORDER BY id DESC LIMIT 40")->fetchAll();
            json_out(['ok' => true, 'data' => [
                'user' => ['id' => $uid, 'name' => $u['display_name'], 'role' => $role],
                'rooms' => $rooms, 'guests' => $guests,
                'reservations' => $reservations, 'staff' => $staff,
                'payments' => $payments, 'activity' => $activity,
                'today' => (new DateTimeImmutable('today'))->format('Y-m-d'),
                'csrf' => csrf_token(),
            ]]);

        case 'reservation.save':
            $d = $in['data'] ?? [];
            $id = v_str($d['id'] ?? null, 20);
            $guestName = v_str($d['guest'] ?? '', 120);
            $guestId = v_int($d['guest_id'] ?? 0, 1);
            $room = v_str($d['room'] ?? '', 10);
            $checkIn = v_date($d['checkIn'] ?? '');
            $checkOut = v_date($d['checkOut'] ?? '');
            $status = v_enum($d['status'] ?? '', ['Pending','Confirmed','Checked In','Checked Out','Cancelled','Overdue']);
            $source = v_enum($d['source'] ?? 'Direct', ['Direct','Website','Phone','Agency','Walk-in']) ?? 'Direct';
            $adults = v_int($d['adults'] ?? 1, 1) ?? 1;

            if (!$guestName || !$room || !$checkIn || !$checkOut || !$status) {
                json_out(['ok' => false, 'error' => 'Missing fields'], 422);
            }
            if ($checkOut <= $checkIn) {
                json_out(['ok' => false, 'error' => 'Check-out must be after check-in'], 422);
            }

            if (!$guestId) {
                $st = db()->prepare("SELECT id FROM guests WHERE name = ? LIMIT 1");
                $st->execute([$guestName]);
                $guestId = (int)($st->fetchColumn() ?: 0);
                if (!$guestId) {
                    db()->prepare("INSERT INTO guests (name, room) VALUES (?,?)")->execute([$guestName, $room]);
                    $guestId = (int)db()->lastInsertId();
                }
            }

            if (!room_available($room, $checkIn, $checkOut, $id ?: null)) {
                json_out(['ok' => false, 'error' => "Room $room is already booked for those dates"], 409);
            }

            if ($id) {
                $b = db()->prepare("SELECT * FROM reservations WHERE id = ?");
                $b->execute([$id]);
                $before = $b->fetch() ?: [];
                db()->prepare("UPDATE reservations SET guest_id=?,room=?,check_in=?,check_out=?,adults=?,source=?,status=? WHERE id=?")
                    ->execute([$guestId,$room,$checkIn,$checkOut,$adults,$source,$status,$id]);
                audit($uid, 'reservation.update', 'reservation', $id, $before, compact('guestId','room','checkIn','checkOut','status'));
                log_activity($uid, 'info', "Updated reservation $id");
            } else {
                $newId = next_code('NC', 'reservations');
                db()->prepare("INSERT INTO reservations (id,guest_id,room,check_in,check_out,adults,source,status) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$newId,$guestId,$room,$checkIn,$checkOut,$adults,$source,$status]);
                audit($uid, 'reservation.create', 'reservation', $newId);
                log_activity($uid, 'success', "Created reservation $newId");
                notif_add(null, 'reservation_new', "New reservation $newId", "$guestName · Room $room", 'reservation', $newId);
            }
            sync_room_from_reservations($room);
            json_out(['ok' => true]);

        case 'reservation.checkin':
            $id = v_str($in['id'] ?? '', 20);
            $r = db()->prepare("SELECT * FROM reservations WHERE id = ?"); $r->execute([$id]);
            $row = $r->fetch();
            if (!$row) json_out(['ok'=>false,'error'=>'Not found'], 404);
            db()->prepare("UPDATE reservations SET status='Checked In' WHERE id=?")->execute([$id]);
            sync_room_from_reservations($row['room']);
            audit($uid, 'reservation.checkin', 'reservation', $id);
            log_activity($uid, 'success', "Checked in reservation $id");
            json_out(['ok' => true]);

        case 'reservation.checkout':
            $id = v_str($in['id'] ?? '', 20);
            $r = db()->prepare("SELECT * FROM reservations WHERE id = ?"); $r->execute([$id]);
            $row = $r->fetch();
            if (!$row) json_out(['ok'=>false,'error'=>'Not found'], 404);
            db()->prepare("UPDATE reservations SET status='Checked Out' WHERE id=?")->execute([$id]);
            sync_room_from_reservations($row['room']);
            audit($uid, 'reservation.checkout', 'reservation', $id);
            log_activity($uid, 'warn', "Checked out reservation $id");
            json_out(['ok' => true]);

        case 'reservation.archive':
        case 'reservation.delete':
            require_role(['admin','manager']);
            $id = v_str($in['id'] ?? '', 20);
            if ($action === 'reservation.delete') {
                db()->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
                audit($uid, 'reservation.delete', 'reservation', $id);
            } else {
                db()->prepare("UPDATE reservations SET archived=1 WHERE id = ?")->execute([$id]);
                audit($uid, 'reservation.archive', 'reservation', $id);
            }
            json_out(['ok' => true]);

        case 'room.save':
            require_role(['admin','manager']);
            $d = $in['data'] ?? [];
            $number = v_str($d['number'] ?? '', 10);
            $type   = v_enum($d['type'] ?? '', ['Standard','Deluxe','Suite','Penthouse']);
            $price  = v_float($d['price'] ?? 0, 0);
            $status = v_enum($d['status'] ?? '', ['Available','Occupied','Maintenance']);
            if (!$number || !$type || $price === null || !$status) {
                json_out(['ok'=>false,'error'=>'Invalid data'], 422);
            }
            $existing = db()->prepare("SELECT * FROM rooms WHERE number = ?");
            $existing->execute([$number]);
            $before = $existing->fetch();
            if ($before) {
                db()->prepare("UPDATE rooms SET type=?,price=?,status=? WHERE number=?")
                    ->execute([$type,$price,$status,$number]);
                audit($uid, 'room.update', 'room', $number, $before, compact('type','price','status'));
            } else {
                db()->prepare("INSERT INTO rooms (number,type,price,status) VALUES (?,?,?,?)")
                    ->execute([$number,$type,$price,$status]);
                audit($uid, 'room.create', 'room', $number);
            }
            json_out(['ok' => true]);

        case 'room.delete':
            require_role(['admin','manager']);
            $number = v_str($in['number'] ?? '', 10);
            $st = db()->prepare("SELECT COUNT(*) FROM reservations WHERE room=? AND archived=0 AND status NOT IN ('Cancelled','Checked Out')");
            $st->execute([$number]);
            if ((int)$st->fetchColumn() > 0) {
                json_out(['ok'=>false,'error'=>'Room has active reservations'], 409);
            }
            db()->prepare("UPDATE rooms SET deleted_at=datetime('now') WHERE number=?")->execute([$number]);
            audit($uid, 'room.delete', 'room', $number);
            json_out(['ok' => true]);

        case 'guest.save':
            $d = $in['data'] ?? [];
            $id = v_int($d['id'] ?? 0, 1);
            $name = v_str($d['name'] ?? '', 120);
            $email = v_email($d['email'] ?? '');
            $phone = v_str($d['phone'] ?? '', 40);
            if (!$name) json_out(['ok'=>false,'error'=>'Name required'], 422);
            if ($id) {
                db()->prepare("UPDATE guests SET name=?,email=?,phone=? WHERE id=?")
                    ->execute([$name,$email,$phone,$id]);
                audit($uid, 'guest.update', 'guest', (string)$id);
            } else {
                db()->prepare("INSERT INTO guests (name,email,phone) VALUES (?,?,?)")
                    ->execute([$name,$email,$phone]);
                audit($uid, 'guest.create', 'guest', (string)db()->lastInsertId());
            }
            json_out(['ok' => true]);

        case 'guest.archive':
        case 'guest.delete':
            require_role(['admin','manager']);
            $id = v_int($in['id'] ?? 0, 1);
            if ($action === 'guest.delete') {
                db()->prepare("DELETE FROM guests WHERE id=?")->execute([$id]);
            } else {
                db()->prepare("UPDATE guests SET archived=1 WHERE id=?")->execute([$id]);
            }
            audit($uid, $action, 'guest', (string)$id);
            json_out(['ok' => true]);

        case 'staff.save':
            require_role(['admin','manager']);
            $d = $in['data'] ?? [];
            $id = v_int($d['id'] ?? 0, 1);
            $name = v_str($d['name'] ?? '', 120);
            $roleSt = v_enum($d['role'] ?? '', ['Hotel Manager','Front Desk','Housekeeping','Maintenance','Security','Other']);
            $shift = v_enum($d['shift'] ?? '', ['Morning','Afternoon','Night']);
            $email = v_email($d['email'] ?? '');
            $phone = v_str($d['phone'] ?? '', 40);
            if (!$name || !$roleSt || !$shift) json_out(['ok'=>false,'error'=>'Missing fields'], 422);
            if ($id) {
                db()->prepare("UPDATE staff SET name=?,role=?,shift=?,email=?,phone=? WHERE id=?")
                    ->execute([$name,$roleSt,$shift,$email,$phone,$id]);
            } else {
                db()->prepare("INSERT INTO staff (name,role,shift,email,phone) VALUES (?,?,?,?,?)")
                    ->execute([$name,$roleSt,$shift,$email,$phone]);
            }
            audit($uid, $id ? 'staff.update' : 'staff.create', 'staff', (string)$id);
            json_out(['ok' => true]);

        case 'staff.archive':
        case 'staff.delete':
            require_role(['admin','manager']);
            $id = v_int($in['id'] ?? 0, 1);
            if ($action === 'staff.delete') db()->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
            else db()->prepare("UPDATE staff SET archived=1 WHERE id=?")->execute([$id]);
            audit($uid, $action, 'staff', (string)$id);
            json_out(['ok' => true]);

        case 'payment.save':
            require_role(['admin','manager','front_desk']);
            $d = $in['data'] ?? [];
            $id = v_str($d['id'] ?? null, 20);
            $guestId = v_int($d['guest_id'] ?? 0, 1);
            $guestName = v_str($d['guest'] ?? '', 120);
            $room = v_str($d['room'] ?? '', 10);
            $date = v_date($d['date'] ?? '');
            $amount = v_float($d['amount'] ?? 0, 0);
            $status = v_enum($d['status'] ?? '', ['Paid','Pending','Overdue']);
            $methodP = v_enum($d['method'] ?? '', ['Cash','Card','Bank Transfer','GCash']);
            if (!$guestId && $guestName) {
                $st = db()->prepare("SELECT id FROM guests WHERE name=? LIMIT 1");
                $st->execute([$guestName]);
                $guestId = (int)($st->fetchColumn() ?: 0);
            }
            if (!$guestId || !$room || !$date || $amount === null || !$status || !$methodP) {
                json_out(['ok'=>false,'error'=>'Missing fields'], 422);
            }
            if ($id) {
                db()->prepare("UPDATE payments SET status=?,method=? WHERE id=?")->execute([$status,$methodP,$id]);
                audit($uid, 'payment.update', 'payment', $id);
            } else {
                $newId = next_code('PAY', 'payments');
                db()->prepare("INSERT INTO payments (id,guest_id,room,date,amount,status,method) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$newId,$guestId,$room,$date,$amount,$status,$methodP]);
                audit($uid, 'payment.create', 'payment', $newId);
                log_activity($uid, 'success', "Recorded payment $newId");
                if ($status === 'Paid') {
                    notif_add(null, 'payment_received', "Payment received $newId",
                              '₱' . number_format($amount), 'payment', $newId);
                }
            }
            json_out(['ok' => true]);

        case 'payment.archive':
            require_role(['admin','manager']);
            $id = v_str($in['id'] ?? '', 20);
            db()->prepare("UPDATE payments SET archived=1 WHERE id=?")->execute([$id]);
            audit($uid, 'payment.archive', 'payment', $id);
            json_out(['ok' => true]);

        case 'payment.delete':
            require_role(['admin','manager']);
            $id = v_str($in['id'] ?? '', 20);
            db()->prepare("DELETE FROM payments WHERE id=?")->execute([$id]);
            audit($uid, 'payment.delete', 'payment', $id);
            json_out(['ok' => true]);

        case 'notifications.list':
            json_out(['ok' => true, 'data' => notif_list($uid)]);

        case 'notifications.read':
            $notifId = isset($in['id']) ? (int)$in['id'] : null;
            notif_mark_read($uid, $notifId);
            json_out(['ok' => true]);

        case 'notifications.clear':
            notif_clear($uid);
            // Also mark all as read for this user
            db()->prepare("UPDATE notifications SET read_at = datetime('now') WHERE read_at IS NULL AND (user_id IS NULL OR user_id = ?)")->execute([$uid]);
            json_out(['ok' => true]);

        case 'profile.get':
            json_out(['ok' => true, 'data' => [
                'id' => $uid, 'username' => $u['username'], 'name' => $u['display_name'],
                'email' => $u['email'], 'phone' => $u['phone'], 'role' => $u['role'],
                'status' => $u['status'], 'member_since' => $u['member_since'],
            ]]);

        case 'profile.save':
            $d = $in['data'] ?? [];
            $name = v_str($d['name'] ?? '', 120);
            $email = v_email($d['email'] ?? '');
            $phone = v_str($d['phone'] ?? '', 40);
            if (!$name) json_out(['ok'=>false,'error'=>'Name required'], 422);
            db()->prepare("UPDATE users SET display_name=?,email=?,phone=? WHERE id=?")
                ->execute([$name,$email,$phone,$uid]);
            audit($uid, 'profile.update', 'user', (string)$uid);
            json_out(['ok' => true]);

        case 'profile.password':
            $old = (string)($in['old'] ?? '');
            $new = (string)($in['new'] ?? '');
            if (strlen($new) < 8) json_out(['ok'=>false,'error'=>'New password must be 8+ chars'], 422);
            if (!password_verify($old, $u['password_hash'])) {
                json_out(['ok'=>false,'error'=>'Current password is wrong'], 403);
            }
            db()->prepare("UPDATE users SET password_hash=? WHERE id=?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            audit($uid, 'profile.password_changed', 'user', (string)$uid);
            json_out(['ok' => true]);

        case 'reports.summary':
            $paid = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Paid' AND voided=0 AND archived=0")->fetchColumn();
            $pending = (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Pending' AND voided=0 AND archived=0")->fetchColumn();
            $totalRooms = (int)db()->query("SELECT COUNT(*) FROM rooms WHERE deleted_at IS NULL")->fetchColumn();
            $occupied = (int)db()->query("SELECT COUNT(*) FROM rooms WHERE status='Occupied' AND deleted_at IS NULL")->fetchColumn();
            $resCount = (int)db()->query("SELECT COUNT(*) FROM reservations WHERE archived=0")->fetchColumn();
            $inHouse = (int)db()->query("SELECT COUNT(*) FROM reservations WHERE status='Checked In' AND archived=0")->fetchColumn();
            json_out(['ok' => true, 'data' => [
                'paid' => $paid, 'pending' => $pending,
                'occupancy' => $totalRooms ? round($occupied / $totalRooms * 100) : 0,
                'rooms' => $totalRooms, 'occupied' => $occupied,
                'reservations' => $resCount, 'in_house' => $inHouse,
            ]]);

        case 'export.csv':
            require_role(['admin','manager', 'front_desk']);
            $section = $_GET['section'] ?? 'reservations';
            $allowed = ['reservations','rooms','guests','payments','staff'];
            if (!in_array($section, $allowed, true)) json_out(['ok'=>false,'error'=>'Invalid section'], 400);
            
            // Change extension to .xls so Excel opens it nicely
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="solmbra-' . $section . '-' . date('Y-m-d') . '.xls"');
            
            $out = fopen('php://output', 'w');
            fputs($out, (chr(0xEF) . chr(0xBB) . chr(0xBF))); // UTF-8 BOM for Peso sign
            
            $rows = db()->query("SELECT * FROM $section")->fetchAll();
            
            if ($rows) {
                // Print an HTML table that Excel reads perfectly
                echo '<table border="1">';
                echo '<tr>';
                foreach (array_keys($rows[0]) as $header) {
                    echo '<th style="background-color: #58327b; color: white; padding: 5px;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
                }
                echo '</tr>';
                foreach ($rows as $r) {
                    echo '<tr>';
                    foreach ($r as $cell) {
                        echo '<td style="padding: 5px;">' . htmlspecialchars((string)$cell) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
            }
            exit;

        default:
            json_out(['ok' => false, 'error' => 'Unknown action'], 404);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_out(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}

// ---- Helpers ---------------------------------------------------------------
function next_code(string $prefix, string $table): string {
    $st = db()->query("SELECT id FROM $table ORDER BY id DESC LIMIT 1");
    $last = (string)($st->fetchColumn() ?: '');
    $n = $last ? (int)substr($last, strlen($prefix) + 1) + 1 : 1001;
    return $prefix . '-' . $n;
}

function sync_room_from_reservations(string $room): void {
    $st = db()->prepare("SELECT * FROM rooms WHERE number=? AND deleted_at IS NULL");
    $st->execute([$room]);
    $r = $st->fetch();
    if (!$r) return;
    if (!empty($r['maintenance_until']) && $r['maintenance_until'] > date('Y-m-d H:i:s')) return;

    $st2 = db()->prepare("SELECT 1 FROM reservations WHERE room=? AND status='Checked In' AND archived=0 LIMIT 1");
    $st2->execute([$room]);
    $newStatus = $st2->fetch() ? 'Occupied'
        : (!empty($r['maintenance_until']) ? 'Maintenance' : 'Available');
    db()->prepare("UPDATE rooms SET status=? WHERE number=?")->execute([$newStatus, $room]);
}