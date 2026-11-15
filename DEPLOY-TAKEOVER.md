# Deploy simulasi tampilan ke VPS

## Sakelar lewat terminal

Jalankan dari direktori aplikasi sebagai user yang memiliki izin menulis `.env` dan `bootstrap/cache/`:

```sh
php artisan takeover:on
php artisan takeover:off
php artisan takeover:status
```

Command on/off menyimpan `TAKEOVER_ENABLED` di `.env` dan membangun ulang cache konfigurasi secara otomatis. Status membaca konfigurasi efektif aplikasi. Tidak perlu mengedit `.env` atau menjalankan `config:cache` lagi. Jika menggunakan proses aplikasi persisten seperti Octane, reload proses tersebut setelah perubahan; purge cache HTML CDN jika ada. Tidak ada perubahan database. Bentuk `php artisan takeover on|off|status` juga tersedia.

Halaman fiktif mengikuti susunan putih–pink pada screenshot, dengan ilustrasi produk buatan sendiri. Bukan salinan identik aset/foto referensi. Seluruh tombol nonaktif dan tidak ada tautan judol. Hanya request GET/HEAD `/` dan `/login` yang diganti; dashboard, API, autentikasi, database, dan file asli tidak dihapus.

## Persiapan VPS

Untuk VPS yang sudah menjalankan aplikasi dengan MySQL, pertahankan `.env`, `APP_KEY`, konfigurasi MySQL, database, dan storage. Pastikan working tree bersih sebelum `git pull --ff-only origin main`; jika Git melaporkan perubahan lokal, simpan dan periksa perubahan tersebut dahulu, jangan gunakan reset paksa. Fitur simulasi ini tidak membutuhkan migrasi database. Setelah pull, set `TAKEOVER_ENABLED=true` di `.env` dan jalankan `php artisan config:cache`. Tidak perlu menginstal ulang dependensi atau build aset hanya untuk fitur ini karena halaman simulasi memakai CSS mandiri dan tidak menambah dependency.

Gunakan Nginx/Apache dengan document root mengarah ke `public/`, PHP **8.4+** (kebutuhan lockfile, bukan hanya minimum composer.json), Composer 2, Node 22.12+ atau 24. Pasang ekstensi PHP yang diwajibkan `composer check-platform-reqs` (termasuk mbstring, XML/DOM, SQLite atau driver database Anda, curl, zip).

Deploy seluruh checkout beserta perubahan ini. Untuk instalasi baru, buat `.env` dari konfigurasi Anda sendiri (repo tidak menyediakan `.env.example`); isi APP_NAME, APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain-anda, koneksi database serta driver session/cache/queue. Buat APP_KEY sekali dengan `php artisan key:generate`. Pada aplikasi yang sudah berjalan, pertahankan `.env`, APP_KEY, database, dan storage yang sudah ada.

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
```

Backup database sebelum migrasi. Berikan izin tulis `storage/` dan `bootstrap/cache/` kepada user PHP-FPM, tanpa menggunakan chmod 777. Konfigurasikan HTTPS dan PHP-FPM; jangan gunakan `artisan serve` sebagai server produksi. Jalankan queue worker jika fitur generator aplikasi asli digunakan. Tidak ada kredensial tambahan yang dibutuhkan halaman simulasi.

## Aktifkan

Tambahkan/ubah di `.env`:

```dotenv
TAKEOVER_ENABLED=true
```

Lalu jalankan:

```sh
php artisan config:cache
```

Periksa `/` dan `/login` menampilkan katalog simulasi. Jika ada CDN/reverse proxy yang menyimpan HTML, purge kedua halaman. Respons simulasi memakai Cache-Control no-store.

## Kembalikan website normal

```dotenv
TAKEOVER_ENABLED=false
```

Jalankan `php artisan config:cache`, lalu purge cache HTML CDN bila ada. `/` akan kembali mengarah ke login asli. Tidak perlu menghapus file atau mengubah database. Jangan mengubah APP_KEY untuk mengaktifkan/menonaktifkan simulasi.

## Melepas seluruh fitur

Nonaktifkan dahulu dan periksa login asli. Hapus baris `ShowTakeover` dari `bootstrap/app.php`, lalu hapus `app/Http/Middleware/ShowTakeover.php`, `config/takeover.php`, `resources/views/takeover.blade.php`, serta `tests/Feature/TakeoverTest.php`. Hapus TAKEOVER_ENABLED dari `.env` dan jalankan `php artisan config:cache` lagi.
