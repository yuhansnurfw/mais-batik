# Panduan Deployment Vercel & Supabase — Mais Batik

Proyek Mais Batik kini telah mendukung arsitektur modern **Jamstack / Serverless** yang kompatibel langsung dengan **Vercel** (frontend hosting) dan **Supabase** (PostgreSQL database & backend API).

---

## Langkah 1: Buat Database di Supabase

1. Buka [https://supabase.com](https://supabase.com) dan buat akun / login.
2. Klik **New Project**, tentukan nama proyek (misal: `mais-batik`) dan kata sandi database.
3. Setelah proyek selesai dibuat, buka menu **SQL Editor** di sidebar kiri.
4. Salin seluruh isi berkas [`database/supabase_schema.sql`](../database/supabase_schema.sql).
5. Tempelkan ke SQL Editor Supabase, lalu klik tombol **Run**.
   - Ini akan otomatis membuat tabel `categories`, `products`, `orders`, `sales_transactions`, mengatur RLS (keamanan), dan mengisikan data 5 produk awal.

---

## Langkah 2: Hubungkan Kredensial Supabase ke Aplikasi

1. Di dashboard Supabase, buka menu **Project Settings** (ikon gerigi) -> **API**.
2. Salin:
   - **Project URL** (contoh: `https://xyzprojectref.supabase.co`)
   - **anon / public key** (kunci panjang berawalan `eyJ...`)
3. Buka berkas [`public/assets/js/supabase-config.js`](../public/assets/js/supabase-config.js) dan tempelkan nilai tersebut:
   ```javascript
   window.SUPABASE_CONFIG = {
     url: "https://xyzprojectref.supabase.co",
     anonKey: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     adminKey: ""
   };
   ```

---

## Langkah 3: Deploy ke Vercel

### Opsi A: Melalui GitHub (Paling Praktis)
1. Push proyek ini ke repositori GitHub Anda.
2. Masuk ke [https://vercel.com](https://vercel.com) dan klik **Add New Project**.
3. Import repositori GitHub Anda.
4. Pada bagian **Root Directory**, biarkan root (atau pilih `public` jika Vercel meminta, berkas `vercel.json` sudah otomatis mengarahkan ke folder `public`).
5. Klik **Deploy**!

### Opsi B: Melalui Vercel CLI
Jalankan di terminal proyek:
```bash
npx vercel
```
Ikuti instruksi di layar, dan web langsung online dalam hitungan detik!

---

## URL Aplikasi Setelah di Vercel
- **Storefront:** `https://nama-proyek-anda.vercel.app/`
- **Katalog:** `https://nama-proyek-anda.vercel.app/katalog.html`
- **Panel Admin Cloud:** `https://nama-proyek-anda.vercel.app/admin/index.html` (Kata sandi: `admin123`)
