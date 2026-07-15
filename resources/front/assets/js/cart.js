window.CartStorage = (function(){
    const CART_KEY = 'kabba_cart';

    function getCart() {
        return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    }

    function setCart(cart) {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }


    function addOrUpdateItem(productData) {
        let cart = getCart();

        // Remove any existing item with same product_unique_id
        cart = cart.filter(item => item.product_unique_id !== productData.product_unique_id);

        // Add the new/updated item from the backend
        cart.push(productData);

        setCart(cart);
        return cart;
    }

    function removeItemByUniqueId(uniqueId) {
        let cart = getCart();
        cart = cart.filter(item => item.product_unique_id !== uniqueId);
        setCart(cart);
        return cart;
    }

    function removeItemByIndex(index) {
        let cart = getCart();
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            setCart(cart);
        }
        return cart;
    }

    function clearCart() {
        localStorage.removeItem(CART_KEY);
    }

    function getTotalQuantity() {
        return getCart().reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
    }

    function updateProductFormFromCart() {
        const cart = getCart();
        const productUniqueId = window.productPageContext.productUniqueId;
        const productVariant = window.productPageContext.productVariant;
        const cartItem = cart.find(item =>
            item.product_unique_id === productUniqueId &&
            item.product_variant === productVariant
        );
        if (!cartItem) return;

        // Update Quantity
        const qtyInput = document.getElementById('qty');
        if (qtyInput && cartItem.quantity != null) qtyInput.value = cartItem.quantity;

        // Update Schedule Start Date
        if (cartItem.delivery_date) {
           const dateInput = document.getElementById('scheduleStartDateInput');
            const dateText = document.getElementById('selectedDateText');
            if (dateInput) {
                dateInput.value = cartItem.delivery_date;
                if (dateInput._airPicker && typeof dateInput._airPicker.selectDate === 'function') {
                    dateInput._airPicker.selectDate(new Date(cartItem.delivery_date));
                }
            }
            if (dateText) dateText.textContent = cartItem.delivery_date;
        }

        // Product Options (checkboxes)
        if (Array.isArray(cartItem.product_option_items)) {
            cartItem.product_option_items.forEach(opt => {
                if (opt.unique_id) {
                    const cb = document.getElementById('options_' + opt.unique_id);
                    if (cb) cb.checked = true;
                }
            });
        }

        // Service Method (radio)
        if (cartItem.service_method) {
            document.querySelectorAll('input[name="service_method"]').forEach(radio => {
                // We need to trigger change for the one we set
                if (radio.value === cartItem.service_method) {
                    radio.checked = true;
                    // Fire change event
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    radio.checked = false;
                }
            });
        }

        // Distance Type (radio) — for Custom, restore the confirmed tier BEFORE
        // dispatching the change so the popup does not reopen; a stale tier
        // (no longer available) stays unconfirmed and the popup asks again
        if (cartItem.distance_type) {
            if (cartItem.distance_type === 'Custom'
                && cartItem.custom_tier
                && window.productPageContext
                && window.CustomDeliveryRestore) {
                window.CustomDeliveryRestore(window.productPageContext, cartItem.custom_tier, cartItem.service_option);
            }
            document.querySelectorAll('input[name="distance_type"]').forEach(radio => {
                if (radio.value === cartItem.distance_type) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    radio.checked = false;
                }
            });
        }

        // Delivery Option Select
        if (cartItem.service_option && document.getElementById('deliveryOptionSelect')) {
            const select = document.getElementById('deliveryOptionSelect');
            select.value = cartItem.service_option;
            // Fire change event for select
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Store Location Select
        if (cartItem.delivery_store_id && document.getElementById('storeSelect')) {
            const select = document.getElementById('storeSelect');
            select.value = cartItem.delivery_store_id;
            // Optional: fire change event if you have listeners
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }


    return {
        getCart,
        setCart,
        addOrUpdateItem,
        clearCart,
        getTotalQuantity,
        removeItemByUniqueId,
        removeItemByIndex,
        updateProductFormFromCart
    };
})();

