document.addEventListener('DOMContentLoaded', () => {
    const fallbackImage = './assets/img/product-placeholder.svg';

    document.querySelectorAll('.product-card__image').forEach((image) => {
        image.addEventListener('error', () => {
            if (!image.src.endsWith('product-placeholder.svg')) {
                image.src = fallbackImage;
            }
        });
    });

    const vietnameseValidationMessage = (field) => {
        const labelText =
            field.dataset.validationLabel ||
            field.labels?.[0]?.textContent.replace(/\s*\*\s*|\s*\([^)]*\)\s*/g, ' ').trim() ||
            'trường này';

        if (field.validity.valueMissing) {
            return ['checkbox', 'radio'].includes(field.type)
                ? `Vui lòng chọn ${labelText}.`
                : `Vui lòng nhập ${labelText}.`;
        }

        if (field.validity.typeMismatch) {
            return field.type === 'email'
                ? 'Vui lòng nhập địa chỉ email hợp lệ.'
                : `Giá trị ${labelText} chưa đúng định dạng.`;
        }

        if (field.validity.patternMismatch) {
            return field.type === 'tel'
                ? 'Vui lòng nhập số điện thoại Việt Nam hợp lệ.'
                : `${labelText} chưa đúng định dạng yêu cầu.`;
        }

        if (field.validity.tooShort) {
            return `Vui lòng nhập ${labelText} tối thiểu ${field.minLength} ký tự.`;
        }

        if (field.validity.tooLong) {
            return `${labelText} không được vượt quá ${field.maxLength} ký tự.`;
        }

        if (field.validity.rangeUnderflow || field.validity.rangeOverflow) {
            return `${labelText} nằm ngoài phạm vi cho phép.`;
        }

        return `Vui lòng kiểm tra lại ${labelText}.`;
    };

    document.querySelectorAll('input, textarea, select').forEach((field) => {
        field.addEventListener('invalid', () => {
            if (!field.validity.customError) {
                field.setCustomValidity(vietnameseValidationMessage(field));
            }
        });

        const clearValidationMessage = () => field.setCustomValidity('');
        field.addEventListener('input', clearValidationMessage);
        field.addEventListener('change', clearValidationMessage);
    });

    const loginModalElement = document.querySelector('#login-modal');
    const loginModal =
        loginModalElement && window.bootstrap
            ? window.bootstrap.Modal.getOrCreateInstance(loginModalElement)
            : null;

    const setAuthView = (view) => {
        if (!loginModalElement) {
            return;
        }

        const selectedView = ['login', 'register', 'forgot'].includes(view) ? view : 'login';
        loginModalElement.dataset.authView = selectedView;

        loginModalElement.querySelectorAll('[data-auth-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.authPanel !== selectedView;
        });

        window.setTimeout(() => {
            const firstInput = loginModalElement.querySelector(
                `[data-auth-panel="${selectedView}"] input:not([type="hidden"])`,
            );
            firstInput?.focus();
        }, 160);
    };

    document.querySelectorAll('[data-open-login]').forEach((button) => {
        button.addEventListener('click', () => {
            setAuthView('login');
            loginModal?.show();
        });
    });

    loginModalElement?.addEventListener('hidden.bs.modal', () => {
        setAuthView('login');
    });

    document.querySelectorAll('[data-auth-target]').forEach((button) => {
        button.addEventListener('click', () => setAuthView(button.dataset.authTarget));
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);

            if (!input) {
                return;
            }

            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-label', showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            const icon = button.querySelector('i');

            if (icon) {
                icon.className = showPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    });

    const passwordForm = document.querySelector('[data-password-form]');

    if (passwordForm) {
        const passwordInput = passwordForm.querySelector('#register-password');
        const confirmationInput = passwordForm.querySelector('#register-password-confirmation');
        const termsInput = passwordForm.querySelector('[data-password-terms]');
        const submitButton = passwordForm.querySelector('[data-password-submit]');
        const strengthBar = passwordForm.querySelector('[data-password-strength-bar]');
        const strengthLabel = passwordForm.querySelector('[data-password-strength-label]');

        const updatePasswordRequirements = () => {
            const password = passwordInput?.value ?? '';
            const confirmation = confirmationInput?.value ?? '';
            const rules = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^\p{L}\p{N}\s]/u.test(password),
                matching: confirmation !== '' && password === confirmation,
            };

            Object.entries(rules).forEach(([name, isValid]) => {
                const ruleElement = passwordForm.querySelector(`[data-password-rule="${name}"]`);
                const icon = ruleElement?.querySelector('i');
                const hasInput = name === 'matching' ? confirmation !== '' : password !== '';

                ruleElement?.classList.toggle('is-valid', isValid);
                ruleElement?.classList.toggle('is-invalid', hasInput && !isValid);

                if (icon) {
                    icon.className = isValid
                        ? 'bi bi-check-circle-fill'
                        : hasInput
                          ? 'bi bi-x-circle-fill'
                          : 'bi bi-circle';
                }
            });

            const strengthScore = ['length', 'lowercase', 'uppercase', 'number', 'special'].filter(
                (name) => rules[name],
            ).length;
            const strengthLevel =
                strengthScore <= 2 ? 'weak' : strengthScore <= 4 ? 'medium' : 'strong';

            if (strengthBar) {
                strengthBar.style.width = `${(strengthScore / 5) * 100}%`;
                strengthBar.dataset.level = password === '' ? 'empty' : strengthLevel;
            }

            if (strengthLabel) {
                strengthLabel.textContent =
                    password === ''
                        ? 'Chưa nhập'
                        : strengthLevel === 'weak'
                          ? 'Yếu'
                          : strengthLevel === 'medium'
                            ? 'Trung bình'
                            : 'Mạnh';
                strengthLabel.dataset.level = password === '' ? 'empty' : strengthLevel;
            }

            const passwordIsValid = Object.values(rules).every(Boolean);
            const formCanSubmit = passwordIsValid && Boolean(termsInput?.checked);

            if (submitButton) {
                submitButton.disabled = !formCanSubmit;
                submitButton.setAttribute('aria-disabled', formCanSubmit ? 'false' : 'true');
            }

            passwordInput?.setCustomValidity(
                password !== '' && !Object.values(rules).slice(0, 5).every(Boolean)
                    ? 'Mật khẩu chưa đáp ứng đầy đủ các tiêu chí.'
                    : '',
            );
            confirmationInput?.setCustomValidity(
                confirmation !== '' && !rules.matching ? 'Mật khẩu xác nhận chưa khớp.' : '',
            );
        };

        passwordInput?.addEventListener('input', updatePasswordRequirements);
        confirmationInput?.addEventListener('input', updatePasswordRequirements);
        termsInput?.addEventListener('change', updatePasswordRequirements);
        updatePasswordRequirements();
    }

    const requestedAuthView = new URLSearchParams(window.location.search).get('auth');

    if (loginModal && ['login', 'register', 'forgot'].includes(requestedAuthView)) {
        setAuthView(requestedAuthView);
        loginModal.show();
    }

    const authToast = document.querySelector('[data-auth-toast]');

    if (authToast) {
        window.setTimeout(() => authToast.remove(), 4000);
    }

    const stickyHeader = document.querySelector('.site-header-sticky');
    const menuToggle = document.querySelector('#category-menu-toggle');
    const megaMenu = document.querySelector('#category-mega-menu');
    const megaBackdrop = document.querySelector('#mega-menu-backdrop');
    const categoryLinks = document.querySelectorAll('[data-mega-target]');
    const menuPanels = document.querySelectorAll('.mega-menu__panel');

    if (!stickyHeader || !menuToggle || !megaMenu || !megaBackdrop) {
        return;
    }

    const updateMenuPosition = () => {
        const headerBottom = stickyHeader.getBoundingClientRect().bottom;
        document.documentElement.style.setProperty(
            '--mega-menu-top',
            `${Math.max(0, headerBottom)}px`,
        );
    };

    const showPanel = (targetId) => {
        categoryLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.megaTarget === targetId);
        });

        menuPanels.forEach((panel) => {
            const isActive = panel.id === targetId;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
        });
    };

    const openMenu = () => {
        updateMenuPosition();
        megaMenu.hidden = false;
        megaBackdrop.hidden = false;
        menuToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mega-menu-open');
    };

    const closeMenu = () => {
        megaMenu.hidden = true;
        megaBackdrop.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mega-menu-open');
    };

    menuToggle.addEventListener('click', () => {
        if (menuToggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        } else {
            openMenu();
        }
    });

    categoryLinks.forEach((link) => {
        const activatePanel = () => showPanel(link.dataset.megaTarget);
        link.addEventListener('mouseenter', activatePanel);
        link.addEventListener('focus', activatePanel);
    });

    megaBackdrop.addEventListener('click', closeMenu);
    window.addEventListener('resize', updateMenuPosition);
    window.addEventListener('scroll', updateMenuPosition, { passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            menuToggle.focus();
        }
    });
});
