# Pengembangan

## Lingkungan

PHP 8.2+, PDO SQLite, dan mbstring untuk aplikasi. Python 3 untuk tes HTTP; Node.js untuk tes JavaScript. Tidak ada dependensi Composer/npm atau proses build. Ikuti [instalasi SQLite](sqlite.md), kemudian jalankan `php -S localhost:4173 -t public` dari root repository.

Konfigurasi dibaca dari `config.local.php`. Variabel lingkungan `MINA_CONFIG` dapat menunjuk file konfigurasi lain untuk CLI dan server pengujian. Path konfigurasi tes harus tersedia pada **kedua** proses, bukan hanya klien HTTP.

## Pembagian kode

| File | Tanggung jawab |
| --- | --- |
| `app/bootstrap.php` | Konfigurasi, koneksi PDO SQLite/MySQL, header, sesi, CSRF, escaping dan URL |
| `app/services.php` | Kanal bersama publik/admin, pemetaan ikon, normalisasi WA dan link layanan |
| `app/icons.php` | Katalog emoji yang dipakai publik/admin |
| `app/admin.php` | Layout/navigasi admin dan validasi layanan |
| `app/structure-contacts.php` | Nama/WhatsApp pengurus, simpan/hapus kontak, kartu publik |
| `app/admin-structure.php` | Pemrosesan form pengurus dengan transaksi |
| `app/admin-structure-form.php` | Form pengurus pada accordion Administrasi |
| `app/letters.php` | Daftar layanan surat dan penyusunan pesan |
| `app/letters-view.php` | Pilihan surat, RT, ringkasan dan tautan WhatsApp |
| `app/umkm.php` | Validasi dan pembacaan UMKM |
| `app/visitors.php` | Cookie, bukti kunjungan, penyimpanan dan statistik |
| `public/index.php` | Portal, kanal, struktur, direktori dan persuratan |
| `public/admin/index.php` | Dashboard, kanal, CRUD layanan, popup dan logout |
| `public/admin/structure.php` | Kompatibilitas URL/form lama ke halaman Administrasi gabungan |
| `public/admin/umkm.php` | CRUD UMKM |
| `public/login.php` | Login dan batas percobaan |
| `public/track.php` | Endpoint pencatatan kunjungan POST |

Skema SQLite berada di `database/schema.sqlite.sql`; MySQL di `database/schema.sql`. Tujuh tabel: `admins`, `services`, `visitors`, `visitor_days`, `login_attempts`, `structure_contacts`, dan `umkm`. Konfigurasi default SQLite; konfigurasi MySQL lama tetap dikenali.

## Perubahan fitur

- Kanal publik dan admin memakai `service_channels()` dan `service_channel()` yang sama. Saat ini kategori mengikuti ikon; perubahan pemetaan memengaruhi kedua sisi.
- Persuratan masuk Akses Layanan sebagai tombol bawaan, bukan baris tabel services. Jenis surat ada di `letter_types()`, pemetaan ikon di `letters-view.php`; ubah keduanya jika menambahkan jenis.
- Validasi surat/RT hanya menerima pilihan yang dikenal. RT tanpa nomor valid tidak boleh menghasilkan tautan kirim.
- Gunakan helper nomor dan pembentukan URL yang tersedia; jangan menyusun nomor atau pesan dari input mentah.
- Pertahankan CSRF, pemeriksaan sesi, prepared statements dan escaping keluaran saat mengubah form.
- Mengubah skema tidak otomatis memigrasikan database yang sudah ada. Sediakan langkah migrasi dan backup; jangan menjalankan ulang skema/seed terhadap data aktif.

## Tampilan

CSS publik: `public/styles.css`; admin: `public/admin.css`. Jangan ubah CSS vendor. Ikon berupa emoji, tampilannya mengikuti perangkat.

Popup layanan memakai elemen native `dialog` dengan `showModal()`. Accordion memakai `details`/`summary`; JavaScript membuka bagian terkait saat navigasi hash. JS admin juga mengatur kolom tujuan WhatsApp/website. Pertahankan nama input/selector saat memindah form, fokus keyboard, tombol tutup, Escape, dan pesan validasi.

## Tes tanpa mutasi database

```powershell
php tests/service_inputs.php
php tests/umkm.php
php tests/letters.php
node tests/admin_dialog.cjs
node --check public/admin.js
node --check public/app.js
node --check public/visitors.js
```

Periksa PHP yang diubah dengan `php -l PATH`. Pemeriksaan semua file:

```powershell
Get-ChildItem app,public,scripts,tests -Filter *.php -Recurse | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { throw 'Sintaks PHP tidak valid' }
}
```

## Tes database/HTTP pada salinan terpisah

Tes HTTP melakukan mutasi, termasuk mengosongkan kontak RW/RT, membuat/menghapus usaha/layanan, menambah pengunjung dan membatasi login. **Jangan jalankan pada data aktif.** `tests/structure_contacts.php` memakai rollback tetapi tetap hanya jalankan pada salinan tes.

Hentikan penulisan saat membuat salinan SQLite, lalu buat direktori privat `.local` bila belum tersedia:

```powershell
New-Item -ItemType Directory -Path .local -Force | Out-Null
Copy-Item storage/mina.sqlite .local/test.sqlite
```

Buat `.local/sqlite-test-config.php`:

```php
<?php
$c = require dirname(__DIR__) . '/config.local.php';
$c['db'] = ['driver' => 'sqlite', 'path' => __DIR__ . '/test.sqlite'];
$c['base_url'] = 'http://127.0.0.1:4174';
return $c;
```

Terminal server tes:

```powershell
$env:MINA_CONFIG = (Resolve-Path .local/sqlite-test-config.php).Path
php -S 127.0.0.1:4174 -t public
```

Terminal tes kedua, dari root repository:

```powershell
$env:MINA_CONFIG = (Resolve-Path .local/sqlite-test-config.php).Path
$env:MINA_TEST_ALLOW_MUTATION = '1'
php tests/structure_contacts.php
php tests/visitor_stats.php
$passwordTest = Read-Host 'Password akun pada salinan tes'
$passwordTest | python tests/admin_sqlite_http.py http://127.0.0.1:4174 admin
Remove-Variable passwordTest
Remove-Item Env:MINA_CONFIG, Env:MINA_TEST_ALLOW_MUTATION
```

Gunakan username yang ada pada salinan tes. `Read-Host` di contoh tes menampilkan input; jalankan pada terminal pribadi. Password dikirim melalui stdin, bukan argumen proses. Tes PHP pengurus/statistik memakai rollback; tes HTTP meninggalkan perubahan di salinan tes. Buat salinan baru sebelum mengulang, atau tunggu jendela limit login berakhir. Hentikan server tes dengan Ctrl+C setelah selesai.

`tests/http_smoke.py` adalah suite lama dengan asumsi seluruh 21 kartu tersedia pada halaman utama dan CRUD di dashboard; jangan gunakan sebagai patokan versi kanal saat ini. `admin_sqlite_http.py` masih mengimpor helper HTTP/parser dari file itu tanpa menjalankan suite lamanya.

## Pemeriksaan manual sebelum rilis

Periksa publik, login, kanal admin, accordion, popup dan persuratan pada desktop serta HP. Uji keyboard/fokus, Escape, isian error yang tetap terlihat, ikon, tombol tanpa nomor RT, label UMKM contoh, serta URL di subfolder jika digunakan. Catat hasil aktual dan keterbatasan di [verifikasi](verification.md).
