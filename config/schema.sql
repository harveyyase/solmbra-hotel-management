PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    display_name  TEXT NOT NULL,
    password_hash TEXT NOT NULL,             -- password_hash(PASSWORD_DEFAULT)
    pin_hash      TEXT NOT NULL,             -- optional 4-digit quick re-entry
    role          TEXT NOT NULL DEFAULT 'front_desk', -- admin|manager|front_desk|housekeeping|maintenance
    status        TEXT NOT NULL DEFAULT 'Active',
    email         TEXT,
    phone         TEXT,
    member_since  TEXT NOT NULL,
    failed_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until  INTEGER NOT NULL DEFAULT 0, -- unix ts, 0 = not locked
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions_audit (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    ip         TEXT,
    ua         TEXT,
    event      TEXT NOT NULL,       -- login_ok|login_fail|logout|lockout
    at         TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS rooms (
    number    TEXT PRIMARY KEY,
    type      TEXT NOT NULL,
    price     REAL NOT NULL,
    status    TEXT NOT NULL,           -- Available|Occupied|Maintenance (derived from reservations on the server for accuracy)
    maintenance_until TEXT,            -- ISO date, optional
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS guests (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    name      TEXT NOT NULL,
    email     TEXT,
    phone     TEXT,
    room      TEXT,                    -- last room hint; NOT the source of truth
    archived  INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS reservations (
    id         TEXT PRIMARY KEY,       -- NC-XXXX
    guest_id   INTEGER NOT NULL,
    room       TEXT NOT NULL,
    check_in   TEXT NOT NULL,
    check_out  TEXT NOT NULL,
    adults     INTEGER NOT NULL DEFAULT 1,
    source     TEXT NOT NULL DEFAULT 'Direct',
    status     TEXT NOT NULL,          -- Pending|Confirmed|Checked In|Checked Out|Cancelled|Overdue
    archived   INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id        TEXT PRIMARY KEY,        -- PAY-XXXX
    reservation_id TEXT,               -- FK now, optional
    guest_id  INTEGER NOT NULL,
    room      TEXT,
    date      TEXT NOT NULL,
    amount    REAL NOT NULL,
    status    TEXT NOT NULL,           -- Paid|Pending|Overdue
    method    TEXT NOT NULL,
    voided    INTEGER NOT NULL DEFAULT 0,  -- append-only: never hard-delete
    archived  INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS staff (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    name      TEXT NOT NULL,
    role      TEXT NOT NULL,
    shift     TEXT NOT NULL,
    email     TEXT,
    phone     TEXT,
    archived  INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS activity (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id  INTEGER,
    type     TEXT NOT NULL,            -- info|success|warn|danger
    message  TEXT NOT NULL,
    at       TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS audit_log (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id  INTEGER,
    action   TEXT NOT NULL,
    entity   TEXT,
    entity_id TEXT,
    before   TEXT,
    after    TEXT,
    at       TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,                -- NULL = broadcast to all
    kind       TEXT NOT NULL,          -- pending_reservation|payment_due|arrival|checkout|maintenance
    title      TEXT NOT NULL,
    detail     TEXT,
    entity     TEXT,
    entity_id  TEXT,
    read_at    TEXT,
    dismissed  INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS rate_limit (
    key     TEXT PRIMARY KEY,
    window_start INTEGER NOT NULL,
    count   INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_res_room_dates ON reservations(room, check_in, check_out);
CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications(user_id, dismissed);