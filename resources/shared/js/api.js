window.apiFetch = async function (input, init = {}) {
    let response;
    let resData;

    // Clear previous error messages
    const clearFieldErrors = () => {
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
    };

    // Show error either inside container (if defined) or below field
    const showFieldError = (field, message) => {
        const fieldEl = document.querySelector(`[name="${field}"]`);
        if (!fieldEl) return; // field not found

        fieldEl.classList.add('has-error');

        const errorEl = document.createElement('div');
        errorEl.className = 'field-error text-sm text-red-600 mt-1';
        errorEl.innerText = message;

        // Check for data-parsley-errors-container
        const customContainerSelector = fieldEl.getAttribute('data-parsley-errors-container');
        if (customContainerSelector) {
            const container = document.querySelector(customContainerSelector);
            if (container) {
                container.appendChild(errorEl);
                return;
            }
        }

        // Fallback: insert directly after the field
        fieldEl.insertAdjacentElement('afterend', errorEl);
    };

    try {
        response = await fetch(input, init);
        resData = await response.json();
    } catch (error) {
        notyf.error('A network error occurred. Please try again.');
        throw error;
    }

    // Always clear old errors before showing new ones
    clearFieldErrors();

    if (response.ok && resData.success) {
        return resData;
    } else if (response.status === 422 && resData.errors) {
        // Handle Laravel validation errors
        Object.entries(resData.errors).forEach(([field, messages]) => {
            // Show toast notifications
            messages.forEach(msg => notyf.error(msg));

            // Show error message on the form
            showFieldError(field, messages[0]);
        });

        throw { type: 'validation', response, resData };
    } else {
        notyf.error(resData.message || 'An error occurred. Please try again.');
        throw { type: 'api', response, resData };
    }
};
