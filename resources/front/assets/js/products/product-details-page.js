import { updateDeliveryPrices, toggleServiceMethod } from './delivery-ui';
import { initDatepicker } from './datepicker';
import { changeQty } from './quantity';
import { initProductOptions } from './product-options';
import { initAddToCart } from './add-to-cart';

// You can inject these context values in the Blade (see tip below)
const context = window.productPageContext || {};

document.addEventListener('DOMContentLoaded', function() {
    // Attach globally if needed in inline HTML
    window.changeQty = changeQty;

    // Set up delivery and UI logic
    document.body.addEventListener('change', function(event) {
        if (event.target.name === 'service_method') {
            toggleServiceMethod(event.target.value, context);
        }
        if (event.target.name === 'distance_type') {
            updateDeliveryPrices(event.target.value, context);
        }
    });

    // Set initial state for service_method
    let checkedService = document.querySelector('input[name="service_method"]:checked');
    toggleServiceMethod(checkedService ? checkedService.value : '', context);

    // Delivery option select
    const deliveryOptionSelect = document.getElementById('deliveryOptionSelect');
    if (deliveryOptionSelect) {
        deliveryOptionSelect.addEventListener('change', function(event) {
            const value = event.target.value;
            const storeDiv = document.getElementById('storeDiv');
            const storeSelect = document.getElementById('storeSelect');
            if (storeSelect) {
                storeSelect.selectedIndex = 0;
            }
            if (value === "Delivery + Return" || value === "Pickup + Return") {
                storeDiv.classList.remove('hidden');
            } else {
                storeDiv.classList.add('hidden');
            }
        });
    }

    // Datepicker, option grid modal, add to cart
    initDatepicker();
    initProductOptions();
    initAddToCart(context);

    // --- Here: Update product form from cart, if present ---
    if (window.CartStorage && typeof window.CartStorage.updateProductFormFromCart === 'function') {
        window.CartStorage.updateProductFormFromCart();
    }

});
