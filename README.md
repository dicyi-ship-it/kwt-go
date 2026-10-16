# Mina Go — PHP + MySQL

Website layanan warga RW 12 Blok Mina dengan login admin, statistik pengunjung unik, dan CRUD tombol. PHP biasa dengan PDO; tidak memakai framework, npm, atau proses build.

## Fitur

- Halaman publik responsif: 5 kolom desktop, 3 tablet, 2 ponsel.
- 21 tombol referensi tersedia sebagai seed: ikon emoji, nama, nomor WA, pesan pembuka, dan urutan.
- Pengelola memilih ikon dengan klik. Untuk WhatsApp cukup isi nomor dan pesan pembuka; link dibuat otomatis.
- Admin dapat menambah, melihat, mengubah, menghapus, mengurutkan, serta menonaktifkan tombol.
- Dashboard pengunjung unik hari ini, bulan ini, dan total sejak pencatatan dimulai.
- Login dengan password hash, pembatasan percobaan, sesi berakhir otomatis, dan token CSRF.
- Tautan berbagi mengikuti `base_url` website yang dihosting.

## Mulai

Memerlukan PHP 8.2+ dengan `pdo_mysql` dan `mbstring`, serta MySQL/MariaDB. Pengujian lokal memakai PHP 8.2.12 dan MariaDB 10.4.32; MySQL server dan hosting produksi belum diuji langsung.

1. Buat database kosong dan user database.
2. Import [schema.sql](database/schema.sql), lalu [seed.sql](database/seed.sql) **sekali** melalui phpMyAdmin atau CLI MySQL.
3. Salin `config.example.php` menjadi `config.local.php`. Isi database, `base_url`, dan `app_key` acak.
4. Buat akun admin melalui [panduan instalasi](docs/deployment.md). Tidak ada akun/password bawaan.
5. Dari folder `output-rea/mina-go`, jalankan:

```sh
php -S localhost:4173 -t public
```

Buka **http://localhost:4173** untuk website atau **http://localhost:4173/login.php** untuk admin. `base_url` harus sesuai alamat yang dibuka. Pada mesin ini, executable PHP XAMPP tersedia di `/opt/lampp/bin/php`.

Versi ini harus dijalankan melalui PHP; server Python dan membuka file langsung tidak menjalankan aplikasinya.

## Struktur

```text
mina-go/
├── public/                # Document root untuk akses web
│   ├── index.php          # Tombol aktif dari database
│   ├── login.php          # Login admin
│   ├── admin/index.php    # Dashboard statistik dan CRUD
│   ├── track.php          # Pencatatan kunjungan tervalidasi
│   ├── app.js             # Dialog berbagi dan gulir
│   ├── visitors.js        # Pengiriman kunjungan halaman terlihat
│   ├── styles.css         # Tema halaman publik
│   ├── admin.css          # Tema admin
│   ├── admin.js           # Form menyesuaikan jenis tujuan
│   └── assets/            # Bootstrap CSS lokal
├── app/                   # Database, sesi, statistik, validasi
├── database/              # Schema dan 21 tombol awal
├── scripts/               # Pembuatan/reset admin dan migrasi data lama
├── tests/                 # Pengujian HTTP dan periode statistik
├── docs/                  # Instalasi dan dokumentasi
├── config.example.php     # Contoh tanpa kredensial nyata
└── config.local.php       # Konfigurasi pribadi, diabaikan Git
```

## Dokumentasi

- [Instalasi database dan hosting](docs/deployment.md).
- [Pengembangan dan pengelolaan tombol](docs/development.md).
- [Definisi dan batas statistik](docs/analytics.md).
- [Hasil pengujian dan bukti REA](docs/verification.md).

Hitungan memakai cookie bertanda tangan dan pemeriksaan kunjungan browser. Ini bukan verifikasi identitas manusia: cookie dihapus, perangkat berbeda, pemblokir JavaScript, dan bot canggih memengaruhi angka. Layanan WhatsApp dan aplikasi eksternal tetap memakai tujuan aslinya.
