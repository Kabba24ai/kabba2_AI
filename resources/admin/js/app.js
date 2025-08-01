import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
// import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import Sortable from 'sortablejs';

import '../../shared/js/app.js';
import '../../shared/js/notif.js'; // Import the Notyf setup
import '../../shared/js/swal.js'; // Import SweetAlert setup
import '../../shared/js/air-datepicker.js'; // Import datepicker setup
import '../../shared/js/api.js';
import '../../shared/js/flatpickr.js';


import AirDatepicker from "air-datepicker";


// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;

// Start Alpine.js
Alpine.start();



// Auto-initiate TinyMCE for all elements with the 'tinymce' class
// document.addEventListener('DOMContentLoaded', () => {
//     if (typeof initEditor === 'function') {
//         initEditor('.tinymce');
//     }
// });

window.AirDatepicker = AirDatepicker;
