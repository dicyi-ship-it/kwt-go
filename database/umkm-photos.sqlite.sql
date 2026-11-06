CREATE TABLE IF NOT EXISTS umkm_photos (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 umkm_id INTEGER NOT NULL REFERENCES umkm(id) ON DELETE CASCADE,
 filename TEXT NOT NULL,
 sort_order INTEGER NOT NULL DEFAULT 0,
 is_sample INTEGER NOT NULL DEFAULT 0,
 UNIQUE (umkm_id, filename)
);
CREATE INDEX IF NOT EXISTS umkm_photo_order ON umkm_photos (umkm_id, sort_order, id);
