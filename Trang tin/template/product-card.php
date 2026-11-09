<?php
$originalPrice = (float) $product['DonGia'];
$salePrice = (float) $product['GiaSauGiam'];
$discount = (float) $product['TiLeGiamGia'];
$stock = (int) $product['SoLuong'];
$sold = (int) ($product['DaBan'] ?? 0);
$views = (int) ($product['LuotXem'] ?? 0);
$specifications = extractSpecifications($product['CauHinh'] ?: $product['MoTa']);
?>
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
    <div class="product-card__metric">
      <?php if ($hasSales): ?>
        Đã bán <?= number_format($sold, 0, ',', '.') ?>
      <?php else: ?>
        <?= number_format($views, 0, ',', '.') ?> lượt xem
      <?php endif; ?>
    </div>

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
      <span class="stock-status <?= $stock > 0 ? 'is-available' : 'is-unavailable' ?>">
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
