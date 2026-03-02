import $ from "jquery";
import Choices from 'choices.js';
import "parsleyjs";
import IMask from "imask";
window.Choices = Choices;
import ApexCharts from "apexcharts";

window.$ = $;
window.jQuery = $;


window.ApexCharts = ApexCharts;

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

// filter-freezer.js

window.FilterFreezer = {
    _isNodeList(ref) {
        return NodeList.prototype.isPrototypeOf(ref) || Array.isArray(ref);
    },

    loadFilters(screenKey, fieldMap) {
        const saved = JSON.parse(localStorage.getItem(screenKey) || '{}');
        if (!saved.freeze) return;

        // Restore all fields
        Object.entries(fieldMap).forEach(([key, ref]) => {
            if (key === 'freeze') return; // handled below

            const value = saved[key];
            if (value === undefined || value === null) return;

            // Checkbox group (e.g. equipment_status[])
            if (this._isNodeList(ref)) {
                Array.from(ref).forEach(el => {
                    if (Array.isArray(value)) {
                        el.checked = value.includes(el.value);
                    } else {
                        el.checked = value == el.value;
                    }
                });
                return;
            }

            if (!ref) return;

            // Single checkbox
            if (ref.type === 'checkbox') {
                ref.checked = !!value;
                return;
            }

            // Select / Select2
            if (ref.tagName === 'SELECT') {
                if (ref.multiple && Array.isArray(value)) {
                    Array.from(ref.options).forEach(opt => {
                        opt.selected = value.includes(opt.value);
                    });
                } else {
                    ref.value = value;
                }

                // If Select2 attached, trigger change so UI updates
                if (window.jQuery && jQuery(ref).data('select2')) {
                    jQuery(ref).trigger('change');
                }
                return;
            }

            // Regular input / text
            if ('value' in ref) {
                ref.value = value;
            }
        });

        // Restore freeze checkbox itself
        if (fieldMap.freeze) {
            fieldMap.freeze.checked = !!saved.freeze;
        }
    },

    saveFilters(screenKey, fieldMap) {
        const freezeCheckbox = fieldMap.freeze || null;

        // If we have a freeze checkbox and it's unchecked → clear storage
        if (freezeCheckbox && !freezeCheckbox.checked) {
            localStorage.removeItem(screenKey);
            return;
        }

        const data = {
            // if no freeze checkbox present, assume always frozen
            freeze: freezeCheckbox ? !!freezeCheckbox.checked : true
        };

        Object.entries(fieldMap).forEach(([key, ref]) => {
            if (key === 'freeze') return;

            // Checkbox group
            if (this._isNodeList(ref)) {
                data[key] = Array.from(ref)
                    .filter(el => el.checked)
                    .map(el => el.value);
                return;
            }

            if (!ref) return;

            // Single checkbox
            if (ref.type === 'checkbox') {
                data[key] = !!ref.checked;
                return;
            }

            // Select / Select2
            if (ref.tagName === 'SELECT') {
                if (ref.multiple) {
                    data[key] = Array.from(ref.selectedOptions).map(o => o.value);
                } else {
                    data[key] = ref.value;
                }
                return;
            }

            // Input / text
            if ('value' in ref) {
                data[key] = ref.value;
            }
        });

        localStorage.setItem(screenKey, JSON.stringify(data));
    }
};

/**
 * Clears all filter fields in the provided fieldMap and saves the cleared state.
 * @param {Object} fieldMap - Map of field names to DOM elements.
 * @param {string} [screenKey] - Optional key for FilterFreezer to save cleared state.
 */
window.clearFilters = function(fieldMap, screenKey) {
    Object.values(fieldMap).forEach(function(input) {
        if (!input) return;
        if (input.tagName === 'INPUT') {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
            // If Select2 or Choices attached, trigger change so UI updates
            if (window.jQuery && jQuery(input).data('select2')) {
                jQuery(input).trigger('change');
            }
            if (window.Choices && input.classList.contains('choices-select')) {
                if (input.choicesInstance) {
                    input.choicesInstance.setChoiceByValue('');
                }
            }
        } else if (window.NodeList && NodeList.prototype.isPrototypeOf(input)) {
            // Checkbox/radio group
            Array.from(input).forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = false;
                } else {
                    el.value = '';
                }
            });
        }
    });
    if (screenKey && window.FilterFreezer) {
        window.FilterFreezer.saveFilters(screenKey, fieldMap);
    }
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

    const numericInputs = document.querySelectorAll("input[data-numeric-input='true']");

    numericInputs.forEach(function (input) {
        input.addEventListener("keypress", function (e) {
            // Allow only digits (0-9) and one dot
            const char = String.fromCharCode(e.charCode);
            if (!/[0-9.]/.test(char)) {
                e.preventDefault();
            }
            // Prevent more than one dot
            if (char === '.' && this.value.includes('.')) {
                e.preventDefault();
            }
        });

        input.addEventListener("input", function () {
            // Remove all except digits and dot, and allow only one dot
            let val = this.value.replace(/[^\d.]/g, "");
            const parts = val.split('.');
            if (parts.length > 2) {
                val = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = val;
        });
    });
});

