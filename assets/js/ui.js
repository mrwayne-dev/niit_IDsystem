// assets/js/ui.js

const loader = document.getElementById('loader');
const toastContainer = document.getElementById('toast-container');

// --- loader code block ---
function showLoader() {
    if (loader) {
        loader.classList.remove('hidden', 'fade-out');
        setTimeout(() => {
            loader.style.opacity = '1'; 
        }, 10);
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

// --- toast notification code block ---
function showToast(message, type = 'info', duration = 5000) { 
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.classList.add('toast', type);

    let icon;
    switch (type) {
        case 'success': icon = 'ic:round-check-circle'; break;
        case 'error': icon = 'ic:round-error'; break;
        case 'warning': icon = 'ic:round-warning'; break;
        default: icon = 'ic:round-info'; break;
    }

    toast.innerHTML = `
        <span class="iconify" data-icon="${icon}" aria-hidden="true"></span>
        <div class="toast-message">${message}</div>
    `;

    toastContainer.prepend(toast);
    const fadeOutDelay = (duration / 1000) - 0.4;
    toast.style.animation = `slideIn 0.4s ease forwards, fadeOut 0.4s ease ${fadeOutDelay}s forwards`;
    setTimeout(() => {
        if (toast.parentNode === toastContainer) {
            toastContainer.removeChild(toast);
        }
    }, duration);
}