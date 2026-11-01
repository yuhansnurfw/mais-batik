-- ==========================================================
-- MAIS BATIK — 1-FILE ALL-IN-ONE SUPABASE SETUP
-- Jalankan seluruh isi file ini di Supabase SQL Editor:
-- https://supabase.com/dashboard/project/avlnhinsnjmvzzhtjoqx/sql
-- ==========================================================

-- 1. TABEL KATEGORI
CREATE TABLE IF NOT EXISTS public.categories (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(60) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 2. TABEL PRODUK
CREATE TABLE IF NOT EXISTS public.products (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category_id BIGINT NOT NULL REFERENCES public.categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    name VARCHAR(120) NOT NULL,
    variant VARCHAR(160) NOT NULL,
    price_min BIGINT NOT NULL CHECK (price_min >= 0),
    price_max BIGINT NOT NULL CHECK (price_max >= price_min),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    image_path VARCHAR(255) NOT NULL DEFAULT 'assets/images/products/baju-koko.jpg',
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3. TABEL PESANAN (ORDERS)
CREATE TABLE IF NOT EXISTS public.orders (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id BIGINT REFERENCES public.products(id) ON DELETE SET NULL,
    product_name VARCHAR(120) NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(254) NOT NULL,
    customer_phone VARCHAR(24) NOT NULL,
    variant VARCHAR(160) NOT NULL DEFAULT '-',
    quantity INT NOT NULL DEFAULT 1 CHECK (quantity >= 1),
    unit_price BIGINT NOT NULL CHECK (unit_price >= 0),
    status VARCHAR(20) NOT NULL DEFAULT 'baru' CHECK (status IN ('baru', 'diproses', 'selesai', 'dibatalkan')),
    notes VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 4. TABEL POS (TRANSAKSI PENJUALAN)
CREATE TABLE IF NOT EXISTS public.sales_transactions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    total_amount BIGINT NOT NULL CHECK (total_amount >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.sales_transaction_items (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id BIGINT NOT NULL REFERENCES public.sales_transactions(id) ON DELETE CASCADE,
    product_id BIGINT REFERENCES public.products(id) ON DELETE SET NULL,
    product_name VARCHAR(120) NOT NULL,
    quantity INT NOT NULL CHECK (quantity >= 1),
    unit_price BIGINT NOT NULL CHECK (unit_price >= 0),
    subtotal BIGINT NOT NULL CHECK (subtotal >= 0)
);

-- 5. NONAKTIFKAN ROW LEVEL SECURITY (RLS)
-- Memastikan storefront dan dashboard admin (login: admin123) dapat membaca serta mengelola data tanpa kendala izin
ALTER TABLE public.categories DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.products DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.orders DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales_transactions DISABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales_transaction_items DISABLE ROW LEVEL SECURITY;

-- 6. DATA AWAL: KATEGORI
INSERT INTO public.categories (name, slug)
VALUES
    ('Batik pria', 'pria'),
    ('Batik anak', 'anak'),
    ('Batik wanita', 'wanita')
ON CONFLICT (slug) DO NOTHING;

-- 7. DATA AWAL: PRODUK (Otomatis dilewati jika nama produk sudah ada)
INSERT INTO public.products (category_id, name, variant, price_min, price_max, stock, image_path, status)
SELECT c.id, p.name, p.variant, p.price_min, p.price_max, p.stock, p.image_path, p.status
FROM (
    VALUES
        ('pria', 'Baju Koko Bordir Putih', 'Bordir putih · ukuran M–XXL', 185000, 235000, 15, 'assets/images/products/baju-koko.jpg', 'active'),
        ('anak', 'Sarung Instan Anak Hitam', 'Warna hitam · usia 4–12 tahun', 75000, 95000, 20, 'assets/images/products/sarun-instan-anak.jpg', 'active'),
        ('wanita', 'Blouse Batik Motif Parang Hitam', 'Motif parang hitam · ukuran S–XXL', 165000, 225000, 10, 'assets/images/products/blouse-motif-parang.jpg', 'active'),
        ('pria', 'Sarung Batik Dewasa Krem', 'Warna krem · ukuran dewasa', 179000, 249000, 5, 'assets/images/products/sarung-batik-dewasa.jpg', 'active'),
        ('wanita', 'Blouse Batik Bunga Alena Maroon', 'Motif bunga Alena · warna maroon', 179000, 249000, 8, 'assets/images/products/blose-motif-bunga-alena.jpg', 'active')
) AS p(slug, name, variant, price_min, price_max, stock, image_path, status)
JOIN public.categories c ON c.slug = p.slug
WHERE NOT EXISTS (
    SELECT 1 FROM public.products WHERE public.products.name = p.name
);

-- 8. DATA AWAL: CONTOH PESANAN (Hanya dibuat jika tabel pesanan masih kosong)
INSERT INTO public.orders (product_name, customer_name, customer_email, customer_phone, variant, quantity, unit_price, status, notes)
SELECT 'Baju Koko Bordir Putih', 'Budi Santoso', 'budi@example.com', '081234567890', 'Bordir putih · ukuran L', 1, 185000, 'baru', 'Pesanan pertama melalui web'
WHERE NOT EXISTS (
    SELECT 1 FROM public.orders LIMIT 1
);
