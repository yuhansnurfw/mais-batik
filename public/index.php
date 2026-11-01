<?php

declare(strict_types=1);

use MaisBatik\Models\ProductRepository;
use MaisBatik\Models\CategoryRepository;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/models/ProductRepository.php';
require_once $projectRoot . '/models/CategoryRepository.php';

function indexEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$products = [];
$categories = [];
$databaseConnected = false;

try {
    if (is_file($projectRoot . '/config/database.php')) {
        $pdo = require $projectRoot . '/config/connection.php';
        $productRepo = new ProductRepository($pdo);
        $categoryRepo = new CategoryRepository($pdo);

        $products = array_values(array_filter(
            $productRepo->findAll(),
            static fn (array $p): bool => ($p['status'] ?? '') === 'active'
        ));

        foreach ($categoryRepo->findAll() as $c) {
            $categories[$c['slug']] = $c['name'];
        }
        $databaseConnected = true;
    }
} catch (Throwable $e) {
    error_log('Database check in index.php: ' . $e->getMessage());
}

if (!$databaseConnected || empty($categories)) {
    $categories = [
        'pria' => 'Batik pria',
        'anak' => 'Batik anak',
        'wanita' => 'Batik wanita',
    ];
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta
      name="description"
      content="Jelajahi katalog baju koko, sarung instan anak, dan blouse batik dari Mais Batik."
    >
    <meta name="theme-color" content="#f8f5ed">
    <title>Mais Batik — Katalog Batik</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/theme.js" defer></script>
    <script src="assets/js/main.js" defer></script>
  </head>
  <body>
    <a class="skip-link" href="#konten-utama">Lewati ke konten utama</a>

    <header class="site-header" id="home">
      <div class="header-inner">
        <a class="brand" href="#home" aria-label="Mais Batik, ke halaman utama">
          <span class="brand-mark" aria-hidden="true">M</span>
          <span class="brand-name">mais<span>batik</span></span>
        </a>

        <nav class="primary-navigation" id="primary-navigation" aria-label="Navigasi utama">
          <a class="nav-link" href="#home">Home</a>
          <a class="nav-link" href="katalog.php">Katalog</a>
          <a class="nav-link" href="#order">Order</a>
          <a class="nav-link" href="#booking">Booking</a>
          <a class="nav-link nav-system" href="admin/?tab=pos">POS</a>
          <a class="nav-login" href="admin/">Login Admin <span aria-hidden="true">↗</span></a>
        </nav>
        <div class="header-tools">
          <button class="theme-toggle" type="button" data-theme-toggle aria-label="Aktifkan mode gelap" aria-pressed="false" title="Aktifkan mode gelap">
            <span class="theme-icon" aria-hidden="true">☾</span>
          </button>
          <button class="menu-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false" aria-controls="primary-navigation">
            <span></span>
            <span></span>
            <span></span>
          </button>
        </div>
      </div>
    </header>

    <main id="konten-utama">
      <section class="hero section-shell" aria-labelledby="hero-title">
        <div class="hero-copy">
          <p class="eyebrow"><span></span> Warisan budaya, sentuhan masa kini</p>
          <h1 id="hero-title">Cerita indah<br>di setiap <em>motif.</em></h1>
          <p class="hero-description">
            Temukan kehangatan batik pilihan yang dibuat dengan ketelitian dan
            kecintaan pada budaya Indonesia.
          </p>
          <div class="hero-actions">
            <a class="button button-primary" href="katalog.php">Jelajahi Katalog <span aria-hidden="true">→</span></a>
            <a class="text-link" href="#booking">Kenali Mais Batik</a>
          </div>
          <div class="hero-note">
            <span class="note-rule" aria-hidden="true"></span>
            <span>Dirangkai dengan cerita.<br>Dikenakan dengan bangga.</span>
          </div>
        </div>

        <div class="hero-art" aria-label="Ilustrasi motif batik bernuansa tanah">
          <div class="art-frame">
            <div class="batik-pattern" aria-hidden="true">
              <span class="pattern-flower flower-one"></span>
              <span class="pattern-flower flower-two"></span>
              <span class="pattern-flower flower-three"></span>
              <span class="pattern-flower flower-four"></span>
              <span class="pattern-diamond diamond-one"></span>
              <span class="pattern-diamond diamond-two"></span>
              <span class="pattern-diamond diamond-three"></span>
              <span class="pattern-diamond diamond-four"></span>
              <span class="pattern-dot dot-one"></span>
              <span class="pattern-dot dot-two"></span>
              <span class="pattern-dot dot-three"></span>
              <span class="pattern-dot dot-four"></span>
            </div>
          </div>
          <div class="art-caption">
            <span class="caption-number">01</span>
            <span class="caption-line" aria-hidden="true"></span>
            <span>Tradisi yang terus hidup</span>
          </div>
          <span class="art-sparkle sparkle-one" aria-hidden="true"></span>
          <span class="art-sparkle sparkle-two" aria-hidden="true"></span>
        </div>
      </section>

      <section class="intro-strip" aria-label="Tentang koleksi">
        <p>Dibuat dengan hati</p>
        <span aria-hidden="true">♡</span>
        <p>Berakar pada tradisi</p>
        <span aria-hidden="true">♡</span>
        <p>Untuk cerita Anda</p>
      </section>

      <section class="catalog section-shell" id="katalog" aria-labelledby="catalog-title">
        <div class="catalog-heading">
          <div>
            <p class="eyebrow"><span></span> Pilihan untuk setiap cerita</p>
            <h2 id="catalog-title">Temukan batik <em>favoritmu.</em></h2>
            <p class="catalog-description">
              Koleksi pilihan Mais Batik, dibuat untuk menemani hari istimewa maupun keseharian.
            </p>
          </div>
          <span class="catalog-note"><?= $databaseConnected ? 'Katalog terhubung<br><strong>Database Aktif</strong>' : 'Foto produk<br><strong>Ilustrasi</strong>' ?></span>
        </div>

        <div class="catalog-tools" aria-label="Filter katalog produk">
          <label class="search-field">
            <span class="visually-hidden">Cari nama produk</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="10.8" cy="10.8" r="6.8"></circle>
              <path d="m16 16 4.5 4.5"></path>
            </svg>
            <input id="product-search" type="search" placeholder="Cari produk..." autocomplete="off">
          </label>
          <label class="category-field">
            <span>Kategori</span>
            <select id="category-filter" aria-label="Filter berdasarkan kategori">
              <option value="semua">Semua kategori</option>
              <?php foreach ($categories as $slug => $cName): ?>
                <option value="<?= indexEscape($slug) ?>"><?= indexEscape($cName) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>

        <div class="catalog-summary">
          <p id="product-count" aria-live="polite">Menampilkan <?= count($products) ?> produk</p>
          <p><?= $databaseConnected ? 'Stok dan harga terhubung langsung dengan database.' : 'Harga dan stok contoh; konfirmasi sebelum memesan.' ?></p>
        </div>

        <div class="product-grid" id="product-grid">
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
              <?php
              $categorySlug = (string) ($p['category'] ?? 'pria');
              $categoryTitle = (string) ($p['category_name'] ?? 'Batik');
              $pName = (string) $p['name'];
              $variant = (string) ($p['variant'] ?? '');
              $stock = (int) ($p['stock'] ?? 0);
              $inStock = $stock > 0;
              $image = (string) ($p['image_path'] ?? 'assets/images/products/baju-koko.jpg');
              $priceFormatted = 'Rp' . number_format((int) $p['price_min'], 0, ',', '.')
                  . ' – Rp' . number_format((int) $p['price_max'], 0, ',', '.');
              $searchQuery = mb_strtolower($pName . ' ' . $variant . ' ' . $categoryTitle, 'UTF-8');
              $detailText = sprintf('%s. Varian: %s. %s (stok: %d).', $categoryTitle, $variant, $inStock ? 'Stok tersedia' : 'Stok habis', $stock);
              ?>
              <article class="product-card" data-category="<?= indexEscape($categorySlug) ?>" data-search="<?= indexEscape($searchQuery) ?>">
                <div class="product-image">
                  <img src="<?= indexEscape($image) ?>" alt="<?= indexEscape($pName) ?>" width="640" height="720" loading="lazy">
                  <span class="product-category"><?= indexEscape($categoryTitle) ?></span>
                  <span class="stock-badge <?= $inStock ? ($stock <= 5 ? 'stock-limited' : '') : 'stock-sold-out' ?>">
                    <span aria-hidden="true"></span> <?= $inStock ? ($stock <= 5 ? 'Stok terbatas · ' . $stock : 'Stok tersedia · ' . $stock) : 'Stok habis' ?>
                  </span>
                </div>
                <div class="product-info">
                  <p class="product-variant"><?= indexEscape($variant) ?></p>
                  <h3><?= indexEscape($pName) ?></h3>
                  <p class="product-price"><?= indexEscape($priceFormatted) ?></p>
                  <div class="product-actions">
                    <?php if ($inStock): ?>
                      <button class="product-order" type="button" data-order-trigger
                        data-product-id="<?= indexEscape($p['id']) ?>"
                        data-product-name="<?= indexEscape($pName) ?>"
                        data-variant="<?= indexEscape($variant) ?>"
                        data-price="<?= indexEscape($p['price_min']) ?>"
                        data-stock="<?= indexEscape($stock) ?>">Pesan <span aria-hidden="true">→</span></button>
                    <?php else: ?>
                      <span class="product-order is-disabled" aria-disabled="true">Stok habis</span>
                    <?php endif; ?>
                    <button class="product-detail" type="button" data-product="<?= indexEscape($pName) ?>" data-description="<?= indexEscape($detailText) ?>">Detail</button>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <p class="catalog-empty" id="catalog-empty" <?= empty($products) ? '' : 'hidden' ?>>Produk tidak ditemukan. Coba kata kunci atau kategori lain.</p>
      </section>

      <section class="service-band" aria-label="Layanan Mais Batik">
        <article class="service-card" id="order">
          <span class="service-number">01</span>
          <div>
            <p class="eyebrow">Mudah dan personal</p>
            <h2>Pemesanan</h2>
            <p>Pilih motif dan ukuran favorit Anda, lalu kirim pesanan langsung ke sistem kami.</p>
            <a class="text-link" href="#katalog">Pilih produk di katalog <span aria-hidden="true">→</span></a>
          </div>
        </article>

        <article class="service-card" id="booking">
          <span class="service-number">02</span>
          <div>
            <p class="eyebrow">Berkunjung lebih dekat</p>
            <h2>Booking</h2>
            <p>Rencanakan kunjungan untuk mengenal koleksi dan cerita di baliknya.</p>
            <a class="text-link" href="mailto:info@maisbatik.example">Rencanakan kunjungan <span aria-hidden="true">→</span></a>
          </div>
        </article>
      </section>

      <section class="system-links section-shell" aria-label="Akses sistem">
        <div id="pos">
          <span class="system-icon" aria-hidden="true">↗</span>
          <p class="eyebrow">Untuk tim Mais Batik</p>
          <h2>Point of Sale</h2>
          <p>Akses kasir dan kelola transaksi penjualan langsung dari database.</p>
          <a class="text-link" href="admin/?tab=pos">Buka POS Kasir <span aria-hidden="true">→</span></a>
        </div>
        <div id="login-admin">
          <span class="system-icon" aria-hidden="true">⌑</span>
          <p class="eyebrow">Area terbatas</p>
          <h2>Login Admin</h2>
          <p>Kelola produk, kategori, dan pesanan melalui panel admin terintegrasi.</p>
          <a class="text-link" href="admin/">Buka panel admin <span aria-hidden="true">→</span></a>
        </div>
      </section>
    </main>

    <dialog class="product-dialog" id="product-dialog" aria-labelledby="dialog-title">
      <form method="dialog">
        <button class="dialog-close" type="submit" aria-label="Tutup detail">×</button>
      </form>
      <p class="eyebrow"><span></span> Detail produk</p>
      <h2 id="dialog-title"></h2>
      <p id="dialog-description"></p>
      <button class="button button-primary" id="dialog-order" type="button">Pesan Produk <span aria-hidden="true">→</span></button>
    </dialog>

    <dialog class="order-dialog" id="order-dialog" aria-labelledby="order-dialog-title">
      <form method="dialog" class="dialog-close-form">
        <button class="dialog-close" type="submit" aria-label="Tutup formulir">×</button>
      </form>
      <p class="eyebrow"><span></span> Formulir Pesanan</p>
      <h2 id="order-dialog-title">Pesan Batik</h2>
      <p class="order-product-info" id="order-product-info"></p>
      <div id="order-alert" class="order-alert" hidden></div>
      <form action="pesan.php" method="post" id="order-form" class="order-form-grid">
        <input type="hidden" name="product_id" id="order-product-id">
        <label>Nama Lengkap
          <input type="text" name="customer_name" required minlength="2" maxlength="120" placeholder="Nama Anda">
        </label>
        <label>Nomor WhatsApp / HP
          <input type="tel" name="customer_phone" required minlength="8" maxlength="24" placeholder="08xxxxxxxxxx">
        </label>
        <label>Email
          <input type="email" name="customer_email" required maxlength="254" placeholder="email@contoh.com">
        </label>
        <label>Varian / Ukuran
          <input type="text" name="order_variant" id="order-variant-input" required maxlength="160">
        </label>
        <label>Jumlah (pcs)
          <input type="number" name="quantity" id="order-qty-input" value="1" min="1" max="1000" required>
        </label>
        <div class="full-width order-summary-box" id="order-subtotal">Estimasi Total: Rp0 (1 pcs)</div>
        <label class="full-width">Catatan Pesanan (opsional)
          <textarea name="notes" maxlength="500" rows="3" placeholder="Alamat pengiriman atau permintaan khusus..."></textarea>
        </label>
        <button class="button button-primary full-width" type="submit" id="order-submit-btn">Kirim Pesanan <span aria-hidden="true">→</span></button>
      </form>
    </dialog>

    <footer class="site-footer">
      <div class="footer-main">
        <div class="footer-brand">
          <a class="brand brand-footer" href="#home" aria-label="Mais Batik, kembali ke atas">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span class="brand-name">mais<span>batik</span></span>
          </a>
          <p>Merawat cerita, merayakan budaya.</p>
        </div>
        <div class="footer-links">
          <p class="footer-label">Jelajahi</p>
          <a href="katalog.php">Katalog</a>
          <a href="#order">Order</a>
          <a href="#booking">Booking</a>
        </div>
        <div class="footer-links">
          <p class="footer-label">Hubungi kami</p>
          <a href="mailto:info@maisbatik.example">info@maisbatik.example</a>
          <a href="#home">Instagram <span aria-hidden="true">↗</span></a>
        </div>
        <a class="back-to-top" href="#home">Kembali ke atas <span aria-hidden="true">↑</span></a>
      </div>
      <div class="footer-bottom">
        <span>© <span id="current-year"><?= date('Y') ?></span> Mais Batik</span>
        <span>Dibuat dengan rasa dan cerita.</span>
      </div>
    </footer>
  </body>
</html>
