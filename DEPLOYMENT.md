# Deployment Mais Batik

Mais Batik memerlukan runtime PHP dan database MySQL. Vercel static hosting saja
tidak menjalankan halaman PHP dan tidak menyediakan database MySQL untuk aplikasi
ini. Pilih hosting gratis atau shared hosting yang menyediakan PHP dan MySQL, atau
gunakan platform lain yang secara eksplisit mendukung keduanya.

## Persyaratan hosting

- PHP 8.1+ dengan ekstensi `PDO MySQL` dan `mbstring`.
- Database MySQL/MariaDB dan akses untuk mengimpor berkas SQL.
- HTTPS aktif untuk halaman admin.
- Document root dapat diarahkan ke folder `public/`, atau hosting menyediakan
  document root terpisah dari folder aplikasi.
- Konfigurasi environment server untuk kredensial database dan akun admin.

Periksa kuota, batas penyimpanan, kebijakan backup, ketersediaan HTTPS, serta
nama host database yang diberikan provider. Paket gratis dapat membatasi resource
atau menonaktifkan akses eksternal/database setelah periode tertentu.

## Langkah deployment

1. Buat aplikasi/domain di provider PHP/MySQL, aktifkan HTTPS, dan catat hostname
   database, port, nama database, nama pengguna, serta kata sandi yang diberikan.
2. Upload source proyek di luar document root publik. Atur document root agar
   menunjuk ke folder `public/`. Folder `config/`, `models/`, dan `database/`
   harus berada di luar area yang dapat diakses langsung melalui web.
3. Buat `config/database.php` di server dari `config/database.php.example`.
   Pastikan konfigurasi mengembalikan nilai dari environment `DB_HOST`, `DB_PORT`,
   `DB_NAME`, `DB_USERNAME`, dan `DB_PASSWORD`. Isi environment variables pada
   panel hosting; aplikasi ini tidak membaca `.env` secara otomatis. Untuk
   production, gunakan kata sandi database yang kuat dan atur `DB_PASSWORD`.
4. Impor `database/schema.sql` lewat phpMyAdmin atau alat database provider.
   Skema membuat database `mais_batik`; jika provider tidak memberi hak untuk
   membuat database, hapus perintah `CREATE DATABASE` dan `USE` dari salinan SQL
   yang akan diimpor, lalu pilih database yang sudah dibuat. Untuk database
   proyek yang sudah ada, jalankan migrasi berikut satu kali secara berurutan:
   `database/migrations/20261030_replace_legacy_product_images.sql`, lalu
   `database/migrations/20261030_categories_and_pos.sql`. Migrasi kedua
   mengubah kategori ENUM lama ke tabel kategori dan membuat tabel transaksi POS.
5. Buat password admin yang kuat dan hash-nya pada mesin yang memiliki PHP:

   ```bash
   php -r "echo password_hash('GantiDenganPasswordKuat', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Atur `ADMIN_USERNAME` dan hash hasilnya di environment server sebagai
   `ADMIN_PASSWORD_HASH`. Jangan upload `config/database.php`, hash admin, atau
   kredensial ke repositori publik.
6. Buka `/katalog.php` untuk memastikan katalog tampil, lalu `/admin/` untuk
   masuk dan membuat produk. Jika setup belum lengkap, halaman menampilkan
   pesan kesalahan yang perlu ditindaklanjuti; jangan mengabaikan status HTTP 503.

## Hosting yang tidak mendukung document root terpisah

Jangan menaruh seluruh proyek di dalam folder publik tanpa aturan akses yang benar:
berkas PHP konfigurasi atau source dapat berisiko terekspos jika server salah
konfigurasi. Gunakan provider yang mengizinkan document root menunjuk ke `public/`
atau minta provider memetakan document root tersebut. Jangan mengandalkan `.htaccess`
sebagai pengganti pemisahan source dan web root tanpa menguji konfigurasi server.

## Uji lokal sebelum upload

Dengan konfigurasi database dan environment sudah disiapkan, jalankan web server
PHP dari root proyek:

```bash
php -S 127.0.0.1:8000 -t public
```

Server bawaan PHP hanya untuk development lokal, bukan untuk production.
