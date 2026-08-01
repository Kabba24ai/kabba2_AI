{{--
    Canonical report payment-status bucket options — ONE definition, shared by
    every sales report filter (previously copy-pasted verbatim in
    sales_by_stores, product_sales_ranking, and pure_sales_summary).

    These are REPORT BUCKETS, not OrderPaymentStatus enum values: the report
    engine (App\Services\Reports\SalesReportingService::applyFilters) recognizes
    'paid' | 'all' | 'pod' | 'account' (+ the internal 'paid_and_account').
    Default is 'paid' when the filter is unset.

    Expects: $filters (array with optional 'payment_status').
--}}
@php $selectedPaymentStatus = $filters['payment_status'] ?? 'paid'; @endphp
<option value="paid"    @selected($selectedPaymentStatus === 'paid')>Paid</option>
<option value="all"     @selected($selectedPaymentStatus === 'all')>All</option>
{{-- POD is an EXPECTED-VALUE projection (order-date basis, uncollected) on
     every report — it never represents cash collections. --}}
<option value="pod"     @selected($selectedPaymentStatus === 'pod')>POD / Expected Revenue</option>
<option value="account" @selected($selectedPaymentStatus === 'account')>Account</option>
