import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

// ✅ Custom Notyf instance with top-right position
window.notyf = new Notyf({
    position: {
        x: 'right',
        y: 'top'
    },
    duration: 3000 // optional: auto-close time in ms
});
