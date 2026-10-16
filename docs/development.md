# Pengembangan dan pengelolaan Mina Go

## Menjalankan

Ikuti [instalasi](deployment.md), lalu dari folder proyek:

```sh
php -S localhost:4173 -t public
```

Halaman utama `/`, login `/login.php`, dashboard `/admin/`. Aset berada di `public/`. Jangan menjalankan server dengan document root seluruh folder proyek.

## Mengelola tombol

Masuk sebagai admin, lalu gunakan tabel dan form dashboard:

- **Tambah:** isi nama, klik gambar ikon, pilih Chat WhatsApp, lalu isi nomor dan pesan pembuka (pretext). Tidak perlu membuat URL.
- **Edit:** ubah nilai melalui tautan Edit.
- **Nonaktif:** hilangkan centang “Tampilkan di website”. Data tetap tersimpan.
- **Hapus:** pilih Hapus, lalu konfirmasi pada form terpisah. Membuka konfirmasi belum menghapus data.
- **Urutkan:** angka terkecil tampil lebih dahulu; jika sama, berdasarkan ID.

Perubahan langsung dibaca halaman publik dari database. Jumlah layanan dihitung dari tombol aktif. Tidak ada daftar tombol duplikat dalam HTML atau JavaScript.

Nomor Indonesia dapat diketik sebagai `081234567890`, `6281234567890`, atau `+62 (812) 3456-7890`. Aplikasi menyimpan bentuk `6281234567890` dan meng-encode pesan saat membentuk link. Pesan boleh beberapa baris, maksimal 2000 karakter, dan tidak otomatis terkirim. Contoh: `Assalamualaikum, saya ingin meminta bantuan pelayanan Info Warga.`

Untuk tujuan selain WhatsApp, pilih **Website / aplikasi lain**. Kolom alamat website hanya diperlukan untuk pilihan tersebut, misalnya Tangerang Live. Kolom WhatsApp disembunyikan dan tidak disimpan untuk tombol website; URL tidak disimpan untuk tombol WhatsApp. Ikon memakai emoji seperti referensi dan dipilih dengan klik, tanpa mengetik lewat keyboard emoji. Bentuknya mengikuti font emoji pada perangkat.

`database/seed.sql` berisi 21 tombol awal: 20 kontak WhatsApp dan satu Tangerang Live. Impor hanya saat instalasi awal. Nomor/pesan diekstrak dari sumber; awalan `0` diubah menjadi `62`, dan spasi pemisah dibersihkan. Nomor singkat/telepon layanan yang berasal dari referensi tetap perlu dikonfirmasi pengelola: format valid tidak membuktikan akun tersebut terdaftar WhatsApp. Tidak ada pesan yang dikirim saat pengujian.

## Pembagian file

| File | Tanggung jawab |
| --- | --- |
| `app/bootstrap.php` | Konfigurasi, PDO, escaping HTML, sesi, CSRF, URL aplikasi |
| `app/admin.php` | Pemeriksaan login, layout admin, validasi tombol |
| `app/services.php` | Pemilik normalisasi nomor, pembentukan link WA, dan parser migrasi URL lama |
| `app/icons.php` | Katalog ikon emoji dan pemetaan ikon lama |
| `app/visitors.php` | Identitas cookie, bukti kunjungan, pencatatan/statistik |
| `public/index.php` | Render tombol aktif dan tautan berbagi |
| `public/login.php` | Login dan pembatasan percobaan |
| `public/admin/index.php` | Dashboard dan aksi CRUD/logout |
| `public/track.php` | Endpoint POST kunjungan |

Input query memakai [prepared statements PDO](https://www.php.net/manual/en/pdo.prepared-statements.php). Password dibuat dengan [`password_hash`](https://www.php.net/manual/en/function.password-hash.php) dan diperiksa dengan `password_verify`; database tidak menyimpan password polos.

## Tampilan

Ubah `public/styles.css` untuk website dan `public/admin.css` untuk admin. Hindari mengedit CSS vendor Bootstrap. Grid publik: hingga 540px dua kolom, 541–900px tiga kolom, di atas 900px lima kolom. Konten maksimal 1080px. Pertahankan fokus keyboard dan `prefers-reduced-motion`.

`public/app.js` bergantung pada selector dialog di `public/index.php`; ubah keduanya jika struktur dialog berubah. URL berbagi memakai `base_url`. QR statis referensi dihapus agar tidak membagikan alamat situs lama saat aplikasi dihosting.

## Pemeriksaan

Periksa sintaks file PHP yang diubah dengan `php -l PATH`. JavaScript:

```sh
node --check public/app.js
node --check public/visitors.js
node --check public/admin.js
php tests/service_inputs.php
```

Tes integrasi **mengubah data**. Gunakan server/database pengujian dengan schema dan 21 seed; jangan jalankan terhadap produksi. Test HTTP menambah dua cookie pengunjung dan sementara membuat/menghapus satu tombol. Test limit login meninggalkan bucket yang dibatasi sampai jendela 15 menit berakhir.

```sh
read -r -s -p 'Password admin pengujian: ' MINA_TEST_PASSWORD
printf '\n'
printf '%s\n' "$MINA_TEST_PASSWORD" | MINA_TEST_ALLOW_MUTATION=1 python3 tests/http_smoke.py http://localhost:4173 testadmin
unset MINA_TEST_PASSWORD
MINA_TEST_ALLOW_MUTATION=1 php tests/visitor_stats.php
```

Tes statistik memakai transaksi yang dibatalkan setelah pemeriksaan lintas hari/bulan. `MINA_CONFIG=/path/config-test.php` dapat dipakai saat memulai server dan CLI untuk memilih konfigurasi pengujian di luar proyek.

Periksa browser pada 320, 390, 768, 1365px: kartu, dialog berbagi, login, tabel, form admin, Escape, Tab/Shift+Tab, dan gulir. Catat hanya pemeriksaan yang benar-benar dijalankan di [verification.md](verification.md).
