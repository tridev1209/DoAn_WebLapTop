<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

function escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $value): string
{
    return number_format($value, 0, ',', '.') . '₫';
}

function extractSpecifications(?string $configuration): array
{
    $configuration = html_entity_decode(strip_tags($configuration ?? ''), ENT_QUOTES, 'UTF-8');
    $parts = preg_split('/[,;\r\n]+/u', $configuration) ?: [];
    $specifications = [];

    foreach ($parts as $part) {
        $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');

        if ($part !== '') {
            $specifications[] = $part;
        }

        if (count($specifications) === 3) {
            break;
        }
    }

    return $specifications;
}

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

function resolveProductImage(?string $imagePath, ?string $productName = null): string
{
    $fallback = './assets/img/product-placeholder.svg';
    $normalizedPath = ltrim(str_replace('\\', '/', trim($imagePath ?? '')), '/');

    if (strpos($normalizedPath, '..') !== false) {
        return $fallback;
    }

    if ($normalizedPath !== '' && is_file(__DIR__ . '/' . $normalizedPath)) {
        return './' . $normalizedPath;
    }

    if ($normalizedPath !== '') {
        $productImage = 'assets/img/' . basename($normalizedPath);

        if (is_file(__DIR__ . '/' . $productImage)) {
            return './' . $productImage;
        }
    }

    // Dữ liệu cũ có thể lưu sai tên hoặc phần mở rộng. Thử tìm ảnh theo tên sản phẩm.
    $safeProductName = basename(trim($productName ?? ''));

    if ($safeProductName !== '') {
        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $extension) {
            $productImage = 'assets/img/' . $safeProductName . '.' . $extension;

            if (is_file(__DIR__ . '/' . $productImage)) {
                return './' . $productImage;
            }
        }
    }

    return $fallback;
}

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$brandId = filter_input(INPUT_GET, 'brand', FILTER_VALIDATE_INT) ?: null;
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;
$priceRange = (string) ($_GET['price'] ?? '');
$allowedPriceRanges = ['under-15', '15-20', 'over-20'];

if (!in_array($priceRange, $allowedPriceRanges, true)) {
    $priceRange = '';
}

$products = [];
$brands = [];
$categories = [];
$groupedProducts = [];
$categoryHighlights = [];
$hasSales = false;
$databaseError = null;
$pdo = null;

try {
    $pdo = getDatabaseConnection();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $databaseError = 'Không kết nối được MySQL: ' . $exception->getMessage();
}

