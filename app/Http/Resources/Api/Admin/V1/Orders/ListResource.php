<?php

namespace App\Http\Resources\Api\Admin\V1\Orders;

use App\Helpers\CustomHelper;
use App\Http\Resources\Api\Admin\V1\OrderAddresses\ListResource as OrderAddressesListResource;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Api\Admin\V1\OrderMedias\ListResource as OrderMediasListResource;
use App\Http\Resources\Api\Admin\V1\OrderNotes\ListResource as OrderNotesListResource;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource as OrderProductsListResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? 0,
            'order_number' => $this->order_number ?? '',
            'customer_name' => $this->customer_name ?? '',
            'customer_email' => $this->customer_email ?? '',
            'customer_phone' => $this->customer_phone ?? '',
            'company_name' => $this->company_name ?? '',
            'company_website' => $this->company_website ?? '',
            'status' => $this->last_payment_status ?? '',
            'order_date' => CustomHelper::formatDate($this->order_date) ?? '',

            'subtotal' => CustomHelper::formatCurrency($this->subtotal) ?? '',
            'is_tax_exempt' => $this->is_tax_exempt ?? false,
            'tax_amount' => CustomHelper::formatCurrency($this->tax_amount) ?? '',
            'coupon_code' => $this->coupon_code ?? '',
            'discount_amount' => CustomHelper::formatCurrency($this->discount_amount) ?? '',

            'amount' => CustomHelper::formatCurrency($this->grand_total) ?? '',
            'platform' => $this->platform ?? '',
            'payment_type' => $this->last_payment_type ?? '',
            'payment_status' => $this->last_payment_status ?? '',
            // Human-readable canonical labels, additive alongside the raw
            // machine values above (which stay unchanged for existing
            // clients) — the same wording Order Details/receipts/reports
            // use, so a client can display them without its own mapping.
            'payment_type_label' => PaymentDescriptionPresenter::methodLabel($this->last_payment_type),
            'payment_status_label' => PaymentDescriptionPresenter::statusLabel($this->last_payment_status),
            // Phase 2 payment experience: the figures a mobile client needs
            // to answer "has the customer paid / how much / how much is
            // left" without a second request — sourced from the same
            // aggregate accessors (not last_payment_type/status) so a
            // split/partial payment history is reflected correctly.
            'amount_paid' => CustomHelper::formatCurrency($this->total_paid) ?? '',
            'balance_due' => CustomHelper::formatCurrency($this->balance_due) ?? '',
            'payment_date' => CustomHelper::formatDateTime(optional($this->lastPaidPayment ?? $this->lastPayment)->payment_datetime) ?? '',
            'payment_reference' => $this->paymentReferenceForApi(),
            'delivery_address' => new OrderAddressesListResource($this->whenLoaded('shippingAddress') ?? []),
            'billing_address' => new OrderAddressesListResource($this->whenLoaded('billingAddress') ?? []),

            'is_same_as_billing' => ($this->relationLoaded('shippingAddress') && $this->shippingAddress)
                ? $this->shippingAddress->isSameAs($this->relationLoaded('billingAddress') ? $this->billingAddress : null)
                : false,

            'license' => OrderMediasListResource::collection($this->whenLoaded('licenseMedia') ?? []),
            'order_products' => OrderProductsListResource::collection($this->whenLoaded('products')) ?? [],
            'order_notes' => OrderNotesListResource::collection($this->whenLoaded('notes')) ?? [],

            'terms_status' => $this->terms_status ?? '',
            'terms_page' => route('front.terms-and-conditions.index', ['orderUniqueId' => $this->unique_id, 'device' => 'mobile']) ?? '',

            'terms_accepted_at' => CustomHelper::formatDateTime($this->terms_accepted_at) ?? '',

        ];

        return $return;
    }

    /**
     * The reference a mobile client would actually want to show alongside
     * the payment — last 4 + auth code for Card, check number for Cheque,
     * otherwise whatever note was captured (Gift Card #, Zelle sender,
     * etc.). Null when there's nothing to show, same "only when relevant"
     * rule the admin Payment Details panel follows.
     */
    private function paymentReferenceForApi(): ?string
    {
        $payment = $this->lastPaidPayment ?? $this->lastPayment;

        if (!$payment) {
            return null;
        }

        return match ($payment->payment_method) {
            \App\Enums\Orders\OrderPaymentMethod::Card => trim(
                ($payment->card_number ? '•••• ' . $payment->card_number : '')
                . ($payment->auth_code ? ' · Auth ' . $payment->auth_code : '')
            ) ?: null,
            \App\Enums\Orders\OrderPaymentMethod::Cheque => $payment->cheque_number ? '#' . $payment->cheque_number : null,
            default => $payment->payment_note ?: null,
        };
    }
}
