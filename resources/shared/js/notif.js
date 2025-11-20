import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

// ✅ Custom Notyf instance with top-right position
window.notyf = new Notyf({
    position: {
        x: 'right',
        y: 'top'
    },
    duration: 3000 // optional: auto-close time in ms
});

window.showStickyError = function(msg, duration = 3000) {
    const notification = notyf.open({
        type: 'error',
        message: msg,
        dismissible: true,
        duration: 0 // We will control the timeout ourselves
    });

    let hideTimeout;

    function startTimeout() {
        hideTimeout = setTimeout(() => {
            notyf.dismiss(notification);
        }, duration);
    }

    setTimeout(() => {
        const notifEls = document.querySelectorAll('.notyf__toast:not(.notyf__toast--disappear)');
        const notifEl = notifEls[notifEls.length - 1];
        if (!notifEl) return;

        startTimeout();

        notifEl.addEventListener('mouseenter', () => {
            if (hideTimeout) {
                clearTimeout(hideTimeout);
                hideTimeout = null;
            }
        });

        notifEl.addEventListener('mouseleave', () => {
            // Only restart if not already hiding
            if (!hideTimeout) startTimeout();
        });
    }, 20);
};

document.addEventListener("DOMContentLoaded", function() {
    if (window.Laravel) {
        if (window.Laravel.success) {
            notyf.success(window.Laravel.success);
        }
        if (window.Laravel.error) {
            notyf.error(window.Laravel.error);
        }
    }
});
