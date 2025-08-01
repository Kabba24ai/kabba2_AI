import AirDatepicker from 'air-datepicker';
import 'air-datepicker/air-datepicker.css';
import localeEn from 'air-datepicker/locale/en';

window.AirDatepicker = AirDatepicker;
window.airDatepickerLocaleEn = localeEn;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.datepicker').forEach(el => {
        // Only initialize if the input is visible (not .hidden)
        if (!el.classList.contains('hidden')) {
            el._airDatepicker = new AirDatepicker(el, {
                locale: localeEn,
                timepicker: false,
                dateFormat: el.dataset.format || window.APP_DATE_FORMAT || 'yyyy-MM-dd HH:mm',
                minDate: el.dataset.minDate ? new Date(el.dataset.minDate) : false,
                autoClose: true,
                keyboardNav: true,
            });
        }
    });

    document.querySelectorAll('.timepicker').forEach(el => {
        // Only initialize if the input is visible (not .hidden)
        if (!el.classList.contains('hidden')) {
            el._airDatepicker = new AirDatepicker(el, {
                locale: localeEn,
                timepicker: true,
                onlyTimepicker: true, // Show only time, no date
                dateFormat: 'HH:mm',
                autoClose: true,
                keyboardNav: true,
                minutesStep: 5,
                showSeconds: false,
            });
        }
    });

    document.dispatchEvent(new Event('airDatepickers:initialized'));
});
