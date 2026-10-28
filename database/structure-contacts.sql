CREATE TABLE IF NOT EXISTS structure_contacts (
    structure_key VARCHAR(10) NOT NULL PRIMARY KEY,
    name VARCHAR(120) NOT NULL DEFAULT '',
    wa_phone VARCHAR(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
