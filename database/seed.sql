-- Initial services. Import once after schema.sql.
SET NAMES utf8mb4;
INSERT INTO services (id, label, icon, target_type, wa_phone, wa_message, url, sort_order, is_active) VALUES
(1, 'Info Warga', 'home', 'whatsapp', '628158842011', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Info Warga', NULL, 1, 1),
(2, 'Darurat', 'alert', 'whatsapp', '6285810690389', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Darurat', NULL, 2, 1),
(3, 'Masjid', 'mosque', 'whatsapp', '6281905058984', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Masjid', NULL, 3, 1),
(4, 'Kebersihan', 'broom', 'whatsapp', '628561577061', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Kebersihan', NULL, 4, 1),
(5, 'Sosial', 'people', 'whatsapp', '628129460022', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Sosial', NULL, 5, 1),
(6, 'UMKM', 'shop', 'whatsapp', '6281294345693', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan UMKM', NULL, 6, 1),
(7, 'Posyandu', 'baby', 'whatsapp', '6281251116001', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Posyandu', NULL, 7, 1),
(8, 'Perpus Mini', 'books', 'whatsapp', '6285213412575', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Perpustakaan Mini', NULL, 8, 1),
(9, 'KWT Mina', 'plant', 'whatsapp', '6281294345693', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan KWT Mina', NULL, 9, 1),
(10, 'Mina Park', 'park', 'whatsapp', '6281585059808', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Mina Park', NULL, 10, 1),
(11, 'Keamanan', 'shield', 'whatsapp', '6287881100091', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Keamanan', NULL, 11, 1),
(12, 'Tangerang Siaga 112', 'logo_tangerang', 'link', NULL, NULL, 'tel:112', 12, 1),
(13, 'PMI Kota Tangerang - Posko / Ambulans', 'logo_pmi', 'link', NULL, NULL, 'tel:02138951051', 13, 1),
(14, 'UPT Damkar Cibodas Tangerang', 'logo_damkar', 'whatsapp', '6281387373100', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan UPT Damkar Cibodas Tangerang', NULL, 14, 1),
(15, 'Lapor Pak RW', 'megaphone', 'whatsapp', '6281282827725', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Lapor Pak RW', NULL, 15, 1),
(16, 'Kotak Saran', 'chat', 'whatsapp', '628567613690', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Kotak Saran', NULL, 16, 1),
(17, 'Tangerang Live', 'phone', 'link', NULL, NULL, 'https://play.google.com/store/apps/details?id=id.go.tangerangkota.tangeranglive', 17, 1),
(18, 'Fardhu Kifayah / PSM / Kematian', 'care', 'whatsapp', '6285210858283', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Fardhu Kifayah / PSM / Kematian', NULL, 18, 1),
(19, 'Puskesmas Panbar', 'logo_puskesmas', 'whatsapp', '62895339017808', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Puskesmas Panbar', NULL, 19, 1),
(20, 'Fardu Kifayah', 'mosque', 'whatsapp', '6287789275712', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Fardu Kifayah', NULL, 20, 0),
(21, 'Ambulan', 'ambulance', 'whatsapp', '622155771135', 'Assalamualaikum, saya ... ingin minta bantuan pelayanan Ambulan', NULL, 21, 1),
(22, 'Polisi 110', 'logo_polri', 'link', NULL, NULL, 'tel:110', 12, 1),
(23, 'PMI Kota Tangerang - Permintaan Darah', 'logo_pmi', 'link', NULL, NULL, 'tel:02155791008', 13, 1);

INSERT INTO umkm (name, category, description, address, wa_phone, is_sample) VALUES
('Dapur Mina Rasa', 'Kuliner', 'Aneka nasi kotak dan masakan rumahan untuk kebutuhan harian dan acara keluarga.', 'Villa Ilhami Blok Mina No. 12, RT 01 / RW 12', '6281294345693', 1),
('Kreasi Jahit Ilhami', 'Fashion & Konveksi', 'Jasa jahit pakaian, permak, dan pembuatan seragam sesuai pesanan.', 'Villa Ilhami Blok Mina No. 8, RT 02 / RW 12', '6281294345693', 1),
('Kebun Hijau Mina', 'Tanaman & Pertanian', 'Pilihan tanaman hias, bibit sayuran, dan perlengkapan berkebun untuk rumah.', 'Villa Ilhami Blok Mina No. 15, RT 03 / RW 12', '6281294345693', 1),
('Bersih Wangi Laundry', 'Jasa Laundry', 'Layanan cuci dan setrika pakaian untuk membantu kebutuhan rumah tangga.', 'Villa Ilhami Blok Mina No. 6, RT 04 / RW 12', '6281294345693', 1),
('Kriya Anyam Panbar', 'Kerajinan', 'Produk anyaman dan suvenir buatan tangan untuk hadiah serta dekorasi rumah.', 'Villa Ilhami Blok Mina No. 10, RT 05 / RW 12', '6281294345693', 1);
