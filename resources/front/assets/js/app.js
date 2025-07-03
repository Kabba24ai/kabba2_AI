import jQuery from "jquery";

window.$ = jQuery;
window.jQuery = jQuery;

import "flowbite";

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
