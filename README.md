# Mina Go - Portal Warga Villa Ilhami Blok Mina

Portal layanan warga RW 12, Kelurahan Panunggangan Barat, Kota Tangerang. Dibangun dengan PHP dan PDO tanpa framework, Composer, npm, atau proses build. Penyimpanan utama menggunakan SQLite; MySQL masih didukung untuk instalasi lama.

## Fitur

- Tiga kanal publik: Layanan Administrasi, Layanan Ekonomi, dan Layanan Permasyarakatan.
- Persuratan: pilih layanan surat > pilih RT 1-6 > periksa pesan > buka WhatsApp ketua RT.
- Nama dan WhatsApp ketua RW/RT, serta direktori UMKM warga.
- Dashboard statistik dan pengelolaan kontak per kanal; Tambah/Edit tombol menggunakan popup.
- Login admin, hash password, validasi input, CSRF, batas percobaan login, dan sesi kedaluwarsa.
- Statistik pengunjung unik harian, bulanan, dan total berdasarkan cookie bertanda tangan.

## Menjalankan lokal

Memerlukan PHP 8.2+ dengan `pdo_sqlite` dan `mbstring`. MySQL tidak perlu dinyalakan untuk konfigurasi SQLite. Python dan Node.js hanya diperlukan untuk pengujian tertentu.

Jika database dan konfigurasi lokal sudah tersedia, jalankan dari terminal PowerShell:

```powershell
cd E:\laragon\www\kwt-go
php -S localhost:4173 -t public
```

Biarkan terminal terbuka. Jika Laragon sudah melayani port 4173, langsung buka alamatnya; jangan menjalankan server kedua pada port yang sama.

| Halaman | Alamat lokal |
| --- | --- |
| Portal warga | http://localhost:4173/ |
| Login admin | http://localhost:4173/login.php |
| Dashboard admin | http://localhost:4173/admin/ |
| Persuratan | http://localhost:4173/?layanan=administrasi&persuratan=1 |

**Setelah clone baru:** `config.local.php` dan `storage/mina.sqlite` tidak tersedia karena diabaikan Git. Ikuti [instalasi SQLite](docs/sqlite.md) untuk membuat instalasi baru atau memindahkan data yang sudah ada. Tidak ada akun/password default pada instalasi baru.

## Panduan

| Kebutuhan | Dokumen |
| --- | --- |
| Penggunaan warga dan pengelola | [Panduan pengguna](docs/user-guide.md) |
| Instalasi baru, menjalankan, hosting, backup, masalah umum | [SQLite](docs/sqlite.md) |
| Instalasi MySQL lama | [Deployment MySQL](docs/deployment.md) |
| Struktur kode dan perintah tes | [Pengembangan](docs/development.md) |
| Arti statistik dan data yang disimpan | [Analitik](docs/analytics.md) |
| Hasil pengujian dan batas verifikasi | [Verifikasi](docs/verification.md) |

## Struktur proyek

```text
app/                 Konfigurasi, koneksi, validasi, fungsi dan bagian tampilan
public/              Document root HTTP: portal, login, admin, CSS dan JavaScript
database/            Skema SQLite/MySQL dan data awal
storage/             File SQLite lokal; hanya .gitkeep masuk Git
scripts/             Pembuatan/reset admin dan migrasi
tests/               Tes PHP, HTTP, dan logika popup
docs/                Panduan proyek
config.example.php   Contoh konfigurasi tanpa rahasia
config.local.php     Konfigurasi pribadi, tidak masuk Git
.local/              Cadangan dan catatan lokal, tidak masuk Git
```

Document root wajib menunjuk `public/`, bukan seluruh folder proyek. Database dan konfigurasi harus berada di luar akses publik. Saat memperbarui kode, pertahankan database hosting dan konfigurasi lokal; jangan menimpanya dengan salinan lama.

Persuratan menyiapkan pesan, tidak mengirimkannya otomatis dan tidak menyimpan permohonan. Statistik mengukur identitas browser, bukan jumlah orang yang terverifikasi. Hosting produksi, pengiriman WhatsApp, dan seluruh ukuran layar belum diverifikasi langsung; lihat catatan pengujian.
