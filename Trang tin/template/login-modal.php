<?php
$authUser = authCurrentUser();
$authFlash = authPullFlash();
$authOldInput = authPullOldInput();
$returnUrl = $_SERVER['REQUEST_URI'] ?? 'index.php';
$requestedAuthView = (string) ($_GET['auth'] ?? '');
$authActiveView = in_array($requestedAuthView, ['login', 'register', 'forgot'], true)
    ? $requestedAuthView
    : 'login';
$showAuthToast = $authFlash !== null && ($authUser !== null || $requestedAuthView === '');
?>

<?php if ($authUser === null): ?>
  <div
    class="modal fade auth-modal"
    id="login-modal"
    tabindex="-1"
    aria-labelledby="auth-modal-title"
    aria-hidden="true"
    data-auth-view="<?= escape($authActiveView) ?>"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content auth-modal__content">
        <button type="button" class="btn-close auth-modal__close" data-bs-dismiss="modal" aria-label="Đóng"></button>

        <div class="modal-body auth-modal__body">
          <?php if ($authFlash !== null && !$showAuthToast): ?>
            <div class="alert alert-<?= $authFlash['type'] === 'success'
                ? 'success'
                : 'danger' ?> py-2 auth-modal__alert" role="alert">
              <?= escape((string) $authFlash['message']) ?>
            </div>
          <?php endif; ?>

          <section class="auth-view" data-auth-panel="login" <?= $authActiveView === 'login'
              ? ''
              : 'hidden' ?>>
            <div class="auth-modal__heading">
              <h2 class="auth-modal__title" id="auth-modal-title">Đăng nhập LaptopShop</h2>
              <p class="auth-modal__subtitle">Nhận ưu đãi thành viên và theo dõi đơn hàng dễ dàng.</p>
            </div>

            <form action="dang-nhap.php" method="post" class="auth-form">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
              <input type="hidden" name="return_to" value="<?= escape($returnUrl) ?>" />

              <div>
                <label class="visually-hidden" for="login-identity">Số điện thoại, email hoặc tên đăng nhập</label>
                <input class="form-control auth-form__control" id="login-identity" name="identity" type="text" autocomplete="username" placeholder="Số điện thoại, email hoặc tên đăng nhập" required />
              </div>

              <div class="auth-password-field">
                <label class="visually-hidden" for="login-password">Mật khẩu</label>
                <input class="form-control auth-form__control" id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Mật khẩu" required />
                <button class="auth-password-toggle" type="button" data-password-toggle="login-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button>
              </div>

              <div class="auth-form__options">
                <label class="auth-remember">
                  <input type="checkbox" name="remember" value="1" />
                  <span>Ghi nhớ đăng nhập</span>
                </label>
                <button class="auth-text-button" type="button" data-auth-target="forgot">Quên mật khẩu?</button>
              </div>

              <button class="btn auth-submit" type="submit">Đăng nhập</button>
            </form>

            <p class="auth-modal__footer-text">
              Chưa có tài khoản?
              <button class="auth-text-button" type="button" data-auth-target="register">Đăng ký ngay</button>
            </p>
          </section>

          <section class="auth-view" data-auth-panel="register" <?= $authActiveView === 'register'
              ? ''
              : 'hidden' ?>>
            <div class="auth-modal__heading auth-modal__heading--center">
              <span class="auth-modal__icon"><i class="bi bi-person-plus"></i></span>
              <h2 class="auth-modal__title">Tạo tài khoản</h2>
              <p class="auth-modal__subtitle">Đăng ký để lưu giỏ hàng và theo dõi đơn hàng thuận tiện hơn.</p>
            </div>

            <form action="dang-ky.php" method="post" class="auth-form" data-password-form>
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
              <input type="hidden" name="return_to" value="<?= escape($returnUrl) ?>" />

              <div class="auth-form-grid">
                <div class="auth-field auth-field--wide">
                  <label for="register-name">Họ và tên</label>
                  <input id="register-name" name="name" type="text" value="<?= escape(
                      (string) ($authOldInput['name'] ?? '')
                  ) ?>" autocomplete="name" required />
                </div>
                <div class="auth-field">
                  <label for="register-username">Tên đăng nhập</label>
                  <input id="register-username" name="username" type="text" value="<?= escape(
                      (string) ($authOldInput['username'] ?? '')
                  ) ?>" autocomplete="username" required />
                </div>
                <div class="auth-field">
                  <label for="register-phone">Số điện thoại <span>(không bắt buộc)</span></label>
                  <input id="register-phone" name="phone" type="tel" value="<?= escape(
                      (string) ($authOldInput['phone'] ?? '')
                  ) ?>" autocomplete="tel" />
                </div>
                <div class="auth-field auth-field--wide">
                  <label for="register-email">Email</label>
                  <input id="register-email" name="email" type="email" value="<?= escape(
                      (string) ($authOldInput['email'] ?? '')
                  ) ?>" autocomplete="email" required />
                </div>
                <div class="auth-field">
                  <label for="register-password">Mật khẩu</label>
                  <div class="auth-password-field">
                    <input id="register-password" name="password" type="password" minlength="8" autocomplete="new-password" required />
                    <button type="button" class="auth-password-toggle" data-password-toggle="register-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button>
                  </div>
                </div>
                <div class="auth-field">
                  <label for="register-password-confirmation">Nhập lại mật khẩu</label>
                  <div class="auth-password-field">
                    <input id="register-password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required />
                    <button type="button" class="auth-password-toggle" data-password-toggle="register-password-confirmation" aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button>
                  </div>
                </div>
                <div class="auth-password-requirements auth-field--wide" aria-live="polite">
                  <div class="auth-password-strength">
                    <span>Độ an toàn mật khẩu</span>
                    <strong data-password-strength-label>Chưa nhập</strong>
                  </div>
                  <div class="auth-password-strength__track" aria-hidden="true">
                    <span data-password-strength-bar></span>
                  </div>
                  <ul>
                    <li data-password-rule="length"><i class="bi bi-circle"></i> Ít nhất 8 ký tự</li>
                    <li data-password-rule="lowercase"><i class="bi bi-circle"></i> Có chữ thường</li>
                    <li data-password-rule="uppercase"><i class="bi bi-circle"></i> Có chữ hoa</li>
                    <li data-password-rule="number"><i class="bi bi-circle"></i> Có chữ số</li>
                    <li data-password-rule="special"><i class="bi bi-circle"></i> Có ký tự đặc biệt</li>
                    <li data-password-rule="matching"><i class="bi bi-circle"></i> Hai mật khẩu trùng nhau</li>
                  </ul>
                </div>
              </div>

              <label class="auth-terms">
                <input type="checkbox" required data-password-terms />
                <span>Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.</span>
              </label>
              <button class="btn auth-submit" type="submit" data-password-submit>Tạo tài khoản</button>
            </form>

            <p class="auth-modal__footer-text">
              Đã có tài khoản?
              <button class="auth-text-button" type="button" data-auth-target="login">Đăng nhập</button>
            </p>
          </section>

          <section class="auth-view" data-auth-panel="forgot" <?= $authActiveView === 'forgot'
              ? ''
              : 'hidden' ?>>
            <div class="auth-modal__heading auth-modal__heading--center">
              <span class="auth-modal__icon"><i class="bi bi-key"></i></span>
              <h2 class="auth-modal__title">Quên mật khẩu?</h2>
              <p class="auth-modal__subtitle">Nhập email đã đăng ký để yêu cầu đặt lại mật khẩu.</p>
            </div>

            <form action="quen-mat-khau.php" method="post" class="auth-form">
              <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>" />
              <input type="hidden" name="return_to" value="<?= escape($returnUrl) ?>" />
              <div class="auth-field">
                <label for="forgot-email">Email tài khoản</label>
                <input id="forgot-email" name="email" type="email" value="<?= escape(
                    (string) ($authOldInput['email'] ?? '')
                ) ?>" autocomplete="email" placeholder="vidu@email.com" required />
              </div>
              <button class="btn auth-submit" type="submit">Gửi yêu cầu</button>
            </form>

            <p class="auth-modal__footer-text">
              <button class="auth-text-button" type="button" data-auth-target="login"><i class="bi bi-arrow-left"></i> Quay lại đăng nhập</button>
            </p>
            <p class="auth-delivery-note"><i class="bi bi-info-circle"></i> Cần cấu hình email/SMTP để gửi thư khôi phục thực tế.</p>
          </section>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($showAuthToast): ?>
  <div class="auth-toast alert alert-<?= $authFlash['type'] === 'success'
      ? 'success'
      : 'danger' ?>" role="status" data-auth-toast>
    <i class="bi bi-<?= $authFlash['type'] === 'success'
        ? 'check-circle-fill'
        : 'exclamation-circle-fill' ?>"></i>
    <?= escape((string) $authFlash['message']) ?>
  </div>
<?php endif; ?>
