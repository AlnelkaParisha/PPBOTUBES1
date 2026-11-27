# Streamly Frontend Demo

Frontend demo sederhana untuk backend Laravel Subscription Streaming.

## Teknologi
- React
- Vite
- Axios
- CSS biasa

## Menjalankan
1. Pastikan backend Laravel berjalan dengan `php artisan serve`.
2. Extract folder frontend.
3. Jalankan `npm install`.
4. Salin `.env.example` menjadi `.env` bila ingin mengubah API URL.
5. Jalankan `npm run dev`.

API default: `http://127.0.0.1:8000/api`

## Fitur demo
- Login dan Register (termasuk konfirmasi password)
- Daftar paket
- Daftar film dan series
- Pencarian konten
- Detail dan simulasi akses menonton
- Subscription, ubah paket, cancel
- Invoice dan simulasi pembayaran

Konten menggunakan data dari API backend. Poster pada demo berupa placeholder sederhana karena backend ContentResource tidak menyediakan URL poster.
