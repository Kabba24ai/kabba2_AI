// resources/admin/js/app.js
import Alpine from 'alpinejs';
import persist from "@alpinejs/persist";
import 'parsleyjs';

// Make Alpine globally available
Alpine.plugin(persist);
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();
