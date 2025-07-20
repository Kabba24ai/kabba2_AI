import jQuery from "jquery";


import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import Sortable from 'sortablejs';
window.$ = jQuery;
window.jQuery = jQuery;

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;

// Start Alpine.js
Alpine.start();

import SignaturePad from "signature_pad";
import "@fortawesome/fontawesome-free/js/all.js"; // Import Font Awesome JS
import '../../../shared/js/app.js'; // Import the Notyf setup
import '../../../shared/js/notif.js'; // Import the Notyf setup
import '../../../shared/js/air-datepicker.js';
import "./custom.js";
import "./cart.js";
import "./glightbox.js";


// Now you can use SignaturePad and other libraries as needed!
window.SignaturePad = SignaturePad;
