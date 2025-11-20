// resources/js/flatpickr.js
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.ft-timepicker').forEach(function(el) {
        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K", // 12-hour, minute, AM/PM
            time_24hr: false,
        });
    });
});
