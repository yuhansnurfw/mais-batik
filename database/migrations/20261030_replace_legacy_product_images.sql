USE mais_batik;

UPDATE products
SET image_path = CASE image_path
    WHEN 'assets/images/baju-koko.svg'
        THEN 'assets/images/products/baju-koko.jpg'
    WHEN 'assets/images/sarung-instan-anak.svg'
        THEN 'assets/images/products/sarun-instan-anak.jpg'
    WHEN 'assets/images/blouse-batik.svg'
        THEN 'assets/images/products/blouse-motif-parang.jpg'
    ELSE image_path
END
WHERE image_path IN (
    'assets/images/baju-koko.svg',
    'assets/images/sarung-instan-anak.svg',
    'assets/images/blouse-batik.svg'
);

ALTER TABLE products
    ALTER COLUMN image_path SET DEFAULT 'assets/images/products/baju-koko.jpg';
