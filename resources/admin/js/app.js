import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import Sortable from 'sortablejs';

import '../../shared/js/app.js'; // Import the Notyf setup
import '../../shared/js/notif.js'; // Import the Notyf setup

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;

// Start Alpine.js
Alpine.start();

const ckeditorElements = document.querySelectorAll('.ckeditor');
window.editors = {}; // store instances globally

ckeditorElements.forEach((el) => {
    const id = el.getAttribute('id');
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
    }).then(editor => {
        if (id) {
            window.editors[id] = editor;
        }
    }).catch(error => {
        console.error('CKEditor init failed:', error);
    });
});




