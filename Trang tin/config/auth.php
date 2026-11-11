<?php
declare(strict_types=1);

require_once __DIR__ . '/page-helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function authCurrentUser(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;

    return is_array($user) ? $user : null;
}

function authIsLoggedIn(): bool
{
    return authCurrentUser() !== null;
}

function authSetFlash(string $type, string $message): void
{
    $_SESSION['auth_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function authPullFlash(): ?array
{
    $flash = $_SESSION['auth_flash'] ?? null;
    unset($_SESSION['auth_flash']);

    return is_array($flash) ? $flash : null;
}

function authSetOldInput(array $values): void
{
    $_SESSION['auth_old_input'] = $values;
}

function authPullOldInput(): array
{
    $values = $_SESSION['auth_old_input'] ?? [];
    unset($_SESSION['auth_old_input']);

    return is_array($values) ? $values : [];
}

function authSafeReturnUrl(?string $url, string $fallback = 'index.php'): string
{
    $url = trim((string) $url);

    if (
        $url === '' ||
        strpos($url, '//') === 0 ||
        preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) ||
        preg_match('/[\r\n]/', $url)
    ) {
        return $fallback;
    }

    return $url;
}

function authUrlWithView(?string $url, string $view): string
{
    $safeUrl = authSafeReturnUrl($url);
    $parts = parse_url($safeUrl);
    $query = [];

    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    unset($query['login'], $query['auth']);
    $query['auth'] = in_array($view, ['login', 'register', 'forgot'], true) ? $view : 'login';

    $result = $parts['path'] ?? 'index.php';
    $result .= '?' . http_build_query($query);

    if (isset($parts['fragment']) && $parts['fragment'] !== '') {
        $result .= '#' . $parts['fragment'];
    }

    return $result;
}

function authPasswordValidationErrors(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'có ít nhất 8 ký tự';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'có ít nhất một chữ thường';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'có ít nhất một chữ hoa';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'có ít nhất một chữ số';
    }

    if (!preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
        $errors[] = 'có ít nhất một ký tự đặc biệt';
    }

    return $errors;
}

function authAttemptLogin(PDO $pdo, string $identity, string $password): array
{
    $identity = trim($identity);

    if ($identity === '' || $password === '') {
        return [false, 'Vui lòng nhập email, số điện thoại hoặc tên đăng nhập và mật khẩu.'];
    }

    $statement = $pdo->prepare(
        'SELECT MaNguoiDung, TenNguoiDung, TenDangNhap, MatKhau, Email, SoDienThoai, QuyenHan, TrangThai
         FROM tbl_nguoidung
         WHERE Email = :identity_email
            OR SoDienThoai = :identity_phone
            OR TenDangNhap = :identity_username
         LIMIT 1'
    );
    $statement->execute([
        'identity_email' => $identity,
        'identity_phone' => $identity,
        'identity_username' => $identity
    ]);
    $user = $statement->fetch();

    if (!is_array($user)) {
        return [false, 'Thông tin đăng nhập không chính xác.'];
    }

    if (($user['TrangThai'] ?? '') !== 'Hoạt động') {
        return [false, 'Tài khoản đang bị khóa. Vui lòng liên hệ bộ phận hỗ trợ.'];
    }

    $storedPassword = (string) ($user['MatKhau'] ?? '');
    $passwordInfo = password_get_info($storedPassword);
    $usesPasswordHash = ($passwordInfo['algoName'] ?? 'unknown') !== 'unknown';
    $passwordMatches = $usesPasswordHash
        ? password_verify($password, $storedPassword)
        : hash_equals($storedPassword, $password);

    if (!$passwordMatches) {
        return [false, 'Thông tin đăng nhập không chính xác.'];
    }

    if (!$usesPasswordHash || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
        $update = $pdo->prepare(
            'UPDATE tbl_nguoidung SET MatKhau = :password WHERE MaNguoiDung = :id'
        );
        $update->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $user['MaNguoiDung']
        ]);
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['MaNguoiDung'],
        'name' => (string) $user['TenNguoiDung'],
        'username' => (string) $user['TenDangNhap'],
        'email' => (string) ($user['Email'] ?? ''),
        'role' => (string) $user['QuyenHan']
    ];

    return [true, 'Đăng nhập thành công.'];
}
