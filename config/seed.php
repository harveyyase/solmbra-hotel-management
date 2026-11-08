<?php
declare(strict_types=1);

function seed_database(PDO $pdo): void {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pin  = password_hash('1234', PASSWORD_DEFAULT);

    $pdo->prepare("INSERT INTO users (username, display_name, password_hash, pin_hash, role, email, member_since)
                   VALUES (?,?,?,?,?,?,?)")
        ->execute(['admin', 'Admin', $pass, $pin, 'admin', 'admin@solmbra.com', 'October 2026']);

    $rooms = [
        ['101','Standard',1800,'Available'],['102','Deluxe',2500,'Occupied'],
        ['103','Standard',1800,'Available'],['104','Suite',4500,'Maintenance'],
        ['201','Deluxe',2500,'Occupied'],['202','Standard',1800,'Available'],
        ['203','Suite',4500,'Available'],['204','Deluxe',2500,'Occupied'],
        ['301','Suite',4500,'Available'],['302','Standard',1800,'Occupied'],
        ['303','Deluxe',2500,'Available'],['304','Suite',4500,'Occupied'],
    ];
    $ins = $pdo->prepare("INSERT INTO rooms (number,type,price,status) VALUES (?,?,?,?)");
    foreach ($rooms as $r) $ins->execute($r);

    $guests = [
        ['Olivia Bennett','olivia@example.com','0917 111 2222','201'],
        ['Ethan Carter','ethan@example.com','0917 333 4444','304'],
        ['Sophia Mitchell','sophia@example.com','0917 555 6666','203'],
        ['Noah Williams','noah@example.com','0917 777 8888','301'],
    ];
    $insG = $pdo->prepare("INSERT INTO guests (name,email,phone,room) VALUES (?,?,?,?)");
    foreach ($guests as $g) $insG->execute($g);

    // Relative reservation dates
    $today = new DateTimeImmutable('today');
    $d = fn(int $n) => $today->modify(($n >= 0 ? '+' : '') . $n . ' days')->format('Y-m-d');

    $res = [
        ['NC-1001',1,'201',$d(-1),$d(2),'Checked In',2,'Direct'],
        ['NC-1002',2,'304',$d(-1),$d(1),'Checked In',1,'Website'],
        ['NC-1003',3,'203',$d(1),$d(3),'Pending',2,'Website'],
        ['NC-1004',4,'301',$d(2),$d(5),'Confirmed',3,'Direct'],
    ];
    $insR = $pdo->prepare("INSERT INTO reservations (id,guest_id,room,check_in,check_out,status,adults,source) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($res as $r) $insR->execute($r);

    $pdo->prepare("INSERT INTO payments (id,reservation_id,guest_id,room,date,amount,status,method) VALUES (?,?,?,?,?,?,?,?)")
        ->execute(['PAY-1001','NC-1001',1,'201',$d(-5),2500,'Paid','Card']);
    $pdo->prepare("INSERT INTO payments (id,reservation_id,guest_id,room,date,amount,status,method) VALUES (?,?,?,?,?,?,?,?)")
        ->execute(['PAY-1002','NC-1002',2,'304',$d(-3),4500,'Paid','GCash']);
}