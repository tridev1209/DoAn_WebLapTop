<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/page-helpers.php';

$pdo = null;

try {
    $pdo = getDatabaseConnection();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
}

$sharedData = loadSharedPageData($pdo);
$brands = $sharedData['brands'];
$categories = $sharedData['categories'];
$categoryHighlights = $sharedData['categoryHighlights'];
$keyword = '';
$brandId = null;
$categoryId = null;
$priceRange = '';
$cartCount = calculateCartCount($_SESSION['cart'] ?? []);
$currentPage = 'about';
$pageTitle = 'Giới thiệu LaptopShop - Laptop chính hãng';
$pageDescription = 'Tìm hiểu về LaptopShop, định hướng phục vụ và những cam kết dành cho khách hàng.';

require __DIR__ . '/template/header.php';
?>
<main class="content-page">
  <section class="content-hero content-hero--about">
    <div class="container content-hero__inner">
      <div>
        <span class="content-eyebrow">Về LaptopShop</span>
        <h1>Chọn đúng chiếc laptop cho hành trình của bạn</h1>
        <p>
          LaptopShop hướng đến trải nghiệm mua sắm minh bạch, dễ hiểu và phù hợp với
          nhu cầu thực tế của từng khách hàng.
        </p>
        <a class="btn content-hero__button" href="index.php#products">Khám phá sản phẩm</a>
      </div>
      <div class="content-hero__visual" aria-hidden="true"><i class="bi bi-laptop"></i></div>
    </div>
  </section>

  <section class="content-section">
    <div class="container story-grid">
      <div>
        <span class="content-eyebrow">Câu chuyện của chúng tôi</span>
        <h2>Công nghệ phù hợp tạo nên khác biệt</h2>
      </div>
      <div class="story-copy">
        <p>
          LaptopShop được xây dựng với mục tiêu giúp học sinh, sinh viên, nhân viên văn phòng
          và người dùng sáng tạo tìm được thiết bị phù hợp mà không bị rối bởi quá nhiều thông số.
        </p>
        <p>
          Mỗi sản phẩm được trình bày rõ cấu hình, giá bán, mức giảm và tình trạng tồn kho.
          Đội ngũ hỗ trợ luôn sẵn sàng tư vấn dựa trên nhu cầu và ngân sách của khách hàng.
        </p>
      </div>
    </div>
  </section>

  <section id="gia-tri" class="content-section content-section--muted">
    <div class="container">
      <div class="content-heading">
        <span class="content-eyebrow">Giá trị cam kết</span>
        <h2>Những điều LaptopShop luôn theo đuổi</h2>
      </div>
      <div class="value-grid">
        <article class="value-card">
          <i class="bi bi-patch-check"></i>
          <h3>Sản phẩm chính hãng</h3>
          <p>Nguồn gốc rõ ràng, thông tin sản phẩm minh bạch và chính sách bảo hành cụ thể.</p>
        </article>
        <article class="value-card">
          <i class="bi bi-chat-heart"></i>
          <h3>Tư vấn đúng nhu cầu</h3>
          <p>Ưu tiên hiệu quả sử dụng và ngân sách thay vì đề xuất cấu hình không cần thiết.</p>
        </article>
        <article class="value-card">
          <i class="bi bi-shield-check"></i>
          <h3>Đồng hành sau mua</h3>
          <p>Hỗ trợ sử dụng, tiếp nhận bảo hành và giải đáp trong suốt vòng đời sản phẩm.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="content-cta">
    <div class="container content-cta__inner">
      <div><span>Bạn chưa biết chọn máy nào?</span><strong>Hãy để LaptopShop tư vấn cùng bạn.</strong></div>
      <a class="btn" href="ho-tro.php#lien-he">Nhận hỗ trợ <i class="bi bi-arrow-right"></i></a>
    </div>
  </section>
</main>
<?php require __DIR__ . '/template/footer.php'; ?>
