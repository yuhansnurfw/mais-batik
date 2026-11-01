-- ==========================================================
-- MAIS BATIK: SCRIPT PERBAIKAN IZIN TABEL SUPABASE (RLS)
-- ==========================================================
-- Jalankan skrip ini di SQL Editor dashboard Supabase Anda:
-- https://supabase.com/dashboard/project/avlnhinsnjmvzzhtjoqx/sql

-- 1. Matikan RLS pada tabel orders agar pesanan dapat dibaca dan dikelola oleh admin web
ALTER TABLE public.orders DISABLE ROW LEVEL SECURITY;

-- 2. Matikan RLS pada produk & kategori agar admin dapat menambah dan mengedit katalog
ALTER TABLE public.products DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.categories DISABLE ROW LEVEL SECURITY;

-- 3. Matikan RLS pada tabel transaksi penjualan (POS)
ALTER TABLE public.sales_transactions DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales_transaction_items DISABLE ROW LEVEL SECURITY;

-- Selesai! Sekarang panel admin Mais Batik dapat mengelola seluruh pesanan dan katalog
-- secara langsung menggunakan kata sandi admin123 tanpa kendala izin RLS.
