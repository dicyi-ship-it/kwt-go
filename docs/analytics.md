# Statistik pengunjung

## Definisi

| Angka | Cara menghitung |
| --- | --- |
| Hari ini | Identitas cookie berbeda yang berkunjung pada tanggal hari ini |
| Bulan ini | Identitas cookie berbeda selama bulan kalender ini; beberapa hari tetap dihitung sekali |
| Total | Identitas cookie berbeda sejak database mulai mencatat |

Batas tanggal mengikuti `timezone`, default `Asia/Jakarta` (WIB). `first_seen`/`last_seen` disimpan UTC; `visit_date` tanggal lokal. Jam server harus benar. Database baru dimulai nol; seed tidak memiliki pengunjung contoh.

## Alur pencatatan

1. Halaman publik memberikan cookie `mina_visitor`: ID acak 256-bit dan tanda tangan HMAC. Cookie HttpOnly, SameSite=Lax, berlaku satu tahun; Secure saat URL memakai HTTPS.
2. HTML menyertakan bukti bertanda tangan, terikat pada cookie dan berlaku 30 menit. Respons PHP memakai `Cache-Control: no-store`.
3. JavaScript menunggu halaman terlihat satu detik, kemudian mengirim POST ke `track.php` dengan cookie/bukti.
4. Server menolak bukti/cookie palsu, tanpa cookie, kedaluwarsa, atau metode selain POST. Browser admin yang login serta bot yang dikenali tidak dicatat.
5. Database menyimpan hash ID dan tanggal dengan primary key unik. Refresh/tab ganda tidak menggandakan pengunjung pada tanggal sama. Bulan memakai distinct ID, bukan penjumlahan harian.

Pencatatan memakai transaksi: kegagalan membatalkan perubahan pengunjung dan tanggal bersama. Website tetap berfungsi bila request tracking gagal; tidak ada angka pengganti palsu.

## Arti “otentik” dan batasnya

Angka berasal dari kunjungan endpoint yang tersimpan di database, bukan angka acak atau counter localStorage. Cookie valid membuktikan identitas browser terbitan server, **bukan memastikan pengunjung manusia atau satu orang tertentu**.

- Cookie dihapus, private browsing, perangkat/browser berbeda, atau kedaluwarsa bisa membuat identitas baru untuk orang sama.
- Beberapa orang yang memakai browser/cookie sama terlihat sebagai satu pengunjung.
- Cookie/JavaScript diblokir, halaman cepat ditutup, proof kedaluwarsa, atau jaringan gagal menyebabkan kunjungan tidak tercatat.
- Filter User-Agent menolak bot yang dikenali, termasuk HeadlessChrome, crawler, preview, curl, dan pemantau umum. Bot yang meniru browser serta memperoleh cookie/proof masih bisa terhitung; filter juga dapat salah mengenali browser tertentu.
- Tidak memakai fingerprint, verifikasi akun pengunjung, anti-bot eksternal, atau CAPTCHA. Tidak ada jaminan anti-manipulasi menyeluruh.

## Data tersimpan

`visitors`: hash cookie, waktu pertama/terakhir. `visitor_days`: hash dan tanggal lokal. Tidak menyimpan IP/User-Agent mentah pada tabel analitik.

`login_attempts` terpisah: HMAC alamat koneksi (`REMOTE_ADDR`) + bucket waktu, jumlah percobaan, kedaluwarsa. Lima percobaan diperbolehkan per jendela tetap 15 menit; berikutnya ditolak. Baris kedaluwarsa dibersihkan saat percobaan login. Di belakang proxy, beberapa pengguna dapat berbagi bucket karena header IP tidak dipercaya otomatis.

Belum ada pembersihan historis otomatis pengunjung. Penghapusan mengubah total/periode terkait. Data bertambah seiring penggunaan; skala besar belum diukur.
