import { updateDeliveryPrices, toggleServiceMethod } from './delivery-ui';
import { initDatepicker } from './datepicker';
import { changeQty } from './quantity';
import { initProductOptions } from './product-options';
import { initAddToCart } from './add-to-cart';
import {
    initCustomDelivery,
    openCustomDeliveryModal,
    getConfirmedCustomSelection,
    renderConfirmedSummary,
} from './custom-delivery';

// You can inject these context values in the Blade (see tip below)
const context = window.productPageContext || {};
let hasBoundGlobalListeners = false;

const syncStoreVisibility = (deliveryOptionValue) => {
    const storeDiv = document.getElementById('storeDiv');
    const storeSelect = document.getElementById('storeSelect');
    const storeError = document.getElementById('storeError');
    const selectedServiceMethod = document.querySelector('input[name="service_method"]:checked')?.value || '';

    if (!storeDiv) {
        return;
    }

    // Delivery option should only control store visibility when Delivery is selected.
    if (selectedServiceMethod !== 'Delivery') {
        if (selectedServiceMethod === 'In Store Pickup') {
            storeDiv.classList.remove('hidden');
        }
        return;
    }

    const needsStore = deliveryOptionValue === 'Delivery Only' || deliveryOptionValue === 'Return Only';

    if (needsStore) {
        storeDiv.classList.remove('hidden');
        return;
    }

    storeDiv.classList.add('hidden');
    if (storeSelect) {
        storeSelect.selectedIndex = 0;
    }
    if (storeError) {
        storeError.classList.add('hidden');
    }
};

const disableElement = (element) => {
    if (!element) {
        return;
    }

    element.disabled = true;
    element.classList.add('opacity-60', 'cursor-not-allowed');
};

const applyParentRentalLock = () => {
    const lock = context.parentRentalLock;
    if (!lock || !lock.enabled || context.productType !== 'Rental') {
        return;
    }

    const qtyInput = document.getElementById('qty');
    if (qtyInput && lock.quantity != null) {
        qtyInput.value = lock.quantity;
    }
    disableElement(qtyInput);

    disableElement(document.getElementById('qtyDecreaseBtn'));
    disableElement(document.getElementById('qtyIncreaseBtn'));

    const dateInput = document.getElementById('scheduleStartDateInput');
    const dateText = document.getElementById('selectedDateText');
    if (dateInput && lock.delivery_date) {
        dateInput.value = lock.delivery_date;
        if (dateInput._airPicker && typeof dateInput._airPicker.selectDate === 'function') {
            dateInput._airPicker.selectDate(new Date(lock.delivery_date));
        }
    }
    if (dateText && lock.delivery_date) {
        dateText.textContent = lock.delivery_date;
    }
    disableElement(dateInput);

    const openDatePicker = document.getElementById('openDatePicker');
    if (openDatePicker) {
        openDatePicker.classList.add('pointer-events-none', 'opacity-60');
        openDatePicker.setAttribute('aria-disabled', 'true');
        openDatePicker.setAttribute('tabindex', '-1');
    }

    if (lock.service_method) {
        document.querySelectorAll('input[name="service_method"]').forEach((radio) => {
            radio.checked = radio.value === lock.service_method;
            disableElement(radio);
            if (radio.checked) {
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    if (lock.distance_type) {
        document.querySelectorAll('input[name="distance_type"]').forEach((radio) => {
            radio.checked = radio.value === lock.distance_type;
            disableElement(radio);
            if (radio.checked) {
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    const deliverySelect = document.getElementById('deliveryOptionSelect');
    if (deliverySelect) {
        if (lock.service_option) {
            deliverySelect.value = lock.service_option;
            deliverySelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        disableElement(deliverySelect);
    }

    const storeSelect = document.getElementById('storeSelect');
    if (storeSelect) {
        if (lock.delivery_store_id != null && lock.delivery_store_id !== '') {
            storeSelect.value = String(lock.delivery_store_id);
            storeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        disableElement(storeSelect);
    }
};

const bindGlobalListeners = () => {
    if (hasBoundGlobalListeners) {
        return;
    }

    hasBoundGlobalListeners = true;

    // Set up delivery and UI logic using event delegation (details markup is loaded via AJAX)
    document.body.addEventListener('change', function(event) {
        if (event.target.name === 'service_method') {
            toggleServiceMethod(event.target.value, context);
            const deliveryOptionValue = document.getElementById('deliveryOptionSelect')?.value || '';
            syncStoreVisibility(deliveryOptionValue);
        }
        if (event.target.name === 'distance_type') {
            updateDeliveryPrices(event.target.value, context);
        }

        if (event.target.id === 'deliveryOptionSelect') {
            syncStoreVisibility(event.target.value);
        }

        if (event.target.id === 'storeSelect') {
            const value = event.target.value;
            const storeError = document.getElementById('storeError');
            if (!storeError) {
                return;
            }

            if (!value) {
                storeError.textContent = 'Please select a store before adding to cart.';
                storeError.classList.remove('hidden');
            } else {
                storeError.classList.add('hidden');
            }
        }
    });

    // Clicking Custom again (already selected) or its Edit link reopens the
    // popup with the confirmed selection intact
    document.body.addEventListener('click', function(event) {
        if (event.target.id === 'customSelectionEditBtn') {
            openCustomDeliveryModal(context);
            return;
        }

        const radio = event.target.closest('label')?.querySelector('input[name="distance_type"][value="Custom"]')
            || (event.target.name === 'distance_type' && event.target.value === 'Custom' ? event.target : null);
        if (radio && radio.checked && getConfirmedCustomSelection(context)) {
            openCustomDeliveryModal(context);
        }
    });

    // A committed Custom selection drives store visibility exactly like the
    // Standard/Extended dropdown does
    document.addEventListener('custom-delivery:confirmed', function(event) {
        syncStoreVisibility(event.detail?.serviceOption || '');
    });
};

const initDynamicDetailsUI = () => {
    // Set initial state for service_method
    const checkedService = document.querySelector('input[name="service_method"]:checked');
    toggleServiceMethod(checkedService ? checkedService.value : '', context);

    // Apply initial store visibility state if delivery option exists.
    const currentDeliveryOption = document.getElementById('deliveryOptionSelect');
    if (currentDeliveryOption) {
        syncStoreVisibility(currentDeliveryOption.value);
    }

    // Datepicker, option grid modal, add to cart, custom delivery popup
    initDatepicker();
    initProductOptions();
    initAddToCart(context);
    initCustomDelivery(context);

    // Update product form from cart, if present
    if (window.CartStorage && typeof window.CartStorage.updateProductFormFromCart === 'function') {
        window.CartStorage.updateProductFormFromCart();
    }

    applyParentRentalLock();
    renderConfirmedSummary(context);

    // Custom confirmed via cart restore: store visibility must match the
    // restored service option
    const restoredCustom = getConfirmedCustomSelection(context);
    if (restoredCustom && document.querySelector('input[name="distance_type"][value="Custom"]')?.checked) {
        syncStoreVisibility(restoredCustom.serviceOption);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Attach globally if needed in inline HTML
    window.changeQty = changeQty;

    bindGlobalListeners();
    initDynamicDetailsUI();
});

document.addEventListener('product:details-loaded', function() {
    initDynamicDetailsUI();
});
