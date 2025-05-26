// resources/admin/js/app.js
import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import 'parsleyjs';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const editors = document.querySelectorAll('.ckeditor');

    editors.forEach((el) => {
        ClassicEditor.create(el).catch(error => {
            console.error(error);
        });
    });

});

