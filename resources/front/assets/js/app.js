import jQuery from "jquery";

window.$ = jQuery;
window.jQuery = jQuery;

import "flowbite";

import Swiper from "swiper";
import "swiper/css";
import SignaturePad from "signature_pad";
import "@fortawesome/fontawesome-free/js/all.js"; // Import Font Awesome JS
import 'parsleyjs';
import Choices from 'choices.js';
import '../../../shared/js/notif.js'; // Import the Notyf setup
import "./custom.js";

// Attach Swiper to window so it’s globally available
window.Swiper = Swiper;

// Now you can use SignaturePad and other libraries as needed!
window.SignaturePad = SignaturePad;

window.Choices = Choices;
