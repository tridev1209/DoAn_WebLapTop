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
$currentPage = 'support';
$pageTitle = 'Trung tâm hỗ trợ - LaptopShop';
$pageDescription = 'Hướng dẫn mua hàng, chính sách bảo hành và các kênh liên hệ hỗ trợ của LaptopShop.';

require __DIR__ . '/template/header.php';
?>
<main class="content-page">
  <section class="content-hero content-hero--support">
    <div class="container content-hero__inner">
      <div>
        <span class="content-eyebrow">Trung tâm hỗ trợ</span>
        <h1>Chúng tôi có thể giúp gì cho bạn?</h1>
        <p>Tìm nhanh hướng dẫn mua hàng, thông tin bảo hành hoặc kết nối với đội ngũ tư vấn.</p>
      </div>
      <div class="content-hero__visual" aria-hidden="true"><i class="bi bi-headset"></i></div>
    </div>
  </section>

  <section id="lien-he" class="content-section">
    <div class="container">
      <div class="support-contact-grid">
        <a class="support-contact-card" href="tel:19000000">
          <i class="bi bi-telephone"></i><span><small>Hotline tư vấn</small><strong>1900 0000</strong><em>8:00 – 21:00 mỗi ngày</em></span>
        </a>
        <a class="support-contact-card" href="mailto:hotro@laptopshop.vn">
          <i class="bi bi-envelope"></i><span><small>Hỗ trợ qua email</small><strong>hotro@laptopshop.vn</strong><em>Phản hồi trong giờ làm việc</em></span>
        </a>
        <a class="support-contact-card" href="#cau-hoi">
          <i class="bi bi-question-circle"></i><span><small>Tự tìm câu trả lời</small><strong>Câu hỏi thường gặp</strong><em>Thông tin nhanh, dễ tra cứu</em></span>
        </a>
      </div>
    </div>
  </section>

  <section id="huong-dan" class="content-section content-section--muted">
    <div class="container">
      <div class="content-heading"><span class="content-eyebrow">Hướng dẫn mua hàng</span><h2>Đặt mua chỉ trong 4 bước</h2></div>
      <ol class="purchase-steps">
        <li><span>1</span><div><strong>Chọn sản phẩm</strong><p>Tìm kiếm hoặc duyệt danh mục, sau đó xem cấu hình chi tiết.</p></div></li>
        <li><span>2</span><div><strong>Thêm giỏ hoặc mua ngay</strong><p>“Thêm vào giỏ” để mua nhiều món; “Mua ngay” để ưu tiên một sản phẩm.</p></div></li>
        <li><span>3</span><div><strong>Nhập thông tin giao hàng</strong><p>Đăng nhập và cung cấp thông tin người nhận chính xác.</p></div></li>
        <li><span>4</span><div><strong>Xác nhận đơn hàng</strong><p>Kiểm tra sản phẩm, tổng tiền và hoàn tất đặt mua.</p></div></li>
      </ol>
    </div>
  </section>

  <section id="bao-hanh" class="content-section">
    <div class="container policy-panel">
      <div><span class="content-eyebrow">Bảo hành &amp; đổi trả</span><h2>An tâm trong suốt quá trình sử dụng</h2></div>
      <div class="policy-list">
        <p><i class="bi bi-check-circle-fill"></i><span><strong>Tiếp nhận minh bạch</strong> Kiểm tra tình trạng và thông báo hướng xử lý trước khi thực hiện.</span></p>
        <p><i class="bi bi-check-circle-fill"></i><span><strong>Theo chính sách hãng</strong> Thời hạn và điều kiện bảo hành áp dụng theo từng sản phẩm.</span></p>
        <p><i class="bi bi-check-circle-fill"></i><span><strong>Hỗ trợ đổi trả</strong> Sản phẩm cần đầy đủ phụ kiện, hóa đơn và đáp ứng điều kiện áp dụng.</span></p>
      </div>
    </div>
  </section>

  <section id="cau-hoi" class="content-section content-section--muted">
    <div class="container faq-wrap">
      <div class="content-heading"><span class="content-eyebrow">Câu hỏi thường gặp</span><h2>Giải đáp nhanh</h2></div>
      <div class="accordion" id="support-faq">
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-one">Tôi có cần đăng nhập để đặt hàng không?</button></h3><div id="faq-one" class="accordion-collapse collapse show" data-bs-parent="#support-faq"><div class="accordion-body">Có. Đăng nhập giúp hệ thống lưu đơn hàng và cho phép bạn theo dõi trạng thái xử lý.</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-two">“Mua ngay” khác gì “Thêm vào giỏ hàng”?</button></h3><div id="faq-two" class="accordion-collapse collapse" data-bs-parent="#support-faq"><div class="accordion-body">“Mua ngay” chuyển tới giỏ và chỉ chọn sản phẩm vừa mua; “Thêm vào giỏ” phù hợp khi bạn muốn tiếp tục chọn thêm sản phẩm.</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-three">Tôi theo dõi đơn hàng ở đâu?</button></h3><div id="faq-three" class="accordion-collapse collapse" data-bs-parent="#support-faq"><div class="accordion-body">Sau khi đăng nhập, mở menu tài khoản ở đầu trang và chọn “Đơn hàng của tôi”.</div></div></div>
      </div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/template/footer.php'; ?>
