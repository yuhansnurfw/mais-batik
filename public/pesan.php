<?php

declare(strict_types=1);

use MaisBatik\Models\OrderRepository;
use MaisBatik\Models\ProductRepository;

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/models/ProductRepository.php';
require_once $projectRoot . '/models/OrderRepository.php';

function pesanEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!is_file($projectRoot . '/config/database.php')) {
            throw new RuntimeException('Konfigurasi database belum tersedia.');
        }

        $pdo = require $projectRoot . '/config/connection.php';
        $products = new ProductRepository($pdo);
        $orders = new OrderRepository($pdo);

        $productIdRaw = $_POST['product_id'] ?? null;
        $productId = filter_var($productIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($productId === false) {
            throw new InvalidArgumentException('Produk tidak valid.');
        }

        $product = $products->findById($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Produk tidak ditemukan di database.');
        }

        $customerName = trim((string) ($_POST['customer_name'] ?? ''));
        if (mb_strlen($customerName, 'UTF-8') < 2 || mb_strlen($customerName, 'UTF-8') > 120) {
            throw new InvalidArgumentException('Nama pelanggan wajib diisi (2–120 karakter).');
        }

        $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
        if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false || strlen($customerEmail) > 254) {
            throw new InvalidArgumentException('Format email tidak valid.');
        }

        $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
        if (preg_match('/^[0-9+().\s-]{8,24}$/D', $customerPhone) !== 1) {
            throw new InvalidArgumentException('Nomor telepon/WhatsApp tidak valid (minimal 8 digit).');
        }

        $variant = trim((string) ($_POST['order_variant'] ?? ''));
        if ($variant === '' || mb_strlen($variant, 'UTF-8') > 160) {
            $variant = (string) $product['variant'];
        }

        $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
        if ($quantity === false) {
            throw new InvalidArgumentException('Jumlah pesanan minimal 1.');
        }

        $notes = trim((string) ($_POST['notes'] ?? ''));
        if (mb_strlen($notes, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Catatan terlalu panjang (maksimal 500 karakter).');
        }

        $unitPrice = (int) $product['price_min'];

        $orders->create([
            'product_id' => $productId,
            'product_name' => (string) $product['name'],
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'variant' => $variant,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'status' => 'baru',
            'notes' => $notes,
        ]);

        if ($isJson) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'message' => 'Pesanan berhasil dikirim ke database! Tim Mais Batik akan segera menghubungi Anda.',
            ]);
            exit;
        }

        header('Location: katalog.php?order_success=1', true, 303);
        exit;
    } catch (InvalidArgumentException | RuntimeException $e) {
        if ($isJson) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
            exit;
        }

        $error = $e->getMessage();
    }
}

// Fallback jika dibuka langsung (GET)
$selectedProduct = null;
$productId = filter_var($_GET['product_id'] ?? null, FILTER_VALIDATE_INT);
if ($productId !== false && is_file($projectRoot . '/config/database.php')) {
    try {
        $pdo = require $projectRoot . '/config/connection.php';
        $productRepo = new ProductRepository($pdo);
        $selectedProduct = $productRepo->findById($productId);
    } catch (Throwable) {
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pemesanan Produk — Mais Batik</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/theme.js" defer></script>
</head>
<body>
  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="index.php">
        <span class="brand-mark" aria-hidden="true">M</span>
        <span class="brand-name">mais<span>batik</span></span>
      </a>
      <nav class="primary-navigation">
        <a class="nav-link" href="index.php">Home</a>
        <a class="nav-link" href="katalog.php">Katalog</a>
        <a class="nav-login" href="admin/">Login Admin ↗</a>
      </nav>
    </div>
  </header>

  <main id="konten-utama" style="max-width: 600px; margin: 3rem auto; padding: 0 1.5rem;">
    <p class="eyebrow">Formulir Pesanan</p>
    <h1>Pesan Produk Batik</h1>
    <?php if (isset($error)): ?>
      <div style="background:#fde8e8;color:#9b1c1c;padding:12px;border-radius:4px;margin-bottom:1rem;font-size:0.9rem;">
        <?= pesanEscape($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="pesan.php" class="form-grid" style="display:flex; flex-direction:column; gap:1rem; margin-top:1.5rem;">
      <input type="hidden" name="product_id" value="<?= pesanEscape($selectedProduct['id'] ?? 1) ?>">
      
      <p style="font-weight:600; color:var(--ink);">
        Produk: <?= pesanEscape($selectedProduct['name'] ?? 'Batik Pilihan') ?>
        (Rp<?= number_format((int) ($selectedProduct['price_min'] ?? 0), 0, ',', '.') ?>)
      </p>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Nama Lengkap
        <input style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" type="text" name="customer_name" required minlength="2" maxlength="120">
      </label>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Nomor WhatsApp / HP
        <input style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" type="tel" name="customer_phone" required minlength="8" maxlength="24" placeholder="08xxxxxxxxxx">
      </label>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Email
        <input style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" type="email" name="customer_email" required maxlength="254">
      </label>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Varian / Ukuran
        <input style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" type="text" name="order_variant" value="<?= pesanEscape($selectedProduct['variant'] ?? '') ?>" required maxlength="160">
      </label>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Jumlah (pcs)
        <input style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" type="number" name="quantity" value="1" min="1" max="1000" required>
      </label>

      <label style="display:flex; flex-direction:column; gap:0.3rem;">
        Catatan Pesanan
        <textarea style="padding:0.6rem; border:1px solid var(--line); border-radius:4px;" name="notes" maxlength="500" rows="3" placeholder="Alamat pengiriman atau permintaan khusus..."></textarea>
      </label>

      <button class="button button-primary" type="submit" style="padding:0.8rem; font-size:1rem; cursor:pointer;">
        Kirim Pesanan →
      </button>
    </form>
    <p style="margin-top:1.5rem;"><a href="katalog.php" class="text-link">← Kembali ke katalog</a></p>
  </main>
</body>
</html>
