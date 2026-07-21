{{-- Canonical phone-call reason lists, shared by the Call Needed modal and
     the unified New Task modal. Assigned on window with an idempotent guard
     so any number of partials on one page can include it safely. --}}
<script>
window.CALL_REASON_LISTS = window.CALL_REASON_LISTS || {
    customer: [
        { value: 'contract_renewal',       label: 'Contract Renewal' },
        { value: 'delivery_pickup',        label: 'Delivery / Pickup' },
        { value: 'equipment_availability', label: 'Equipment Availability' },
        { value: 'equipment_return',       label: 'Equipment Return' },
        { value: 'general_followup',       label: 'General Follow-up' },
        { value: 'maintenance_request',    label: 'Maintenance Request' },
        { value: 'order_review',           label: 'Order Review' },
        { value: 'payment_followup',       label: 'Payment Follow-up' },
        { value: 'rental_inquiry',         label: 'Rental Inquiry' },
        { value: 'returning_call',         label: 'Returning Their Call' },
    ],
    supplier: [
        { value: 'availability_lead_time', label: 'Availability / Lead Time' },
        { value: 'equipment_service',      label: 'Equipment Service / Technical Support' },
        { value: 'general_followup',       label: 'General Follow-up' },
        { value: 'invoice_billing',        label: 'Invoice / Billing Question' },
        { value: 'order_parts',            label: 'Order Parts' },
        { value: 'order_status',           label: 'Order Status' },
        { value: 'other',                  label: 'Other' },
        { value: 'price_quote',            label: 'Price Quote' },
        { value: 'return_exchange',        label: 'Return / Exchange' },
        { value: 'returning_call',         label: 'Returning Their Call' },
        { value: 'warranty_defective',     label: 'Warranty / Defective Item' },
    ],
};
</script>
