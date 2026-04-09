import { updateDeliveryPrices, toggleServiceMethod } from './delivery-ui';
import { initDatepicker } from './datepicker';
import { changeQty } from './quantity';
import { initProductOptions } from './product-options';
import { initAddToCart } from './add-to-cart';

// You can inject these context values in the Blade (see tip below)
const context = window.productPageContext || {};
let hasBoundGlobalListeners = false;

const syncStoreVisibility = (deliveryOptionValue) => {
    const storeDiv = document.getElementById('storeDiv');
    const storeSelect = document.getElementById('storeSelect');
    const storeError = document.getElementById('storeError');

    if (!storeDiv) {
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

const bindGlobalListeners = () => {
    if (hasBoundGlobalListeners) {
        return;
    }

    hasBoundGlobalListeners = true;

    // Set up delivery and UI logic using event delegation (details markup is loaded via AJAX)
    document.body.addEventListener('change', function(event) {
        if (event.target.name === 'service_method') {
            toggleServiceMethod(event.target.value, context);
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

    // Datepicker, option grid modal, add to cart
    initDatepicker();
    initProductOptions();
    initAddToCart(context);

    // Update product form from cart, if present
    if (window.CartStorage && typeof window.CartStorage.updateProductFormFromCart === 'function') {
        window.CartStorage.updateProductFormFromCart();
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
