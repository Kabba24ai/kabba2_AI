import { getConfirmedCustomSelection, openCustomDeliveryModal } from './custom-delivery';

export function initAddToCart(context) {
    const addToCartBtn = document.getElementById('addToCart');

    if (!addToCartBtn) return;

    addToCartBtn.addEventListener('click', function() {
        // 🛑 If high demand → show modal and stop here
        if (context.hasHighDemandAlert) {
            const modal = document.getElementById('modalHighDemandAlert');
            const continueReservationBtn = document.getElementById('continueReservationBtn');

            modal.classList.remove('hidden');

            // Continue → hide modal and trigger add-to-cart again (without showing modal)
            continueReservationBtn.onclick = () => {
                modal.classList.add('hidden');
                context.hasHighDemandAlert = false; // converting to false to move further
                addToCartBtn.click(); // re-trigger same handler
                context.hasHighDemandAlert = true; // reset to true for future adds
            };

            return; // stop here until user decides
        }

        const loader = addToCartBtn.querySelector('.loader-gif');
        if (loader) loader.classList.remove('hidden');

        addToCartBtn.disabled = true;
        addToCartBtn.classList.add('opacity-60', 'cursor-not-allowed');

        const scheduleInput = document.getElementById('scheduleStartDateInput');
        const scheduleDate = scheduleInput ? scheduleInput.value : '';

        let qty = parseInt(document.getElementById('qty').value);
        // Weekend Special: force qty to 1
        if (context.productVariant === 'weekend') {
            qty = 1;
            document.getElementById('qty').value = 1;
        }
        const storeId = document.getElementById('storeSelect')?.value || '';
        const serviceMethod = document.querySelector('input[name="service_method"]:checked')?.value || '';
        const dateError = document.getElementById('dateError');
        const qtyError = document.getElementById('qtyError');
        const storeError = document.getElementById('storeError');
        const deliveryOptionSelectError = document.getElementById('deliveryOptionSelectError');

        // Custom delivery picks its service inside the popup; Standard and
        // Extended keep using the dropdown
        const selectedDistanceType = serviceMethod === 'Delivery'
            ? (document.querySelector('input[name="distance_type"]:checked')?.value || '')
            : '';
        const confirmedCustom = selectedDistanceType === 'Custom' ? getConfirmedCustomSelection(context) : null;
        const serviceOption = selectedDistanceType === 'Custom'
            ? (confirmedCustom?.serviceOption || '')
            : (document.getElementById('deliveryOptionSelect')?.value || '');

        // Clear previous errors
        if (storeError) storeError.classList.add('hidden');
        if (dateError) dateError.classList.add('hidden');
        if (qtyError) qtyError.classList.add('hidden');

        let errorMsg = '';
        let customIncomplete = false;
        if(serviceMethod === 'Delivery'){

            if (deliveryOptionSelectError) deliveryOptionSelectError.classList.add('hidden');
            if (selectedDistanceType === 'Custom' && !confirmedCustom) {
                customIncomplete = true;
                errorMsg = 'Please complete your custom delivery selection before adding to cart.';
                if (deliveryOptionSelectError) {
                    deliveryOptionSelectError.textContent = errorMsg;
                    deliveryOptionSelectError.classList.remove('hidden');
                }
            } else if(!serviceOption){
                errorMsg = 'Please select a delivery option before adding to cart.';
                if (deliveryOptionSelectError) {
                    deliveryOptionSelectError.textContent = errorMsg;
                    deliveryOptionSelectError.classList.remove('hidden');
                }
            }
        }
        if (context.productType === 'Rental' && !storeId && serviceOption !== 'Delivery + Pickup') {
            errorMsg = 'Please select a store before adding to cart.';
            if (storeError) {
                storeError.textContent = errorMsg;
                storeError.classList.remove('hidden');
            }
        }
        if (context.productType === 'Rental' && !scheduleDate) {
            errorMsg = 'Please select a date before adding to cart.';
            if (dateError) {
                dateError.textContent = errorMsg;
                dateError.classList.remove('hidden');
            }
        }
        if (!qty) {
            errorMsg = 'Please enter a valid quantity.';
            if (qtyError) {
                qtyError.textContent = errorMsg;
                qtyError.classList.remove('hidden');
            }
        }

        if (customIncomplete || (context.productType === 'Rental' && !storeId && serviceOption !== 'Delivery + Pickup') || (context.productType === 'Rental' && !scheduleDate) || !qty) {
            if (loader) loader.classList.add('hidden');
            addToCartBtn.disabled = false;
            addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            if (customIncomplete) {
                openCustomDeliveryModal(context);
            }
            return;
        }


        const distanceType = selectedDistanceType;

        let distanceRange = '';
        let unit = context.distanceUnit || '';
        if (distanceType === 'Standard') {
            distanceRange = context.standardDeliveryRange + ' ' + unit;
        } else if (distanceType === 'Extended') {
            distanceRange = context.extendedDeliveryRange + ' ' + unit;
        } else if (distanceType === 'Custom' && confirmedCustom) {
            distanceRange = confirmedCustom.distance + ' ' + confirmedCustom.unit;
        }
        const productOptionItems = [];
        const productRentalItems = [];
        document.querySelectorAll('#optionDiv input[type="checkbox"].form-checkbox:checked')
            .forEach(checkbox => {
                if (checkbox.dataset.unique_id) {
                    productOptionItems.push({
                        unique_id: checkbox.dataset.unique_id,
                    });
                } else {
                    productRentalItems.push(checkbox.dataset.name);
                }
            });

        const payload = {
            product_unique_id: context.productUniqueId,
            product_type: context.productType,
            product_variant: context.productVariant,
            quantity: qty,
            delivery_date: scheduleDate,
            service_method: serviceMethod,
            distance_type: distanceType,
            distance_range: distanceRange,
            // Internal tier identifier only — the server resolves the canonical
            // distance, one-way rate, and final amount itself
            custom_tier: distanceType === 'Custom' ? (confirmedCustom?.tier || '') : '',
            service_option: serviceOption,
            delivery_store_id: storeId,
            product_option_items: productOptionItems,
            product_rental_items: productRentalItems
        };

        fetch(context.cartSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': context.csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw errorData || { message: 'An unknown error occurred.' };
                }
                return response.json();
            })
            .then(resp => {
                if (resp.success && resp.cart_data) {
                    resp.cart_data.cart_items.forEach(item => CartStorage.addOrUpdateItem(item));
                    window.loadCartSidebarPreview();
                    document.querySelector('.toggleCart')?.click();
                } else {
                    notyf.error(resp.message || 'Failed to add to cart.');
                }
            })
            .catch(err => {
                if (err?.errors) {
                    Object.values(err.errors).forEach(messages => {
                        messages.forEach(msg => showStickyError(msg));
                    });
                } else {
                    showStickyError(err.message || 'Could not connect to server. Please try again.');
                }
            })
            .finally(() => {
                if (loader) loader.classList.add('hidden');
                addToCartBtn.disabled = false;
                addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            });
    });
}
