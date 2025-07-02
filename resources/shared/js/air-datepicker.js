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
                dateFormat: el.dataset.format || 'yyyy-MM-dd HH:mm',
                minDate: el.dataset.minDate ? new Date(el.dataset.minDate) : false,
                autoClose: true,
                keyboardNav:true,
            });
        }
    });

    document.dispatchEvent(new Event('airDatepickers:initialized'));
});
