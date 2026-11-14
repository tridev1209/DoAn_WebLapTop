<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/page-helpers.php';
require_once __DIR__ . '/config/auth.php';

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
    if (!($pdo instanceof PDO) || $productId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare('SELECT SoLuong FROM tbl_sanpham WHERE IdSanPham = :id LIMIT 1');
    $statement->execute(['id' => $productId]);

    return max(0, (int) ($statement->fetchColumn() ?: 0));
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_POST['action'] ?? ($_GET['action'] ?? ''));

if ($action === 'add' && $requestMethod === 'GET') {
    $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $stock = cartProductStock($pdo, $productId);

    if ($stock > 0) {
        $_SESSION['cart'][$productId] = min($stock, (int) ($_SESSION['cart'][$productId] ?? 0) + 1);
        header('Location: gio-hang.php?added=1');
        exit();
    }

    header('Location: gio-hang.php?error=unavailable');
    exit();
}

if ($requestMethod === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $cartError = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif ($action === 'add' || $action === 'buy_now') {
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $stock = cartProductStock($pdo, $productId);

        if ($stock > 0) {
            $_SESSION['cart'][$productId] = min(
                $stock,
                (int) ($_SESSION['cart'][$productId] ?? 0) + $quantity
            );
            $redirect = $action === 'buy_now'
                ? 'gio-hang.php?buy_now=' . $productId
                : 'gio-hang.php?added=1';
            header('Location: ' . $redirect);
            exit();
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
        exit();
    } elseif ($action === 'remove') {
        $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        unset($_SESSION['cart'][$productId]);
        header('Location: gio-hang.php?removed=1');
        exit();
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: gio-hang.php?cleared=1');
        exit();
    }
}

$buyNowProductId = filter_input(INPUT_GET, 'buy_now', FILTER_VALIDATE_INT) ?: 0;

if ($buyNowProductId > 0) {
    $cartMessage = 'Sản phẩm đã sẵn sàng. Vui lòng kiểm tra và tiếp tục đặt hàng.';
} elseif (isset($_GET['added'])) {
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
$totalOriginal = 0.0;
$totalItemsCount = 0;

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
        $product['OriginalLineTotal'] = (float) $product['DonGia'] * $quantity;

        $subtotal += $product['LineTotal'];
        $totalOriginal += $product['OriginalLineTotal'];
        $totalItemsCount += $quantity;
        $cartItems[] = $product;
    }
}

$discountAmount = max(0.0, $totalOriginal - $subtotal);

$authUser = authCurrentUser();
$customerProfile = [
    'name' => (string) ($authUser['name'] ?? ''),
    'phone' => '',
    'email' => (string) ($authUser['email'] ?? ''),
    'address' => ''
];

