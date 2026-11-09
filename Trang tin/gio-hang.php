<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/page-helpers.php';

$pdo = null;
$databaseError = null;
$cartMessage = null;
$cartError = null;

try {
    $pdo = getDatabaseConnection();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $databaseError = 'Không thể kết nối cơ sở dữ liệu.';
}

$rawCart = $_SESSION['cart'] ?? [];
$cart = [];

foreach ($rawCart as $id => $item) {
    $productId = (int) $id;
    $quantity = is_array($item) ? (int) ($item['quantity'] ?? 0) : (int) $item;

    if ($productId > 0 && $quantity > 0) {
        $cart[$productId] = $quantity;
    }
}

$_SESSION['cart'] = $cart;

function cartProductStock(?PDO $pdo, int $productId): int
{
    if (!$pdo instanceof PDO || $productId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare('SELECT SoLuong FROM tbl_sanpham WHERE IdSanPham = :id LIMIT 1');
    $statement->execute(['id' => $productId]);

    return max(0, (int) ($statement->fetchColumn() ?: 0));
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'add' && $requestMethod === 'GET') {
    $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $stock = cartProductStock($pdo, $productId);

    if ($stock > 0) {
        $_SESSION['cart'][$productId] = min($stock, (int) ($_SESSION['cart'][$productId] ?? 0) + 1);
        header('Location: gio-hang.php?added=1');
        exit;
    }

    header('Location: gio-hang.php?error=unavailable');
    exit;
}

if ($requestMethod === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $cartError = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif ($action === 'add') {
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $stock = cartProductStock($pdo, $productId);

        if ($stock > 0) {
            $_SESSION['cart'][$productId] = min(
                $stock,
                (int) ($_SESSION['cart'][$productId] ?? 0) + $quantity
            );
            header('Location: gio-hang.php?added=1');
            exit;
        }

        $cartError = 'Sản phẩm hiện không còn hàng.';
    } elseif ($action === 'update') {
        foreach ((array) ($_POST['quantities'] ?? []) as $id => $quantity) {
            $productId = (int) $id;
            $requestedQuantity = (int) $quantity;
            $stock = cartProductStock($pdo, $productId);

            if ($requestedQuantity <= 0 || $stock <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = min($requestedQuantity, $stock);
            }
        }

        header('Location: gio-hang.php?updated=1');
        exit;
    } elseif ($action === 'remove') {
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        unset($_SESSION['cart'][$productId]);
        header('Location: gio-hang.php?removed=1');
        exit;
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: gio-hang.php?cleared=1');
        exit;
    }
}

if (isset($_GET['added'])) {
    $cartMessage = 'Đã thêm sản phẩm vào giỏ hàng.';
} elseif (isset($_GET['updated'])) {
    $cartMessage = 'Đã cập nhật số lượng sản phẩm.';
} elseif (isset($_GET['removed'])) {
    $cartMessage = 'Đã xóa sản phẩm khỏi giỏ hàng.';
} elseif (isset($_GET['cleared'])) {
    $cartMessage = 'Đã xóa toàn bộ giỏ hàng.';
} elseif (isset($_GET['error'])) {
    $cartError = 'Sản phẩm không tồn tại hoặc đã hết hàng.';
}

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$subtotal = 0.0;

if ($pdo instanceof PDO && $cart !== []) {
    $productIds = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $statement = $pdo->prepare(
        "SELECT
            IdSanPham,
            TenSanPham,
            HinhAnh,
            DonGia,
            TiLeGiamGia,
            SoLuong,
            (DonGia - (DonGia * TiLeGiamGia / 100)) AS GiaSauGiam
         FROM tbl_sanpham
         WHERE IdSanPham IN ({$placeholders})"
    );
    $statement->execute($productIds);

    foreach ($statement->fetchAll() as $product) {
        $productId = (int) $product['IdSanPham'];
        $quantity = min((int) ($cart[$productId] ?? 0), (int) $product['SoLuong']);

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $product['CartQuantity'] = $quantity;
        $product['LineTotal'] = (float) $product['GiaSauGiam'] * $quantity;
        $subtotal += $product['LineTotal'];
        $cartItems[] = $product;
    }
}

$sharedData = loadSharedPageData($pdo);
$brands = $sharedData['brands'];
$categories = $sharedData['categories'];
$categoryHighlights = $sharedData['categoryHighlights'];
$keyword = '';
$brandId = null;
$categoryId = null;
$priceRange = '';
$cartCount = calculateCartCount($_SESSION['cart'] ?? []);
$pageTitle = 'Giỏ hàng - LaptopShop';
$pageDescription = 'Kiểm tra và cập nhật các sản phẩm trong giỏ hàng của bạn.';

require __DIR__ . '/template/header.php';
?>
<main class="page-main">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Đường dẫn">
      <a href="index.php">Trang chủ</a><span>›</span><span>Giỏ hàng</span>
    </nav>

    <div class="page-heading">
      <div><span>Đơn hàng của bạn</span><h1>Giỏ hàng</h1></div>
      <a href="index.php#products">Tiếp tục mua hàng</a>
    </div>

    <?php if ($cartMessage !== null): ?>
      <div class="alert alert-success"><?= escape($cartMessage) ?></div>
    <?php endif; ?>
    <?php if ($cartError !== null): ?>
      <div class="alert alert-danger"><?= escape($cartError) ?></div>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
      <div class="alert alert-warning"><?= escape($databaseError) ?></div>
    <?php endif; ?>

    <?php if ($cartItems === []): ?>
      <section class="empty-state page-state">
        <i class="bi bi-cart3"></i>
        <h2>Giỏ hàng đang trống</h2>
        <p>Hãy chọn một chiếc laptop phù hợp với nhu cầu của bạn.</p>
        <a class="btn btn-primary" href="index.php#products">Xem sản phẩm</a>
      </section>
    <?php else: ?>
      <div class="cart-layout">
        <section class="cart-card">
          <form action="gio-hang.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
            <input type="hidden" name="action" value="update" />

            <div class="cart-list">
              <?php foreach ($cartItems as $item): ?>
                <article class="cart-item">
                  <a class="cart-item__image" href="chi-tiet-san-pham.php?id=<?= (int) $item['IdSanPham'] ?>">
                    <img src="<?= escape(resolveProductImage($item['HinhAnh'], $item['TenSanPham'])) ?>" alt="<?= escape($item['TenSanPham']) ?>" />
                  </a>
                  <div class="cart-item__info">
                    <h2><a href="chi-tiet-san-pham.php?id=<?= (int) $item['IdSanPham'] ?>"><?= escape($item['TenSanPham']) ?></a></h2>
                    <span>Còn <?= (int) $item['SoLuong'] ?> sản phẩm</span>
                  </div>
                  <div class="cart-item__price">
                    <strong><?= formatCurrency((float) $item['GiaSauGiam']) ?></strong>
                    <?php if ((float) $item['TiLeGiamGia'] > 0): ?><del><?= formatCurrency((float) $item['DonGia']) ?></del><?php endif; ?>
                  </div>
                  <label class="cart-item__quantity">
                    <span>Số lượng</span>
                    <input class="form-control" type="number" name="quantities[<?= (int) $item['IdSanPham'] ?>]" min="0" max="<?= (int) $item['SoLuong'] ?>" value="<?= (int) $item['CartQuantity'] ?>" />
                  </label>
                  <strong class="cart-item__total"><?= formatCurrency((float) $item['LineTotal']) ?></strong>
                  <button
                    class="cart-item__remove"
                    type="submit"
                    name="remove_item"
                    value="<?= (int) $item['IdSanPham'] ?>"
                    formaction="gio-hang.php"
                    formmethod="post"
                    aria-label="Xóa <?= escape($item['TenSanPham']) ?>"
                    onclick="this.form.action.value='remove'; const input=document.createElement('input'); input.type='hidden'; input.name='id'; input.value=this.value; this.form.appendChild(input);"
                  >
                    <i class="bi bi-trash3"></i>
                  </button>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="cart-card__actions">
              <button class="btn btn-outline-secondary" type="submit">Cập nhật giỏ hàng</button>
            </div>
          </form>
        </section>

        <aside class="cart-summary">
          <h2>Tóm tắt đơn hàng</h2>
          <div><span>Tạm tính</span><strong><?= formatCurrency($subtotal) ?></strong></div>
          <div><span>Phí vận chuyển</span><strong>Miễn phí</strong></div>
          <div class="cart-summary__total"><span>Tổng cộng</span><strong><?= formatCurrency($subtotal) ?></strong></div>
          <button class="btn cart-summary__checkout" type="button" disabled>Thanh toán — đang hoàn thiện</button>
          <p>Trang thanh toán sẽ được bổ sung ở bước tiếp theo.</p>
          <form action="gio-hang.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
            <input type="hidden" name="action" value="clear" />
            <button class="cart-summary__clear" type="submit">Xóa toàn bộ giỏ hàng</button>
          </form>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/template/footer.php'; ?>
