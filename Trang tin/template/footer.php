    <footer class="footer">
      <div class="container">
        <div class="row g-4">
          <div class="col-12 col-lg-4">
            <a class="brand footer__brand" href="index.php">
              <img src="./assets/img/logo1.png" alt="" class="brand__image" />
              <span class="brand__name">LaptopShop</span>
            </a>
            <p class="footer__description">
              Laptop chính hãng cho học tập, công việc và giải trí.
            </p>
          </div>
          <div class="col-6 col-lg-3">
            <h2 class="footer__title">Hỗ trợ khách hàng</h2>
            <ul class="footer__list">
              <li><a href="ho-tro.php#lien-he">Liên hệ</a></li>
              <li><a href="ho-tro.php#bao-hanh">Chính sách bảo hành</a></li>
              <li><a href="ho-tro.php#huong-dan">Hướng dẫn mua hàng</a></li>
            </ul>
          </div>
          <div class="col-6 col-lg-3">
            <h2 class="footer__title">Về LaptopShop</h2>
            <ul class="footer__list">
              <li><a href="gioi-thieu.php">Giới thiệu</a></li>
              <li><a href="gioi-thieu.php#gia-tri">Giá trị cam kết</a></li>
              <li><a href="ho-tro.php#cau-hoi">Câu hỏi thường gặp</a></li>
            </ul>
          </div>
          <div class="col-12 col-lg-2">
            <h2 class="footer__title">Tổng đài</h2>
            <a class="footer__hotline" href="tel:19000000">1900 0000</a>
            <span>8:00 – 21:00</span>
          </div>
        </div>
        <div class="footer__bottom">© <?= date('Y') ?> LaptopShop. Đồ án website bán laptop.</div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    $mainJsPath = __DIR__ . '/../assets/js/main.js';
    $mainJsVersion = is_file($mainJsPath) ? (string) filemtime($mainJsPath) : '1';
    ?>
    <script src="./assets/js/main.js?v=<?= escape($mainJsVersion) ?>"></script>
  </body>
</html>
