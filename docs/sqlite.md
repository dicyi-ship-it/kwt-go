# Instalasi, hosting, dan perawatan SQLite

SQLite menyimpan database dalam file tanpa server MySQL. PHP tetap diperlukan; hosting statis tidak dapat menjalankan aplikasi. Gunakan PHP 8.2+ dengan `pdo_sqlite` dan `mbstring`.

## Menjalankan instalasi lokal

Database workspace ada di `storage/mina.sqlite`. Data sudah disalin dari MySQL; sumber MySQL tidak dihapus dan tidak lagi tersinkron. Konfigurasi sumber sebelum migrasi ada di `.local/config.mysql.backup.php` jika cadangan lokal masih tersedia.

```powershell
cd E:\laragon\www\kwt-go
php -S localhost:4173 -t public
```

Biarkan terminal terbuka. Jika Laragon sudah melayani port 4173, langsung buka http://localhost:4173/ tanpa memulai server kedua. MySQL tidak perlu dinyalakan. Ctrl+C menghentikan server CLI.

## Instalasi baru setelah clone

Konfigurasi, database dan password tidak ikut Git. Langkah inisialisasi berikut hanya untuk database baru; jangan menimpa konfigurasi/database aktif.

### 1. PHP dan konfigurasi

```powershell
php -v
php -m
Copy-Item config.example.php config.local.php
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Pastikan modul `pdo_sqlite` dan `mbstring` tersedia. Salin hasil 64 karakter hex ke `app_key` pada `config.local.php`.

| Kunci | Isi |
| --- | --- |
| `db.driver` | `sqlite` |
| `db.path` | `__DIR__ . '/storage/mina.sqlite'`, di luar document root |
| `app_key` | 64 karakter hex acak; jangan dipublikasikan |
| `base_url` | `http://localhost:4173`, tanpa slash terakhir |
| `timezone` | `Asia/Jakarta`, default batas hari/bulan analitik |

### 2. Database dan layanan awal

Blok PowerShell ini membuat file baru secara eksklusif. Jika gagal, perbaiki penyebabnya dan pilih file baru; jangan menghapus database aktif untuk mencoba ulang.

```powershell
New-Item -ItemType Directory -Path storage -Force | Out-Null
@'
<?php
$c = require 'config.local.php';
$path = $c['db']['path'];
$file = fopen($path, 'x');
if ($file === false) { exit("File sudah ada atau tidak dapat dibuat.\n"); }
fclose($file);
$pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec(file_get_contents('database/schema.sqlite.sql'));
$pdo->beginTransaction();
try {
    $seed = str_replace('SET NAMES utf8mb4;', '', file_get_contents('database/seed.sql'));
    $pdo->exec($seed);
    $pdo->commit();
    echo "Database SQLite dan 21 layanan awal siap.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
'@ | php
```

Jangan mengimpor skema MySQL `database/schema.sql` ke SQLite. Lima UMKM demonstrasi pada `database/umkm-seed.sql` opsional; direktori mulai kosong bila tidak diimpor. Kontak pengurus tidak diisi otomatis. Nomor layanan seed adalah referensi, bukan bukti nomor masih aktif atau milik pengurus saat ini; verifikasi sebelum publikasi.

### Akun admin

Instalasi baru tidak memiliki akun/password default. Password harus 12-72 byte. Contoh PowerShell meminta input tersembunyi dan mengirimkannya melalui stdin:

```powershell
$passwordSecure = Read-Host 'Password admin baru' -AsSecureString
$passwordPlain = [System.Net.NetworkCredential]::new('', $passwordSecure).Password
$passwordPlain | php scripts/create-admin.php admin
Remove-Variable passwordPlain, passwordSecure
```

Untuk mengganti password akun yang ada, gunakan `php scripts/create-admin.php admin --reset` pada baris pipe. Username 3-100 karakter huruf/angka/`_`/`.`/`-`. Reset password tidak langsung mencabut sesi yang masih aktif. Jangan menyimpan password aktual di dokumentasi atau Git.

### 3. Jalankan dan lengkapi kontak

Jalankan `php -S localhost:4173 -t public`, buka `/login.php`, lalu pilih kanal melalui Kelola layanan. Isi WhatsApp ketua RT agar persuratan dapat digunakan. Lihat [panduan pengguna](user-guide.md).

## Hosting

