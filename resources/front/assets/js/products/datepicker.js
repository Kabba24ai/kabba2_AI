export function initDatepicker() {
    const scheduleStartDateInput = document.getElementById('scheduleStartDateInput');
    const openDatePicker = document.getElementById('openDatePicker');
    const selectedDateText = document.getElementById('selectedDateText');
    if (!scheduleStartDateInput || !openDatePicker || !selectedDateText) return;

    openDatePicker.blur();
    let lastInputType = null;
    let lastKeyPressed = null;

    document.addEventListener('keydown', function(e) {
        lastInputType = 'keyboard';
        lastKeyPressed = e.key;
    });

    document.addEventListener('mousedown', function() {
        lastInputType = 'mouse';
        lastKeyPressed = null;
    });

    // Init Air Datepicker
    const picker = new window.AirDatepicker(scheduleStartDateInput, {
        locale: window.airDatepickerLocaleEn,
        timepicker: false,
        dateFormat: scheduleStartDateInput.dataset.format || 'yyyy-MM-dd',
        minDate: scheduleStartDateInput.dataset.minDate ? new Date(scheduleStartDateInput.dataset.minDate) : false,
        autoClose: false,
        keyboardNav: true,
        onSelect({ date, formattedDate, datepicker }) {
            if (date) {
                selectedDateText.textContent = formattedDate;
                scheduleStartDateInput.value = formattedDate;
                if (lastInputType === 'mouse' || lastKeyPressed === 'Enter') {
                    datepicker.hide();
                }
                lastInputType = null;
                lastKeyPressed = null;
            } else {
                selectedDateText.textContent = 'Select Start Date';
                scheduleStartDateInput.value = '';
            }
        }
    });

    openDatePicker.addEventListener('click', function(e) {
        e.preventDefault();
        picker.show();
        document.getElementById('dateError').textContent = "";
    });

    if (scheduleStartDateInput.value) {
        selectedDateText.textContent = scheduleStartDateInput.value;
    }

    scheduleStartDateInput._airPicker = picker;
}
