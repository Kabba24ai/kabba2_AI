export function changeQty(delta) {
    const input = document.getElementById("qty");
    if (!input || input.disabled) {
        return;
    }

    let current = parseInt(input.value) || 1;
    let newValue = current + delta;
    // Weekend Special: force max 1 and show message
    if (window.productPageContext && window.productPageContext.productVariant === 'weekend') {
        if (delta > 0 && current >= 1) {
            if (window.notyf) {
                notyf.error('You cannot increase quantity for the Weekend Special.');
            }
        }
        newValue = 1;
    }
    if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
    if (input.max && newValue > parseInt(input.max)) newValue = parseInt(input.max);
    input.value = newValue;
    document.getElementById('qtyError').textContent = "";
}
