<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$authUser = authCurrentUser();

if ($authUser === null) {
    authSetFlash('error', 'Vui lòng đăng nhập để xem đơn hàng của bạn.');
    header('Location: ' . authUrlWithView('index.php', 'login'));
    exit();
}

$pdo = null;
$orders = [];
$orderDetails = [];
$ordersError = null;

try {
    $pdo = getDatabaseConnection();
    $orderStatement = $pdo->prepare(
        'SELECT MaDonHang, PhiVanChuyen, TongTien, TrangThaiDonHang, GhiChu
         FROM tbl_donhang
         WHERE MaNguoiDung = :user_id
         ORDER BY MaDonHang DESC'
    );
    $orderStatement->execute(['user_id' => (int) $authUser['id']]);
    $orders = $orderStatement->fetchAll();

    if ($orders !== []) {
        $orderIds = array_map('intval', array_column($orders, 'MaDonHang'));
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $detailStatement = $pdo->prepare(
            "SELECT MaDonHang, MaSanPham, TenSanPham, GiaBan, SoLuong, ThanhTien
             FROM tbl_chitietdonhang
             WHERE MaDonHang IN ({$placeholders})
             ORDER BY MaChiTiet ASC"
        );
        $detailStatement->execute($orderIds);

        foreach ($detailStatement->fetchAll() as $detail) {
            $orderDetails[(int) $detail['MaDonHang']][] = $detail;
        }
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $ordersError = 'Không thể tải danh sách đơn hàng lúc này.';
}

function orderStatusClass(string $status): string
{
    $classes = [
        'Chờ xác nhận' => 'is-pending',
        'Đã xác nhận' => 'is-confirmed',
        'Đang giao hàng' => 'is-shipping',
        'Đã giao hàng' => 'is-completed',
        'Đã hủy' => 'is-cancelled'
    ];

    return $classes[$status] ?? 'is-pending';
}

$sharedData = loadSharedPageData($pdo);
$brands = $sharedData['brands'];
$categories = $sharedData['categories'];
$categoryHighlights = $sharedData['categoryHighlights'];
$cartCount = calculateCartCount($_SESSION['cart'] ?? []);
$pageTitle = 'Đơn hàng của tôi — LaptopShop';
$pageDescription = 'Theo dõi các đơn hàng đã đặt tại LaptopShop.';

require __DIR__ . '/template/header.php';
?>

<main class="account-page">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Đường dẫn">
      <a href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
      <span class="separator">/</span>
      <span class="current">Đơn hàng của tôi</span>
    </nav>

    <div class="account-layout">
      <aside class="account-sidebar">
        <div class="account-avatar"><?= escape(
            mb_strtoupper(mb_substr((string) $authUser['name'], 0, 1))
        ) ?></div>
        <strong><?= escape((string) $authUser['name']) ?></strong>
        <span><?= escape((string) $authUser['email']) ?></span>
        <nav>
          <a href="tai-khoan.php"><i class="bi bi-person"></i> Tài khoản của tôi</a>
          <a class="is-active" href="don-hang-cua-toi.php"><i class="bi bi-bag-check"></i> Đơn hàng của tôi</a>
        </nav>
      </aside>

      <section class="account-content-card orders-content">
        <div class="account-content-card__heading">
          <div>
            <span>LỊCH SỬ MUA HÀNG</span>
            <h1>Đơn hàng của tôi</h1>
            <p>Theo dõi trạng thái và sản phẩm trong từng đơn hàng.</p>
          </div>
          <i class="bi bi-box-seam"></i>
        </div>

        <?php if (isset($_GET['placed'])): ?>
          <div class="alert alert-success" role="status">
            <i class="bi bi-check-circle-fill me-2"></i>Đặt hàng thành công. Mã đơn của bạn là
            <strong>#LS<?= str_pad(
                (string) (int) $_GET['placed'],
                5,
                '0',
                STR_PAD_LEFT
            ) ?></strong>.
          </div>
        <?php endif; ?>
        <?php if ($ordersError !== null): ?>
          <div class="alert alert-danger" role="alert"><?= escape($ordersError) ?></div>
        <?php elseif ($orders === []): ?>
          <div class="orders-empty-state">
            <i class="bi bi-bag-x"></i>
            <h2>Bạn chưa có đơn hàng nào</h2>
            <p>Khám phá các mẫu laptop phù hợp và đặt đơn hàng đầu tiên.</p>
            <a class="btn" href="index.php#products">Tiếp tục mua sắm</a>
          </div>
        <?php else: ?>
          <div class="orders-list">
            <?php foreach ($orders as $order): ?>
              <?php
              $orderId = (int) $order['MaDonHang'];
              $details = $orderDetails[$orderId] ?? [];
              $itemCount = array_sum(
                  array_map(function (array $item): int {
                      return (int) $item['SoLuong'];
                  }, $details)
              );
              ?>
              <article class="order-card">
                <header class="order-card__header">
                  <div>
                    <span>Mã đơn hàng</span>
                    <strong>#LS<?= str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) ?></strong>
                  </div>
                  <span class="order-status <?= orderStatusClass(
                      (string) $order['TrangThaiDonHang']
                  ) ?>"><?= escape((string) $order['TrangThaiDonHang']) ?></span>
                </header>

                <div class="order-card__items">
                  <?php foreach ($details as $detail): ?>
                    <div class="order-product-row">
                      <span class="order-product-row__icon"><i class="bi bi-laptop"></i></span>
                      <div>
                        <a href="chi-tiet-san-pham.php?id=<?= (int) $detail[
                            'MaSanPham'
                        ] ?>"><?= escape((string) $detail['TenSanPham']) ?></a>
                        <span><?= (int) $detail['SoLuong'] ?> × <?= formatCurrency(
     (float) $detail['GiaBan']
 ) ?></span>
                      </div>
                      <strong><?= formatCurrency((float) $detail['ThanhTien']) ?></strong>
                    </div>
                  <?php endforeach; ?>
                </div>

                <footer class="order-card__footer">
                  <span><?= $itemCount ?> sản phẩm</span>
                  <div>Tổng thanh toán <strong><?= formatCurrency(
                      (float) $order['TongTien']
                  ) ?></strong></div>
                </footer>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>

<?php require __DIR__ . '/template/footer.php'; ?>
