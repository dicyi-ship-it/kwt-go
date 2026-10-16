> Arsip versi statis sebelum PHP/MySQL. Struktur file dan perintah server di bawah tidak berlaku untuk versi sekarang; gunakan deployment.md dan verification.md.

# Catatan verifikasi Mina Go

Bukti browser berikut berasal dari sebelum pemindahan folder ke `output-rea/mina-go/`. Pemindahan mempertahankan file aplikasi dan referensi relatifnya; pemeriksaan aset lokal dan sintaks JavaScript dilakukan kembali setelah pemindahan. Skenario browser tidak dijalankan ulang untuk perubahan folder ini. ID Evidence merujuk ledger sesi REA asal, bukan berkas Evidence yang diekspor bersama proyek.

# Mina Go — salinan tampilan

Salinan lokal halaman https://mybiolink.id/satu-tombol-satu-informasi-RW-12-Mina.
HTML, CSS, dan JavaScript statis; tidak memerlukan npm atau proses build.

Jalankan dari root repository:

```sh
python3 -m http.server 4173 --directory output-rea/mina-go
```

Buka http://localhost:4173. File `index.html` juga dapat dibuka langsung;
fitur clipboard/native share bergantung dukungan dan izin browser.

## Cakupan

- Tata letak, gradasi, emoji, 21 kartu layanan, footer, tombol gulir, dan dialog berbagi.
- Tujuan tautan, nomor kontak, pesan WhatsApp, serta QR berbagi mempertahankan nilai referensi.
- CSS dan gambar QR tersimpan lokal. Script layanan pihak ketiga tidak disertakan.
- Logo referensi yang gagal dimuat diganti ikon daun lokal.
- Layout terpusat: 5 kolom desktop (>900px), 3 kolom tablet (541–900px), dan 2 kolom ponsel (≤540px).
- Tombol gulir berada di footer agar tidak menutupi layanan. Dialog berbagi memiliki batas tinggi dan dapat digulir di layar pendek.
- Struktur halaman memakai satu header, main, dan footer; CSS grid layanan terpisah dari tombol dialog, tanpa wrapper atau stylesheet salinan yang saling menimpa.
- Layanan eksternal tidak diduplikasi dan tidak diuji dengan mengirim pesan.

## Verifikasi REA versi responsif

- Desktop 1365 × 1000: `ev_b34d94071ff7d4cdb9d10f79df8237301fbabaab8d4b76525eb28714a6d862ee`.
- Ponsel 320 × 844: `ev_02345430d6d9b06495767d6a11a816b7407b9f8644951b1e30457c1043ce6a00`.
- Ponsel 390 × 844, termasuk membuka dialog berbagi: `ev_6793bbb7e93d43257baabd319bb17f4a7f7748ead12ee5f38a040166d044895b`.
- Tablet 768 × 844: `ev_bfafa470cd076d79d6e53285c1ca7cbc98c9c265e3793bb5daa65095f27535d6`.
- Dialog ditutup dengan Escape dan navigasi ke footer berhasil pada 390 × 844: `ev_3ef5615b26348e4cf2595a998199f7432b70af82f5770f6664d82ef37e37bcc1`; tidak ada page-error.
- Tidak ada page-error JavaScript dalam keempat skenario. `node --check output-rea/app.js` lulus.
- Layout baru sengaja merapikan referensi; kesamaan piksel historis di bawah hanya berlaku untuk salinan awal.

## Verifikasi salinan awal (sebelum perbaikan responsif)

- Referensi desktop 1365 × 900: `ev_b3c5a09a749a61be69299ace7a449145ac03d539fce44722f2edd6f41158d4e2`.
- Lokal desktop 1365 × 900: `ev_729c9e0b4fa12c7c40ea7842ff04c3a6e6e834697416199d3883b2f166532f4c`.
- SHA-256 PNG kedua tangkapan identik: `2f7af6329557c669670e54fbc6b6c3365771e27cbab5014af93958ac5a148939`.
- Lokal ponsel 390 × 844 dan dialog berbagi buka/tutup berhasil: `ev_1ad4dd2325d3a1573e58988be9a81dd5f9c5db46fa6a9f0c6a3891ab80e11727`.
- Tidak ada page-error JavaScript dalam kedua skenario lokal. `node --check output-rea/app.js` lulus.

Kesamaan piksel hanya diverifikasi pada viewport desktop tersebut dan browser Chromium 155.0.8059.79; bukan klaim kesamaan seluruh perilaku atau semua perangkat.
