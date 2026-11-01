// ============================================
// SNHS WELLNESS - MAIN JAVASCRIPT
// ============================================

// ============================================
// DARK MODE - INITIALIZE EARLY (before page render)
// ============================================
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // DARK MODE TOGGLE
    // ============================================
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const htmlElement = document.documentElement;
    
    // Function to update the icon based on current theme
    function updateDarkModeIcon() {
        if (!darkModeIcon) return;
        const currentTheme = htmlElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            darkModeIcon.className = 'fas fa-sun';
        } else {
            darkModeIcon.className = 'fas fa-moon';
        }
    }
    
    // Initialize icon
    updateDarkModeIcon();
    
    // Toggle click handler
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const currentTheme = htmlElement.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                htmlElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                htmlElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateDarkModeIcon();
        });
    }
    
    // ============================================
    // SMOOTH SCROLLING
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId.length > 1) {
                e.preventDefault();
                const target = document.querySelector(targetId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
    // ============================================
    // AUTO-HIDE ALERTS
    // ============================================
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // ============================================
    // ACTIVE NAV LINK
    // ============================================
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (linkPath && currentPath.includes(linkPath)) {
            link.classList.add('active');
        }
    });
    
    // ============================================
    // PASSWORD CONFIRMATION VALIDATION
    // ============================================
    const passwordForm = document.querySelector('form[data-password-form]');
    if (passwordForm) {
        const password = passwordForm.querySelector('input[name="new_password"]');
        const confirm = passwordForm.querySelector('input[name="confirm_password"]');
        const submitBtn = passwordForm.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                if (password && confirm && password.value !== confirm.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }
    }
    
    // ============================================
    // CONFIRM DELETE ACTIONS
    // ============================================
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // DATE INPUT - Set min to today
    // ============================================
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.getAttribute('min')) {
            input.setAttribute('min', new Date().toISOString().split('T')[0]);
        }
    });
    
    // ============================================
    // FLASH MESSAGES (Toast)
    // ============================================
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        const type = flashMessage.getAttribute('data-type');
        const message = flashMessage.getAttribute('data-message');
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const container = document.querySelector('.toast-container') || document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.appendChild(toast);
        document.body.appendChild(container);
        
        const bootstrapToast = new bootstrap.Toast(toast, { delay: 5000 });
        bootstrapToast.show();
    }
    
    // ============================================
    // FORM SUBMISSION PREVENTION (double click)
    // ============================================
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            }
        });
    });
    
    // ============================================
    // CANCEL APPOINTMENT CONFIRMATION
    // ============================================
    document.querySelectorAll('form[data-cancel-appointment]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to cancel this appointment? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});