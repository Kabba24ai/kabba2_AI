
import $ from "jquery";
import Choices from 'choices.js';
import "parsleyjs";
import IMask from "imask";
window.Choices = Choices;


window.$ = $;
window.jQuery = $;

window.Parsley = Parsley;



// Initialize scripts on DOM ready
document.addEventListener('DOMContentLoaded', () => {

    const form = document.querySelector("form");
    if (form) {
        $(form).parsley();
    }

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
        let digits = "";

        // Initialize digits from existing value
        if (input.value) {
            // Remove non-digits (keeps only numbers)
            digits = input.value.replace(/\D/g, "");
        }

        const updateInput = () => {
            let num = parseFloat(digits || "0") / 100;
            input.value = num.toFixed(2);
        };

        // Update once at the beginning if there was a value
        if (digits) {
            updateInput();
        }

        input.addEventListener("keydown", function (e) {
            const allowedKeys = ["Backspace", "Tab", "ArrowLeft", "ArrowRight"];
            if (allowedKeys.includes(e.key)) {
                if (e.key === "Backspace") {
                    e.preventDefault();
                    digits = digits.slice(0, -1);
                    updateInput();
                }
                return;
            }

            // Allow only number keys (0–9)
            if (/^[0-9]$/.test(e.key)) {
                e.preventDefault();
                digits += e.key;
                updateInput();
                return;
            }

            e.preventDefault();
        });

        input.addEventListener("paste", (e) => e.preventDefault());
        input.addEventListener("input", (e) => e.preventDefault());

        input.placeholder = "0.00";
    });

});
