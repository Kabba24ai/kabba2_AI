import {
    openCustomDeliveryModal,
    clearCustomSelection,
    getConfirmedCustomSelection,
    renderConfirmedSummary,
} from './custom-delivery';

export function updateDeliveryPrices(type, context) {
    const {
        formattedStandardFeeX2,
        formattedStandardFee,
        formattedExtendedFeeX2,
        formattedExtendedFee,
        formattedZeroFee
    } = context;

    const dropdown = document.getElementById('deliveryOptionsDropdown');
    // Reset dropdown to first option
    const deliveryOptionSelect = document.getElementById('deliveryOptionSelect');
    if (deliveryOptionSelect) {
        deliveryOptionSelect.selectedIndex = 0;
        deliveryOptionSelect.dispatchEvent(new Event('change'));
    }

    const select = document.getElementById('deliveryOptionSelect');
    const isParentLockedRelatedProduct = Boolean(context?.parentRentalLock?.enabled);
    const zeroFee = formattedZeroFee || '$0.00';

    if (isParentLockedRelatedProduct) {
        dropdown.classList.remove('hidden');
        updateOptionText(select, 'Delivery + Pickup', zeroFee);
        updateOptionText(select, 'Delivery Only', zeroFee);
        updateOptionText(select, 'Return Only', zeroFee);
        return;
    }

    if (type === 'Standard' || type === 'Extended') {
        dropdown.classList.remove('hidden');
        // Leaving Custom clears the confirmed tier — a later return to Custom
        // requires a fresh selection
        clearCustomSelection(context);

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
        // The service is chosen inside the Custom Delivery popup — the
        // Standard/Extended dropdown stays hidden
        dropdown.classList.add('hidden');

        if (getConfirmedCustomSelection(context)) {
            // Restored/confirmed selection: show the compact summary only
            renderConfirmedSummary(context);
        } else {
            openCustomDeliveryModal(context);
        }

    } else {
        dropdown.classList.remove('hidden');
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
