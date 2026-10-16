# Verifikasi PHP + MySQL

## Pembaruan input WhatsApp dan ikon

- Database lokal yang berjalan telah dimigrasikan dengan backup baris asli sebelum perubahan. 20 tombol WhatsApp kini menyimpan nomor/pesan terpisah; satu tombol aplikasi tetap menyimpan alamat website. Menjalankan migrasi ulang menghasilkan “Migrasi sudah diterapkan”, tanpa memindahkan ulang data.
- `tests/service_inputs.php` lulus: normalisasi 08/62/+62, penolakan input bukan nomor, round-trip encoding pesan (baris baru, `&`, `+`, `?`, emoji), parser URL lama, pelestarian website, dan katalog SVG.
- HTTP integration pada database baru terpisah lulus untuk CRUD lama ditambah pergantian website → WhatsApp → website, penyusunan nomor/teks link publik, ikon tidak valid, nomor salah, pesan terlalu panjang, serta aktif/nonaktif/hapus. Database lokal pengguna tidak diberi record tes. Tes statistik lintas hari/bulan tetap lulus. Database/proses PHP tes dibersihkan.
- Satu warm workflow HTTP terbaru selesai 1,379 detik. CPU, peak memory, dan token agen untuk perubahan ini belum diukur; workflow memiliki pemeriksaan tambahan sehingga tidak dibandingkan langsung dengan angka historis di bawah.
- Ikon layanan sekarang SVG lokal; gambar dan pilihan dapat dipakai tanpa keyboard atau font emoji. Belum mengirim pesan atau memverifikasi registrasi nomor ke WhatsApp. Format link mengikuti [panduan WhatsApp](https://faq.whatsapp.com/5913398998672934).
- Login lokal, form edit nomor WA, pilihan ikon, dan edit Tangerang Live diverifikasi melalui HTTP menggunakan akun yang sudah ada. Seluruh hasil migrasi dibandingkan dengan backup asli dan sesuai transformasi nomor/pesan/ikon yang direncanakan. Akun tetap sama.
- Screenshot publik SVG 390 × 844: `ev_82a07f87747705764ce998bf1659c85ebacba57859e1cab2a9b69b6cb337fa76`, tanpa page-error JavaScript. Form admin belum diuji melalui screenshot browser terautentikasi.

## Lingkungan dan cakupan

Pemeriksaan dilakukan pada 16 Oktober 2026 (WIB), memakai PHP 8.2.12, PDO MySQL, MariaDB 10.4.32, dan Chromium 155.0.8059.79. Database dibuat khusus pengujian di direktori sementara, dengan socket lokal dan networking MySQL dimatikan. Database/konfigurasi layanan lain tidak diubah. Akun/password tes dibuat sementara, bukan akun produksi.

Schema dan seluruh 21 tombol seed berhasil diimpor ke database tersebut. Angka mulai nol; tes menambahkan identitas pengunjung sungguhan pada endpoint lokal, bukan data statistik dalam seed. Pengujian MariaDB tidak membuktikan verifikasi langsung MySQL server atau hosting produksi.

## Pemeriksaan yang dijalankan

- `php -l` pada semua file PHP; `node --check` pada `public/app.js` dan `public/visitors.js`.
- `tests/http_smoke.py` melalui HTTP ke aplikasi PHP dan database nyata: redirect admin tanpa login, login valid, penolakan CSRF, pembatasan percobaan, logout.
- CRUD: tambah, edit, nonaktif/aktif, konfirmasi hapus via GET tanpa mutasi, hapus POST, respons record tidak ditemukan, escaping label HTML, penolakan URL JavaScript.
- Analitik HTTP: bukti/cookie valid, cookie hilang, proof salah, metode GET ditolak, refresh deduplicated, pengunjung kedua, bot/admin dikecualikan, angka dashboard dari database.
- `tests/visitor_stats.php`: pengunjung bulan lalu saja, pengunjung berulang lintas tanggal bulan ini, pengunjung hari ini; memastikan monthly distinct dan total sepanjang waktu. Semua fixture tes ini di-rollback.
- CLI admin: pembuatan akun, penolakan akun duplikat, penolakan reset akun tidak ada, reset password, serta ekspor SQL offline tanpa konfigurasi dan impor hasilnya ke database tes.

Tidak ada request yang mengirim pesan WhatsApp atau memverifikasi layanan pihak ketiga. Password akun produksi, domain, dan konfigurasi hosting belum diberikan; tidak ada deployment produksi.

## Browser REA

- Website publik PHP 390 × 844: `ev_940ff7ffc689932bd72d0ab61e05f62093573b9e5d43e7b0212c1cd6ba2f3eff`.
- Login PHP 1365 × 900: `ev_596c39d8ddab9b0c2f37c028f705735b22161b782bbc6df979fb09aa9ddd0873`.
- Keduanya selesai tanpa event page-error JavaScript. Tangkapan publik menampilkan 21 tombol aktif dari database.

Login admin, dashboard, dan CRUD diverifikasi melalui HTTP integration, bukan screenshot browser terautentikasi. HeadlessChrome bawaan sengaja tersaring bot; kedua screenshot di atas tidak menguji pencatatan kunjungan.

Pemeriksaan JavaScript tambahan memakai Chrome headless milik pengujian dengan User-Agent eksplisit `MinaGoIntegrationBrowser/1.0` (tidak tersaring bot). Sebelum membuka browser total database 4; setelah halaman terbuka total menjadi 5. Skenario REA kemudian memuat ulang halaman dengan cookie sama: terlihat fetch POST `track.php` dan respons 204, total tetap 5. Evidence `ev_e8aba91affe4af767d44c01d3e3b463ec0cd3e7d0a6d86cff1c72f40bbb366ae`. Capture events berstatus **incomplete** karena setelah respons 204 terdapat event `request-failed: net::ERR_ABORTED`; penyebabnya belum dipastikan. Observasi ini mendukung eksekusi script/penerimaan server/dedup, bukan klaim seluruh lifecycle jaringan berhasil. Perlu pemeriksaan lagi pada hosting dengan browser biasa, cookie/JS aktif, dan akun admin terpisah.

Evidence ID merujuk ledger sesi REA asal; bundle lengkap belum diekspor bersama proyek. Bukti versi statis dan breakpoint terdahulu tetap ada di [arsip](history-static.md); tidak membuktikan verifikasi ulang seluruh viewport versi PHP.

## Pengukuran satu workflow

Satu pengulangan warm tes HTTP lengkap (login, analitik, CRUD, logout, limit login) pada server/database yang sudah berjalan:

| Ukuran | Hasil |
| --- | --- |
| Elapsed | 1,189 detik |
| CPU gabungan | 1,948 detik |
| Peak jumlah RSS teramati | 147,3 MiB |
| Interval sampling | 10 ms |

CPU meliputi server PHP, MariaDB, klien HTTP tes, dan runner/sampler; biaya sampling ikut dihitung. Memori adalah maksimum **jumlah RSS tersampling** proses-proses tersebut, bukan peak presisi kernel atau memori fisik unik; shared pages dapat terhitung lebih dari sekali dan lonjakan di antara sampel dapat terlewat. Tidak mencakup browser, startup database, atau impor schema. Ini bukan hasil uji beban.

Cold run, token agen, serta skala banyak pengunjung belum diukur. Versi statis sebelumnya tidak memiliki workflow login/database/CRUD setara untuk perbandingan sebelum/sesudah. Tidak ada klaim peningkatan performa.
