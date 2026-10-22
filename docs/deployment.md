# Instalasi MySQL dan hosting

## 1. Database

Gunakan hosting PHP dengan PDO MySQL dan `mbstring`, database MySQL/MariaDB dengan InnoDB dan `utf8mb4`. Tidak memerlukan Composer atau Node.js.

Buat database kosong (misalnya `mina_go`) dan user khusus lewat panel hosting. User aplikasi memerlukan `SELECT`, `INSERT`, `UPDATE`, `DELETE`. Impor schema juga memerlukan izin membuat tabel/index/foreign key; lakukan melalui phpMyAdmin dengan akun pengelola.

Import berurutan:

1. `database/schema.sql` — tabel admin, tombol, pengunjung, tanggal kunjungan, limit login.
2. `database/seed.sql` — 21 tombol awal, berurutan dan aktif.

Atau dari folder proyek lewat CLI:

```sh
mysql -u mina_go -p mina_go < database/schema.sql
mysql -u mina_go -p mina_go < database/seed.sql
```

Ganti user/database sesuai hosting. Seed hanya untuk instalasi awal; impor ulang gagal pada ID yang sama. Jangan menghapus database aktif untuk mengulang instalasi.

## 2. Konfigurasi

Salin `config.example.php` menjadi `config.local.php`, lalu isi:

| Kunci | Nilai |
| --- | --- |
| `db.host`, `db.port` | Host/port penyedia database |
| `db.name`, `db.user`, `db.password` | Database dan kredensial aplikasi |
| `db.socket` | Kosong untuk TCP; isi hanya untuk socket MySQL lokal |
| `app_key` | 64 karakter hex acak untuk tanda tangan cookie/bukti |
| `base_url` | URL tepat, misalnya `https://mina.example.id` atau `https://example.id/mina` |
| `timezone` | `Asia/Jakarta` untuk batas hari/bulan WIB |

Buat `app_key`:

```sh
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Simpan hasilnya dalam konfigurasi pribadi. `config.local.php` diabaikan Git. Mengganti key membatalkan cookie lama sehingga pengunjung kembali dapat memperoleh identitas baru.

`base_url` menentukan navigasi, berbagi, endpoint tracking, dan path cookie. HTTPS mengaktifkan cookie Secure; HTTP hanya untuk pengembangan lokal. Samakan hostname ketika membuka halaman: `localhost` dan `127.0.0.1` adalah host berbeda. Di belakang reverse proxy, gunakan URL HTTPS publik; aplikasi tidak mempercayai header proxy untuk hostname/IP.

## 3. Akun admin

Tidak ada akun/password default. Dari folder proyek pada Bash, setelah database terhubung:

```sh
read -r -s -p 'Password admin baru (12–72 byte): ' MINA_ADMIN_PASSWORD
printf '\n'
printf '%s\n' "$MINA_ADMIN_PASSWORD" | php scripts/create-admin.php admin
unset MINA_ADMIN_PASSWORD
```

Username 3–100 karakter: huruf, angka, `_`, `.`, atau `-`. Password dikirim melalui stdin, bukan argumen command. Password ASCII panjang memudahkan batas byte.

Jika hosting tidak memiliki terminal, jalankan generator lokal dengan PHP. Mode `--sql` tidak membutuhkan konfigurasi/koneksi database:

```sh
read -r -s -p 'Password admin baru: ' MINA_ADMIN_PASSWORD
printf '\n'
printf '%s\n' "$MINA_ADMIN_PASSWORD" | php scripts/create-admin.php admin --sql > /tmp/mina-admin.sql
unset MINA_ADMIN_PASSWORD
```

Import `/tmp/mina-admin.sql` melalui phpMyAdmin setelah schema. File berisi hash akun; simpan di luar direktori web, jangan commit, dan hapus setelah impor.

Untuk mengganti password melalui CLI, gunakan perintah yang sama dengan opsi `--reset` menggantikan `--sql`. Sesi lama berakhir setelah 30 menit tidak aktif atau maksimal 8 jam sejak login; reset password tidak langsung mencabut sesi aktif.

## 4. Document root

Arahkan document root domain ke **`mina-go/public/`**, bukan seluruh proyek. Dengan begitu `app/`, `database/`, `scripts/`, `tests/`, dan konfigurasi berada di luar akses HTTP. Apache/Nginx harus meneruskan `.php` ke PHP; hosting statis tidak dapat menjalankan proyek ini.

Jika panel hanya menyediakan `public_html`, gunakan struktur setara:

```text
/home/USER/
├── app/                 # Isi app proyek
├── config.local.php
└── public_html/         # Isi public proyek
    ├── index.php
    ├── login.php
    ├── admin/
    └── ...
