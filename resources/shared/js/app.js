
import $ from "jquery";
import Choices from 'choices.js';
import "parsleyjs";
import IMask from "imask";
window.Choices = Choices;


window.$ = $;
window.jQuery = $;

window.Parsley = Parsley;

window.Parsley.getHiddenFieldErrors = function (parsleyForm) {
    let errors = [];

    parsleyForm.fields.forEach(function (field) {
        const fieldErrors = field.getErrorsMessages();
        if (fieldErrors.length > 0) {
            const $element = field.$element;

            // Only collect errors for hidden fields
            const isHidden = $element.is(':hidden');
            if (isHidden) {
                let fieldName = $element.attr('name') || 'Field';
                const labelText = $(`label[for="${$element.attr('id')}"]`).text();
                if (labelText) {
                    fieldName = labelText.trim();
                }

                fieldErrors.forEach(function (msg) {
                    errors.push(`${fieldName}: ${msg}`);
                });
            }
        }
    });

    return errors;
};

/**
 * Global loader wrapper.
 *
 * @param {string} containerSelector - The selector for the container to dim (e.g. "#ordersTable")
 * @param {string} loaderSelector - The selector for the loader/spinner (e.g. "#orders-loading")
 * @param {Function|Promise} asyncFn - An async function or Promise to run
 * @returns {Promise}
 */
window.withLoader = function(containerSelector, loaderSelector, asyncFn) {
    const container = document.querySelector(containerSelector);
    const loader = document.querySelector(loaderSelector);

    if (loader) {
        loader.classList.remove('hidden');
    }
    if (container) {
        container.classList.add('opacity-50', 'pointer-events-none');
    }

    const promise = (typeof asyncFn === 'function') ? asyncFn() : asyncFn;

    return Promise.resolve(promise).finally(() => {
        if (loader) {
            loader.classList.add('hidden');
        }
        if (container) {
            container.classList.remove('opacity-50', 'pointer-events-none');
        }
    });
};


// Initialize scripts on DOM ready
document.addEventListener('DOMContentLoaded', () => {

    // Input masking
    const phoneInputs = document.querySelectorAll('.masked-phone');
    phoneInputs.forEach(input => {
        IMask(input, {
            mask: '(000) 000-0000'
        });
    });

    document.querySelectorAll('.choices-select').forEach(function (el) {
        new Choices(el, {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            removeItemButton: true
        });
    });

    const inputs = document.querySelectorAll("input[data-digit-input='true']");

    inputs.forEach((input) => {
        // Use property on input element
        if (!input.digits) input.digits = ""; // initialize if not set

        // Initialize digits from existing value
        if (input.value) {
            input.digits = input.value.replace(/\D/g, "");
        }

        const updateInput = () => {
            let num = parseFloat(input.digits || "0") / 100;
            input.value = num.toFixed(2);

            const event = new Event("input", { bubbles: true });
            input.dispatchEvent(event);
        };

        if (input.digits) updateInput();

        input.addEventListener("keydown", function (e) {
            const allowedKeys = [
                "Backspace", "Tab", "ArrowLeft", "ArrowRight", "Delete", "Control", "Meta", "Shift", "Alt", "Home", "End"
            ];

            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z, Ctrl+Shift+Z, Cmd+A, Cmd+C, Cmd+V, Cmd+X, Cmd+Z, Cmd+Shift+Z
            if (
                (e.ctrlKey || e.metaKey) &&
                ["a", "c", "v", "x", "z", "A", "C", "V", "X", "Z"].includes(e.key)
            ) {
                return;
            }

            // Allow navigation and editing keys
            if (allowedKeys.includes(e.key)) {
                if (e.key === "Backspace") {
                    e.preventDefault();
                    input.digits = input.digits.slice(0, -1);
                    updateInput();
                } else if (e.key === "Delete") {
                    input.digits = "";
                }
                return;
            }

            // Allow numbers
            if (/^[0-9]$/.test(e.key)) {
                e.preventDefault();
                input.digits += e.key;
                updateInput();
                return;
            }

            // Block all other input
            e.preventDefault();
        });

        // Allow paste, but filter to digits only
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData("text");
            if (pasted) {
                input.digits = pasted.replace(/\D/g, "");
                updateInput();
            }
        });

        // Allow select all, copy, cut, etc.
        input.placeholder = "0.00";
    });
});

