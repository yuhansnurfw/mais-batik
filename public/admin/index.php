<?php

declare(strict_types=1);

use MaisBatik\Models\OrderRepository;
use MaisBatik\Models\CategoryRepository;
use MaisBatik\Models\PosRepository;
use MaisBatik\Models\ProductRepository;
use MaisBatik\Controllers\CategoryController;
use MaisBatik\Controllers\PosController;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/models/ProductRepository.php';
require $projectRoot . '/models/OrderRepository.php';
require $projectRoot . '/models/CategoryRepository.php';
require $projectRoot . '/models/PosRepository.php';
require $projectRoot . '/controllers/CategoryController.php';
require $projectRoot . '/controllers/PosController.php';

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('mais_batik_admin');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

function adminEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function adminPostString(string $field): string
{
    $value = $_POST[$field] ?? null;

    if (!is_string($value)) {
        throw new InvalidArgumentException('Format data tidak valid.');
    }

    return trim($value);
}

function adminPasswordInput(): string
{
    $value = $_POST['password'] ?? null;

    if (!is_string($value) || strlen($value) > 4096) {
        throw new InvalidArgumentException('Kata sandi tidak valid.');
    }

    return $value;
}

function adminRequiredText(string $field, string $label, int $maxLength): string
{
    $value = adminPostString($field);

    if (
        !mb_check_encoding($value, 'UTF-8')
        || $value === ''
        || mb_strlen($value, 'UTF-8') > $maxLength
    ) {
        throw new InvalidArgumentException(sprintf('%s wajib diisi (maksimal %d karakter).', $label, $maxLength));
    }

    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
        throw new InvalidArgumentException(sprintf('%s mengandung karakter yang tidak diizinkan.', $label));
    }

    return $value;
}

function adminOptionalText(string $field, int $maxLength): string
{
    $value = adminPostString($field);

    if (!mb_check_encoding($value, 'UTF-8')
        || mb_strlen($value, 'UTF-8') > $maxLength
        || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1
    ) {
        throw new InvalidArgumentException('Catatan terlalu panjang atau mengandung karakter yang tidak diizinkan.');
    }

    return $value;
}

function adminUnsignedInteger(string $field, string $label, int $maximum): int
{
    $value = adminPostString($field);

    if (preg_match('/^\d{1,12}$/D', $value) !== 1 || (int) $value > $maximum) {
        throw new InvalidArgumentException(sprintf('%s harus berupa angka yang valid.', $label));
    }

    return (int) $value;
}

function adminRecordId(string $field = 'id'): int
{
    $id = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($id === false) {
        throw new InvalidArgumentException('ID data tidak valid.');
    }

    return $id;
}

function adminProductImages(): array
{
    return [
        'assets/images/products/baju-koko.jpg' => 'Baju Koko Bordir Putih',
        'assets/images/products/sarun-instan-anak.jpg' => 'Sarung Instan Anak Hitam',
        'assets/images/products/blouse-motif-parang.jpg' => 'Blouse Batik Motif Parang Hitam',
        'assets/images/products/sarung-batik-dewasa.jpg' => 'Sarung Batik Dewasa Krem',
        'assets/images/products/blose-motif-bunga-alena.jpg' => 'Blouse Batik Bunga Alena Maroon',
    ];
}

function adminProductInput(CategoryRepository $categories): array
{
    $statuses = ['active', 'inactive'];
    $name = adminRequiredText('name', 'Nama produk', 120);
    $categoryId = adminUnsignedInteger('category_id', 'Kategori', 999999999999);
    $variant = adminRequiredText('variant', 'Varian', 160);
    $priceMin = adminUnsignedInteger('price_min', 'Harga minimum', 999999999999);
    $priceMax = adminUnsignedInteger('price_max', 'Harga maksimum', 999999999999);
    $stock = adminUnsignedInteger('stock', 'Stok', 1000000);
    $imagePath = adminPostString('image_path');
    $status = adminPostString('status');

    if ($categories->findById($categoryId) === null) {
        throw new InvalidArgumentException('Kategori produk tidak valid.');
    }

    if (!in_array($status, $statuses, true)) {
        throw new InvalidArgumentException('Status produk tidak valid.');
    }

    if ($priceMax < $priceMin) {
        throw new InvalidArgumentException('Harga maksimum tidak boleh kurang dari harga minimum.');
    }

    if (
        !array_key_exists($imagePath, adminProductImages())
        || !is_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath))
    ) {
        throw new InvalidArgumentException('Pilih berkas gambar yang tersedia di folder aset produk.');
    }

    return [
        'name' => $name,
        'category_id' => $categoryId,
        'variant' => $variant,
        'price_min' => $priceMin,
        'price_max' => $priceMax,
        'stock' => $stock,
        'image_path' => $imagePath,
        'status' => $status,
    ];
}

