# Panduan warga dan pengelola

## Warga: membuka layanan

1. Buka website melalui tautan atau scan QR yang mengarah ke URL website.
2. Pilih Layanan Administrasi, Layanan Ekonomi, atau Layanan Permasyarakatan.
3. Pilih tombol pada bagian **Akses Layanan**. Tujuan dapat berupa WhatsApp atau website lain.

QR untuk cetak harus dibuat menggunakan URL publik setelah domain hosting tersedia. Repository belum menyediakan generator QR. QR menuju `localhost` hanya berfungsi pada komputer yang menjalankan server, bukan ponsel warga.

## Warga: persuratan

Alur: **Layanan Administrasi > Akses Layanan > Persuratan > pilih layanan surat > pilih RT > Buka WhatsApp Pak RT**.

Pilihan awal:

- Pengantar Umum.
- Keterangan Domisili.
- Surat Keterangan Tidak Mampu (SKTM).
- Keterangan Usaha.
- Pengantar Nikah.
- Lapor Kelahiran.
- Lapor Kematian.
- Administrasi Lainnya.

Setiap pilihan memiliki ikon. Pilih RT sesuai tempat tinggal, lalu periksa ringkasan jenis layanan, RT tujuan, nomor kontak, dan pesan. Gunakan **Ganti RT** atau **Ganti layanan surat** untuk memperbaiki pilihan.

**Buka WhatsApp Pak RT** membuka WhatsApp dengan pesan yang sudah disiapkan. Warga tetap menekan Kirim sendiri. Situs tidak meminta NIK, unggahan KTP, atau berkas; tidak ada nomor tiket, status permohonan, maupun riwayat pengajuan di situs. Pengiriman dan tindak lanjut dilakukan melalui WhatsApp.

Nama dan nomor tujuan dibaca dari kontak pengurus yang diisi admin. Jika nomor belum tersedia, pilihan RT tidak memiliki tombol lanjut; URL langsung ke RT tersebut juga tidak menampilkan tombol WhatsApp. Nama ketua boleh belum diisi selama nomor valid tersedia.

Daftar ini merupakan jalur konsultasi/pengajuan pengantar. Persyaratan dan kewenangan penerbitan surat tetap perlu dikonfirmasi kepada pengurus dan kelurahan; situs tidak menetapkan semua surat wajib memakai pengantar RT/RW.

## Warga: UMKM

Buka **Layanan Ekonomi > Akses Layanan > UMKM**. Direktori menampilkan nama usaha, kategori, deskripsi, alamat, serta WhatsApp jika sudah tersedia. Label **Data contoh** berarti usaha tersebut masih data demonstrasi.

## Pengelola: masuk dan navigasi

Buka `/login.php` dan gunakan akun yang sudah dibuat. Instalasi baru tidak memiliki akun bawaan; lihat [pembuatan akun](sqlite.md#akun-admin). Jangan menaruh password aktual dalam dokumentasi atau Git.

Dashboard `/admin/` menampilkan ringkasan pengunjung dan pilihan kanal. Pengisian kontak dimulai melalui **Kelola layanan** atau menu kanal; form tidak tersedia langsung pada dashboard.

| Menu | Yang dikelola |
| --- | --- |
| Layanan Administrasi | Kontak layanan administrasi dan pengurus RW/RT dalam dua accordion pada satu halaman |
| Layanan Ekonomi | Kontak layanan ekonomi dan tautan Kelola Daftar UMKM |
| Layanan Permasyarakatan | Kontak layanan sosial, kesehatan, keamanan, dan lingkungan |

## Mengelola tombol layanan

1. Masuk ke kanal yang sesuai.
2. Tekan **Tambah tombol**; form terbuka dalam popup. **Edit** membuka popup berisi data yang dipilih.
3. Isi nama, pilih ikon, dan pilih tujuan Chat WhatsApp atau Website/aplikasi lain.
4. Untuk WhatsApp, isi nomor dan pesan pembuka. Untuk website, isi URL `http://` atau `https://`.
5. Tentukan urutan dan centang **Tampilkan di website**, lalu **Simpan tombol**.

Nomor menerima format `08`, `62`, atau `+62`, lalu disimpan sebagai angka berawalan `62`. Pesan maksimal 2000 karakter. Form mempertahankan isian saat validasi gagal. Tutup popup dengan **Tutup**, **Batal**, atau Escape. Simpan mengembalikan pengguna ke kanal terkait.

Urutan terkecil tampil dahulu; urutan sama mengikuti ID. Hilangkan centang tampil untuk menyembunyikan layanan tanpa menghapusnya. **Hapus** membuka konfirmasi dahulu; data baru dihapus setelah tombol konfirmasi ditekan.

**Batas saat ini:** kanal mengikuti ikon, bukan kolom kategori terpisah. Ikon `home`, `megaphone`, `chat`, `phone` masuk Administrasi; `shop`, `plant` masuk Ekonomi; lainnya masuk Permasyarakatan. Mengubah ikon dapat memindahkan kanal. Persuratan merupakan fitur bawaan aplikasi, sehingga tidak ditambah/dihapus lewat CRUD tombol. Daftar jenis surat saat ini diatur di `app/letters.php`.

## Pengurus RW dan RT

Buka **Administrasi > accordion Pengurus RW dan RT**. Isi nama ketua dan WhatsApp untuk RW 12 serta RT 1-6, lalu **Simpan data pengurus**. Nama/nomor tampil di portal; nomor RT juga menjadi tujuan persuratan.

Mengosongkan **kedua** kolom suatu jabatan lalu menyimpan menghapus data pengurus pada jabatan itu. Daftar jabatan tetap tersedia. Data sekretaris dan bendahara yang ditampilkan sebagai kerangka belum dapat dikelola lewat form ini.

## UMKM

Buka **Ekonomi > Kelola Daftar UMKM**. Tambah/Edit memerlukan nama usaha, kategori, deskripsi, dan alamat; WhatsApp opsional. Simpan usaha nyata setelah memperoleh data yang benar, lalu hilangkan tanda Data contoh. Hapus memerlukan konfirmasi.

## Keluar dan statistik

Gunakan **Keluar** setelah selesai. Sesi berakhir setelah 30 menit tidak aktif atau maksimal 8 jam sejak login. Reset password melalui CLI tidak langsung mencabut sesi lain yang masih aktif.

Pengunjung admin yang sedang login tidak dicatat. Angka hari ini/bulan ini/total menghitung cookie berbeda; refresh tidak menambah identitas yang sama. Penjelasan lengkap ada di [analitik](analytics.md).