if ($authUser !== null && $pdo instanceof PDO) {
    try {
        $profileStatement = $pdo->prepare(
            'SELECT TenNguoiDung, SoDienThoai, Email, DiaChi
             FROM tbl_nguoidung
             WHERE MaNguoiDung = :id
             LIMIT 1'
        );
        $profileStatement->execute(['id' => (int) $authUser['id']]);
        $profile = $profileStatement->fetch();

        if (is_array($profile)) {
            $customerProfile = [
                'name' => (string) ($profile['TenNguoiDung'] ?? ''),
                'phone' => (string) ($profile['SoDienThoai'] ?? ''),
                'email' => (string) ($profile['Email'] ?? ''),
                'address' => (string) ($profile['DiaChi'] ?? '')
            ];
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
    }
}

$orderForm = [
    'name' => trim((string) ($_POST['customer_name'] ?? $customerProfile['name'])),
    'phone' => trim((string) ($_POST['customer_phone'] ?? $customerProfile['phone'])),
    'email' => trim((string) ($_POST['customer_email'] ?? $customerProfile['email'])),
    'address' => trim((string) ($_POST['customer_address'] ?? $customerProfile['address'])),
    'note' => trim((string) ($_POST['order_note'] ?? '')),
    'payment_method' => (string) ($_POST['payment_method'] ?? 'cod')
];

if ($requestMethod === 'POST' && $action === 'place_order' && $cartError === null) {
    if ($authUser === null) {
        authSetFlash('error', 'Vui lòng đăng nhập trước khi đặt hàng.');
        header('Location: ' . authUrlWithView('gio-hang.php', 'login'));
        exit();
    }

    $selectedProductIds = array_values(
        array_filter(array_map('intval', (array) ($_POST['selected_ids'] ?? [])))
    );
    $orderItems = array_values(
        array_filter($cartItems, function (array $item) use ($selectedProductIds): bool {
            return in_array((int) $item['IdSanPham'], $selectedProductIds, true);
        })
    );
    $orderTotal = array_reduce($orderItems, function (float $total, array $item): float {
        return $total + (float) $item['LineTotal'];
    }, 0.0);

    if ((string) ($_POST['checkout_confirmation'] ?? '') !== '1') {
        $cartError = 'Vui lòng hoàn tất đủ ba bước trước khi đặt hàng.';
    } elseif ($orderItems === []) {
        $cartError = 'Vui lòng chọn ít nhất một sản phẩm để đặt hàng.';
    } elseif (mb_strlen($orderForm['name']) < 2) {
        $cartError = 'Vui lòng nhập họ tên người nhận.';
    } elseif (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $orderForm['phone'])) {
        $cartError = 'Số điện thoại người nhận không hợp lệ.';
    } elseif (!filter_var($orderForm['email'], FILTER_VALIDATE_EMAIL)) {
        $cartError = 'Email người nhận không hợp lệ.';
    } elseif (mb_strlen($orderForm['address']) < 8) {
        $cartError = 'Vui lòng nhập địa chỉ giao hàng đầy đủ.';
    } elseif ($orderForm['payment_method'] !== 'cod') {
        $cartError = 'Hình thức thanh toán không hợp lệ.';
    } elseif (!($pdo instanceof PDO)) {
        $cartError = 'Chưa thể kết nối cơ sở dữ liệu để tạo đơn hàng.';
    } else {
        try {
            $pdo->beginTransaction();
            $orderNote = implode("\n", [
                'Người nhận: ' . $orderForm['name'],
                'Số điện thoại: ' . $orderForm['phone'],
                'Email: ' . $orderForm['email'],
                'Địa chỉ: ' . $orderForm['address'],
                'Thanh toán: COD - Thanh toán khi nhận hàng',
                'Ghi chú: ' . ($orderForm['note'] !== '' ? $orderForm['note'] : 'Không có')
            ]);
            $orderStatement = $pdo->prepare(
                'INSERT INTO tbl_donhang (MaNguoiDung, PhiVanChuyen, TongTien, TrangThaiDonHang, GhiChu)
                 VALUES (:user_id, 0, :total, \'Chờ xác nhận\', :note)'
            );
            $orderStatement->execute([
                'user_id' => (int) $authUser['id'],
                'total' => $orderTotal,
                'note' => $orderNote
            ]);
            $orderId = (int) $pdo->lastInsertId();
            $detailStatement = $pdo->prepare(
                'INSERT INTO tbl_chitietdonhang
                    (MaDonHang, MaSanPham, TenSanPham, GiaBan, SoLuong, ThanhTien)
                 VALUES
                    (:order_id, :product_id, :name, :price, :quantity, :line_total)'
            );
            $stockStatement = $pdo->prepare(
                'UPDATE tbl_sanpham
                 SET SoLuong = SoLuong - :deduct_quantity
                 WHERE IdSanPham = :product_id AND SoLuong >= :required_quantity'
            );

            foreach ($orderItems as $item) {
                $quantity = (int) $item['CartQuantity'];
                $stockStatement->execute([
                    'deduct_quantity' => $quantity,
                    'required_quantity' => $quantity,
                    'product_id' => (int) $item['IdSanPham']
                ]);

                if ($stockStatement->rowCount() !== 1) {
                    throw new RuntimeException('Sản phẩm không còn đủ số lượng trong kho.');
                }

                $detailStatement->execute([
                    'order_id' => $orderId,
                    'product_id' => (int) $item['IdSanPham'],
                    'name' => (string) $item['TenSanPham'],
                    'price' => (float) $item['GiaSauGiam'],
                    'quantity' => $quantity,
                    'line_total' => (float) $item['LineTotal']
                ]);
            }

            $pdo->commit();
            foreach ($selectedProductIds as $selectedProductId) {
                unset($_SESSION['cart'][$selectedProductId]);
            }
            header('Location: don-hang-cua-toi.php?placed=' . $orderId);
            exit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($exception->getMessage());
            $cartError = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Chưa thể tạo đơn hàng lúc này. Vui lòng thử lại.';
        }
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
$pageTitle = 'Giỏ hàng của bạn — Trislaptop';
$pageDescription = 'Kiểm tra và cập nhật các sản phẩm công nghệ trong giỏ hàng của bạn.';

require __DIR__ . '/template/header.php';
?>

<main class="page-main cart-page-pro">
  <div class="container">
    <!-- Breadcrumb -->
    <nav class="page-breadcrumb" aria-label="Đường dẫn">
      <a href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
      <span class="separator">/</span>
      <span class="current">Giỏ hàng</span>
    </nav>

    <!-- Thông báo hệ thống -->
   
    <?php if ($cartError !== null): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= escape($cartError) ?>
      </div>
    <?php endif; ?>
    <?php if ($databaseError !== null): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-database-exclamation me-2"></i><?= escape($databaseError) ?>
      </div>
    <?php endif; ?>
    <?php if ($cartMessage !== null): ?>
      <div class="alert alert-success alert-dismissible fade show" role="status">
        <i class="bi bi-check-circle-fill me-2"></i><?= escape($cartMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
      </div>
    <?php endif; ?>

    <?php if ($cartItems === []): ?>
      <!-- Trạng thái giỏ hàng trống -->
      <section class="empty-state-modern">
        <div class="empty-icon-wrap">
          <i class="bi bi-cartX"></i>
        </div>
        <h2>Giỏ hàng đang trống</h2>
        <p>Bạn chưa chọn được sản phẩm công nghệ hoặc chiếc laptop nào ưng ý.</p>
        <a class="btn btn-primary btn-lg" href="index.php#products">
          <i class="bi bi-bag-plus"></i> Khám phá sản phẩm ngay
        </a>
      </section>
    <?php else: ?>
      <ol class="checkout-progress" aria-label="Tiến trình đặt hàng">
        <li class="is-active" data-checkout-progress="1"><span>1</span>Giỏ hàng</li>
        <li data-checkout-progress="2"><span>2</span>Thông tin giao hàng</li>
        <li data-checkout-progress="3"><span>3</span>Xác nhận đơn hàng</li>
      </ol>

      <!-- Bố cục Giỏ hàng chuẩn giao diện chuyên nghiệp -->
      <div
        class="cart-layout-pro"
        data-checkout-root
        data-authenticated="<?= $authUser !== null ? '1' : '0' ?>"
        data-initial-step="<?= $requestMethod === 'POST' && $action === 'place_order' && $cartError !== null
            ? '2'
            : '1' ?>"
      >
        
        <!-- Cột trái: Danh sách sản phẩm -->
        <section class="cart-left-column">
          <div class="checkout-step-panel" data-checkout-panel="1">
            <div class="checkout-step-heading">
              <div>
                <span>BƯỚC 1</span>
                <h2>Kiểm tra giỏ hàng</h2>
              </div>
              <strong><?= $totalItemsCount ?> sản phẩm</strong>
            </div>

            <form action="gio-hang.php" method="post" id="cart-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
            <input type="hidden" name="action" value="update" />

            <div class="cart-items-container">
              <?php foreach ($cartItems as $item): ?>
                <div class="cart-item-card-pro" data-id="<?= (int) $item['IdSanPham'] ?>">
                  
                  <div class="cart-item-main-row">
                    <!-- Checkbox chọn sản phẩm (truyền dữ liệu giá gốc, giá giảm qua dataset để JS tính toán) -->
                    <div class="item-select-wrap">
                      <input 
                        class="form-check-input item-checkbox" 
                        type="checkbox" 
                      name="selected_ids[]"
                      value="<?= (int) $item['IdSanPham'] ?>"
                      form="checkout-form"
                        <?= $buyNowProductId === 0 || $buyNowProductId === (int) $item['IdSanPham']
                            ? 'checked'
                            : '' ?>
                        aria-label="Chọn sản phẩm này" 
                        data-original-price="<?= (float) $item['OriginalLineTotal'] ?>"
                        data-final-price="<?= (float) $item['LineTotal'] ?>"
                        onchange="updateCartSummary()"
                      />
                    </div>

                    <!-- Ảnh sản phẩm -->
                    <a class="item-thumb-pro" href="chi-tiet-san-pham.php?id=<?= (int) $item[
                        'IdSanPham'
                    ] ?>">
                      <img src="<?= escape(
                          resolveProductImage($item['HinhAnh'], $item['TenSanPham'])
                      ) ?>" alt="<?= escape($item['TenSanPham']) ?>" loading="lazy" />
                    </a>

                    <!-- Tiêu đề & Tồn kho -->
                    <div class="item-info-pro">
                      <h3>
                        <a href="chi-tiet-san-pham.php?id=<?= (int) $item['IdSanPham'] ?>">
                          <?= escape($item['TenSanPham']) ?>
                        </a>
                      </h3>
                      <span class="stock-badge">Còn <?= (int) $item[
                          'SoLuong'
                      ] ?> sản phẩm trong kho</span>
                    </div>

                    <!-- Giá tiền -->
                    <div class="item-price-pro">
                      <strong class="price-current"><?= formatCurrency(
                          (float) $item['GiaSauGiam']
                      ) ?></strong>
                      <?php if ((float) $item['TiLeGiamGia'] > 0): ?>
                        <span class="price-old">Giá niêm yết: <?= formatCurrency(
                            (float) $item['DonGia']
                        ) ?></span>
                      <?php endif; ?>
                    </div>

                    <!-- Bộ tăng giảm số lượng -->
                    <div class="item-qty-pro">
                      <div class="qty-stepper-box">
                        <button type="button" class="stepper-btn" onclick="adjustQty(this, -1)">−</button>
                        <input 
                          class="form-control stepper-input" 
                          type="number" 
                          name="quantities[<?= (int) $item['IdSanPham'] ?>]" 
                          min="1" 
                          max="<?= (int) $item['SoLuong'] ?>" 
                          value="<?= (int) $item['CartQuantity'] ?>" 
                          onchange="document.getElementById('cart-form').submit();"
                        />
                        <button type="button" class="stepper-btn" onclick="adjustQty(this, 1)">+</button>
                      </div>
                    </div>

                    <!-- Nút xóa sản phẩm -->
                    <div class="item-remove-pro">
                      <button
                        class="btn-delete-item"
                        type="submit"
                        name="remove_item"
                        value="<?= (int) $item['IdSanPham'] ?>"
                        formaction="gio-hang.php"
                        formmethod="post"
                        title="Xóa sản phẩm"
                        onclick="this.form.action.value='remove'; const input=document.createElement('input'); input.type='hidden'; input.name='id'; input.value=this.value; this.form.appendChild(input);"
                      >
                        <i class="bi bi-trash3"></i>
                      </button>
                    </div>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>

           
            </form>

            <p class="checkout-step-error" data-checkout-selection-error hidden>
              <i class="bi bi-exclamation-circle"></i> Vui lòng chọn ít nhất một sản phẩm.
            </p>
            <div class="checkout-step-actions checkout-step-actions--end">
              <button class="checkout-next-button" type="button" data-checkout-next="2">
                Tiếp theo <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>

          <div class="checkout-step-panel" data-checkout-panel="2" hidden>
          <section class="checkout-customer-card" aria-labelledby="checkout-customer-title">
            <div class="checkout-customer-card__heading">
              <div>
                <span class="checkout-customer-card__eyebrow">THÔNG TIN GIAO HÀNG</span>
                <h2 id="checkout-customer-title">Thông tin người đặt hàng</h2>
              </div>
              <i class="bi bi-shield-check" aria-hidden="true"></i>
            </div>

            <?php if ($authUser === null): ?>
              <div class="checkout-login-notice">
                <i class="bi bi-person-lock"></i>
                <span>Bạn cần đăng nhập để đặt hàng và theo dõi trạng thái đơn.</span>
                <button type="button" data-open-login>Đăng nhập</button>
              </div>
            <?php endif; ?>

            <form id="checkout-form" action="gio-hang.php" method="post" class="checkout-customer-form">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
              <input type="hidden" name="action" value="place_order" />
              <input type="hidden" name="checkout_confirmation" id="checkout-confirmation" value="0" />

              <div class="checkout-field checkout-field--wide">
                <label for="customer-name">Họ và tên <span>*</span></label>
                <input id="customer-name" name="customer_name" type="text" value="<?= escape(
                    $orderForm['name']
                ) ?>" autocomplete="name" placeholder="Nhập họ và tên người nhận" minlength="2" required />
              </div>
              <div class="checkout-field">
                <label for="customer-phone">Số điện thoại <span>*</span></label>
                <input id="customer-phone" name="customer_phone" type="tel" value="<?= escape(
                    $orderForm['phone']
                ) ?>" autocomplete="tel" placeholder="Ví dụ: 0912345678" pattern="(0|\+84)[0-9]{9,10}" required />
              </div>
              <div class="checkout-field">
                <label for="customer-email">Email <span>*</span></label>
                <input id="customer-email" name="customer_email" type="email" value="<?= escape(
                    $orderForm['email']
                ) ?>" autocomplete="email" placeholder="vidu@email.com" required />
              </div>
              <div class="checkout-field checkout-field--wide">
                <label for="customer-address">Địa chỉ nhận hàng <span>*</span></label>
                <input id="customer-address" name="customer_address" type="text" value="<?= escape(
                    $orderForm['address']
                ) ?>" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" minlength="8" required />
              </div>
              <div class="checkout-field checkout-field--wide">
                <label for="order-note">Ghi chú đơn hàng <small>(không bắt buộc)</small></label>
                <textarea id="order-note" name="order_note" rows="3" placeholder="Thời gian nhận hàng hoặc yêu cầu khác"><?= escape(
                    $orderForm['note']
                ) ?></textarea>
              </div>

              <p class="checkout-privacy-note">
                <i class="bi bi-lock"></i> Thông tin chỉ được sử dụng để xác nhận và giao đơn hàng.
              </p>

              <fieldset class="checkout-payment-method">
                <legend>Chọn hình thức thanh toán</legend>
                <label class="checkout-payment-option">
                  <input type="radio" name="payment_method" value="cod" data-validation-label="hình thức thanh toán" checked required />
                  <span class="checkout-payment-option__icon"><i class="bi bi-cash-stack"></i></span>
                  <span>
                    <strong>COD</strong>
                    <small>Thanh toán khi nhận hàng</small>
                  </span>
                </label>
              </fieldset>
            </form>
          </section>

            <div class="checkout-step-actions">
              <button class="checkout-back-button" type="button" data-checkout-back="1">
                <i class="bi bi-arrow-left"></i> Quay lại giỏ hàng
              </button>
              <button class="checkout-next-button" type="button" data-checkout-next="3">
                Tiếp theo <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>

          <div class="checkout-step-panel" data-checkout-panel="3" hidden>
            <section class="order-confirmation-card" aria-labelledby="order-confirmation-title">
              <div class="checkout-customer-card__heading">
                <div>
                  <span class="checkout-customer-card__eyebrow">BƯỚC XÁC NHẬN</span>
                  <h2 id="order-confirmation-title">Kiểm tra lại đơn hàng</h2>
                </div>
                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
              </div>

              <div class="order-confirmation-section">
                <h3><i class="bi bi-box-seam"></i> Sản phẩm đã chọn</h3>
                <div class="order-confirmation-products">
                  <?php foreach ($cartItems as $item): ?>
                    <div class="order-confirmation-product" data-confirm-product="<?= (int) $item[
                        'IdSanPham'
                    ] ?>">
                      <img src="<?= escape(
                          resolveProductImage($item['HinhAnh'], $item['TenSanPham'])
                      ) ?>" alt="" />
                      <div>
                        <strong><?= escape($item['TenSanPham']) ?></strong>
                        <span>Số lượng: <?= (int) $item['CartQuantity'] ?></span>
                      </div>
                      <b><?= formatCurrency((float) $item['LineTotal']) ?></b>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="order-confirmation-section">
                <h3><i class="bi bi-geo-alt"></i> Thông tin giao hàng</h3>
                <dl class="order-confirmation-info">
                  <div><dt>Người nhận</dt><dd data-confirm-name></dd></div>
                  <div><dt>Số điện thoại</dt><dd data-confirm-phone></dd></div>
                  <div><dt>Email</dt><dd data-confirm-email></dd></div>
                  <div><dt>Địa chỉ</dt><dd data-confirm-address></dd></div>
                  <div><dt>Thanh toán</dt><dd data-confirm-payment></dd></div>
                  <div><dt>Ghi chú</dt><dd data-confirm-note></dd></div>
                </dl>
              </div>

              <div class="order-confirmation-notice">
                <i class="bi bi-shield-check"></i>
                <span>Vui lòng kiểm tra kỹ thông tin trước khi nhấn “Đặt mua hàng”.</span>
              </div>
            </section>

            <div class="checkout-step-actions">
              <button class="checkout-back-button" type="button" data-checkout-back="2">
                <i class="bi bi-arrow-left"></i> Sửa thông tin giao hàng
              </button>
            </div>
          </div>
        </section>

        <!-- Cột phải: Bảng Tóm tắt thanh toán -->
        <aside class="cart-summary-card-pro">
          <div class="summary-card-header">
            <span>Chi tiết thanh toán</span>
          </div>

          <div class="summary-row">
            <span>Tổng giá sản phẩm</span>
            <strong id="summary-total-original"><?= formatCurrency($totalOriginal) ?></strong>
          </div>

          <div class="summary-row">
            <span>Giảm giá sản phẩm</span>
            <strong id="summary-discount" class="text-danger">-<?= formatCurrency(
                $discountAmount
            ) ?></strong>
          </div>

          <div class="summary-row">
            <span>Tạm tính sau giảm</span>
            <strong id="summary-subtotal"><?= formatCurrency($subtotal) ?></strong>
          </div>

          <div class="summary-row">
            <span>Tổng tiền phí vận chuyển</span>
            <strong>0đ</strong>
          </div>

          <div class="summary-row">
            <span>Tổng cộng Voucher giảm giá</span>
            <strong>-0đ</strong>
          </div>

          <div class="summary-divider-pro"></div>

          <div class="summary-row total-payment-row">
            <span>Tổng tiền thanh toán</span>
            <strong id="summary-final" class="final-amount-text"><?= formatCurrency(
                $subtotal
            ) ?></strong>
          </div>

          <!-- Nút Đặt mua hàng (Mở khóa động bằng JS) -->
          <button class="btn btn-checkout-pro disabled-state" id="btn-checkout" type="submit" form="checkout-form" disabled>
            <i class="bi bi-bag-check"></i> Đặt mua hàng
          </button>
          
          <p class="terms-notice-text">
            Nhấn "Đặt mua hàng" đồng nghĩa với việc bạn đồng ý tuân theo <a href="#" target="_blank">Điều khoản dịch vụ của Trislaptop</a>.
          </p>
        </aside>

      </div>

      <!-- Nút Tiếp theo phía dưới cùng bên phải -->
      <div class="cart-bottom-actions-pro">
        <a href="index.php#products" class="btn btn-continue-shopping-alt">
          <i class="bi bi-arrow-left"></i> Tiếp tục mua hàng
        </a>
        
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
let currentCheckoutStep = 1;

