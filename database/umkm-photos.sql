CREATE TABLE IF NOT EXISTS umkm_photos (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 umkm_id INT UNSIGNED NOT NULL,
 filename VARCHAR(36) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 is_sample TINYINT(1) NOT NULL DEFAULT 0,
 UNIQUE KEY umkm_photo_file (umkm_id, filename),
 KEY umkm_photo_order (umkm_id, sort_order, id),
 FOREIGN KEY (umkm_id) REFERENCES umkm(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
