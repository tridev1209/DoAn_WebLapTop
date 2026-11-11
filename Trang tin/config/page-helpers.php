<?php
declare(strict_types=1);

if (!function_exists('escape')) {
    function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency(float $value): string
    {
        return number_format($value, 0, ',', '.') . '₫';
    }
}

if (!function_exists('resolveProductImage')) {
    function resolveProductImage(?string $imagePath, ?string $productName = null): string
    {
        $fallback = './assets/img/product-placeholder.svg';
        $normalizedPath = ltrim(str_replace('\\', '/', trim($imagePath ?? '')), '/');
        $projectRoot = dirname(__DIR__);

        if (strpos($normalizedPath, '..') !== false) {
            return $fallback;
        }

        $candidates = [];

        if ($normalizedPath !== '') {
            $candidates[] = $normalizedPath;
            $candidates[] = 'assets/img/' . basename($normalizedPath);

            if (pathinfo($normalizedPath, PATHINFO_EXTENSION) === '') {
                foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $extension) {
                    $candidates[] = $normalizedPath . '.' . $extension;
                    $candidates[] = 'assets/img/' . basename($normalizedPath) . '.' . $extension;
                }
            }
        }

        $safeProductName = basename(trim($productName ?? ''));

        if ($safeProductName !== '') {
            foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $extension) {
                $candidates[] = 'assets/img/' . $safeProductName . '.' . $extension;
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($projectRoot . '/' . $candidate)) {
                return './' . $candidate;
            }
        }

        return $fallback;
    }
}

if (!function_exists('extractSpecifications')) {
    function extractSpecifications(?string $configuration, int $limit = 3): array
    {
        $configuration = html_entity_decode(strip_tags($configuration ?? ''), ENT_QUOTES, 'UTF-8');
        $parts = preg_split('/[,;\r\n]+/u', $configuration) ?: [];
        $specifications = [];

        foreach ($parts as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');

            if ($part !== '') {
                $specifications[] = $part;
            }

            if (count($specifications) === $limit) {
                break;
            }
        }

        return $specifications;
    }
}

if (!function_exists('specificationIcon')) {
    function specificationIcon(string $specification): string
    {
        $normalized = strtolower($specification);

        if (preg_match('/ram|gb ddr/u', $normalized)) {
            return 'bi-memory';
        }

        if (preg_match('/ssd|hdd|tb|ổ cứng/u', $normalized)) {
            return 'bi-device-ssd';
        }

        if (preg_match('/rtx|gtx|graphics|gpu|vga/u', $normalized)) {
            return 'bi-gpu-card';
        }

        if (preg_match('/màn hình|inch|hz/u', $normalized)) {
            return 'bi-display';
        }

        return 'bi-cpu';
    }
}

function loadSharedPageData(?PDO $pdo): array
{
    $data = [
        'brands' => [],
        'categories' => [],
        'categoryHighlights' => []
    ];

    if (!($pdo instanceof PDO)) {
        return $data;
    }

    try {
        $data['brands'] = $pdo
            ->query('SELECT IdNhaSanXuat, TenNhaSanXuat FROM tbl_nhasanxuat ORDER BY TenNhaSanXuat')
            ->fetchAll();
        $data['categories'] = $pdo
            ->query('SELECT MaDanhMuc, TenDanhMuc FROM tbl_danhmuc ORDER BY TenDanhMuc')
            ->fetchAll();

        $highlightStatement = $pdo->prepare(
            'SELECT IdSanPham, TenSanPham
             FROM tbl_sanpham
             WHERE MaDanhMuc = :category
             ORDER BY LuotXem DESC, IdSanPham DESC
             LIMIT 4'
        );

        foreach ($data['categories'] as $category) {
            $categoryId = (int) $category['MaDanhMuc'];
            $highlightStatement->execute(['category' => $categoryId]);
            $data['categoryHighlights'][$categoryId] = $highlightStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
    }

    return $data;
}

function calculateCartCount(array $cart): int
{
    $count = 0;

    foreach ($cart as $item) {
        $count += is_array($item) ? (int) ($item['quantity'] ?? 0) : (int) $item;
    }

    return $count;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION['csrf_token'];
}

function isValidCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
        is_string($token) &&
        hash_equals((string) $_SESSION['csrf_token'], $token);
}
