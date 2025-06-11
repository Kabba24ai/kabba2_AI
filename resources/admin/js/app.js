import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import 'parsleyjs';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import Sortable from 'sortablejs';
import IMask from 'imask';
// import TomSelect from 'tom-select'; // ✅ Import Tom Select
import Choices from 'choices.js';

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;
// expose for inline scripts
window.Choices = Choices;

// Start Alpine.js
Alpine.start();

// Initialize scripts on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // CKEditor
    const editors = document.querySelectorAll('.ckeditor');
    editors.forEach((el) => {
        ClassicEditor.create(el, {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo', 'sourceEditing'
                ]
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            },
            removePlugins: ['CKBox', 'CKFinder']
        }).catch(error => {
            console.error(error);
        });
    });

    // Input masking
    const phoneInputs = document.querySelectorAll('.masked-phone');
    phoneInputs.forEach(input => {
        IMask(input, {
            mask: '(000) 000-0000'
        });
    });

    // Tom Select
    // document.querySelectorAll('.tom-select').forEach((el) => {
    //     new TomSelect(el);
    // });

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

        const updateInput = () => {
            let num = parseFloat(digits || "0") / 100;
            input.value = num.toFixed(2);
        };

        input.addEventListener("keydown", function (e) {
            // Allow: Backspace, Tab, Arrow keys
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

            // Block everything else
            e.preventDefault();
        });

        input.addEventListener("paste", (e) => e.preventDefault());
        input.addEventListener("input", (e) => e.preventDefault());

        // Set initial display value
        input.placeholder = "0.00";
    });
});
