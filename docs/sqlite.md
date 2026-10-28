# Hosting dengan SQLite

Aplikasi lokal sudah menggunakan SQLite di `storage/mina.sqlite`. Akun admin, layanan, UMKM, dan statistik lama disalin dari MySQL dan diverifikasi per baris. MySQL tidak dihapus. Konfigurasi MySQL sebelum migrasi tersimpan di `.local/config.mysql.backup.php` untuk referensi; sesudah ada perubahan di SQLite, data MySQL tidak lagi tersinkron.

## Memindahkan ke hosting

1. Gunakan hosting PHP 8.2+ dengan ekstensi `pdo_sqlite` dan `mbstring`. Tidak memerlukan layanan MySQL, Composer, atau Node.js. Hosting statis tidak dapat menjalankan PHP.
2. Salin `app/`, `public/`, `storage/mina.sqlite`, dan `config.local.php`. File database dan konfigurasi sengaja diabaikan Git. Jangan unggah `.local/` karena berisi cadangan konfigurasi dan catatan lokal.
3. Arahkan document root ke `public/`. Simpan `storage/` dan konfigurasi di luar document root agar file database tidak dapat diunduh pengunjung. Jika hosting hanya menyediakan `public_html`, tempatkan isi `public/` di sana dan `app/`, `storage/`, serta konfigurasi sejajar dengan `public_html`.
4. Pastikan pengguna PHP dapat menulis file database **dan folder** `storage/` untuk file journal SQLite. Jangan memberikan izin tulis kepada semua pengguna server.
5. Atur konfigurasi:

```php
'db' => [
    'driver' => 'sqlite',
    'path' => __DIR__ . '/storage/mina.sqlite',
],
'base_url' => 'https://domain-anda.id',
```

Pertahankan `app_key` saat memindahkan aplikasi yang sama. Gunakan HTTPS dan ganti password admin lokal sebelum publikasi melalui `scripts/create-admin.php admin --reset` (password 12–72 byte dikirim lewat stdin, lihat panduan akun di [deployment.md](deployment.md)). Buka `/login.php`, lalu menu **Pengurus RW & RT** dan **Daftar UMKM**. Mengosongkan nama dan WhatsApp suatu jabatan lalu menyimpan akan menghapus data pengurus jabatan itu; jabatan RW 12 dan RT 1–6 tetap tersedia.

## Migrasi MySQL lain

Dengan konfigurasi masih menunjuk MySQL dan folder `storage/` sudah dibuat:

```sh
php scripts/migrate-to-sqlite.php storage/mina.sqlite
```

Tujuan harus file baru; skrip menolak menimpa file yang ada. Skrip menyalin tujuh tabel dalam transaksi, memeriksa isi setiap baris serta foreign key, dan tidak mengubah sumber MySQL. Jalankan saat tidak ada penulisan data; setelah sukses, ubah blok `db` ke SQLite. Jika gagal, jangan gunakan file tujuan yang belum lengkap; pilih nama file baru setelah memperbaiki penyebabnya.

Untuk instalasi kosong, buat file SQLite baru memakai `database/schema.sqlite.sql`, impor `database/seed.sql` tanpa baris `SET NAMES utf8mb4;`, lalu buat admin melalui `scripts/create-admin.php`. Jangan mengimpor skema MySQL ke SQLite.

## Cadangan

Sebelum deploy atau memperbarui aplikasi, buat cadangan konsisten melalui SQLite backup API atau `VACUUM INTO` ke file baru. Menyalin file database biasa harus dilakukan ketika tidak ada proses menulis. Jangan menimpa database hosting dengan salinan lokal lama saat memperbarui kode.

SQLite sesuai untuk situs warga dengan lalu lintas rendah sampai menengah pada satu server. Penulisan dilakukan bergantian; jika kelak banyak penulisan serentak atau beberapa server aplikasi, evaluasi MySQL kembali. Referensi: [PDO SQLite](https://www.php.net/manual/en/ref.pdo-sqlite.php) dan [penggunaan SQLite](https://www.sqlite.org/whentouse.html).