/* Hàm định dạng tiền tệ VND phía client */
function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount).replace('₫', 'đ');
}

/* Hàm tính toán lại giá trị bảng bên phải dựa trên các checkbox được tích chọn */
function updateCartSummary() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    let totalOriginal = 0;
    let totalFinal = 0;
    let checkedCount = 0;

    checkboxes.forEach(cb => {
        if (cb.checked) {
            checkedCount++;
            totalOriginal += parseFloat(cb.getAttribute('data-original-price')) || 0;
            totalFinal += parseFloat(cb.getAttribute('data-final-price')) || 0;
        }
    });

    const totalDiscount = Math.max(0, totalOriginal - totalFinal);

    // Cập nhật DOM bảng bên phải
    document.getElementById('summary-total-original').textContent = formatVND(totalOriginal);
    document.getElementById('summary-discount').textContent = '-' + formatVND(totalDiscount);
    document.getElementById('summary-subtotal').textContent = formatVND(totalFinal);
    document.getElementById('summary-final').textContent = formatVND(totalFinal);

    updateCheckoutButtonState();
}

function getSelectedCartItems() {
    return Array.from(document.querySelectorAll('.item-checkbox:checked'));
}

function updateCheckoutButtonState() {
    const checkoutRoot = document.querySelector('[data-checkout-root]');
    const checkoutForm = document.getElementById('checkout-form');
    const btnCheckout = document.getElementById('btn-checkout');

    if (!checkoutRoot || !checkoutForm || !btnCheckout) return;

    const canPlaceOrder =
        currentCheckoutStep === 3 &&
        checkoutRoot.dataset.authenticated === '1' &&
        getSelectedCartItems().length > 0 &&
        checkoutForm.checkValidity();

    btnCheckout.disabled = !canPlaceOrder;
    btnCheckout.classList.toggle('disabled-state', !canPlaceOrder);
}

