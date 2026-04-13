const loader         = document.getElementById('loader');
const toastContainer = document.getElementById('toast-container');

// ── Loader ───────────────────────────────────────────────────
function showLoader() {
    if (loader) {
        loader.classList.remove('hidden', 'fade-out');
        setTimeout(() => { loader.style.opacity = '1'; }, 10);
    }
}

function hideLoader() {
    if (loader) {
        loader.classList.add('fade-out');
        setTimeout(() => {
            loader.classList.add('hidden');
            loader.classList.remove('fade-out');
            loader.style.opacity = '0';
        }, 300);
    }
}

// ── Toast notifications ───────────────────────────────────────
function showToast(message, type = 'info', duration = 5000) {
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.classList.add('toast', type);

    let icon;
    switch (type) {
        case 'success': icon = 'ic:round-check-circle'; break;
        case 'error':   icon = 'ic:round-error';        break;
        case 'warning': icon = 'ic:round-warning';      break;
        default:        icon = 'ic:round-info';         break;
    }

    toast.innerHTML = `
        <span class="iconify" data-icon="${icon}" aria-hidden="true"></span>
        <div class="toast-message">${message}</div>
    `;

    toastContainer.prepend(toast);
    const fadeDelay = (duration / 1000) - 0.4;
    toast.style.animation = `slideIn 0.4s ease forwards, fadeOut 0.4s ease ${fadeDelay}s forwards`;
    setTimeout(() => {
        if (toast.parentNode === toastContainer) toastContainer.removeChild(toast);
    }, duration);
}

// ── Dark mode toggle ──────────────────────────────────────────
function initDarkMode() {
    const saved  = localStorage.getItem('niit-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);

    const toggle = document.getElementById('dark-mode-toggle');
    if (!toggle) return;

    toggle.checked = (saved === 'dark');
    toggle.addEventListener('change', () => {
        const theme = toggle.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('niit-theme', theme);
    });
}

// ── Service Worker (PWA) ──────────────────────────────────────
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .catch(err => console.warn('SW registration failed:', err));
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    registerServiceWorker();
});
