export function changeQty(delta) {
    const input = document.getElementById("qty");
    let current = parseInt(input.value) || 1;
    let newValue = current + delta;
    if (newValue < parseInt(input.min)) newValue = parseInt(input.min);
    if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
    input.value = newValue;
}