function updateOrderConfirmation() {
    const selectedProductIds = new Set(getSelectedCartItems().map(item => item.value));

    document.querySelectorAll('[data-confirm-product]').forEach(item => {
        item.hidden = !selectedProductIds.has(item.dataset.confirmProduct);
    });

    const confirmationFields = {
        '[data-confirm-name]': document.getElementById('customer-name')?.value.trim(),
        '[data-confirm-phone]': document.getElementById('customer-phone')?.value.trim(),
        '[data-confirm-email]': document.getElementById('customer-email')?.value.trim(),
        '[data-confirm-address]': document.getElementById('customer-address')?.value.trim(),
        '[data-confirm-payment]': 'COD - Thanh toán khi nhận hàng',
        '[data-confirm-note]': document.getElementById('order-note')?.value.trim() || 'Không có',
    };

    Object.entries(confirmationFields).forEach(([selector, value]) => {
        const target = document.querySelector(selector);
        if (target) target.textContent = value || 'Chưa cập nhật';
    });
}

function showCheckoutStep(step, shouldScroll = true) {
    const checkoutRoot = document.querySelector('[data-checkout-root]');
    const confirmationInput = document.getElementById('checkout-confirmation');
    if (!checkoutRoot || ![1, 2, 3].includes(step)) return;

    currentCheckoutStep = step;
    if (confirmationInput) confirmationInput.value = step === 3 ? '1' : '0';

    document.querySelectorAll('[data-checkout-panel]').forEach(panel => {
        panel.hidden = Number(panel.dataset.checkoutPanel) !== step;
    });

    document.querySelectorAll('[data-checkout-progress]').forEach(indicator => {
        const indicatorStep = Number(indicator.dataset.checkoutProgress);
        const circle = indicator.querySelector('span');
        indicator.classList.toggle('is-active', indicatorStep === step);
        indicator.classList.toggle('is-complete', indicatorStep < step);

        if (circle) {
            circle.innerHTML = indicatorStep < step ? '<i class="bi bi-check"></i>' : String(indicatorStep);
        }
    });

    if (step === 3) updateOrderConfirmation();
    updateCheckoutButtonState();

    if (shouldScroll) {
        document.querySelector('.checkout-progress')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function goToNextCheckoutStep(targetStep) {
    const checkoutRoot = document.querySelector('[data-checkout-root]');
    const checkoutForm = document.getElementById('checkout-form');
    const selectionError = document.querySelector('[data-checkout-selection-error]');

    if (!checkoutRoot || !checkoutForm) return;

    if (targetStep === 2) {
        const hasSelectedItems = getSelectedCartItems().length > 0;
        if (selectionError) selectionError.hidden = hasSelectedItems;
        if (!hasSelectedItems) return;
    }

    if (targetStep === 3) {
        if (checkoutRoot.dataset.authenticated !== '1') {
            document.querySelector('[data-open-login]')?.click();
            return;
        }

        if (!checkoutForm.reportValidity()) return;
    }

    showCheckoutStep(targetStep);
}

/* Hàm tăng giảm số lượng mượt mà */
function adjustQty(btn, delta) {
    const wrapper = btn.closest('.qty-stepper-box');
    const input = wrapper.querySelector('.stepper-input');
    let currentVal = parseInt(input.value) || 1;
    let maxVal = parseInt(input.getAttribute('max')) || 999;
    let minVal = parseInt(input.getAttribute('min')) || 1;

    let newVal = currentVal + delta;
    if (newVal < minVal) newVal = minVal;
    if (newVal > maxVal) newVal = maxVal;

    if (newVal !== currentVal) {
        input.value = newVal;
        document.getElementById('cart-form').submit();
    }
}

// Chạy khởi tạo kiểm tra trạng thái ban đầu khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    const checkoutRoot = document.querySelector('[data-checkout-root]');
    const checkoutForm = document.getElementById('checkout-form');

    document.querySelectorAll('[data-checkout-next]').forEach(button => {
        button.addEventListener('click', () => goToNextCheckoutStep(Number(button.dataset.checkoutNext)));
    });

    document.querySelectorAll('[data-checkout-back]').forEach(button => {
        button.addEventListener('click', () => showCheckoutStep(Number(button.dataset.checkoutBack)));
    });

    checkoutForm?.addEventListener('input', updateCheckoutButtonState);
    updateCartSummary();
    showCheckoutStep(Number(checkoutRoot?.dataset.initialStep || 1), false);
});
</script>

<?php require __DIR__ . '/template/footer.php'; ?>
