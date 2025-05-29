// resources/admin/js/app.js
import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import 'parsleyjs';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import Sortable from 'sortablejs';

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;

// Start Alpine.js
Alpine.start();

// Initialize CKEditor
document.addEventListener('DOMContentLoaded', () => {
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

});