if ($pdo instanceof PDO) {
    try {
        $salesTables = (int) $pdo
            ->query(
                "SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name IN ('tbl_donhang', 'tbl_chitietdonhang')"
            )
            ->fetchColumn();
        $salesTrackingAvailable = $salesTables === 2;
        $salesSelect = '0 AS DaBan';
        $salesJoin = '';

        if ($salesTrackingAvailable) {
            $salesSelect = 'COALESCE(sales.DaBan, 0) AS DaBan';
            $salesJoin = "LEFT JOIN (
                    SELECT ct.MaSanPham, SUM(ct.SoLuong) AS DaBan
                    FROM tbl_chitietdonhang AS ct
                    INNER JOIN tbl_donhang AS dh ON dh.MaDonHang = ct.MaDonHang
                    WHERE dh.TrangThaiDonHang = 'Đã giao hàng'
                    GROUP BY ct.MaSanPham
                ) AS sales ON sales.MaSanPham = sp.IdSanPham";
            $hasSales = (int) $pdo
                ->query(
                    "SELECT COALESCE(SUM(ct.SoLuong), 0)
                     FROM tbl_chitietdonhang AS ct
                     INNER JOIN tbl_donhang AS dh ON dh.MaDonHang = ct.MaDonHang
                     WHERE dh.TrangThaiDonHang = 'Đã giao hàng'"
                )
                ->fetchColumn() > 0;
        }

        $sql = "SELECT
                sp.IdSanPham,
                sp.TenSanPham,
                sp.HinhAnh,
                sp.DonGia,
                sp.TiLeGiamGia,
                sp.SoLuong,
                sp.MoTa,
                sp.CauHinh,
                sp.LuotXem,
                {$salesSelect},
                COALESCE(nsx.TenNhaSanXuat, 'Chưa cập nhật') AS TenNhaSanXuat,
                COALESCE(dm.TenDanhMuc, 'Laptop') AS TenDanhMuc,
                (sp.DonGia - (sp.DonGia * sp.TiLeGiamGia / 100)) AS GiaSauGiam
            FROM tbl_sanpham AS sp
            LEFT JOIN tbl_nhasanxuat AS nsx
                ON nsx.IdNhaSanXuat = sp.IdNhaSanXuat
            LEFT JOIN tbl_danhmuc AS dm
                ON dm.MaDanhMuc = sp.MaDanhMuc
            {$salesJoin}
            WHERE 1 = 1";

        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND (sp.TenSanPham LIKE :keyword_name OR sp.MaSanPham LIKE :keyword_code)';
            $params['keyword_name'] = '%' . $keyword . '%';
            $params['keyword_code'] = '%' . $keyword . '%';
        }

        if ($brandId !== null) {
            $sql .= ' AND sp.IdNhaSanXuat = :brand';
            $params['brand'] = $brandId;
        }

        if ($categoryId !== null) {
            $sql .= ' AND sp.MaDanhMuc = :category';
            $params['category'] = $categoryId;
        }

        if ($priceRange === 'under-15') {
            $sql .= ' AND sp.DonGia < 15000000';
        } elseif ($priceRange === '15-20') {
            $sql .= ' AND sp.DonGia BETWEEN 15000000 AND 20000000';
        } elseif ($priceRange === 'over-20') {
            $sql .= ' AND sp.DonGia > 20000000';
        }

        $sql .= $hasSales
            ? ' ORDER BY DaBan DESC, sp.LuotXem DESC, sp.IdSanPham DESC LIMIT 12'
            : ' ORDER BY sp.LuotXem DESC, sp.IdSanPham DESC LIMIT 12';

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $products = $statement->fetchAll();

        try {
            $brands = $pdo
                ->query(
                    "SELECT IdNhaSanXuat, TenNhaSanXuat
             FROM tbl_nhasanxuat
             ORDER BY TenNhaSanXuat"
                )
                ->fetchAll();

            $categories = $pdo
                ->query(
                    "SELECT MaDanhMuc, TenDanhMuc
             FROM tbl_danhmuc
             ORDER BY TenDanhMuc"
                )
                ->fetchAll();

            if ($keyword === '' && $brandId === null && $categoryId === null && $priceRange === '') {
                $groupOrder = $hasSales
                    ? 'DaBan DESC, sp.LuotXem DESC, sp.IdSanPham DESC'
                    : 'sp.LuotXem DESC, sp.IdSanPham DESC';
                $groupSql = "SELECT
                        sp.IdSanPham,
                        sp.TenSanPham,
                        sp.HinhAnh,
                        sp.DonGia,
                        sp.TiLeGiamGia,
                        sp.SoLuong,
                        sp.MoTa,
                        sp.CauHinh,
                        sp.LuotXem,
                        {$salesSelect},
                        COALESCE(nsx.TenNhaSanXuat, 'Chưa cập nhật') AS TenNhaSanXuat,
                        COALESCE(dm.TenDanhMuc, 'Laptop') AS TenDanhMuc,
                        (sp.DonGia - (sp.DonGia * sp.TiLeGiamGia / 100)) AS GiaSauGiam
                    FROM tbl_sanpham AS sp
                    LEFT JOIN tbl_nhasanxuat AS nsx
                        ON nsx.IdNhaSanXuat = sp.IdNhaSanXuat
                    LEFT JOIN tbl_danhmuc AS dm
                        ON dm.MaDanhMuc = sp.MaDanhMuc
                    {$salesJoin}
                    WHERE sp.MaDanhMuc = :group_category
                    ORDER BY {$groupOrder}
                    LIMIT 5";
                $groupStatement = $pdo->prepare($groupSql);

                foreach ($categories as $category) {
                    $groupStatement->execute(['group_category' => (int) $category['MaDanhMuc']]);
                    $categoryProducts = $groupStatement->fetchAll();

                    if ($categoryProducts !== []) {
                        $categoryKey = (int) $category['MaDanhMuc'];
                        $groupedProducts[$categoryKey] = $categoryProducts;
                        $categoryHighlights[$categoryKey] = array_slice($categoryProducts, 0, 4);
                    }
                }
            }
        } catch (Throwable $navigationException) {
            error_log($navigationException->getMessage());
        }
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        $databaseError = 'Không đọc được bảng sản phẩm: ' . $exception->getMessage();
    }
}