1. Pastikan hosting mendukung PHP, PDO SQLite dan mbstring. PHP CLI/web dapat memakai `php.ini` berbeda; driver terminal belum membuktikan driver web aktif.
2. Salin proyek, `storage/mina.sqlite` dan `config.local.php`. Jangan unggah `.git/`, `.local/` atau data tes.
3. **Pertahankan struktur proyek dan arahkan document root domain ke `public/`.** Konfigurasi, `app/` dan `storage/` berada di luar akses HTTP. Jika panel hanya menyediakan `public_html`, minta penyedia mengatur document root/symlink yang mempertahankan struktur ini; penamaan ulang folder publik belum diuji.
4. Atur `base_url` ke URL HTTPS domain. Pertahankan `app_key` saat memindahkan instalasi yang sama; mengganti key membatalkan cookie lama.
5. Berikan izin menulis file database **dan folder induknya** kepada user PHP untuk journal, tanpa memberi akses tulis kepada semua pengguna server. Gunakan penyimpanan lokal server.
6. Gunakan password admin yang kuat. Matikan `display_errors` dan directory listing. Jangan cache penuh respons PHP karena cookie/bukti kunjungan per browser; aset boleh dicache.
7. Verifikasi portal, login, CRUD, kontak RT, persuratan, dan statistik melalui browser non-admin. Jangan jalankan tes mutasi pada produksi.

`php -S` untuk pengembangan lokal. Produksi memakai layanan web/PHP dari hosting. Belum ada deployment hosting produksi yang diverifikasi.

## Migrasi dari MySQL

Dengan konfigurasi masih menunjuk MySQL dan folder tujuan sudah ada:

```powershell
php scripts/migrate-to-sqlite.php storage/mina.sqlite
```

Tujuan harus file baru. Skrip menyalin tujuh tabel dalam transaksi, memeriksa isi setiap baris serta foreign key, dan tidak mengubah MySQL. Hentikan penulisan selama migrasi. Sumber harus memiliki struktur layanan terkini serta tabel kontak/UMKM; gunakan [migrasi MySQL](deployment.md) jika belum.

Setelah sukses, backup konfigurasi sumber lalu ubah blok `db`:

```php
'db' => [
    'driver' => 'sqlite',
    'path' => __DIR__ . '/storage/mina.sqlite',
],
```

Jika gagal, jangan gunakan file tujuan yang belum lengkap; pilih nama file baru setelah memperbaiki masalah. Setelah aplikasi menulis ke SQLite, menunjuk MySQL lama kembali tidak membawa perubahan terbaru; diperlukan migrasi balik terencana.

## Backup, pembaruan, dan restore

Buat cadangan konsisten sebelum deploy/migrasi. Contoh dari root repository membuat file backup baru dalam `.local/` yang privat dan diabaikan Git:

```powershell
New-Item -ItemType Directory -Path .local -Force | Out-Null
@'
<?php
require 'app/bootstrap.php';
$destination = getcwd() . '/.local/mina-backup-' . gmdate('Ymd-His') . '.sqlite';
$pdo = db();
$pdo->exec('VACUUM INTO ' . $pdo->quote($destination));
echo 'Cadangan dibuat: ' . $destination . PHP_EOL;
'@ | php
```

Pastikan tujuan dapat ditulis dan di luar document root. Simpan konfigurasi secara terpisah. Alternatifnya gunakan SQLite backup API atau salin file ketika seluruh proses penulis dihentikan; jangan menyalin file database aktif sembarangan.

Saat memperbarui kode, pertahankan database produksi dan konfigurasi. Jangan menimpanya dengan database lokal lama. Untuk restore: hentikan penulisan, simpan salinan database saat ini, pulihkan backup/konfigurasi yang sesuai, lalu verifikasi sebelum membuka akses. Tidak ada migrasi schema otomatis saat halaman dibuka.

## Masalah umum

| Gejala | Yang diperiksa |
| --- | --- |
| `php` tidak dikenali | Gunakan terminal Laragon atau tambahkan direktori PHP ke PATH |
| Port 4173 digunakan | Buka URL dan gunakan server yang sudah ada; jika mengganti port, ubah `base_url` |
| `could not find driver` | Aktifkan `pdo_sqlite` pada PHP web lalu restart layanan |
| Layanan belum tersedia / HTTP 503 | Log PHP, app_key 64 hex, path SQLite, struktur folder dan izin |
| Unable to open database / readonly | File tersedia dan file/folder induk dapat ditulis user PHP |
| Database is locked | Proses penulis/transaksi panjang; aplikasi menunggu hingga 5 detik sebelum gagal |
| Login kembali ke halaman masuk | Samakan hostname/port dengan `base_url`, periksa cookie/sesi; jangan berganti localhost/127.0.0.1 |
| Terlalu banyak percobaan | Tunggu jendela 15 menit; jangan jalankan suite login pada data aktif |
| RT tidak bisa dipilih | Isi WhatsApp RT di Administrasi > Pengurus RW dan RT |
| CSS/JS belum berubah | Refresh dan periksa aset serta versi URL yang dilayani server |
| Statistik tidak bertambah | Browser non-admin, cookie/JS aktif, tunggu satu detik; lihat [analitik](analytics.md) |

SQLite menjalankan penulisan bergantian. Untuk beberapa server atau banyak penulisan serentak, evaluasi MySQL. Referensi: [PDO SQLite](https://www.php.net/manual/en/ref.pdo-sqlite.php) dan [penggunaan SQLite](https://www.sqlite.org/whentouse.html).
