-- Initial services. Import once after schema.sql.
SET NAMES utf8mb4;
INSERT INTO services (id, label, icon, target_type, wa_phone, wa_message, url, sort_order, is_active) VALUES
(1, 'Info Warga', 'home', 'whatsapp', '628158842011', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Info Warga', NULL, 1, 1),
(2, 'Darurat', 'alert', 'whatsapp', '6285810690389', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Darurat', NULL, 2, 1),
(3, 'Masjid', 'mosque', 'whatsapp', '6281905058984', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Masjid', NULL, 3, 1),
(4, 'Kebersihan', 'broom', 'whatsapp', '628561577061', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Kebersihan', NULL, 4, 1),
(5, 'Sosial', 'people', 'whatsapp', '628129460022', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Sosial', NULL, 5, 1),
(6, 'UMKM', 'shop', 'whatsapp', '6285811728162', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan UMKM', NULL, 6, 1),
(7, 'Posyandu', 'baby', 'whatsapp', '6281251116001', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Posyandu', NULL, 7, 1),
(8, 'Perpus Mini', 'books', 'whatsapp', '6285213412575', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Perpustakaan Mini', NULL, 8, 1),
(9, 'KWT Mina', 'plant', 'whatsapp', '6285624616646', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan KWT Mina', NULL, 9, 1),
(10, 'Mina Park', 'park', 'whatsapp', '6281585059808', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Mina Park', NULL, 10, 1),
(11, 'Keamanan', 'shield', 'whatsapp', '6287881100091', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Keamanan', NULL, 11, 1),
(12, 'Tangerang Siaga', 'bolt', 'whatsapp', '6221112', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Tangerang Siaga', NULL, 12, 1),
(13, 'PMI Kota Tangerang', 'health', 'whatsapp', '6281818242081', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan PMI Kota Tangerang', NULL, 13, 1),
(14, 'BPBD / Damkar', 'fire', 'whatsapp', '622155732113', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan BPBD / Damkar', NULL, 14, 1),
(15, 'Lapor Pak RW', 'megaphone', 'whatsapp', '6281282827725', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Lapor Pak RW', NULL, 15, 1),
(16, 'Kotak Saran', 'chat', 'whatsapp', '628567613690', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Kotak Saran', NULL, 16, 1),
(17, 'Tangerang Live', 'phone', 'link', NULL, NULL, 'https://play.google.com/store/apps/details?id=id.go.tangerangkota.tangeranglive', 17, 1),
(18, 'PSM / Kematian / Kesehatan', 'care', 'whatsapp', '6285210858283', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan PSM / Kematian / Kesehatan', NULL, 18, 1),
(19, 'Puskesmas Panbar', 'hospital', 'whatsapp', '6281110516661', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Puskesmas Panbar', NULL, 19, 1),
(20, 'Fardu Kifayah', 'mosque', 'whatsapp', '6287789275712', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Fardu Kifayah', NULL, 20, 1),
(21, 'Ambulan', 'ambulance', 'whatsapp', '622155771135', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Ambulan', NULL, 21, 1);

INSERT INTO umkm (name, category, description, address, wa_phone, is_sample) VALUES
('Dapur Mina Rasa', 'Kuliner', 'Aneka nasi kotak dan masakan rumahan untuk kebutuhan harian dan acara keluarga.', 'Alamat usaha belum diisi', '', 1),
('Kreasi Jahit Ilhami', 'Fashion & Konveksi', 'Jasa jahit pakaian, permak, dan pembuatan seragam sesuai pesanan.', 'Alamat usaha belum diisi', '', 1),
('Kebun Hijau Mina', 'Tanaman & Pertanian', 'Pilihan tanaman hias, bibit sayuran, dan perlengkapan berkebun untuk rumah.', 'Alamat usaha belum diisi', '', 1),
('Bersih Wangi Laundry', 'Jasa Laundry', 'Layanan cuci dan setrika pakaian untuk membantu kebutuhan rumah tangga.', 'Alamat usaha belum diisi', '', 1),
('Kriya Anyam Panbar', 'Kerajinan', 'Produk anyaman dan suvenir buatan tangan untuk hadiah serta dekorasi rumah.', 'Alamat usaha belum diisi', '', 1);