```

Struktur ini mempertahankan satu induk direktori yang dicari entry point PHP. Pastikan tidak berbenturan dengan aplikasi lain. Schema/dokumen/skrip tetap di luar `public_html`. Bila memakai CLI di hosting ini, letakkan `scripts/` sejajar dengan `app/`, atau buat akun lewat SQL lokal.

Aktifkan HTTPS, nonaktifkan directory listing, dan matikan `display_errors` di produksi. Aplikasi menampilkan pesan umum saat database/config gagal; detail masuk log PHP hosting. Jangan aktifkan full-page/CDN cache untuk PHP karena cookie dan bukti kunjungan per-browser; aset CSS/JS boleh dicache.

## 5. Verifikasi setelah upload

Buka halaman publik dan `/login.php`. Login, edit tombol, lalu periksa hasilnya di website. Nonaktifkan/aktifkan kembali untuk memeriksa status tampil. Gunakan browser terpisah yang tidak login admin, izinkan cookie/JavaScript, dan tunggu minimal satu detik pada halaman publik. Hitungan seharusnya bertambah satu; refresh cookie yang sama tidak menambah pengunjung unik pada periode yang sama.

Jangan impor data pengunjung tes ke produksi. Database kosong dimulai dari nol. Lihat [definisi statistik](analytics.md).

Backup database/config sebelum pembaruan atau perubahan schema. Belum ada migrasi otomatis untuk database produksi.

## Memperbarui versi lama yang hanya memiliki URL tombol

Instalasi baru cukup memakai schema/seed terbaru. Untuk database versi URL lama, jalankan dari folder proyek:

```sh
php scripts/migrate-service-inputs.php
```

Skrip ini memerlukan akun database dengan izin `ALTER` selain izin baca/tulis. Gunakan konfigurasi migrasi pribadi melalui `MINA_CONFIG=/path/config-migrasi.php` bila akun runtime hanya memiliki izin data; jangan menambah izin schema permanen pada akun runtime.

Migrasi membuat backup baris layanan dalam `.local/backups/` sebelum mengubah schema/data. Nomor dan pretext diekstrak dari URL lama, ikon dipetakan ke SVG, dan Tangerang Live tetap menjadi tujuan website. Data asli tersimpan pada backup. Hentikan perubahan tombol selama migrasi. Jalankan ulang aman: baris yang sudah dipindahkan tidak ditimpa. Migrasi DDL MySQL bukan transaksi penuh; bila terhenti, simpan backup, periksa pesan kesalahan dan jalankan ulang setelah masalah diperbaiki. Simpan backup di lokasi pribadi, tidak di document root. Pada versi lokal di mesin ini, migrasi sudah diterapkan.

## Kontak WhatsApp struktur RW dan RT

Untuk instalasi lama, impor `database/structure-contacts.sql` melalui akun pengelola database yang memiliki izin CREATE. Alternatif CLI: `php scripts/migrate-structure-contacts.php` dengan konfigurasi database berizin CREATE. Migrasi hanya menambahkan tabel kontak; tidak mengubah data layanan.

Login sebagai admin, pilih **Kelola WhatsApp RW dan RT**, lalu isi satu nomor untuk RW 12 dan masing-masing RT 1-6. Nomor boleh dikosongkan; nomor yang tersimpan akan dipublikasikan sebagai tautan WhatsApp pada kartu struktur.

## Direktori UMKM

Instalasi lama: impor `database/umkm.sql`, lalu `database/umkm-seed.sql` satu kali melalui akun pengelola database. Alternatif: jalankan `php scripts/migrate-umkm.php` dengan konfigurasi berizin CREATE. Skrip melewati tabel yang sudah tersedia sehingga tidak mengembalikan data yang telah dihapus.

Buka **Layanan Ekonomi > UMKM** untuk melihat direktori. Login admin lalu pilih **Kelola Data UMKM** untuk menambah, mengedit, dan menghapus usaha. Lima usaha awal adalah data contoh; alamat dan nomor kontak nyata belum diisi. Hapus penanda data contoh setelah menggantinya dengan data usaha yang benar.
