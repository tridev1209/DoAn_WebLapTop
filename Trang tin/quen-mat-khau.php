<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php?auth=forgot');
    exit();
}

$returnUrl = authSafeReturnUrl($_POST['return_to'] ?? null);
$email = trim((string) ($_POST['email'] ?? ''));

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    authSetOldInput(['email' => $email]);
    authSetFlash('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    authSetOldInput(['email' => $email]);
    authSetFlash('error', 'Vui lòng nhập một địa chỉ email hợp lệ.');
} else {
    authSetFlash(
        'success',
        'Yêu cầu hợp lệ. Quản trị viên cần cấu hình email/SMTP để gửi đường dẫn đặt lại mật khẩu.'
    );
}

header('Location: ' . authUrlWithView($returnUrl, 'forgot'));
exit();
