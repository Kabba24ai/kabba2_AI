// Canonical Custom Delivery selection state.
//
// One confirmed selection lives at context.customDelivery.confirmed:
//   { tier, distance, unit, serviceOption, oneWayRate, amount }
// The popup edits a DRAFT (its own radio inputs) and only commits on
// "Continue with Reservation" — Cancel never applies partial changes.
// All amounts here are display-only; the server recalculates from the
// canonical product rate on every cart save.

const SERVICE_LABELS = {
    'Delivery + Pickup': 'Delivery + Return Pickup',
    'Delivery Only': 'Delivery Only',
    'Return Only': 'Return Pickup Only',
};

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

export function serviceOptionLabel(value) {
    return SERVICE_LABELS[value] || value || '';
}

function calculateAmount(oneWayRate, serviceOption) {
    const rate = parseFloat(oneWayRate);
    if (isNaN(rate)) return null;
    return serviceOption === 'Delivery + Pickup' ? rate * 2 : rate;
}

function summaryText(selection) {
    return `Up to ${selection.distance} ${selection.unit} — ${serviceOptionLabel(selection.serviceOption)} — ${currency.format(selection.amount)}`;
}

export function getConfirmedCustomSelection(context) {
    return context?.customDelivery?.confirmed || null;
}

// Rebuild a confirmed selection from stored cart data. Only tiers that are
// still available (context.customTiers) restore; a stale tier returns null
// and the customer must reselect.
export function restoreConfirmedSelection(context, tier, serviceOption) {
    const available = (context.customTiers || []).find(t => t.tier === tier);
    if (!available || !SERVICE_LABELS[serviceOption]) return null;

    const confirmed = {
        tier: available.tier,
        distance: available.distance,
        unit: available.unit,
        serviceOption,
        oneWayRate: parseFloat(available.one_way_rate),
        amount: calculateAmount(available.one_way_rate, serviceOption),
    };
    context.customDelivery = context.customDelivery || {};
    context.customDelivery.confirmed = confirmed;
    renderConfirmedSummary(context);
    return confirmed;
}

export function clearCustomSelection(context) {
    if (context.customDelivery) context.customDelivery.confirmed = null;
    renderConfirmedSummary(context);
}

export function renderConfirmedSummary(context) {
    const wrap = document.getElementById('customSelectionSummary');
    const text = document.getElementById('customSelectionSummaryText');
    if (!wrap || !text) return;

    const confirmed = getConfirmedCustomSelection(context);
    const customChecked = document.querySelector('input[name="distance_type"][value="Custom"]')?.checked;

    if (confirmed && customChecked) {
        text.textContent = summaryText(confirmed);
        wrap.classList.remove('hidden');
    } else {
        text.textContent = '';
        wrap.classList.add('hidden');
    }
}

function modalElements() {
    const modal = document.getElementById('customDeliveryModal');
    if (!modal) return null;
    return {
        modal,
        tierRadios: Array.from(modal.querySelectorAll('input[name="custom_delivery_tier"]')),
        serviceRadios: Array.from(modal.querySelectorAll('input[name="custom_delivery_service"]')),
        priceSummary: modal.querySelector('#customDeliveryPriceSummary'),
        continueBtn: modal.querySelector('#customDeliveryContinueBtn'),
        cancelBtn: modal.querySelector('#customDeliveryCancelBtn'),
    };
}

function refreshDraftSummary(els) {
    const tier = els.tierRadios.find(r => r.checked);
    const service = els.serviceRadios.find(r => r.checked)?.value || 'Delivery + Pickup';

    if (!tier) {
        els.priceSummary.textContent = 'Choose a distance range to see pricing.';
        els.continueBtn.disabled = true;
        return;
    }

    const amount = calculateAmount(tier.dataset.rate, service);
    els.priceSummary.textContent =
        `Up to ${tier.dataset.distance} ${tier.dataset.unit} — ${serviceOptionLabel(service)} — ${currency.format(amount)}`;
    els.continueBtn.disabled = false;
}

export function openCustomDeliveryModal(context) {
    const els = modalElements();
    if (!els || !els.tierRadios.length) return;

    // Seed the draft from the confirmed selection (or defaults)
    const confirmed = getConfirmedCustomSelection(context);
    els.tierRadios.forEach(r => { r.checked = confirmed ? r.value === confirmed.tier : false; });
    els.serviceRadios.forEach(r => {
        r.checked = r.value === (confirmed ? confirmed.serviceOption : 'Delivery + Pickup');
    });
    refreshDraftSummary(els);

    els.modal.classList.remove('hidden');
    (els.tierRadios.find(r => r.checked) || els.tierRadios[0])?.focus();
}

export function closeCustomDeliveryModal() {
    document.getElementById('customDeliveryModal')?.classList.add('hidden');
}

export function initCustomDelivery(context) {
    const els = modalElements();
    if (!els || els.modal._customDeliveryBound) return;
    els.modal._customDeliveryBound = true;

    context.customDelivery = context.customDelivery || { confirmed: null };

    [...els.tierRadios, ...els.serviceRadios].forEach(radio => {
        radio.addEventListener('change', () => refreshDraftSummary(els));
    });

    els.continueBtn.addEventListener('click', () => {
        const tier = els.tierRadios.find(r => r.checked);
        if (!tier) return; // button is disabled without a range, but stay safe

        const serviceOption = els.serviceRadios.find(r => r.checked)?.value || 'Delivery + Pickup';
        context.customDelivery.confirmed = {
            tier: tier.value,
            distance: tier.dataset.distance,
            unit: tier.dataset.unit,
            serviceOption,
            oneWayRate: parseFloat(tier.dataset.rate),
            amount: calculateAmount(tier.dataset.rate, serviceOption),
        };

        closeCustomDeliveryModal();
        renderConfirmedSummary(context);
        document.dispatchEvent(new CustomEvent('custom-delivery:confirmed', {
            detail: context.customDelivery.confirmed,
        }));
    });

    // Cancel discards the draft; the last confirmed selection (if any) stays
    els.cancelBtn.addEventListener('click', () => closeCustomDeliveryModal());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !els.modal.classList.contains('hidden')) {
            closeCustomDeliveryModal();
        }
    });
}

// cart.js is a plain script (not an ES module); it restores confirmed
// selections from stored cart items through this global.
window.CustomDeliveryRestore = restoreConfirmedSelection;
