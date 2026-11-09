<?php
$keyword = $keyword ?? '';
$brandId = $brandId ?? null;
$categoryId = $categoryId ?? null;
$priceRange = $priceRange ?? '';
$brands = $brands ?? [];
$categories = $categories ?? [];
$categoryHighlights = $categoryHighlights ?? [];
$cartCount = $cartCount ?? 0;
$pageTitle = $pageTitle ?? 'LaptopShop - Laptop chính hãng';
$pageDescription =
    $pageDescription ??
    'LaptopShop - laptop chính hãng dành cho học tập, văn phòng, đồ họa và gaming.';
$mainCssPath = __DIR__ . '/../assets/css/main.css';
$mainCssVersion = is_file($mainCssPath) ? (string) filemtime($mainCssPath) : '1';

if (!function_exists('escape')) {
    function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="<?= escape($pageDescription) ?>"
    />
    <title><?= escape($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Lato:wght@400;500;600;700;900&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    />
    <link rel="stylesheet" href="./assets/css/main.css?v=<?= escape($mainCssVersion) ?>" />
  </head>

  <body>
    <div class="site-header-sticky">
    <div class="topbar d-none d-md-block">
      <div class="container topbar__inner">
        <span><i class="bi bi-shield-check"></i> Sản phẩm chính hãng</span>
        <span><i class="bi bi-telephone"></i> Hotline: 1900 0000</span>
      </div>
    </div>

    <header id="header" class="site-header">
      <div class="container site-header__inner">
        <a class="brand" href="index.php" aria-label="Về trang chủ LaptopShop">
          <img src="./assets/img/logo1.png" alt="" class="brand__image" />
          <span class="brand__name">LaptopShop</span>
        </a>

        <form class="search-form" action="index.php" method="get" role="search">
          <?php if ($categoryId !== null): ?>
            <input type="hidden" name="category" value="<?= $categoryId ?>" />
          <?php endif; ?>
          <?php if ($priceRange !== ''): ?>
            <input type="hidden" name="price" value="<?= escape($priceRange) ?>" />
          <?php endif; ?>
          <label class="visually-hidden" for="search-input">Tìm kiếm laptop</label>
          <input
            id="search-input"
            class="form-control search-form__input"
            type="search"
            name="keyword"
            value="<?= escape($keyword) ?>"
            placeholder="Bạn cần tìm laptop nào?"
          />
          <button class="btn search-form__button" type="submit" aria-label="Tìm kiếm">
            <i class="bi bi-search"></i>
          </button>
        </form>

        <div class="header-actions">
          <a href="gio-hang.php" class="header-action" aria-label="Giỏ hàng">
            <span class="header-action__icon">
              <i class="bi bi-cart3"></i>
              <span class="cart-count"><?= $cartCount ?></span>
            </span>
            <span class="header-action__text">Giỏ hàng</span>
          </a>

          <a href="dang-nhap.php" class="header-action">
            <span class="header-action__icon"><i class="bi bi-person"></i></span>
            <span class="header-action__text">Đăng nhập</span>
          </a>
        </div>
      </div>
    </header>

    <nav class="navbar navbar-expand-lg main-nav" aria-label="Điều hướng chính">
      <div class="container">
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#main-navigation"
          aria-controls="main-navigation"
          aria-expanded="false"
          aria-label="Mở menu"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div id="main-navigation" class="collapse navbar-collapse">
          <ul class="navbar-nav align-items-lg-center">
            <?php if ($categories !== []): ?>
              <li class="nav-item category-menu">
                <button
                  id="category-menu-toggle"
                  class="nav-link category-menu__toggle"
                  type="button"
                  aria-expanded="false"
                  aria-controls="category-mega-menu"
                >
                  <span class="category-menu__icon" aria-hidden="true">
                    <span></span><span></span><span></span>
                  </span>
                  <span>Danh mục</span>
                </button>
              </li>
            <?php endif; ?>
      
            <li class="nav-item"><a class="nav-link" href="lien-he.php">Giới thiệu</a></li>
            <li class="nav-item"><a class="nav-link" href="#">Hỗ trợ</a></li>
          </ul>

        
        </div>
      </div>
    </nav>
    </div>

    <?php if ($categories !== []): ?>
      <div id="mega-menu-backdrop" class="mega-menu-backdrop" hidden></div>
      <div id="category-mega-menu" class="mega-menu" hidden>
        <div class="container mega-menu__shell">
          <nav class="mega-menu__categories" aria-label="Danh sách danh mục sản phẩm">
            <?php foreach ($categories as $index => $category): ?>
              <?php $menuCategoryId = (int) $category['MaDanhMuc']; ?>
              <a
                class="mega-menu__category <?= $index === 0 ? 'is-active' : '' ?>"
                href="index.php?category=<?= $menuCategoryId ?>#products"
                data-mega-target="mega-category-<?= $menuCategoryId ?>"
              >
                <span><?= escape($category['TenDanhMuc']) ?></span>
                <span aria-hidden="true">›</span>
              </a>
            <?php endforeach; ?>
          </nav>

          <div class="mega-menu__content">
            <?php foreach ($categories as $index => $category): ?>
              <?php
              $menuCategoryId = (int) $category['MaDanhMuc'];
              $highlightProducts = $categoryHighlights[$menuCategoryId] ?? [];
              ?>
              <section
                id="mega-category-<?= $menuCategoryId ?>"
                class="mega-menu__panel <?= $index === 0 ? 'is-active' : '' ?>"
                <?= $index === 0 ? '' : 'hidden' ?>
              >
                <div class="mega-menu__panel-heading">
                  <h2><?= escape($category['TenDanhMuc']) ?></h2>
                  <a href="index.php?category=<?= $menuCategoryId ?>#products">Xem tất cả ›</a>
                </div>

                <div class="mega-menu__columns">
                  <div class="mega-menu__column">
                    <h3>Thương hiệu</h3>
                    <?php foreach ($brands as $brand): ?>
                      <a href="index.php?category=<?= $menuCategoryId ?>&amp;brand=<?= (int) $brand[
    'IdNhaSanXuat'
] ?>#products">
                        <?= escape($brand['TenNhaSanXuat']) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>

                  <div class="mega-menu__column">
                    <h3>Giá bán</h3>
                    <a href="index.php?category=<?= $menuCategoryId ?>&amp;price=under-15#products">Dưới 15 triệu</a>
                    <a href="index.php?category=<?= $menuCategoryId ?>&amp;price=15-20#products">Từ 15 đến 20 triệu</a>
                    <a href="index.php?category=<?= $menuCategoryId ?>&amp;price=over-20#products">Trên 20 triệu</a>
                  </div>

                  <div class="mega-menu__column mega-menu__column--wide">
                    <h3>Sản phẩm nổi bật</h3>
                    <?php if ($highlightProducts !== []): ?>
                      <?php foreach ($highlightProducts as $highlightProduct): ?>
                        <a href="chi-tiet-san-pham.php?id=<?= (int) $highlightProduct[
                            'IdSanPham'
                        ] ?>">
                          <?= escape($highlightProduct['TenSanPham']) ?>
                        </a>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <a href="index.php?category=<?= $menuCategoryId ?>#products">Khám phá sản phẩm trong danh mục</a>
                    <?php endif; ?>
                  </div>

                  <div class="mega-menu__column">
                    <h3>Danh mục khác</h3>
                    <?php foreach ($categories as $relatedCategory): ?>
                      <?php if ((int) $relatedCategory['MaDanhMuc'] !== $menuCategoryId): ?>
                        <a href="index.php?category=<?= (int) $relatedCategory[
                            'MaDanhMuc'
                        ] ?>#products">
                          <?= escape($relatedCategory['TenDanhMuc']) ?>
                        </a>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                </div>
              </section>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

