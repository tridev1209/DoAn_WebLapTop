<?php
declare(strict_types=1);

/**
 * Tạo kết nối PDO dùng chung cho toàn bộ website.
 * Có thể đổi thông tin bằng biến môi trường mà không phải sửa mã nguồn:
 * DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 */
function getDatabaseConnection(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'ShopLapTop';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: 'Zalo@123';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}
