window.apiFetch = async function (input, init = {}, uiOptions = {}) {
    let response;
    let resData;

    // Get loader and container selectors (optional)
    const loader = uiOptions.loaderSelector
        ? document.querySelector(uiOptions.loaderSelector)
        : null;
    const container = uiOptions.containerSelector
        ? document.querySelector(uiOptions.containerSelector)
        : null;

    // Show loader and disable UI
    if (loader) loader.classList.remove('hidden');
    if (container) container.classList.add('opacity-50', 'pointer-events-none');

    // Clear previous error messages
    const clearFieldErrors = () => {
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
        document.querySelectorAll('[class*="-error"]').forEach(el => el.textContent = '');
    };

    // Show error either inside container (if defined) or below field
    const showFieldError = (field, message) => {
        const fieldEl = document.querySelector(`[name="${field}"]`);
        if (!fieldEl) return;

        fieldEl.classList.add('has-error');

        const errorEl = document.createElement('div');
        errorEl.className = 'field-error text-sm text-red-600 mt-1';
        errorEl.innerText = message;

        // If a custom error container is defined
        const customContainerSelector = fieldEl.getAttribute('data-parsley-errors-container');
        if (customContainerSelector) {
            const container = document.querySelector(customContainerSelector);
            if (container) {
                container.appendChild(errorEl);
                return;
            }
        }

        // Default: show error after the field
        fieldEl.insertAdjacentElement('afterend', errorEl);
    };

    try {
        response = await fetch(input, init);
        resData = await response.json();
    } catch (error) {
        notyf.error('A network error occurred. Please try again.');
        throw error;
    } finally {
        // Always hide loader after request
        if (loader) loader.classList.add('hidden');
        if (container) container.classList.remove('opacity-50', 'pointer-events-none');
    }

    // Clear previous errors
    clearFieldErrors();

    if (response.ok && resData.success) {
        return resData;
    } else if (response.status === 422 && resData.errors) {
        // Handle Laravel validation errors
        Object.entries(resData.errors).forEach(([field, messages]) => {
            messages.forEach(msg => notyf.error(msg));
            showFieldError(field, messages[0]);
        });

        throw { type: 'validation', response, resData };
    } else {
        notyf.error(resData.message || 'An error occurred. Please try again.');
        throw { type: 'api', response, resData };
    }
};
