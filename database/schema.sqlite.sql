-- SQLite schema. Keep the database file outside the public document root.
PRAGMA foreign_keys = ON;
CREATE TABLE admins (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT NOT NULL COLLATE NOCASE UNIQUE,
 password_hash TEXT NOT NULL,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE services (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 label TEXT NOT NULL,
 icon TEXT NOT NULL,
 target_type TEXT NOT NULL DEFAULT 'whatsapp',
 wa_phone TEXT,
 wa_message TEXT,
 url TEXT,
 sort_order INTEGER NOT NULL DEFAULT 0,
 is_active INTEGER NOT NULL DEFAULT 1,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX services_display ON services (is_active, sort_order, id);
CREATE TRIGGER services_updated AFTER UPDATE ON services
WHEN NEW.updated_at = OLD.updated_at
BEGIN
 UPDATE services SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
CREATE TABLE visitors (
 visitor_hash TEXT NOT NULL PRIMARY KEY,
 first_seen TEXT NOT NULL,
 last_seen TEXT NOT NULL
);
CREATE TABLE visitor_days (
 visitor_hash TEXT NOT NULL REFERENCES visitors(visitor_hash) ON DELETE CASCADE,
 visit_date TEXT NOT NULL,
 PRIMARY KEY (visitor_hash, visit_date)
);
CREATE INDEX visitor_days_date ON visitor_days (visit_date, visitor_hash);
CREATE TABLE login_attempts (
 bucket_hash TEXT NOT NULL PRIMARY KEY,
 attempts INTEGER NOT NULL DEFAULT 0,
 expires_at TEXT NOT NULL
);
CREATE INDEX login_expiry ON login_attempts (expires_at);
CREATE TABLE structure_contacts (
 structure_key TEXT NOT NULL PRIMARY KEY,
 name TEXT NOT NULL DEFAULT '',
 wa_phone TEXT NOT NULL
);
CREATE TABLE umkm (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL,
 category TEXT NOT NULL,
 description TEXT NOT NULL,
 address TEXT NOT NULL,
 wa_phone TEXT NOT NULL DEFAULT '',
 is_sample INTEGER NOT NULL DEFAULT 0,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