$cartCount = 0;

foreach ($_SESSION['cart'] ?? [] as $cartItem) {
    $cartCount += is_array($cartItem) ? (int) ($cartItem['quantity'] ?? 0) : (int) $cartItem;
}
?>
<?php require __DIR__ . '/template/header.php'; ?>
    <main>
      <section class="hero-section">
        <div class="container">
          <div class="hero-card">
            <div class="hero-card__content">
              <span class="hero-card__eyebrow">Công nghệ cho mọi nhu cầu</span>
              <h1>Laptop chính hãng,<br />chọn đúng máy – đúng giá</h1>
              <p>
                Khám phá laptop học tập, văn phòng và gaming với chính sách bảo
                hành minh bạch.
              </p>
              <a class="btn hero-card__button" href="#products">
                Xem sản phẩm <i class="bi bi-arrow-right"></i>
              </a>
            </div>
            <div class="hero-card__visual" aria-hidden="true">
              <i class="bi bi-laptop"></i>
            </div>
          </div>
        </div>
      </section>

      <section class="benefits-section" aria-label="Quyền lợi mua hàng">
        <div class="container">
          <div class="benefits-grid">
            <div class="benefit-item">
              <i class="bi bi-truck"></i>
              <div><strong>Giao hàng toàn quốc</strong><span>Nhanh chóng, an toàn</span></div>
            </div>
            <div class="benefit-item">
              <i class="bi bi-patch-check"></i>
              <div><strong>Hàng chính hãng</strong><span>Nguồn gốc rõ ràng</span></div>
            </div>
            <div class="benefit-item">
              <i class="bi bi-arrow-repeat"></i>
              <div><strong>Đổi trả dễ dàng</strong><span>Hỗ trợ tận tình</span></div>
            </div>
            <div class="benefit-item">
              <i class="bi bi-headset"></i>
              <div><strong>Tư vấn miễn phí</strong><span>8:00 – 21:00 mỗi ngày</span></div>
            </div>
          </div>
        </div>
      </section>

      <?php if ($groupedProducts !== []): ?>
      <section id="products" class="products-section product-showcases">
        <div class="container">
          <div class="ranking-note">
            <strong><?= $hasSales ? 'Xếp hạng theo số lượng đã bán' : 'Xếp hạng theo mức độ quan tâm' ?></strong>
            <span>
              <?= $hasSales
                  ? 'Số liệu được tính từ các đơn hàng đã giao thành công.'
                  : 'Chưa có đơn đã giao, hệ thống đang dùng lượt xem và sẽ tự chuyển sang số lượng bán khi có dữ liệu.' ?>
            </span>
          </div>

          <?php foreach ($categories as $category): ?>
            <?php
            $showcaseCategoryId = (int) $category['MaDanhMuc'];
            $showcaseProducts = $groupedProducts[$showcaseCategoryId] ?? [];

            if ($showcaseProducts === []) {
                continue;
            }
            ?>
            <section class="product-showcase" aria-labelledby="category-<?= $showcaseCategoryId ?>-title">
              <div class="product-showcase__header">
                <div class="product-showcase__title-wrap">
                  <h2 id="category-<?= $showcaseCategoryId ?>-title">
                    <?= escape($category['TenDanhMuc']) ?>
                    <?= $hasSales ? 'bán chạy' : 'được quan tâm' ?>
                  </h2>
                  <span class="product-showcase__badge">
                    <?= $hasSales ? 'Dữ liệu bán thực tế' : 'Theo lượt xem' ?>
                  </span>
                </div>

                <div class="product-showcase__links" aria-label="Lọc theo hãng">
                  <?php foreach (array_slice($brands, 0, 5) as $brand): ?>
                    <a href="index.php?category=<?= $showcaseCategoryId ?>&amp;brand=<?= (int) $brand['IdNhaSanXuat'] ?>#products">
                      <?= escape($brand['TenNhaSanXuat']) ?>
                    </a>
                  <?php endforeach; ?>
                  <a class="product-showcase__all" href="index.php?category=<?= $showcaseCategoryId ?>#products">
                    Xem tất cả <span aria-hidden="true">›</span>
                  </a>
                </div>
              </div>

              <div class="product-showcase__grid">
                <?php foreach ($showcaseProducts as $product): ?>
                  <?php require __DIR__ . '/template/product-card.php'; ?>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      </section>
      <?php else: ?>
      <section id="products" class="products-section">
        <div class="container">
          <div class="section-heading">
            <div>
              <span class="section-heading__eyebrow">Gợi ý dành cho bạn</span>
              <h2><?= $keyword !== '' ? 'Kết quả tìm kiếm' : 'Laptop nổi bật' ?></h2>
              <?php if ($keyword !== ''): ?>
                <p>Kết quả cho từ khóa “<?= escape($keyword) ?>”</p>
              <?php endif; ?>
            </div>
            <?php if ($keyword !== '' || $brandId !== null || $categoryId !== null || $priceRange !== ''): ?>
              <a class="btn btn-outline-secondary" href="index.php#products">
                <i class="bi bi-x-lg"></i> Xóa bộ lọc
              </a>
            <?php endif; ?>
          </div>

          <?php if ($databaseError !== null): ?>
            <div class="alert alert-warning database-alert" role="alert">
              <i class="bi bi-database-exclamation"></i>
              <div>
                <strong><?= escape($databaseError) ?></strong>
                <span>
                  Mặc định: database <code>ShopLapTop</code>, tài khoản
                  <code>root</code>, mật khẩu trống.
                </span>
              </div>
            </div>
          <?php elseif ($products === []): ?>
            <div class="empty-state">
              <i class="bi bi-search"></i>
              <h3>Không tìm thấy sản phẩm</h3>
              <p>Hãy thử từ khóa khác hoặc xóa bộ lọc hiện tại.</p>
              <a class="btn btn-primary" href="index.php#products">Xem tất cả sản phẩm</a>
            </div>
          <?php else: ?>
            <div class="row g-4">
              <?php foreach ($products as $product): ?>
                <?php
                $originalPrice = (float) $product['DonGia'];
                $salePrice = (float) $product['GiaSauGiam'];
                $discount = (float) $product['TiLeGiamGia'];
                $stock = (int) $product['SoLuong'];
                $specifications = extractSpecifications($product['CauHinh'] ?: $product['MoTa']);
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                  <article class="product-card h-100">
                    <a
                      class="product-card__image-wrap"
                      href="chi-tiet-san-pham.php?id=<?= (int) $product['IdSanPham'] ?>"
                    >
                      <?php if ($discount > 0): ?>
                        <span class="product-card__discount">-<?= (int) $discount ?>%</span>
                      <?php endif; ?>
                      <img
                        class="product-card__image"
                        src="<?= escape(resolveProductImage($product['HinhAnh'], $product['TenSanPham'])) ?>"
                        alt="<?= escape($product['TenSanPham']) ?>"
                        loading="lazy"
                      />
                    </a>

                    <div class="product-card__body">
                      <h3 class="product-card__title">
                        <a href="chi-tiet-san-pham.php?id=<?= (int) $product['IdSanPham'] ?>">
                          <?= escape($product['TenSanPham']) ?>
                        </a>
                      </h3>

                      <?php if ($specifications !== []): ?>
                        <ul class="product-card__specifications">
                          <?php foreach ($specifications as $specification): ?>
                            <li>
                              <i class="bi <?= specificationIcon($specification) ?>"></i>
                              <span><?= escape($specification) ?></span>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>

                      <div class="product-card__pricing">
                        <?php if ($discount > 0): ?>
                          <del><?= formatCurrency($originalPrice) ?></del>
                        <?php endif; ?>
                        <strong><?= formatCurrency($salePrice) ?></strong>
                      </div>

                      <div class="product-card__footer">
                        <span class="stock-status <?= $stock > 0
                            ? 'is-available'
                            : 'is-unavailable' ?>">
                          <i class="bi <?= $stock > 0 ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                          <?= $stock > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                        </span>
                        <?php if ($stock > 0): ?>
                          <a
                            class="btn product-card__cart-button"
                            href="gio-hang.php?action=add&id=<?= (int) $product['IdSanPham'] ?>"
                            aria-label="Thêm <?= escape($product['TenSanPham']) ?> vào giỏ hàng"
                          >
                            <i class="bi bi-cart-plus"></i>
                          </a>
                        <?php else: ?>
                          <button class="btn product-card__cart-button" type="button" disabled>
                            <i class="bi bi-cart-x"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </article>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
      <?php endif; ?>
    </main>

<?php require __DIR__ . '/template/footer.php'; ?>