function adminOrderInput(ProductRepository $products): array
{
    $productId = adminUnsignedInteger('product_id', 'Produk', 999999999999);
    $product = $products->findById($productId);

    if ($product === null) {
        throw new InvalidArgumentException('Produk yang dipilih tidak ditemukan.');
    }

    $email = adminRequiredText('customer_email', 'Email pelanggan', 254);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Format email pelanggan tidak valid.');
    }

    $phone = adminRequiredText('customer_phone', 'Nomor telepon', 24);
    if (preg_match('/^[0-9+().\s-]{8,24}$/D', $phone) !== 1) {
        throw new InvalidArgumentException('Format nomor telepon tidak valid.');
    }

    $quantity = adminUnsignedInteger('quantity', 'Jumlah', 1000000);
    $unitPrice = adminUnsignedInteger('unit_price', 'Harga satuan', 999999999999);
    if ($quantity < 1 || $unitPrice < 1) {
        throw new InvalidArgumentException('Jumlah dan harga satuan harus lebih dari nol.');
    }

    $status = adminPostString('order_status');
    if (!in_array($status, ['baru', 'diproses', 'selesai', 'dibatalkan'], true)) {
        throw new InvalidArgumentException('Status pesanan tidak valid.');
    }

    return [
        'product_id' => $productId,
        'product_name' => $product['name'],
        'customer_name' => adminRequiredText('customer_name', 'Nama pelanggan', 120),
        'customer_email' => $email,
        'customer_phone' => $phone,
        'variant' => adminRequiredText('order_variant', 'Varian pesanan', 160),
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'status' => $status,
        'notes' => adminOptionalText('notes', 500),
    ];
}

function adminRedirect(string $tab = 'products'): never
{
    header('Location: ./?tab=' . rawurlencode($tab), true, 303);
    exit;
}

function adminDatabaseConnection(string $projectRoot): PDO
{
    if (!is_file($projectRoot . '/config/database.php')) {
        throw new RuntimeException('Missing config/database.php.');
    }

    return require $projectRoot . '/config/connection.php';
}

function adminFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function adminActionTab(string $action): string
{
    if (str_contains($action, 'order')) {
        return 'orders';
    }

    if (str_contains($action, 'category')) {
        return 'categories';
    }

    return str_contains($action, 'sale') ? 'pos' : 'products';
}

