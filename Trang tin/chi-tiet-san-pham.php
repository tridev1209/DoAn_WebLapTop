<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/page-helpers.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$product = null;
$relatedProducts = [];
$databaseError = null;
$pdo = null;
$keyword = '';
$brandId = null;
$categoryId = null;
$priceRange = '';
$hasSales = false;

try {
    $pdo = getDatabaseConnection();

    if ($productId > 0) {
        $pdo->prepare(
            'UPDATE tbl_sanpham SET LuotXem = LuotXem + 1 WHERE IdSanPham = :id'
        )->execute(['id' => $productId]);

        $statement = $pdo->prepare(
            "SELECT
                sp.IdSanPham,
                sp.TenSanPham,
                sp.HinhAnh,
                sp.DonGia,
                sp.TiLeGiamGia,
                sp.SoLuong,
                sp.MoTa,
                sp.CauHinh,
                sp.LuotXem,
                sp.MaDanhMuc,
                0 AS DaBan,
                COALESCE(nsx.TenNhaSanXuat, 'Chưa cập nhật') AS TenNhaSanXuat,
                COALESCE(dm.TenDanhMuc, 'Laptop') AS TenDanhMuc,
                (sp.DonGia - (sp.DonGia * sp.TiLeGiamGia / 100)) AS GiaSauGiam
             FROM tbl_sanpham AS sp
             LEFT JOIN tbl_nhasanxuat AS nsx ON nsx.IdNhaSanXuat = sp.IdNhaSanXuat
             LEFT JOIN tbl_danhmuc AS dm ON dm.MaDanhMuc = sp.MaDanhMuc
             WHERE sp.IdSanPham = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch() ?: null;

        if ($product !== null) {
            // === THÊM ĐOẠN NÀY ĐỂ LẤY DANH SÁCH ẢNH PHỤ ===
            $productImages = [];
            if (!empty($product['HinhAnh'])) {
                $productImages[] = $product['HinhAnh']; // Đưa ảnh chính vào đầu danh sách
            }

            $galleryStmt = $pdo->prepare(
                'SELECT duong_dan_anh FROM tbl_anh_san_pham WHERE id_san_pham = :id ORDER BY thu_tu_hien_thi ASC'
            );
            $galleryStmt->execute(['id' => $productId]);
            $extraImages = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

            // Gộp ảnh chính và ảnh phụ, loại bỏ trùng lặp
            $productImages = array_unique(array_merge($productImages, $extraImages));
            // ==============================================
            $relatedStatement = $pdo->prepare(
                "SELECT
                    sp.IdSanPham,
                    sp.TenSanPham,
                    sp.HinhAnh,
                    sp.DonGia,
                    sp.TiLeGiamGia,
                    sp.SoLuong,
                    sp.MoTa,
                    sp.CauHinh,
                    sp.LuotXem,
                    0 AS DaBan,
                    COALESCE(nsx.TenNhaSanXuat, 'Chưa cập nhật') AS TenNhaSanXuat,
                    COALESCE(dm.TenDanhMuc, 'Laptop') AS TenDanhMuc,
                    (sp.DonGia - (sp.DonGia * sp.TiLeGiamGia / 100)) AS GiaSauGiam
                 FROM tbl_sanpham AS sp
                 LEFT JOIN tbl_nhasanxuat AS nsx ON nsx.IdNhaSanXuat = sp.IdNhaSanXuat
                 LEFT JOIN tbl_danhmuc AS dm ON dm.MaDanhMuc = sp.MaDanhMuc
                 WHERE sp.MaDanhMuc = :category AND sp.IdSanPham <> :id
                 ORDER BY sp.LuotXem DESC
                 LIMIT 4"
            );
            $relatedStatement->execute([
                'category' => (int) $product['MaDanhMuc'],
                'id' => $productId
            ]);
            $relatedProducts = $relatedStatement->fetchAll();
        }
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $databaseError = 'Không thể tải thông tin sản phẩm lúc này.';
}

$sharedData = loadSharedPageData($pdo);
$brands = $sharedData['brands'];
$categories = $sharedData['categories'];
$categoryHighlights = $sharedData['categoryHighlights'];
$cartCount = calculateCartCount($_SESSION['cart'] ?? []);
$pageTitle =
    $product !== null
        ? $product['TenSanPham'] . ' - LaptopShop'
        : 'Không tìm thấy sản phẩm - LaptopShop';
$pageDescription =
    $product !== null
        ? strip_tags((string) ($product['MoTa'] ?: $product['CauHinh']))
        : 'Thông tin chi tiết sản phẩm tại LaptopShop.';

if ($product === null && $databaseError === null) {
    http_response_code(404);
}

require __DIR__ . '/template/header.php';
?>
<main class="page-main">
  <div class="container">
    <nav class="page-breadcrumb" aria-label="Đường dẫn">
      <a href="index.php">Trang chủ</a><span class="page-breadcrumb__separator">›</span>
      <?php if ($product !== null): ?>
        <a href="index.php?category=<?= (int) $product['MaDanhMuc'] ?>#products">
          <?= escape($product['TenDanhMuc']) ?>
        </a>
        <span class="page-breadcrumb__separator">›</span><span><?= escape(
            $product['TenSanPham']
        ) ?></span>
      <?php else: ?>
        <span>Sản phẩm</span>
      <?php endif; ?>
    </nav>

    <?php if ($databaseError !== null): ?>
      <div class="empty-state page-state"><h1><?= escape($databaseError) ?></h1></div>
    <?php elseif ($product === null): ?>
      <div class="empty-state page-state">
        <i class="bi bi-search"></i>
        <h1>Không tìm thấy sản phẩm</h1>
        <p>Sản phẩm có thể đã ngừng kinh doanh hoặc đường dẫn không chính xác.</p>
        <a class="btn btn-primary" href="index.php#products">Quay lại danh sách</a>
      </div>
    <?php else: ?>
      <?php
      $originalPrice = (float) $product['DonGia'];
      $salePrice = (float) $product['GiaSauGiam'];
      $discount = (float) $product['TiLeGiamGia'];
      $stock = (int) $product['SoLuong'];
      $fullSpecifications = extractSpecifications($product['CauHinh'], 12);
      ?>
      <section class="product-detail">
        <div class="product-detail__gallery">
          
          <!-- Khung ảnh chính lớn -->
          <div class="product-main-image-container">
            <button type="button" class="gallery-nav-btn prev-btn" onclick="changeImage(-1)">
              <i class="bi bi-chevron-left"></i>
            </button>
            
            <img
              id="main-product-img"
              src="<?= escape(
                  resolveProductImage(
                      $productImages[0] ?? $product['HinhAnh'],
                      $product['TenSanPham']
                  )
              ) ?>"
              alt="<?= escape($product['TenSanPham']) ?>"
            />
            
            <button type="button" class="gallery-nav-btn next-btn" onclick="changeImage(1)">
              <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Huy hiệu đếm số lượng ảnh (Ví dụ: 1/3) -->
            <div class="gallery-counter">
              <span id="current-index">1</span>/<?= count($productImages) ?>
            </div>
          </div>

          <!-- Danh sách Thumbnail nhỏ bên dưới -->
          <?php if (count($productImages) > 1): ?>
            <div class="product-thumbnails-slider">
              <?php foreach ($productImages as $index => $img): ?>
                <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                     onclick="setMainImage('<?= escape(
                         resolveProductImage($img, $product['TenSanPham'])
                     ) ?>', <?= $index ?>)">
                  <img src="<?= escape(
                      resolveProductImage($img, $product['TenSanPham'])
                  ) ?>" alt="Thumbnail <?= $index + 1 ?>" />
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
        <script>
          const productImages = <?= json_encode(
              array_map(function ($img) use ($product) {
                  return resolveProductImage($img, $product['TenSanPham']);
              }, $productImages)
          ) ?>;
  
  let currentIndex = 0;

  function setMainImage(url, index) {
      currentIndex = index;
      document.getElementById('main-product-img').src = url;
      document.getElementById('current-index').textContent = currentIndex + 1;
      
      document.querySelectorAll('.thumbnail-item').forEach((item, idx) => {
          if (idx === currentIndex) {
              item.classList.add('active');
          } else {
              item.classList.remove('active');
          }
      });
  }

  function changeImage(direction) {
      currentIndex += direction;
      if (currentIndex < 0) {
          currentIndex = productImages.length - 1;
      } else if (currentIndex >= productImages.length) {
          currentIndex = 0;
      }
      setMainImage(productImages[currentIndex], currentIndex);
  }
        </script>
        <div class="product-detail__info">
          
          <h1><?= escape($product['TenSanPham']) ?></h1>
          <p class ="product-detail__desc">Thông số kỹ thuật</p>

          <div class="product-detail__price">
            <strong class = "product-detail__sale-price"><?= formatCurrency($salePrice) ?></strong>
            <?php if ($discount > 0): ?><del><?= formatCurrency(
    $originalPrice
) ?></del><?php endif; ?>
            <?php if ($discount > 0): ?>
            <span class="product-detail__discount"> - <?= (int) $discount ?>%</span>
          <?php endif; ?>
          </div>

          <?php if ($fullSpecifications !== []): ?>
            <ul class="product-detail__specs">
              <?php foreach ($fullSpecifications as $specification): ?>
                <li><span><?= escape($specification) ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <div class="product-detail__availability <?= $stock > 0
              ? 'is-available'
              : 'is-unavailable' ?>">
            <?= $stock > 0 ? 'Còn ' . $stock . ' sản phẩm' : 'Sản phẩm hiện đã hết hàng' ?>
          </div>

          <?php if ($stock > 0): ?>
            <form class="product-detail__buy" action="gio-hang.php" method="post">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
              <input type="hidden" name="action" value="add" />
              <input type="hidden" name="id" value="<?= (int) $product['IdSanPham'] ?>" />
              
              <!-- Phần chọn số lượng có nút bấm - / + -->
              <div class="quantity-wrapper-custom">
                <label for="product-quantity">Số lượng:</label>
                <div class="quantity-control">
                  <button type="button" class="qty-btn minus" onclick="decreaseQty()">-</button>
                  <input id="product-quantity" class="form-control" type="number" name="quantity" min="1" max="<?= $stock ?>" value="1" />
                  <button type="button" class="qty-btn plus" onclick="increaseQty()">+</button>
                </div>
              </div>

              <!-- Nút thêm vào giỏ hàng full width -->
              <button class="btn product-detail__cart" type="submit">
                <i class="bi bi-cart-plus"></i> Thêm vào giỏ hàng
              </button>
            </form>
          <?php endif; ?>

          <div class="product-detail__policies">
            <span><i class="bi bi-shield-check"></i> Hàng chính hãng</span>
            <span><i class="bi bi-arrow-repeat"></i> Hỗ trợ đổi trả</span>
            <span><i class="bi bi-truck"></i> Giao hàng toàn quốc</span>
          </div>

          <!-- JavaScript xử lý tăng giảm số lượng dựa theo tồn kho PHP ($stock) -->
          <script>
            function decreaseQty() {
                const input = document.getElementById('product-quantity');
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                }
            }

            function increaseQty() {
                const input = document.getElementById('product-quantity');
                let value = parseInt(input.value) || 1;
                let max = parseInt(input.getAttribute('max')) || 99;
                if (value < max) {
                    input.value = value + 1;
                }
            }
          </script>
        </div>
      </section>

      <section class="content-card product-description">
        <h2>Mô tả sản phẩm</h2>
        <p><?= nl2br(escape($product['MoTa'] ?: 'Thông tin sản phẩm đang được cập nhật.')) ?></p>
      </section>

      <?php if ($relatedProducts !== []): ?>
        <section class="related-products">
          <div class="section-heading"><div><h2>Sản phẩm liên quan</h2></div></div>
          <div class="related-products__grid">
            <?php foreach ($relatedProducts as $product): ?>
              <?php require __DIR__ . '/template/product-card.php'; ?>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/template/footer.php'; ?>
