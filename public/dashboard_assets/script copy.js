// ============================================
// نظام إدارة الجداول الدراسية
// كلية فلسطين التقنية
// ملف JavaScript الرئيسي
// ============================================

document.addEventListener('DOMContentLoaded', function() {

    // ============= إعداد المتغيرات الرئيسية =============
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
    const backToTopBtn = document.getElementById('backToTop');
    const searchInput = document.querySelector('.search-input');

    // ============= نظام الثيم (Dark/Light Mode) =============

    // جلب الثيم المحفوظ من LocalStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        updateThemeIcons();
    }

    // تبديل الثيم عند الضغط على الزر
    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            const isDarkMode = body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeIcons();

            // إضافة انيميشن للتبديل
            this.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                this.style.transform = '';
            }, 300);
        });
    });

    // تحديث أيقونات الثيم
    function updateThemeIcons() {
        const isDarkMode = body.classList.contains('dark-mode');
        themeToggleBtns.forEach(btn => {
            const icon = btn.querySelector('.theme-icon');
            if (icon) {
                icon.className = isDarkMode ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
        });
    }

    // ============= التحكم بالقائمة الجانبية =============

    // فتح وإغلاق القائمة الجانبية
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // إغلاق القائمة عند النقر على الـ overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // إغلاق القائمة عند النقر خارجها في الموبايل
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        }
    });

    // دوال التحكم بالقائمة الجانبية
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');

        // منع التمرير في الـ body عند فتح القائمة في الموبايل
        if (sidebar.classList.contains('active')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        body.style.overflow = '';
    }

    // ============= القوائم المنسدلة في السايدبار =============

    // التحكم بالقوائم المنسدلة
    const dropdownToggles = document.querySelectorAll('.sidebar .dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            const parent = this.parentElement;
            const isOpen = parent.classList.contains('active');

            // إغلاق جميع القوائم المفتوحة
            if (!e.ctrlKey) { // السماح بفتح عدة قوائم بالضغط على Ctrl
                dropdownToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        otherToggle.parentElement.classList.remove('active');
                        const otherSubmenu = otherToggle.nextElementSibling;
                        if (otherSubmenu) {
                            bootstrap.Collapse.getInstance(otherSubmenu)?.hide();
                        }
                    }
                });
            }

            // تبديل حالة القائمة الحالية
            parent.classList.toggle('active');
        });
    });

    // إبقاء القائمة مفتوحة إذا كان هناك عنصر نشط بداخلها
    const activeSubmenuItems = document.querySelectorAll('.submenu li.active');
    activeSubmenuItems.forEach(item => {
        const parentSubmenu = item.closest('.submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('show');
            parentSubmenu.previousElementSibling.setAttribute('aria-expanded', 'true');
            parentSubmenu.closest('.nav-item').classList.add('active');
        }
    });

    // ============= زر العودة للأعلى =============

    // إظهار وإخفاء زر العودة للأعلى
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    // العودة للأعلى عند الضغط على الزر
    if (backToTopBtn) {
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ============= البحث =============

    // التركيز على حقل البحث بالضغط على Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // البحث الفوري
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.trim();

            // تأخير البحث لتحسين الأداء
            searchTimeout = setTimeout(() => {
                if (searchTerm.length >= 2) {
                    performSearch(searchTerm);
                } else {
                    clearSearchResults();
                }
            }, 300);
        });

        // مسح البحث بالضغط على Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                clearSearchResults();
                this.blur();
            }
        });
    }

    // دالة البحث (يمكن تخصيصها حسب الحاجة)
    function performSearch(term) {
        console.log('Searching for:', term);
        // هنا يمكن إضافة منطق البحث الفعلي
    }

    function clearSearchResults() {
        console.log('Clearing search results');
        // هنا يمكن إضافة منطق مسح نتائج البحث
    }

    // ============= تحسين الأداء للشاشات المختلفة =============

    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            handleResponsive();
        }, 250);
    });

    function handleResponsive() {
        const width = window.innerWidth;

        // إغلاق السايدبار تلقائياً عند التبديل للشاشات الكبيرة
        if (width > 1024) {
            closeSidebar();
        }

        // تعديل حجم الخط حسب حجم الشاشة
        if (width < 480) {
            document.documentElement.style.fontSize = '14px';
        } else if (width < 768) {
            document.documentElement.style.fontSize = '15px';
        } else {
            document.documentElement.style.fontSize = '16px';
        }
    }

    // استدعاء الدالة عند التحميل
    handleResponsive();

    // ============= تهيئة مكتبة AOS للانيميشن =============

    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            once: true,
            offset: 50,
            delay: 100
        });
    }

    // ============= تحسينات تجربة المستخدم =============

    // إضافة loading state للأزرار عند الضغط
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.type === 'submit' || this.dataset.loading === 'true') {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                this.disabled = true;

                // إعادة الزر لحالته الأصلية بعد 3 ثواني (للتجربة)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 3000);
            }
        });
    });

    // تأكيد الحذف
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ============= Tooltips و Popovers =============

    // تهيئة Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // تهيئة Bootstrap Popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // ============= معالجة النماذج =============

    // تحسين تجربة النماذج
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

    // مسح النماذج
    document.querySelectorAll('[data-reset-form]').forEach(btn => {
        btn.addEventListener('click', function() {
            const formId = this.dataset.resetForm;
            const form = document.getElementById(formId);
            if (form) {
                form.reset();
                form.classList.remove('was-validated');

                // مسح رسائل الخطأ والنجاح
                form.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            }
        });
    });

    // ============= رسائل التنبيه =============

    // إخفاء رسائل التنبيه تلقائياً بعد 5 ثواني
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ============= مساعد التنقل بالكيبورد =============

    // التنقل في القائمة الجانبية بالأسهم
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link, .sidebar .submenu-list a');
    let currentFocusIndex = -1;

    document.addEventListener('keydown', function(e) {
        if (document.activeElement.closest('.sidebar')) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                navigateSidebar(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                navigateSidebar(-1);
            }
        }
    });

    function navigateSidebar(direction) {
        const visibleLinks = Array.from(sidebarLinks).filter(link => {
            return link.offsetParent !== null;
        });

        if (visibleLinks.length === 0) return;

        currentFocusIndex = Math.max(0, Math.min(currentFocusIndex + direction, visibleLinks.length - 1));
        visibleLinks[currentFocusIndex].focus();
    }

    // ============= معالجة الجداول =============

    // إضافة خاصية التصدير للجداول
    document.querySelectorAll('[data-table-export]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tableId = this.dataset.tableExport;
            const table = document.getElementById(tableId);
            if (table) {
                exportTableToCSV(table, `export_${Date.now()}.csv`);
            }
        });
    });

    function exportTableToCSV(table, filename) {
        const csv = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const csvRow = [];
            cols.forEach(col => {
                csvRow.push(`"${col.innerText.replace(/"/g, '""')}"`);
            });
            csv.push(csvRow.join(','));
        });

        downloadCSV(csv.join('\n'), filename);
    }

    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ============= طباعة الصفحات =============

    // تحسين الطباعة
    document.querySelectorAll('[data-print]').forEach(btn => {
        btn.addEventListener('click', function() {
            const elementId = this.dataset.print;
            const element = document.getElementById(elementId);
            if (element) {
                printElement(element);
            }
        });
    });

    function printElement(element) {
        const printWindow = window.open('', '', 'width=800,height=600');
        const styles = Array.from(document.styleSheets)
            .map(styleSheet => {
                try {
                    return Array.from(styleSheet.cssRules)
                        .map(rule => rule.cssText)
                        .join('');
                } catch (e) {
                    return '';
                }
            })
            .join('');

        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Preview</title>
                    <style>${styles}</style>
                </head>
                <body>${element.outerHTML}</body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    // ============= إدارة الجلسة =============

    // تحذير قبل انتهاء الجلسة
    let sessionWarningShown = false;
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 دقيقة
    const WARNING_TIME = 5 * 60 * 1000; // تحذير قبل 5 دقائق

    let sessionTimer = setTimeout(() => {
        if (!sessionWarningShown) {
            showSessionWarning();
        }
    }, SESSION_TIMEOUT - WARNING_TIME);

    function resetSessionTimer() {
        clearTimeout(sessionTimer);
        sessionWarningShown = false;
        sessionTimer = setTimeout(() => {
            if (!sessionWarningShown) {
                showSessionWarning();
            }
        }, SESSION_TIMEOUT - WARNING_TIME);
    }

    function showSessionWarning() {
        sessionWarningShown = true;
        if (confirm('Your session will expire in 5 minutes. Do you want to continue?')) {
            resetSessionTimer();
            // هنا يمكن إرسال طلب للخادم لتجديد الجلسة
            fetch('/refresh-session', { method: 'POST' });
        }
    }

    // إعادة تعيين المؤقت عند أي نشاط من المستخدم
    ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
        document.addEventListener(event, resetSessionTimer, { passive: true });
    });

    // ============= تحسين أداء التحميل =============

    // Lazy Loading للصور
    const lazyImages = document.querySelectorAll('img[data-lazy]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.lazy;
                img.removeAttribute('data-lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));

    // ============= إشعارات الويب =============

    // طلب إذن الإشعارات
    if ('Notification' in window && Notification.permission === 'default') {
        document.querySelector('.notification-btn')?.addEventListener('click', function() {
            Notification.requestPermission();
        }, { once: true });
    }

    // دالة إظهار إشعار
    window.showNotification = function(title, options = {}) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                icon: '/favicon.ico',
                badge: '/badge.png',
                ...options
            });
        }
    };

    // ============= حفظ حالة واجهة المستخدم =============

    // حفظ حالة السايدبار
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed' && window.innerWidth > 1024) {
        sidebar.classList.add('collapsed');
    }

    // حفظ آخر صفحة تم زيارتها
    localStorage.setItem('lastPage', window.location.pathname);

    // ============= معالجة الأخطاء العامة =============

    window.addEventListener('error', function(e) {
        console.error('Global error:', e);
        // يمكن إرسال الأخطاء لخادم التتبع هنا
    });

    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled promise rejection:', e);
        // يمكن إرسال الأخطاء لخادم التتبع هنا
    });

    // ============= دوال مساعدة عامة =============

    // دالة لتنسيق الأرقام
    window.formatNumber = function(num) {
        return new Intl.NumberFormat('en-US').format(num);
    };

    // دالة لتنسيق التاريخ
    window.formatDate = function(date, format = 'short') {
        const options = format === 'short'
            ? { year: 'numeric', month: 'short', day: 'numeric' }
            : { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return new Intl.DateTimeFormat('en-US', options).format(new Date(date));
    };

    // دالة للنسخ للحافظة
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copied to clipboard!', 'success');
            });
        } else {
            // Fallback للمتصفحات القديمة
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('Copied to clipboard!', 'success');
        }
    };

    // دالة إظهار رسالة Toast
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // ============= تهيئة المكونات الخاصة =============

    // تهيئة محرر الكود إذا وجد
    if (typeof CodeMirror !== 'undefined') {
        document.querySelectorAll('.code-editor').forEach(element => {
            CodeMirror.fromTextArea(element, {
                lineNumbers: true,
                mode: element.dataset.mode || 'javascript',
                theme: body.classList.contains('dark-mode') ? 'monokai' : 'default'
            });
        });
    }

    // تهيئة المخططات إذا وجدت
    if (typeof Chart !== 'undefined') {
        document.querySelectorAll('[data-chart]').forEach(canvas => {
            const config = JSON.parse(canvas.dataset.chart);
            new Chart(canvas, config);
        });
    }

    // ============= تنظيف الموارد عند الخروج =============

    window.addEventListener('beforeunload', function() {
        // حفظ البيانات المهمة
        localStorage.setItem('lastVisit', new Date().toISOString());

        // تنظيف المؤقتات
        clearTimeout(sessionTimer);
        clearTimeout(resizeTimeout);
    });

    // ============= رسالة ترحيب في الـ Console =============

    console.log('%cTimetable Pro - Palestine Technical College',
                'color: #3b82f6; font-size: 20px; font-weight: bold;');
    console.log('%cVersion 1.0.0 | Built with ❤️',
                'color: #64748b; font-size: 14px;');
    console.warn('This is a browser feature intended for developers. ' +
                 'Do not paste any code here unless you understand what it does!');

});
