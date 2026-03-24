export function updateDeliveryPrices(type, context) {
    const {
        formattedStandardFeeX2,
        formattedStandardFee,
        formattedExtendedFeeX2,
        formattedExtendedFee
    } = context;

    const dropdown = document.getElementById('deliveryOptionsDropdown');
    const customBox = document.getElementById('customServiceOption');
    const title = document.getElementById('distanceTypeTitle');
    const addToCartBtn = document.getElementById('addToCart');
    const continueReservationBtnCustom = document.getElementById('continueReservationBtnCustom');
    // Reset dropdown to first option
    const deliveryOptionSelect = document.getElementById('deliveryOptionSelect');
    if (deliveryOptionSelect) {
        deliveryOptionSelect.selectedIndex = 0;
        deliveryOptionSelect.dispatchEvent(new Event('change'));
    }

    if (continueReservationBtnCustom) {
        continueReservationBtnCustom.onclick = () => {
            deliveryOptionSelect.selectedIndex = 1;
            deliveryOptionSelect.dispatchEvent(new Event('change'));
            addToCartBtn.click();
            customBox.classList.add('hidden');
        };
    }

    // Clear price spans
    // ['DeliveryPickupPrice', 'DeliveryReturnPrice', 'PickupReturnPrice'].forEach(id => {
    //     const el = document.getElementById(id);
    //     if (el) el.innerText = '';
    // });

    // let radio = document.querySelector('input[name="distance_type"]:checked');
    // if (radio && radio.dataset.text) {
    //     title.textContent = `(${radio.dataset.text})`;
    // } else {
    //     title.textContent = '';
    // }

    // if (type === 'Standard' || type === 'Extended') {
    //     dropdown.classList.remove('hidden');
    //     customBox.classList.add('hidden');
    //     if (type === 'Standard') {
    //         document.getElementById('DeliveryPickupPrice').innerText = formattedStandardFeeX2;
    //         document.getElementById('DeliveryReturnPrice').innerText = formattedStandardFee;
    //         document.getElementById('PickupReturnPrice').innerText = formattedStandardFee;
    //     } else {
    //         document.getElementById('DeliveryPickupPrice').innerText = formattedExtendedFeeX2;
    //         document.getElementById('DeliveryReturnPrice').innerText = formattedExtendedFee;
    //         document.getElementById('PickupReturnPrice').innerText = formattedExtendedFee;
    //     }
    // }else if(type === 'Custom'){
    //     dropdown.classList.add('hidden');
    //     customBox.classList.remove('hidden');
    //     document.getElementById('DeliveryPickupPrice').innerText = formattedExtendedFeeX2;
    //     document.getElementById('DeliveryReturnPrice').innerText = formattedExtendedFee;
    //     document.getElementById('PickupReturnPrice').innerText = formattedExtendedFee;

    // } else {
    //     dropdown.classList.remove('hidden');
    //     customBox.classList.add('hidden');
    // }

    // ------- ---------------------------------------------------------------

    const select = document.getElementById('deliveryOptionSelect');

    if (type === 'Standard' || type === 'Extended') {
        dropdown.classList.remove('hidden');
        customBox.classList.add('hidden');

        if (type === 'Standard') {
            updateOptionText(select, 'Delivery + Pickup', formattedStandardFeeX2);
            updateOptionText(select, 'Delivery Only', formattedStandardFee);
            updateOptionText(select, 'Return Only', formattedStandardFee);
        } else {
            updateOptionText(select, 'Delivery + Pickup', formattedExtendedFeeX2);
            updateOptionText(select, 'Delivery Only', formattedExtendedFee);
            updateOptionText(select, 'Return Only', formattedExtendedFee);
        }

    } else if (type === 'Custom') {
        dropdown.classList.add('hidden');
        customBox.classList.remove('hidden');

        updateOptionText(select, 'Delivery + Pickup', formattedExtendedFeeX2);
        updateOptionText(select, 'Delivery Only', formattedExtendedFee);
        updateOptionText(select, 'Return Only', formattedExtendedFee);

    } else {
        dropdown.classList.remove('hidden');
        customBox.classList.add('hidden');
    }
}

export function toggleServiceMethod(selected, context) {
    const deliveryDiv = document.getElementById('deliveryDiv');
    const storeDiv = document.getElementById('storeDiv');
    const storeSelect = document.getElementById('storeSelect');
    if (storeSelect) {
        storeSelect.selectedIndex = 0;
    }
    if (selected === 'Delivery') {
        if (deliveryDiv) deliveryDiv.classList.remove('hidden');
        if (storeDiv) storeDiv.classList.add('hidden');
        // Show delivery options for default checked distance type
        let checkedRadio = document.querySelector('input[name="distance_type"]:checked');
        updateDeliveryPrices(checkedRadio ? checkedRadio.value : 'Standard', context);
    } else if (selected === 'In Store Pickup') {
        if (deliveryDiv) deliveryDiv.classList.add('hidden');
        if (storeDiv) storeDiv.classList.remove('hidden');
    }
}


function updateOptionText(select, value, price) {
    if (!select) return;

    const option = select.querySelector(`option[value="${value}"]`);
    if (!option) return;

    const label = option.dataset.label || option.textContent.split('[')[0].trim();

    option.textContent = `${label} [${price}]`;
}