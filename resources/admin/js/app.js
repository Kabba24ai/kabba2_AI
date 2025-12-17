import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import Sortable from 'sortablejs';

import '../../shared/js/app.js';
import '../../shared/js/notif.js'; // Import the Notyf setup
import '../../shared/js/swal.js'; // Import SweetAlert setup
import '../../shared/js/air-datepicker.js'; // Import datepicker setup
import '../../shared/js/api.js';
import '../../shared/js/flatpickr.js';
import '../../shared/js/pagination.js';


import AirDatepicker from "air-datepicker";

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;
window.Sortable = Sortable;

// Start Alpine.js
// Alpine.start();


window.AirDatepicker = AirDatepicker;
