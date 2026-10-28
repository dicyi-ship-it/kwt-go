# Instalasi dan migrasi MySQL (opsional)

Konfigurasi default dan instalasi lokal sekarang memakai SQLite. Untuk hosting tanpa server MySQL, gunakan [panduan SQLite](sqlite.md). Dokumen ini hanya untuk instalasi MySQL/MariaDB yang tetap dipertahankan atau disiapkan sebagai sumber migrasi ke SQLite.

## Persyaratan dan konfigurasi

Gunakan PHP 8.2+, `pdo_mysql`, mbstring, dan MySQL/MariaDB dengan InnoDB/utf8mb4. Arahkan document root ke folder `public/`, pertahankan struktur proyek, serta letakkan konfigurasi di luar akses HTTP. Petunjuk HTTPS/backup dan pemeriksaan pasca-upload ada di [panduan hosting](sqlite.md#hosting).

Salin `config.example.php` hanya untuk instalasi baru. Ganti blok `db` pada konfigurasi lokal:

```php
'db' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'mina_go',
    'user' => 'mina_go',
    'password' => 'ISI_PASSWORD_DATABASE',
    'socket' => '',
],
```

Isi `app_key` dengan 64 hex acak dan `base_url` sesuai domain. `socket` opsional menggantikan host/port. Host dan nama database hanya menerima huruf/angka/underscore/titik/tanda minus.

## Instalasi kosong

Buat database dan user khusus melalui panel hosting. User runtime memerlukan SELECT/INSERT/UPDATE/DELETE. Impor skema memerlukan izin DDL dan sebaiknya dilakukan lewat akun pengelola.

```sh
mysql -u mina_go -p mina_go < database/schema.sql
mysql -u mina_go -p mina_go < database/seed.sql
```

Impor hanya pada database kosong. Skema terkini sudah mencakup tujuh tabel, termasuk kontak RW/RT dan UMKM. Lima usaha demonstrasi dari `database/umkm-seed.sql` opsional, tidak wajib diimpor. Jangan mengimpor seed berulang atau menghapus database aktif untuk mengulang instalasi.

Buat akun dengan `scripts/create-admin.php`, password melalui stdin. Contoh PowerShell dan batas password ada di [akun admin](sqlite.md#akun-admin); mekanisme pembuatan akun sama untuk kedua database. Tidak ada akun bawaan.

## Memperbarui database lama

Buat backup database serta konfigurasi dan hentikan perubahan data sebelum migrasi. Gunakan akun migrasi dengan izin DDL, melalui file konfigurasi privat bila diperlukan. Di PowerShell:

```powershell
$env:MINA_CONFIG = 'C:/lokasi-privat/config-migrasi.php'
php scripts/migrate-service-inputs.php
php scripts/migrate-structure-contacts.php
php scripts/migrate-umkm.php
Remove-Item Env:MINA_CONFIG
```

- `migrate-service-inputs.php`: khusus MySQL versi lama yang menyimpan URL saja. Membuat backup layanan di `.local/backups/`, memisahkan nomor/pesan, dan memetakan ikon lama ke katalog emoji. Membutuhkan ALTER. DDL MySQL bukan transaksi penuh; jika gagal, simpan backup dan periksa kondisi sebelum mengulang.
- `migrate-structure-contacts.php`: membuat tabel kontak bila belum tersedia dan menambahkan kolom nama ketua pada tabel lama. Kontak yang ada dipertahankan. Pada SQLite terkini, skrip hanya memeriksa struktur tabel.
- `migrate-umkm.php`: membuat tabel beserta lima usaha contoh jika tabel belum ada. Tidak mengembalikan seed jika tabel sudah tersedia.

Dua skrip migrasi layanan/UMKM tersebut ditujukan untuk MySQL, bukan inisialisasi SQLite. Setelah sumber lengkap, gunakan [migrasi SQLite](sqlite.md#migrasi-dari-mysql).

## Penggunaan dan pembaruan

Menu pengelola terbaru dijelaskan di [panduan pengguna](user-guide.md): kontak layanan di dalam kanal, pengurus RW/RT dalam accordion Administrasi, dan UMKM melalui Ekonomi. Form kontak tidak lagi tersedia pada dashboard.

Backup database sebelum pembaruan. Jangan mengandalkan menyalin folder data MySQL aktif; gunakan fasilitas backup hosting atau dump konsisten. Jangan memindahkan akun pengunjung tes ke produksi. Tidak ada migrasi skema otomatis saat aplikasi dibuka.

Dukungan MySQL terbaru belum diuji ulang menyeluruh; hasil MariaDB versi terdahulu dipertahankan sebagai [arsip historis](history-php-mysql.md), bukan bukti uji hosting saat ini.
