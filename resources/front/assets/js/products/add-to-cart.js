export function initAddToCart(context) {
    const addToCartBtn = document.getElementById('addToCart');
    if (!addToCartBtn) return;

    addToCartBtn.addEventListener('click', function() {
        const loader = addToCartBtn.querySelector('.loader-gif');
        if (loader) loader.classList.remove('hidden');
        addToCartBtn.disabled = true;
        addToCartBtn.classList.add('opacity-60', 'cursor-not-allowed');

        const qty = parseInt(document.getElementById('qty').value);
        const storeId = document.getElementById('storeSelect')?.value || '';
        const scheduleDate = document.getElementById('scheduleStartDateInput').value;
        const dateError = document.getElementById('dateError');
        const qtyError = document.getElementById('qtyError');
        const storeError = document.getElementById('storeError');

        // Clear previous errors
        if (storeError) storeError.classList.add('hidden');
        if (dateError) dateError.classList.add('hidden');
        if (qtyError) qtyError.classList.add('hidden');

        let errorMsg = '';
        if (context.productType === 'Rental' && !storeId) {
            errorMsg = 'Please select a store before adding to cart.';
            if (storeError) {
            storeError.textContent = errorMsg;
            storeError.classList.remove('hidden');
            }
        }
        if (!scheduleDate) {
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

        if ((context.productType === 'Rental' && !storeId) || !scheduleDate || !qty) {
            if (loader) loader.classList.add('hidden');
            addToCartBtn.disabled = false;
            addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            return;
        }

        const serviceMethod = document.querySelector('input[name="service_method"]:checked')?.value || '';
        let distanceType = '';
        if (serviceMethod === 'Delivery') {
            distanceType = document.querySelector('input[name="distance_type"]:checked')?.value || '';
        }
        const serviceOption = document.getElementById('deliveryOptionSelect')?.value || '';

        let distanceRange = '';
        let unit = context.distanceUnit || '';
        if (distanceType === 'Standard') {
            distanceRange = context.standardDeliveryRange + ' ' + unit;
        } else if (distanceType === 'Extended') {
            distanceRange = context.extendedDeliveryRange + ' ' + unit;
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
