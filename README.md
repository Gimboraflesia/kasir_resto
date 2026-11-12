# Kasir Resto

Aplikasi POS restoran berbasis Laravel 13. Antarmuka menggunakan Blade, Vite, Tailwind CSS, dan JavaScript ringan.

## Persyaratan

- PHP 8.3 atau lebih baru dan Composer
- Ekstensi PHP `pdo_mysql`, `zip`, `gd`, `dom`, `mbstring`, dan `fileinfo`
- MySQL
- Node.js dan npm untuk proses build frontend

## Menjalankan secara lokal

1. Salin `.env.example` menjadi `.env`, lalu isi konfigurasi MySQL lokal.
2. Jalankan `composer install` dan `npm ci`.
3. Jalankan `php artisan key:generate`.
4. Buat database sesuai `DB_DATABASE`, lalu jalankan `php artisan migrate`.
5. Buat akun admin pertama secara interaktif: `php artisan app:create-admin`.
6. Build aset dengan `npm run build`.
7. Jalankan `php artisan serve` untuk membuka aplikasi.

Tidak ada registrasi publik. Admin awal dibuat melalui perintah Artisan; akun kasir dikelola dari area admin.

## Aset produksi

Jalankan `npm run build` saat menyiapkan rilis. Server shared hosting hanya perlu PHP, Composer dependencies yang sudah terpasang, database MySQL, dan hasil build di `public/build`; Vite development server tidak diperlukan.

## Fitur yang tersedia

- Login admin dan kasir dengan pembatasan akses berdasarkan role; registrasi publik tidak tersedia.
- Pengelolaan kategori dan menu, termasuk stok barang, harga modal, target keuntungan per menu, arsip produk, dan unggah gambar.
- Kasir untuk pembayaran tunai, QRIS manual, dan QRIS Midtrans Snap; total dihitung di backend, pembayaran Midtrans menunggu webhook bertanda tangan, stok diperbarui sesuai status pembayaran, dan struk dapat dicetak.
- Dashboard penjualan, riwayat transaksi, laporan, ekspor Excel `.xlsx`, dan unduhan PDF.
- Pengaturan identitas restoran serta pengelolaan akun kasir.

Ekspor Excel dan PDF memakai PhpSpreadsheet dan Dompdf. Aktifkan ekstensi PHP `zip` untuk file `.xlsx`; server juga perlu ekstensi `gd`, `dom`, dan `mbstring` untuk pemrosesan file serta PDF.

Untuk menerima pembayaran Midtrans sungguhan, atur `MIDTRANS_IS_PRODUCTION=true` dan gunakan pasangan Server Key serta Client Key production dari dashboard Midtrans. Pastikan URL notifikasi/webhook Midtrans mengarah ke `/webhooks/midtrans`. Pembayaran hanya dianggap lunas setelah webhook bertanda tangan diverifikasi. Kunci Sandbox yang sekarang ada di `.env` lokal tidak dapat memproses pembayaran sungguhan.

Panduan deployment shared hosting tersedia di [DEPLOYMENT.md](DEPLOYMENT.md).

