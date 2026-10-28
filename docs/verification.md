# Verifikasi versi SQLite dan portal warga

Catatan diperbarui 28 Oktober 2026. Hasil berikut berasal dari pemeriksaan selama pengembangan versi saat ini. Laporan historis PHP/MySQL ada di [arsip](history-php-mysql.md); laporan statis terdahulu di [history-static.md](history-static.md).

## Lingkungan dan pemisahan data

- Windows/Laragon; PHP CLI 8.3.30 dengan driver PDO SQLite dan MySQL.
- Aplikasi lokal di `http://localhost:4173/`, konfigurasi aktif SQLite.
- Integrasi HTTP menggunakan server PHP terpisah di `http://127.0.0.1:4174/` dan salinan SQLite `.local/test.sqlite`, bukan database aktif.
- Browser/perangkat nyata tidak termasuk tes otomatis HTTP atau mock JavaScript.

## Migrasi data

Migrasi MySQL ke SQLite berhasil menyalin dan membandingkan isi setiap baris pada tujuh tabel. Kondisi saat migrasi: 1 admin, 21 layanan, 4 pengunjung, 7 baris tanggal kunjungan, 0 bucket login, 0 kontak pengurus dan 5 UMKM. Angka ini catatan migrasi, bukan nilai statistik yang harus tetap sama setelah penggunaan.

MySQL sumber tidak diubah. Setelah itu kontak RT 1 ditambahkan atas permintaan pengguna. Data SQLite dan konfigurasi lokal tidak masuk Git; clone repository bukan salinan database pengguna.

## Pemeriksaan yang lulus

| Pemeriksaan | Cakupan |
| --- | --- |
| `php -l` | Sintaks file PHP aplikasi, skrip dan tes |
| `tests/service_inputs.php` | Nomor WA valid/tidak valid, encoding pesan, parser lama, URL eksternal dan katalog ikon |
| `tests/umkm.php` | Validasi field, normalisasi WA, nomor opsional |
| `tests/structure_contacts.php` | Baca/tambah/ubah/hapus pengurus, tautan WA, kondisi kosong, tujuh jabatan; rollback |
| `tests/visitor_stats.php` | Hitungan lintas tanggal/bulan, deduplikasi bulanan dan rollback |
| `tests/letters.php` | Delapan jenis surat, RT 1/6, pesan/encoding dan penolakan pilihan tidak valid |
| `tests/admin_dialog.cjs` | Popup awal tertutup, tambah, tutup, pemulihan fokus, buka otomatis dan tambah setelah edit melalui mock DOM |
| `tests/admin_sqlite_http.py` | Login, CSRF, akses admin, kanal, CRUD, persuratan, tracking, limit login pada database terpisah |
| `git diff --check` | Tidak ada kesalahan whitespace pada perubahan yang diperiksa |

Suite HTTP mencakup:

- Dashboard tanpa form kontak; POST simpan/hapus tanpa kanal ditolak.
- Kontak RW/RT: validasi, simpan, baca publik, ubah, hapus dan escaping nama HTML.
- UMKM: tambah, ubah, konfirmasi hapus tanpa mutasi GET, hapus dan respons record tidak ada.
- Layanan per kanal: daftar terpisah, hasil publik sesuai, CRUD dan redirect mempertahankan kanal.
- Form popup: markup dialog, kondisi buka saat Tambah/Edit/error dan pelestarian isian invalid.
- Persuratan dalam Akses Layanan; pilihan surat, RT, nomor tujuan dan isi pesan sesuai; RT tanpa kontak serta parameter array/tidak valid tidak menghasilkan tombol WhatsApp.
- Bukti kunjungan, upsert SQLite dan deduplikasi refresh; admin login tidak diberi bukti tracking.
- Lima percobaan login salah diperbolehkan, percobaan keenam dibatasi.

Respons HTTP lokal juga diperiksa untuk delapan ikon pilihan surat dan tautan persuratan yang menuju nomor RT 1 yang diminta. Pemeriksaan tautan tidak mengirim pesan.

## Pemeriksaan dokumentasi

Pemeriksaan dokumentasi juga lulus: seluruh tautan file Markdown lokal tersedia. Contoh PHP untuk inisialisasi SQLite, pembuatan akun dan backup `VACUUM INTO` dijalankan pada folder uji terpisah. Database baru dan backup lolos `PRAGMA integrity_check`, masing-masing berisi 21 layanan serta satu akun uji. Pemeriksaan ini tidak menjalankan prompt password interaktif dan tidak menyentuh data aktif.

## Yang belum diverifikasi

- Tampilan akhir desktop/HP, semua breakpoint, fokus keyboard dan perilaku dialog/accordion di browser nyata belum diperiksa ulang secara menyeluruh. Mock DOM bukan pengganti uji visual.
- Computer Use pada Firefox Windows dihentikan karena dukungan enforcement kebijakan URL belum tersedia. Screenshot dari pengguna membantu identifikasi masalah tampilan, tetapi bukan bukti pengujian seluruh alur setelah perubahan.
- Belum mengirim pesan WhatsApp, memverifikasi kepemilikan/keaktifan akun tujuan, atau menguji respons pengurus.
- Belum deploy ke hosting publik, menguji izin hosting, HTTPS/reverse proxy, atau menghasilkan QR domain produksi.
- Belum uji beban/penulisan serentak, restore produksi, maupun migrasi balik SQLite ke MySQL.
- Dukungan MySQL yang dipertahankan belum diuji ulang end-to-end pada seluruh fitur terbaru.
- Suite lama `http_smoke.py` memiliki asumsi layout/CRUD dashboard yang sudah berubah; gunakan `admin_sqlite_http.py` sebagai suite HTTP terkini.

Panduan perintah dan pembuatan database tes tersedia di [development.md](development.md). Jalankan tes mutasi hanya pada salinan; suite HTTP mengubah kontak dan meninggalkan bucket pembatasan login.
