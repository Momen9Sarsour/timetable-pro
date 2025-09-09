// ============================================
// نظام إدارة الجداول الدراسية - JavaScript مبسط
// كلية فلسطين التقنية
// ============================================

document.addEventListener('DOMContentLoaded', function() {

    // ============= المتغيرات الأساسية =============
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
    const backToTopBtn = document.getElementById('backToTop');

    // ============= نظام الثيم =============
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        updateThemeIcons();
    }

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeIcons();
        });
    });

    function updateThemeIcons() {
        const isDarkMode = body.classList.contains('dark-mode');
        themeToggleBtns.forEach(btn => {
            const icon = btn.querySelector('.theme-icon');
            if (icon) {
                icon.className = isDarkMode ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
        });
    }

    // ============= القائمة الجانبية =============
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            if (sidebar && !sidebar.contains(e.target) &&
                sidebarToggle && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        }
    });

    function toggleSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active');
        }
        if (sidebar.classList.contains('active')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }
        body.style.overflow = '';
    }

    // ============= القوائم المنسدلة =============
    const dropdownToggles = document.querySelectorAll('.sidebar .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;

            if (!e.ctrlKey) {
                dropdownToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        otherToggle.parentElement.classList.remove('active');
                    }
                });
            }
            parent.classList.toggle('active');
        });
    });

    // الحفاظ على القوائم المفتوحة للصفحة النشطة
    const activeSubmenuItems = document.querySelectorAll('.submenu li.active');
    activeSubmenuItems.forEach(item => {
        const parentSubmenu = item.closest('.submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('show');
            const navItem = parentSubmenu.closest('.nav-item');
            if (navItem) {
                navItem.classList.add('active');
            }
        }
    });

    // ============= زر العودة للأعلى =============
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ============= AOS Animation =============
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 300,
            once: true,
            offset: 30,
            delay: 50
        });
    }

    // ============= Bootstrap Components =============
    // Tooltips
    try {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } catch (error) {
        console.warn('Tooltips failed to initialize');
    }

    // ============= معالجة النماذج (مبسط) =============
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Loading state للنماذج (بدون تعطيل الإرسال)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        if (submitBtn && !submitBtn.hasAttribute('data-bs-toggle')) {
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;

            // إعادة الزر بعد ثانيتين للأمان
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        }
    });

    // ============= إخفاء التنبيهات تلقائياً =============
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            } catch (error) {
                alert.style.display = 'none';
            }
        });
    }, 4000);

    // ============= Responsive =============
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        }, 100);
    });

    // ============= دوال مساعدة =============
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            }
        } catch (error) {
            setTimeout(() => toast.remove(), 3000);
        }
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1060';
        document.body.appendChild(container);
        return container;
    }

    console.log('✅ Timetable Pro initialized successfully');
    console.log('✅ All modal and form issues resolved');
});

// ============= حل مشكلة المودال نهائياً =============
// تنظيف بسيط وفعال
document.addEventListener('DOMContentLoaded', function() {
    // إزالة أي backdrop عالق
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';

    console.log('✅ Modal cleanup completed - Bootstrap will handle everything else');
});

// لا نتدخل في Bootstrap Modal - نتركه يعمل بشكل طبيعي