$csrfToken = adminCsrfToken();
$isAuthenticated = ($_SESSION['admin_authenticated'] ?? false) === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(400);
        adminFlash('error', 'Sesi formulir tidak valid atau kedaluwarsa. Silakan coba lagi.');
        adminRedirect();
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'login') {
        $expectedUsername = getenv('ADMIN_USERNAME');
        if ($expectedUsername === false || $expectedUsername === '') {
            $expectedUsername = 'admin';
        }

        $passwordHash = getenv('ADMIN_PASSWORD_HASH');
        if ($passwordHash === false || $passwordHash === '') {
            // Default password: admin123
            $passwordHash = '$2y$10$CwRVNfxPue2kO3O4DmPiQeevmxs/Tq5LDLgovzslaMkekBKsbAh3e';
        }

        $usernameInput = $_POST['username'] ?? null;
        $passwordInput = $_POST['password'] ?? null;
        if (!is_string($usernameInput) || !is_string($passwordInput) || strlen($passwordInput) > 4096) {
            adminFlash('error', 'Nama pengguna atau kata sandi tidak sesuai.');
            adminRedirect();
        }
        $username = trim($usernameInput);
        $password = adminPasswordInput();

        if (hash_equals($expectedUsername, $username) && password_verify($password, $passwordHash)) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            unset($_SESSION['csrf_token']);
            adminCsrfToken();
            adminFlash('success', 'Berhasil masuk ke panel Mais Batik.');
            adminRedirect();
        }

        adminFlash('error', 'Nama pengguna atau kata sandi tidak sesuai.');
        adminRedirect();
    }

    if (!$isAuthenticated) {
        http_response_code(403);
        adminFlash('error', 'Silakan masuk untuk mengakses panel admin.');
        adminRedirect();
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        adminCsrfToken();
        adminFlash('success', 'Anda telah keluar.');
        adminRedirect();
    }

    try {
        $pdo = adminDatabaseConnection($projectRoot);
        $products = new ProductRepository($pdo);
        $orders = new OrderRepository($pdo);
        $categories = new CategoryRepository($pdo);
        $categoryController = new CategoryController($categories);
        $pos = new PosRepository($pdo);
        $posController = new PosController($pos);

        switch ($action) {
            case 'create_product':
                $products->create(adminProductInput($categories));
                adminFlash('success', 'Produk berhasil ditambahkan.');
                adminRedirect('products');

            case 'update_product':
                $id = adminRecordId();
                if ($products->findById($id) === null) {
                    throw new InvalidArgumentException('Produk tidak ditemukan.');
                }
                $products->update($id, adminProductInput($categories));
                adminFlash('success', 'Produk berhasil diperbarui.');
                adminRedirect('products');

            case 'delete_product':
                $id = adminRecordId();
                if ($products->findById($id) === null) {
                    throw new InvalidArgumentException('Produk tidak ditemukan.');
                }
                $products->delete($id);
                adminFlash('success', 'Produk berhasil dihapus. Riwayat pesanan tetap tersimpan.');
                adminRedirect('products');

            case 'create_order':
                $orders->create(adminOrderInput($products));
                adminFlash('success', 'Pesanan berhasil ditambahkan.');
                adminRedirect('orders');

            case 'update_order':
                $id = adminRecordId();
                if ($orders->findById($id) === null) {
                    throw new InvalidArgumentException('Pesanan tidak ditemukan.');
                }
                $orders->update($id, adminOrderInput($products));
                adminFlash('success', 'Pesanan berhasil diperbarui.');
                adminRedirect('orders');

            case 'delete_order':
                $id = adminRecordId();
                if ($orders->findById($id) === null) {
                    throw new InvalidArgumentException('Pesanan tidak ditemukan.');
                }
                $orders->delete($id);
                adminFlash('success', 'Pesanan berhasil dihapus.');
                adminRedirect('orders');

            case 'create_category':
                $categoryController->create($_POST);
                adminFlash('success', 'Kategori berhasil ditambahkan.');
                adminRedirect('categories');

            case 'update_category':
                $categoryController->update(adminRecordId(), $_POST);
                adminFlash('success', 'Kategori berhasil diperbarui.');
                adminRedirect('categories');

            case 'delete_category':
                $categoryController->delete(adminRecordId());
                adminFlash('success', 'Kategori berhasil dihapus.');
                adminRedirect('categories');

            case 'create_sale':
                $sale = $posController->createSale($_POST['items'] ?? null);
                adminFlash(
                    'success',
                    sprintf(
                        'Transaksi #%d berhasil disimpan. Total Rp%s.',
                        $sale['id'],
                        number_format($sale['total_amount'], 0, ',', '.')
                    )
                );
                adminRedirect('pos');

            default:
                throw new InvalidArgumentException('Aksi tidak dikenal.');
        }
    } catch (InvalidArgumentException $exception) {
        adminFlash('error', $exception->getMessage());
        adminRedirect(adminActionTab($action));
    } catch (DomainException $exception) {
        adminFlash('error', $exception->getMessage());
        adminRedirect(adminActionTab($action));
    } catch (PDOException | RuntimeException $exception) {
        error_log('Mais Batik admin database operation failed ('
            . $exception::class . ', code ' . $exception->getCode() . ').');
        adminFlash('error', 'Operasi gagal karena database tidak tersedia. Periksa konfigurasi dan skema database.');
        adminRedirect(adminActionTab($action));
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$databaseError = null;
$productRows = [];
$orderRows = [];
$categoryRows = [];
$saleRows = [];
$posProducts = [];

if ($isAuthenticated) {
    try {
        $pdo = adminDatabaseConnection($projectRoot);
        $products = new ProductRepository($pdo);
        $orders = new OrderRepository($pdo);
        $categories = new CategoryRepository($pdo);
        $pos = new PosRepository($pdo);
        $productRows = $products->findAll();
        $orderRows = $orders->findAll();
        $categoryRows = $categories->findAll();
        $saleRows = $pos->findRecentSales();
        $posProducts = $pos->findActiveProducts();
    } catch (PDOException | RuntimeException $exception) {
        error_log('Mais Batik admin database connection failed ('
            . $exception::class . ', code ' . $exception->getCode() . ').');
        http_response_code(503);
        $databaseError = 'Database belum siap. Atur config/database.php lalu impor database/schema.sql.';
    }
}

$requestedTab = $_GET['tab'] ?? 'products';
$activeTab = is_string($requestedTab)
    && in_array($requestedTab, ['products', 'orders', 'categories', 'pos'], true)
    ? $requestedTab
    : 'products';
$categoryLabels = [];
foreach ($categoryRows as $category) {
    $categoryLabels[$category['slug']] = $category['name'];
}
$statusLabels = [
    'baru' => 'Baru',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel Admin — Mais Batik</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/theme.js" defer></script>
    <script src="../assets/js/admin.js" defer></script>
  </head>
  <body>
    <header class="admin-header">
      <a class="admin-brand" href="../">Mais<span>Batik</span><small>ADMIN</small></a>
      <div class="admin-header-tools">
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Aktifkan mode gelap" aria-pressed="false" title="Aktifkan mode gelap">
          <span class="theme-icon" aria-hidden="true">☾</span>
        </button>
        <?php if ($isAuthenticated): ?>
          <form method="post" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
            <input type="hidden" name="action" value="logout">
            <button class="button button-quiet" type="submit">Keluar</button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <main class="admin-shell">
      <?php if (is_array($flash)): ?>
        <div class="notice notice-<?= adminEscape($flash['type'] ?? 'error') ?>" role="status">
          <?= adminEscape($flash['message'] ?? '') ?>
        </div>
      <?php endif; ?>

      <?php if (!$isAuthenticated): ?>
        <section class="login-panel">
          <p class="eyebrow">Area terbatas</p>
          <h1>Masuk ke panel admin</h1>
          <p>Gunakan kredensial admin (default: <strong>admin</strong> / <strong>admin123</strong>, atau sesuai environment).</p>
          <form method="post" class="form-grid" data-validate>
            <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
            <input type="hidden" name="action" value="login">
            <label>Nama pengguna
              <input name="username" autocomplete="username" required maxlength="80">
            </label>
            <label>Kata sandi
              <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="button button-primary" type="submit">Masuk</button>
          </form>
          <a class="back-link" href="../">← Kembali ke katalog</a>
        </section>
      <?php else: ?>
        <section class="dashboard-heading">
          <div>
            <p class="eyebrow">Mais Batik · Workspace</p>
            <h1>Kelola data</h1>
            <p>Atur katalog produk dan pesanan pelanggan dengan aman.</p>
          </div>
          <a class="back-link" href="../">Lihat katalog <span aria-hidden="true">↗</span></a>
        </section>

        <?php if ($databaseError !== null): ?>
          <div class="notice notice-error" role="alert"><?= adminEscape($databaseError) ?></div>
        <?php else: ?>
          <nav class="admin-tabs" aria-label="Bagian pengelolaan">
            <a class="<?= $activeTab === 'products' ? 'active' : '' ?>" href="?tab=products">Produk <span><?= count($productRows) ?></span></a>
            <a class="<?= $activeTab === 'orders' ? 'active' : '' ?>" href="?tab=orders">Pesanan <span><?= count($orderRows) ?></span></a>
            <a class="<?= $activeTab === 'categories' ? 'active' : '' ?>" href="?tab=categories">Kategori <span><?= count($categoryRows) ?></span></a>
            <a class="<?= $activeTab === 'pos' ? 'active' : '' ?>" href="?tab=pos">POS</a>
          </nav>

          <?php if ($activeTab === 'products'): ?>
            <section class="management-section">
              <div class="section-title">
                <div><p class="eyebrow">Katalog</p><h2>Produk</h2></div>
                <span><?= count($productRows) ?> produk</span>
              </div>
              <details class="editor-card" open>
                <summary>Tambah produk baru</summary>
                <form method="post" class="form-grid product-form" data-validate>
                  <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                  <input type="hidden" name="action" value="create_product">
                  <label>Nama produk
                    <input name="name" required maxlength="120" minlength="2" autocomplete="off">
                  </label>
                  <label>Kategori
                    <select name="category_id" required>
                      <option value="">Pilih kategori</option>
                      <?php foreach ($categoryRows as $category): ?>
                        <option value="<?= adminEscape($category['id']) ?>"><?= adminEscape($category['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label>Varian / ukuran
                    <input name="variant" required maxlength="160" placeholder="Motif parang · ukuran M–XXL">
                  </label>
                  <label>Gambar produk
                    <select name="image_path" required>
                    <?php foreach (adminProductImages() as $path => $label): ?>
                      <option value="<?= adminEscape($path) ?>"><?= adminEscape($label) ?></option>
                    <?php endforeach; ?>
                    </select>
                  </label>
                  <label>Harga minimum (Rp)
                    <input name="price_min" type="number" inputmode="numeric" min="1" max="999999999999" step="1" required>
                  </label>
                  <label>Harga maksimum (Rp)
                    <input name="price_max" type="number" inputmode="numeric" min="1" max="999999999999" step="1" required>
                  </label>
                  <label>Stok
                    <input name="stock" type="number" inputmode="numeric" min="0" max="1000000" step="1" required value="0">
                  </label>
                  <label>Status
                    <select name="status" required>
                      <option value="active">Aktif</option>
                      <option value="inactive">Nonaktif</option>
                    </select>
                  </label>
                  <div class="form-actions"><button class="button button-primary" type="submit">Simpan produk</button></div>
                </form>
              </details>

              <div class="record-list">
                <?php if ($productRows === []): ?>
                  <p class="empty-state">Belum ada produk. Gunakan formulir di atas untuk menambahkan produk pertama.</p>
                <?php endif; ?>
                <?php foreach ($productRows as $product): ?>
                  <article class="record-card">
                    <img class="record-image" src="../<?= adminEscape($product['image_path']) ?>" alt="" width="72" height="72">
                    <div class="record-summary">
                      <span class="record-meta"><?= adminEscape($product['category_name']) ?> · <?= adminEscape($product['variant']) ?></span>
                      <h3><?= adminEscape($product['name']) ?></h3>
                      <p>Rp<?= number_format((int) $product['price_min'], 0, ',', '.') ?>–Rp<?= number_format((int) $product['price_max'], 0, ',', '.') ?> · Stok <?= adminEscape($product['stock']) ?> · <?= $product['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></p>
                    </div>
                    <div class="record-controls">
                      <details class="inline-editor">
                        <summary>Edit</summary>
                        <form method="post" class="form-grid product-form compact-form" data-validate>
                          <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                          <input type="hidden" name="action" value="update_product">
                          <input type="hidden" name="id" value="<?= adminEscape($product['id']) ?>">
                          <label>Nama produk<input name="name" required maxlength="120" minlength="2" value="<?= adminEscape($product['name']) ?>"></label>
                          <label>Kategori<select name="category_id" required><?php foreach ($categoryRows as $category): ?><option value="<?= adminEscape($category['id']) ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= adminEscape($category['name']) ?></option><?php endforeach; ?></select></label>
                          <label>Varian / ukuran<input name="variant" required maxlength="160" value="<?= adminEscape($product['variant']) ?>"></label>
                          <label>Gambar produk<select name="image_path" required><?php foreach (adminProductImages() as $path => $label): ?><option value="<?= adminEscape($path) ?>" <?= $product['image_path'] === $path ? 'selected' : '' ?>><?= adminEscape($label) ?></option><?php endforeach; ?></select></label>
                          <label>Harga minimum (Rp)<input name="price_min" type="number" min="1" max="999999999999" step="1" required value="<?= adminEscape($product['price_min']) ?>"></label>
                          <label>Harga maksimum (Rp)<input name="price_max" type="number" min="1" max="999999999999" step="1" required value="<?= adminEscape($product['price_max']) ?>"></label>
                          <label>Stok<input name="stock" type="number" min="0" max="1000000" step="1" required value="<?= adminEscape($product['stock']) ?>"></label>
                          <label>Status<select name="status" required><option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Aktif</option><option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option></select></label>
                          <div class="form-actions"><button class="button button-primary" type="submit">Simpan perubahan</button></div>
                        </form>
                      </details>
                      <button class="button button-danger delete-request" type="button" data-record-type="produk" data-record-name="<?= adminEscape($product['name']) ?>" data-record-id="<?= adminEscape($product['id']) ?>">Hapus</button>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php elseif ($activeTab === 'orders'): ?>
            <section class="management-section">
              <div class="section-title">
                <div><p class="eyebrow">Transaksi</p><h2>Pesanan</h2></div>
                <span><?= count($orderRows) ?> pesanan</span>
              </div>
              <details class="editor-card" open>
                <summary>Tambah pesanan baru</summary>
                <?php if ($productRows === []): ?>
                  <p class="empty-state">Tambahkan produk terlebih dahulu sebelum membuat pesanan.</p>
                <?php else: ?>
                  <form method="post" class="form-grid order-form" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="create_order">
                    <label>Produk
                      <select name="product_id" required data-product-select>
                        <option value="">Pilih produk</option>
                        <?php foreach ($productRows as $product): ?><option value="<?= adminEscape($product['id']) ?>" data-variant="<?= adminEscape($product['variant']) ?>" data-price="<?= adminEscape($product['price_min']) ?>"><?= adminEscape($product['name']) ?> · Rp<?= number_format((int) $product['price_min'], 0, ',', '.') ?></option><?php endforeach; ?>
                      </select>
                    </label>
                    <label>Nama pelanggan<input name="customer_name" required minlength="2" maxlength="120" autocomplete="name"></label>
                    <label>Email pelanggan<input type="email" name="customer_email" required maxlength="254" autocomplete="email"></label>
                    <label>Nomor telepon<input type="tel" name="customer_phone" required minlength="8" maxlength="24" pattern="[0-9+(). -]{8,24}" autocomplete="tel" placeholder="+62 812 3456 7890"></label>
                    <label>Varian / ukuran<input name="order_variant" required maxlength="160" data-order-variant></label>
                    <label>Jumlah<input name="quantity" type="number" min="1" max="1000000" step="1" required value="1"></label>
                    <label>Harga satuan (Rp)<input name="unit_price" type="number" min="1" max="999999999999" step="1" required data-order-price></label>
                    <label>Status
                      <select name="order_status" required><?php foreach ($statusLabels as $value => $label): ?><option value="<?= adminEscape($value) ?>"><?= adminEscape($label) ?></option><?php endforeach; ?></select>
                    </label>
                    <label class="full-width">Catatan<textarea name="notes" maxlength="500" rows="2"></textarea></label>
                    <div class="form-actions"><button class="button button-primary" type="submit">Simpan pesanan</button></div>
                  </form>
                <?php endif; ?>
              </details>

              <div class="record-list">
                <?php if ($orderRows === []): ?>
                  <p class="empty-state">Belum ada pesanan.</p>
                <?php endif; ?>
                <?php foreach ($orderRows as $order): ?>
                  <article class="record-card order-record">
                    <div class="order-mark" aria-hidden="true">#<?= adminEscape($order['id']) ?></div>
                    <div class="record-summary">
                      <span class="record-meta"><?= adminEscape($order['customer_name']) ?> · <?= adminEscape($order['created_at']) ?></span>
                      <h3><?= adminEscape($order['product_name']) ?> <span class="status-pill status-<?= adminEscape($order['status']) ?>"><?= adminEscape($statusLabels[$order['status']] ?? $order['status']) ?></span></h3>
                      <p><?= adminEscape($order['customer_email']) ?> · <?= adminEscape($order['customer_phone']) ?> · <?= adminEscape($order['variant']) ?> · <?= adminEscape($order['quantity']) ?> × Rp<?= number_format((int) $order['unit_price'], 0, ',', '.') ?></p>
                    </div>
                    <div class="record-controls">
                      <details class="inline-editor">
                        <summary>Edit</summary>
                        <form method="post" class="form-grid order-form compact-form" data-validate>
                          <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                          <input type="hidden" name="action" value="update_order">
                          <input type="hidden" name="id" value="<?= adminEscape($order['id']) ?>">
                          <label>Produk<select name="product_id" required data-product-select><?php foreach ($productRows as $product): ?><option value="<?= adminEscape($product['id']) ?>" data-variant="<?= adminEscape($product['variant']) ?>" data-price="<?= adminEscape($product['price_min']) ?>" <?= (int) $order['product_id'] === (int) $product['id'] ? 'selected' : '' ?>><?= adminEscape($product['name']) ?></option><?php endforeach; ?></select></label>
                          <label>Nama pelanggan<input name="customer_name" required minlength="2" maxlength="120" value="<?= adminEscape($order['customer_name']) ?>"></label>
                          <label>Email pelanggan<input type="email" name="customer_email" required maxlength="254" value="<?= adminEscape($order['customer_email']) ?>"></label>
                          <label>Nomor telepon<input type="tel" name="customer_phone" required minlength="8" maxlength="24" pattern="[0-9+(). -]{8,24}" value="<?= adminEscape($order['customer_phone']) ?>"></label>
                          <label>Varian / ukuran<input name="order_variant" required maxlength="160" data-order-variant value="<?= adminEscape($order['variant']) ?>"></label>
                          <label>Jumlah<input name="quantity" type="number" min="1" max="1000000" step="1" required value="<?= adminEscape($order['quantity']) ?>"></label>
                          <label>Harga satuan (Rp)<input name="unit_price" type="number" min="1" max="999999999999" step="1" required data-order-price value="<?= adminEscape($order['unit_price']) ?>"></label>
                          <label>Status<select name="order_status" required><?php foreach ($statusLabels as $value => $label): ?><option value="<?= adminEscape($value) ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= adminEscape($label) ?></option><?php endforeach; ?></select></label>
                          <label class="full-width">Catatan<textarea name="notes" maxlength="500" rows="2"><?= adminEscape($order['notes']) ?></textarea></label>
                          <div class="form-actions"><button class="button button-primary" type="submit">Simpan perubahan</button></div>
                        </form>
                      </details>
                      <button class="button button-danger delete-request" type="button" data-record-type="pesanan" data-record-name="<?= adminEscape($order['product_name']) ?>" data-record-id="<?= adminEscape($order['id']) ?>">Hapus</button>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php elseif ($activeTab === 'categories'): ?>
            <section class="management-section">
              <div class="section-title">
                <div><p class="eyebrow">Pengaturan katalog</p><h2>Kategori</h2></div>
                <span><?= count($categoryRows) ?> kategori</span>
              </div>
              <details class="editor-card" open>
                <summary>Tambah kategori</summary>
                <form method="post" class="form-grid" data-validate>
                  <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                  <input type="hidden" name="action" value="create_category">
                  <label>Nama kategori<input name="name" required maxlength="120"></label>
                  <label>Slug URL<input name="slug" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="contoh: batik-pria"></label>
                  <div class="form-actions"><button class="button button-primary" type="submit">Simpan kategori</button></div>
                </form>
              </details>

              <div class="record-list">
                <?php if ($categoryRows === []): ?>
                  <p class="empty-state">Belum ada kategori.</p>
                <?php endif; ?>
                <?php foreach ($categoryRows as $category): ?>
                  <article class="record-card category-record">
                    <div class="record-summary">
                      <span class="record-meta">Slug: <?= adminEscape($category['slug']) ?></span>
                      <h3><?= adminEscape($category['name']) ?></h3>
                    </div>
                    <div class="record-controls">
                      <details class="inline-editor">
                        <summary>Edit</summary>
                        <form method="post" class="form-grid compact-form" data-validate>
                          <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                          <input type="hidden" name="action" value="update_category">
                          <input type="hidden" name="id" value="<?= adminEscape($category['id']) ?>">
                          <label>Nama kategori<input name="name" required maxlength="120" value="<?= adminEscape($category['name']) ?>"></label>
                          <label>Slug URL<input name="slug" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= adminEscape($category['slug']) ?>"></label>
                          <div class="form-actions"><button class="button button-primary" type="submit">Simpan perubahan</button></div>
                        </form>
                      </details>
                      <button class="button button-danger delete-request" type="button" data-record-type="kategori" data-record-name="<?= adminEscape($category['name']) ?>" data-record-id="<?= adminEscape($category['id']) ?>">Hapus</button>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php else: ?>
            <section class="management-section">
              <div class="section-title">
                <div><p class="eyebrow">Kasir</p><h2>Point of Sale</h2></div>
                <span><?= count($saleRows) ?> transaksi terbaru</span>
              </div>

              <details class="editor-card" open>
                <summary>Buat transaksi penjualan</summary>
                <?php if ($posProducts === []): ?>
                  <p class="empty-state">Tidak ada produk aktif dengan stok tersedia untuk dijual.</p>
                <?php else: ?>
                  <form method="post" class="pos-form" data-pos-form>
                    <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="create_sale">
                    <div class="pos-items" data-pos-items>
                      <div class="pos-item-row" data-pos-row>
                        <label>Produk
                          <select name="items[0][product_id]" required data-pos-product-select>
                            <option value="">Pilih produk</option>
                            <?php foreach ($posProducts as $product): ?>
                              <option value="<?= adminEscape($product['id']) ?>" data-stock="<?= adminEscape($product['stock']) ?>" data-price="<?= adminEscape($product['price_min']) ?>">
                                <?= adminEscape($product['name']) ?> · stok <?= adminEscape($product['stock']) ?> · Rp<?= number_format((int) $product['price_min'], 0, ',', '.') ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </label>
                        <label>Jumlah
                          <input name="items[0][quantity]" type="number" min="1" max="1000000" step="1" value="1" required data-pos-quantity>
                        </label>
                        <span class="pos-line-total" data-pos-line-total>Harga memakai harga katalog</span>
                        <button class="button button-quiet" type="button" data-remove-pos-item disabled>Hapus baris</button>
                      </div>
                    </div>
                    <div class="pos-form-actions">
                      <button class="button button-quiet" type="button" data-add-pos-item>Tambah produk</button>
                      <button class="button button-primary" type="submit">Simpan transaksi</button>
                    </div>
                    <p class="pos-total" data-pos-total>Total: Rp0</p>
                    <p class="form-help">Harga transaksi diambil dari harga minimum di katalog. Stok diperiksa dan dikurangi saat transaksi disimpan.</p>
                  </form>
                <?php endif; ?>
              </details>

              <div class="section-title">
                <div><p class="eyebrow">Riwayat</p><h2>Transaksi POS terbaru</h2></div>
              </div>
              <div class="record-list">
                <?php if ($saleRows === []): ?>
                  <p class="empty-state">Belum ada transaksi POS.</p>
                <?php endif; ?>
                <?php foreach ($saleRows as $sale): ?>
                  <article class="record-card order-record">
                    <div class="order-mark" aria-hidden="true">#<?= adminEscape($sale['id']) ?></div>
                    <div class="record-summary">
                      <span class="record-meta"><?= adminEscape($sale['created_at']) ?> · <?= adminEscape($sale['item_count']) ?> baris produk</span>
                      <h3>Transaksi POS #<?= adminEscape($sale['id']) ?></h3>
                      <p>Total penjualan Rp<?= number_format((int) $sale['total_amount'], 0, ',', '.') ?></p>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </main>

    <dialog class="delete-dialog" id="delete-dialog" aria-labelledby="delete-title">
      <form method="post" id="delete-form">
        <input type="hidden" name="csrf_token" value="<?= adminEscape($csrfToken) ?>">
        <input type="hidden" name="action" id="delete-action">
        <input type="hidden" name="id" id="delete-id">
        <p class="eyebrow">Konfirmasi tindakan</p>
        <h2 id="delete-title">Hapus data ini?</h2>
        <p id="delete-message">Data yang dihapus tidak dapat dipulihkan.</p>
        <div class="dialog-actions">
          <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
          <button class="button button-danger" type="submit">Ya, hapus</button>
        </div>
      </form>
    </dialog>
  </body>
</html>
