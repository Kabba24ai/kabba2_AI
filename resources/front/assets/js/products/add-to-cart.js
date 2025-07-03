export function initAddToCart(context) {
    const addToCartBtn = document.getElementById('addToCart');
    if (!addToCartBtn) return;

    addToCartBtn.addEventListener('click', function() {
        const loader = addToCartBtn.querySelector('.loader-gif');
        if (loader) loader.classList.remove('hidden');
        addToCartBtn.disabled = true;
        addToCartBtn.classList.add('opacity-60', 'cursor-not-allowed');

        const qty = parseInt(document.getElementById('qty').value) || 1;
        const scheduleDate = document.getElementById('scheduleStartDateInput').value;
        const dateError = document.getElementById('dateError');
        if (!scheduleDate) {
            if (loader) loader.classList.add('hidden');
            addToCartBtn.disabled = false;
            addToCartBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            if (dateError) {
                dateError.textContent = 'Please select a date before adding to cart.';
                dateError.classList.remove('hidden');
            }
            return;
        }
        const serviceMethod = document.querySelector('input[name="service_method"]:checked')?.value || '';
        let distanceType = '';
        if (serviceMethod === 'Delivery') {
            distanceType = document.querySelector('input[name="distance_type"]:checked')?.value || '';
        }
        const serviceOption = document.getElementById('deliveryOptionSelect')?.value || '';
        const storeId = document.getElementById('storeSelect')?.value || '';
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
            schedule_start_date: scheduleDate,
            service_method: serviceMethod,
            distance_type: distanceType,
            distance_range: distanceRange,
            service_option: serviceOption,
            store_id: storeId,
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
                if (resp.success && resp.cart_items) {
                    resp.cart_items.cart_data.forEach(item => CartStorage.addOrUpdateItem(item));
                    window.loadCartSidebarPreview();
                    document.querySelector('.toggleCart')?.click();
                } else {
                    notyf.error(resp.message || 'Failed to add to cart.');
                }
            })
            .catch(err => {
                if (err && err.errors) {
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
