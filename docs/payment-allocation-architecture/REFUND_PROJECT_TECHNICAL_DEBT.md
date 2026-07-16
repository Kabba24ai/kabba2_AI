# Refund Project — Final Technical Debt Inventory

Written at project close (Final Phase — Refund Consumer Cleanup). Every item
below was found during the refund project's audits, is real, and is
deliberately **not** fixed here because it sits outside `order_payments`/
`PaymentAllocationService` — each belongs to **Payment Architecture
Finalization – Source of Truth Consolidation**, not the refund project.

1. **`Order::lastPayment()`/`lastPaidPayment()` "most recent payment"
   simplification** — used for display/filtering (not dollar totals) in
   `Http/Resources/Api/Admin/V1/Orders/ListResource.php`,
   `ReceiptService::currentPaymentMethodLabel()`, a checkout SMS listener,
   and the Dispatch/Schedules payment-status filter. On a split-payment
   order these show/filter by whichever payment was entered last, not a
   true aggregate. Not a refund-correctness bug (no dollar figure is wrong),
   but part of the same general "single-payment assumption" this project's
   own investigation phase already scoped as out-of-bounds general payment
   architecture work.

2. **`Api/SalesReports/V1/SalesReportController`'s three legacy-query
   endpoints** (`getRevenueBreakdown`, `getTaxAndPayments`,
   `getProductSalesDetails`) do not net out refunds at all. This was
   already true before this project touched them — their dead
   refund-detection branches (removed this phase) never actually executed.
   Correctly modeling refunds here requires the same canonical-engine work
   the sibling endpoints on this controller already received in the prior
   "Phase 2D consolidation" (see that controller's own docblock) and is
   deferred to the next project.

That's the complete list. Nothing else surfaced across four full audit
passes (financial-integrity, legacy-assumption, workflow-consistency, and
this final consumer sweep) remains outstanding for the order-payment refund
system itself.
