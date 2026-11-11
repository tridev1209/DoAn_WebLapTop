<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$authUser = authCurrentUser();

if ($authUser === null) {
    authSetFlash('error', 'Vui lòng đăng nhập để xem tài khoản của bạn.');
    header('Location: ' . authUrlWithView('index.php', 'login'));
    exit();
}

$pdo = null;
$profileError = null;
$profileSuccess = null;
$profile = null;

try {
    $pdo = getDatabaseConnection();
    $profileStatement = $pdo->prepare(
        'SELECT MaNguoiDung, TenNguoiDung, TenDangNhap, Email, SoDienThoai, DiaChi, NgayTao
         FROM tbl_nguoidung
         WHERE MaNguoiDung = :id
         LIMIT 1'
    );
    $profileStatement->execute(['id' => (int) $authUser['id']]);
    $profile = $profileStatement->fetch() ?: null;
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $profileError = 'Không thể tải thông tin tài khoản lúc này.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && is_array($profile)) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $profileError = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif (mb_strlen($name) < 2) {
        $profileError = 'Vui lòng nhập họ và tên hợp lệ.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileError = 'Email không hợp lệ.';
    } elseif ($phone !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
        $profileError = 'Số điện thoại không hợp lệ.';
    } else {
        try {
            $updateStatement = $pdo->prepare(
                'UPDATE tbl_nguoidung
                 SET TenNguoiDung = :name, Email = :email, SoDienThoai = :phone, DiaChi = :address
                 WHERE MaNguoiDung = :id'
            );
            $updateStatement->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $address !== '' ? $address : null,
                'id' => (int) $authUser['id']
            ]);
            $_SESSION['auth_user']['name'] = $name;
            $_SESSION['auth_user']['email'] = $email;
            $profile = array_merge($profile, [
                'TenNguoiDung' => $name,
                'Email' => $email,
                'SoDienThoai' => $phone,
                'DiaChi' => $address
            ]);
            $profileSuccess = 'Thông tin tài khoản đã được cập nhật.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $profileError =
                'Email có thể đã được sử dụng hoặc hệ thống chưa thể cập nhật thông tin.';
        }
    }
}

$sharedData = loadSharedPageData($pdo);
$brands = $sharedData['brands'];
$categories = $sharedData['categories'];
$categoryHighlights = $sharedData['categoryHighlights'];
$cartCount = calculateCartCount($_SESSION['cart'] ?? []);
$pageTitle = 'Tài khoản của tôi — LaptopShop';
$pageDescription = 'Quản lý thông tin tài khoản khách hàng LaptopShop.';

require __DIR__ . '/template/header.php';
?>

<main class="account-page">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Đường dẫn">
      <a href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
      <span class="separator">/</span>
      <span class="current">Tài khoản của tôi</span>
    </nav>

    <div class="account-layout">
      <aside class="account-sidebar">
        <div class="account-avatar"><?= escape(
            mb_strtoupper(mb_substr((string) $authUser['name'], 0, 1))
        ) ?></div>
        <strong><?= escape((string) $authUser['name']) ?></strong>
        <span><?= escape((string) $authUser['email']) ?></span>
        <nav>
          <a class="is-active" href="tai-khoan.php"><i class="bi bi-person"></i> Tài khoản của tôi</a>
          <a href="don-hang-cua-toi.php"><i class="bi bi-bag-check"></i> Đơn hàng của tôi</a>
        </nav>
      </aside>

      <section class="account-content-card">
        <div class="account-content-card__heading">
          <div>
            <span>THÔNG TIN CÁ NHÂN</span>
            <h1>Tài khoản của tôi</h1>
            <p>Cập nhật thông tin liên hệ để đặt hàng nhanh hơn.</p>
          </div>
          <i class="bi bi-person-gear"></i>
        </div>

        <?php if ($profileError !== null): ?>
          <div class="alert alert-danger" role="alert"><?= escape($profileError) ?></div>
        <?php endif; ?>
        <?php if ($profileSuccess !== null): ?>
          <div class="alert alert-success" role="status"><?= escape($profileSuccess) ?></div>
        <?php endif; ?>

        <?php if (is_array($profile)): ?>
          <form method="post" class="account-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
            <div class="account-field">
              <label for="profile-name">Họ và tên</label>
              <input id="profile-name" name="name" type="text" value="<?= escape(
                  (string) $profile['TenNguoiDung']
              ) ?>" required />
            </div>
            <div class="account-field">
              <label for="profile-username">Tên đăng nhập</label>
              <input id="profile-username" type="text" value="<?= escape(
                  (string) $profile['TenDangNhap']
              ) ?>" disabled />
            </div>
            <div class="account-field">
              <label for="profile-email">Email</label>
              <input id="profile-email" name="email" type="email" value="<?= escape(
                  (string) $profile['Email']
              ) ?>" required />
            </div>
            <div class="account-field">
              <label for="profile-phone">Số điện thoại</label>
              <input id="profile-phone" name="phone" type="tel" value="<?= escape(
                  (string) ($profile['SoDienThoai'] ?? '')
              ) ?>" />
            </div>
            <div class="account-field account-field--wide">
              <label for="profile-address">Địa chỉ mặc định</label>
              <input id="profile-address" name="address" type="text" value="<?= escape(
                  (string) ($profile['DiaChi'] ?? '')
              ) ?>" />
            </div>
            <div class="account-form__actions account-field--wide">
              <span><i class="bi bi-calendar3"></i> Thành viên từ <?= escape(
                  date('d/m/Y', strtotime((string) $profile['NgayTao']))
              ) ?></span>
              <button class="btn" type="submit"><i class="bi bi-check2"></i> Lưu thay đổi</button>
            </div>
          </form>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>

<?php require __DIR__ . '/template/footer.php'; ?>

