# Persiapan deployment shared hosting

## Sebelum upload

1. Pastikan hosting menyediakan PHP 8.3 atau lebih baru, Composer 2, serta ekstensi `pdo_mysql`, `zip`, `gd`, `dom`, `mbstring`, dan `fileinfo`.
2. Buat database MySQL dan user khusus aplikasi dari panel hosting. Berikan hak akses hanya ke database aplikasi.
3. Salin `.env.example` menjadi `.env` di server, lalu isi `APP_URL`, `APP_KEY`, dan kredensial database hosting.
4. Atur `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan gunakan URL HTTPS.
5. Jangan menyalin `.env` ke folder publik dan jangan memakai kredensial akun dummy lokal.

## Susunan file

- Atur document root domain ke folder `public` milik aplikasi.
- Simpan seluruh kode aplikasi, `.env`, `vendor`, dan folder `storage` di luar document root.
- Node.js/Vite hanya diperlukan saat build. Upload hasil `public/build` bersama file aplikasi; server produksi tidak perlu menjalankan Vite.
- Ekspor `.xlsx` memerlukan ekstensi PHP `zip`; unduhan PDF menggunakan Dompdf dengan ekstensi `dom`, `mbstring`, dan `gd`.

## Langkah rilis

1. Cadangkan database hosting sebelum menjalankan perubahan skema.
2. Pasang dependency production dengan `composer install --no-dev --optimize-autoloader`.
3. Pada komputer development, jalankan `npm ci` lalu `npm run build`; upload hasil di `public/build`.
4. Jalankan migration secara bertahap dengan `php artisan migrate --force`. Jangan memakai `migrate:fresh` pada database yang berisi data.
5. Jalankan `php artisan storage:link` jika hosting mengizinkan symbolic link.
6. Pastikan `storage` dan `bootstrap/cache` dapat ditulis oleh PHP.
7. Buat admin pertama dengan `php artisan app:create-admin` melalui terminal hosting.
8. Setelah konfigurasi final, jalankan `php artisan optimize`.

## Setelah rilis

- Uji login admin dan kasir, upload gambar, transaksi tunai, cetak struk, laporan, serta koneksi MySQL.
- Pastikan HTTPS aktif sebelum menggunakan webhook pembayaran.
- Gunakan akun pembayaran sandbox hanya setelah provider disetujui; kredensial production disimpan di environment server dan tidak dimasukkan ke Git.
- Hapus atau nonaktifkan akun demo sebelum aplikasi dipakai operasional.
