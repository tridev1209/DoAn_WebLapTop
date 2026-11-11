<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$returnUrl = authSafeReturnUrl($_POST['return_to'] ?? null);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php?auth=login');
    exit();
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    authSetFlash('error', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    header('Location: ' . authUrlWithView($returnUrl, 'login'));
    exit();
}

try {
    $pdo = getDatabaseConnection();
    [$success, $message] = authAttemptLogin(
        $pdo,
        (string) ($_POST['identity'] ?? ''),
        (string) ($_POST['password'] ?? '')
    );
    authSetFlash($success ? 'success' : 'error', $message);

    if ($success && isset($_POST['remember'])) {
        setcookie(
            session_name(),
            session_id(),
            time() + 60 * 60 * 24 * 30,
            '/',
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            true
        );
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $success = false;
    authSetFlash('error', 'Chưa thể đăng nhập lúc này. Vui lòng thử lại sau.');
}

if (!$success) {
    $returnUrl = authUrlWithView($returnUrl, 'login');
}

header('Location: ' . $returnUrl);
exit();
