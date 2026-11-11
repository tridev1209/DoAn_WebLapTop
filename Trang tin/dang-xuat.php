<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
    isValidCsrfToken($_POST['csrf_token'] ?? null)
) {
    unset($_SESSION['auth_user']);
    session_regenerate_id(true);
    authSetFlash('success', 'Bạn đã đăng xuất.');
}

header('Location: ' . authSafeReturnUrl($_POST['return_to'] ?? null));
exit();
