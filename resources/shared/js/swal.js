import Swal from 'sweetalert2'

// Example: Custom function to show a success alert
export function showSuccess(message, title = 'Success') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonColor: '#3085d6'
    });
}

// Example: Custom function to show an error alert
export function showError(message, title = 'Error') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#d33'
    });
}

// Example: Custom function to show a confirmation dialog
export function showConfirm(message, title = 'Are you sure?') {
    return Swal.fire({
        icon: 'question',
        title: title,
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    });
}

// Expose to window for global access
window.showSuccess = showSuccess;
window.showError = showError;
window.showConfirm = showConfirm;

// Inject custom z-index for SweetAlert2 container
if (typeof window !== 'undefined') {
    const style = document.createElement('style');
    style.innerHTML = `div:where(.swal2-container) { z-index: 99999 !important; }`;
    document.head.appendChild(style);
}
