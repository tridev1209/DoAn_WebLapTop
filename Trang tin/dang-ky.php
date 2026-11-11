<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php?auth=register');
    exit();
}

$returnUrl = authSafeReturnUrl($_POST['return_to'] ?? null);
$values = [
    'name' => trim((string) ($_POST['name'] ?? '')),
    'username' => trim((string) ($_POST['username'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? ''))
];
$password = (string) ($_POST['password'] ?? '');
$passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
$passwordErrors = authPasswordValidationErrors($password);
$error = null;

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
} elseif (mb_strlen($values['name']) < 2) {
    $error = 'Vui lòng nhập họ và tên hợp lệ.';
} elseif (!preg_match('/^[A-Za-z0-9_.-]{4,50}$/', $values['username'])) {
    $error =
        'Tên đăng nhập cần từ 4 ký tự và chỉ gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.';
} elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $error = 'Địa chỉ email không hợp lệ.';
} elseif ($values['phone'] !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $values['phone'])) {
    $error = 'Số điện thoại không hợp lệ.';
} elseif ($passwordErrors !== []) {
    $error = 'Mật khẩu phải ' . implode(', ', $passwordErrors) . '.';
} elseif ($password !== $passwordConfirmation) {
    $error = 'Mật khẩu xác nhận chưa khớp.';
}

if ($error === null) {
    try {
        $pdo = getDatabaseConnection();
        $check = $pdo->prepare(
            'SELECT COUNT(*) FROM tbl_nguoidung
             WHERE TenDangNhap = :username OR Email = :email OR (SoDienThoai <> \'\' AND SoDienThoai = :phone)'
        );
        $check->execute([
            'username' => $values['username'],
            'email' => $values['email'],
            'phone' => $values['phone']
        ]);

        if ((int) $check->fetchColumn() > 0) {
            $error = 'Tên đăng nhập, email hoặc số điện thoại đã được sử dụng.';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO tbl_nguoidung
                    (TenNguoiDung, TenDangNhap, MatKhau, Email, SoDienThoai, QuyenHan, TrangThai)
                 VALUES
                    (:name, :username, :password, :email, :phone, \'Khách hàng\', \'Hoạt động\')'
            );
            $insert->execute([
                'name' => $values['name'],
                'username' => $values['username'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $values['email'],
                'phone' => $values['phone'] !== '' ? $values['phone'] : null
            ]);
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $error = 'Chưa thể tạo tài khoản lúc này. Vui lòng thử lại sau.';
    }
}

if ($error !== null) {
    authSetOldInput($values);
    authSetFlash('error', $error);
    header('Location: ' . authUrlWithView($returnUrl, 'register'));
    exit();
}

authSetFlash('success', 'Đăng ký thành công. Bạn có thể đăng nhập ngay.');
header('Location: ' . authUrlWithView($returnUrl, 'login'));
exit();
