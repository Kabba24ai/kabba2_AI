# SMS Automation Workflow Audit

**Status:** Audit only — no code changed as part of this document.
**Author:** Technical audit (Claude Code), performed 2026-07-20.
**Codebase:** Kabba / Rent 'n King rental order-management platform (Laravel 11).
**Trigger:** Reports that automated SMS sequences (payment reminders, payment links, order/pickup reminders) continue sending after the customer has already completed the intended action.

---

## 1. Executive Summary

This audit traced every automated SMS-sending code path in the codebase — 9 scheduled jobs, 1 checkout event listener, 2 manual admin-triggered controllers, and a tag-based CRM broadcast system — from trigger to completion logic, and cross-referenced them against the order/payment/delivery/pickup data model.

**The reported symptom is real and has a confirmed root cause**, plus several confirmed and probable secondary causes:

1. **Confirmed root cause — payment reminders continue after the invoice is paid.** When a COD ("Pay on Delivery") order is paid off through any method *other than* completing the COD payment record itself (cash, card, check received in person, etc.), the original `order_payments` row for the COD method is never transitioned out of `status = 'Pending'`. The fix for this was written, reviewed, and then **commented out** in `ReceivePaymentController.php` (lines ~284-290) rather than shipped. Because `SendPodPaymentReminderJob` (runs every minute, 4 sub-sequences) gates every check on `payments.payment_method = 'COD' AND payments.status = 'Pending'`, it has no way of knowing the order was actually paid, and will keep sending payment-link and payment-reminder SMS indefinitely. This is the single highest-priority finding in this audit. See §7.

2. **Confirmed — 3 of the 4 rental delivery/pickup reminder jobs have no re-send guard at all.** Only `SendDeliveryDayBeforeRentalReminderJob` checks `sms_logs` to avoid re-sending. `SendDeliverySameDayRentalReminderJob`, `SendReturnDayBeforeRentalReminderJob`, and `SendReturnSameDayRentalReminderJob` rely solely on a mutable status string (`delivery_status`/`pickup_status = 'Pending'`) staying unchanged, and that string can be reset to `'Pending'` by two different admin actions even after the delivery/pickup was already marked complete. See §6, §8.

3. **Confirmed — Weekend Special pickup time defect, root-caused to two exact lines of code.** `CartHelper.php`'s rental-variant switch statement correctly special-cases *delivery* time for Weekend Special (2:00 PM) but leaves *pickup* time hardcoded at `09:00:00` for every variant including Weekend Special — a copy/paste omission, not a systemic design gap. See §9.

4. **Confirmed — two independent SMS systems (rental reminders vs. Sales Funnel) can both message the same customer about the same delivery on the same day**, with zero mutual awareness, if the Sales Funnel feature flag is enabled. See §10.

5. **Probable/design-gap — no cross-system daily message throttle.** A single COD same-day-delivery order can legitimately receive 3+ SMS in one calendar day from three different, individually-correct pieces of logic, none of which know about the others.

No GoHighLevel-style "pipeline"/"opportunity"/"tag-workflow-enrollment" system exists in this codebase — that terminology in the audit brief does not map onto this application. This is a from-scratch Laravel app with its own `SalesFunnel` step-offset engine and a separate tag-based `SmsAudienceResolver` broadcast system. This distinction matters for anyone reading this report who is expecting GHL-shaped remediation (e.g., "remove contact from workflow") — the actual remediation here is SQL-query-guard and status-lifecycle work, documented in detail below.

---

## 2. Audit Scope

Everything in the codebase capable of sending an SMS was inventoried and traced. In scope:

- All `Schedule::job(...)` entries in `routes/console.php` (Laravel 11 — there is no `app/Console/Kernel.php`).
- Every Job class using `TwilioService`.
- The checkout-triggered `SendSmsListener`.
- Manual admin "resend" controllers.
- The tag-based CRM broadcast system (`SmsBroadcastEvent` / `SmsAudienceResolver`).
- The order/payment/delivery/pickup data model fields that gate each automation.
- The pickup-time / merge-field generation pipeline (`CartHelper.php`, `SettingSeeder.php`).
- Queue/retry configuration, model observers, and locking behavior relevant to race conditions and duplicate sends.

Out of scope / confirmed not present: GHL-style pipelines, opportunities, or per-contact workflow enrollment tables (none exist — verified by exhaustive grep, see §14). Marketing/campaign automation beyond the CRM tag broadcast system (none found). Email automation (this audit is SMS-only, per the brief).

---

## 3. Complete SMS Automation Inventory

| # | Automation | Type | Schedule / Trigger | Customer-facing? |
|---|---|---|---|---|
| 1 | `SendDeliveryDayBeforeRentalReminderJob` | Scheduled job | Daily 15:00 (America/Chicago) | Yes |
| 2 | `SendDeliverySameDayRentalReminderJob` | Scheduled job | Daily 07:00 | Yes |
| 3 | `SendReturnDayBeforeRentalReminderJob` | Scheduled job | Daily 15:00 | Yes |
| 4 | `SendReturnSameDayRentalReminderJob` | Scheduled job | Daily 07:00 | Yes |
| 5 | `SendPodPaymentReminderJob` (4 internal sub-sequences) | Scheduled job | Every minute | Yes |
| 6 | `SalesFunnelBeforeEventJob` | Scheduled job | Every 15 min; feature-flagged (`SALES_FUNNEL_ENABLED`, default `false`) | Yes |
| 7 | `SalesFunnelAfterEventJob` | Scheduled job | Every 15 min; same feature flag | Yes |
| 8 | `SendSmsBroadcastEventJob` | Job, dispatched on-demand | Admin "send now" or `ProcessScheduledSmsBroadcastsJob` (every minute) | Yes |
| 9 | `SendDailyPendingTermsReminderJob` → `SendTermsRequestJob` | Scheduled job → job | Daily 06:45 (scheduled #3); also dispatched directly from checkout (#1 immediate+15min, #2 +2hr) | Yes |
| 10 | `SendSmsListener` (checkout) | Event listener | `OrderPlacedEvent`, COD orders only | Yes |
| 11 | `ResendPodPaymentLinkController` | Manual admin action | Admin clicks "Resend Payment Link" | Yes |
| 12 | `SendTermsAndConditionsController` | Manual admin action | Admin clicks "Send Terms" | Yes |
| 13 | `SendMeetingReminderJob` | Scheduled job | Every 30 min | No — internal, 4 hardcoded admin phone numbers |
| 14 | `AutoLunchReminderJob` / `AutoClockOutEmployeesJob` | Scheduled jobs | Every minute / every 20 min | No — staff timeclock |

Items 13–14 are internal/staff-facing, not customer automations, but are included for completeness because they consume the same Twilio account/rate limits and the same `TwilioService`.

**Central audit trail:** `sms_logs` table (model `App\Models\Global\SMSLog`), written by `TwilioService::sendSms()` for every attempt (success or failure), keyed by `sms_type` (21-case enum `App\Enums\Communication\SmsType`), `order_id`, `customer_id`, `phone`, `status`, `twilio_sid`. This is the only cross-automation record of "was an SMS sent" — but as shown below, most jobs don't consistently *use* it as a guard.

**No dedicated `sms_templates` table exists.** Templates live as free-text settings fields (`ConfigurationHelper::getSettings(...)`) except CRM broadcasts, which pull from a `Message` relation on `SmsBroadcastEvent`.

**No `Pipeline`, `Opportunity`, or `Stage` CRM model exists.** The only "Opportunity" model in the codebase is unrelated (`OpportunityQuestion`, part of an employment-application module). "Tags" exist only as a JSON column on `Customer`, consumed by the broadcast audience resolver — there is no tag-driven workflow-enrollment engine.

---

## 4. Detailed Workflow Documentation

### 4.1 `SendDeliveryDayBeforeRentalReminderJob`
- **Purpose:** Notify customer their rental delivery is scheduled for tomorrow.
- **Entry trigger:** Cron, daily 15:00.
- **Entry conditions:** `OrderProduct.product.is_default_funnel = true`, `product_data->product_type = 'Rental'`, `delivery_date = tomorrow`, `delivery_status = 'Pending'`, order has no unresolved COD-Pending payment.
- **Exit/completion criteria:** N/A (fire-and-forget); dedup guard prevents re-send.
- **Dedup/loop guard:** `whereNotExists` subquery against `sms_logs` for `sms_type = DELIVERY_DAY_BEFORE` on that `order_id` — **the only one of the 4 rental jobs with this guard.**
- **Merge fields:** `{{customer_name}}`, `{{store_name}}`, `{{delivery_date}}` (date only, `M d, Y` format — no time).
- **Next step / repeat logic:** None; single send per order per the dedup guard.

### 4.2 `SendDeliverySameDayRentalReminderJob`
- **Purpose:** Notify customer delivery is happening today; branches paid vs. COD-Pending message content.
- **Entry trigger:** Cron, daily 07:00.
- **Entry conditions:** Same product/date filter, `delivery_date = today`, `delivery_status = 'Pending'`. Splits recipients via `OrderPaymentSummary::for($order)->unresolvedPaymentAttempts` into paid-template vs. COD-template buckets.
- **Dedup/loop guard: NONE.** No `sms_logs` check. Relies entirely on `delivery_status` staying `'Pending'` for the rest of the day.
- **Merge fields:** `{{customer_name}}`, `{{store_name}}`, `{{delivery_date}}`.
- **Loop/duplicate risk:** If `delivery_status` is reset to `'Pending'` after this job already ran today (see §8), the job will re-send on the *next day's* 07:00 run only if `delivery_date` still equals that day — but a same-day admin reset combined with a delayed queue retry, or the equipment-removal reset described in §8, can cause a second send within the reminder window before status changes.

### 4.3 `SendReturnDayBeforeRentalReminderJob`
- **Purpose:** Notify customer their rental is due back tomorrow.
- **Entry trigger:** Cron, daily 15:00.
- **Entry conditions:** `pickup_date = tomorrow`, `pickup_status = 'Pending'`. **No COD split.**
- **Dedup/loop guard: NONE.**
- **Merge fields:** `{{customer_name}}`, `{{store_name}}`, `{{return_date}}` (date only).

### 4.4 `SendReturnSameDayRentalReminderJob`
- **Purpose:** Notify customer rental is due back today.
- **Entry trigger:** Cron, daily 07:00.
- **Entry conditions:** `pickup_date = today`, `pickup_status = 'Pending'`.
- **Dedup/loop guard: NONE.**
- **Merge fields:** same as 4.3.

### 4.5 `SendPodPaymentReminderJob` (4 sub-sequences, every-minute cron)
- **Purpose:** Chase payment for "Pay on Delivery" (COD) orders through an escalating sequence.
- **Sub-sequence 1 — `processPaymentLinkMessage`:** fires ~1 minute after the checkout `COD_ORDER_NOTIFICATION` SMS was logged, if payment is still COD-Pending and no `POD_PAYMENT_LINK` sms_log exists yet. Contains a hardcoded cutover guard (`created_at < '2026-06-21'` → skip), marked "intentional, do not remove" in comments — a migration/cutover artifact worth flagging for eventual removal once all pre-cutover orders have aged out.
- **Sub-sequence 2 — `processDayBefore3pm`:** fires only when `now()->hour >= 15`; COD-Pending + `delivery_date = tomorrow` + no `POD_DAY_BEFORE` sms_log.
- **Sub-sequence 3 — `processFinalReminder9am`:** fires only when `hour >= 9`; COD-Pending + `delivery_date = today` + no `POD_FINAL_REMINDER` sms_log.
- **Sub-sequence 4 — `processLastDitch4pm`:** fires only when `hour >= 16`; COD-Pending + `delivery_date = today` + no `POD_LAST_DITCH` sms_log.
- **Entry conditions (all 4):** `payments.payment_method = 'COD' AND payments.status = 'Pending'`. **This is the exact condition that never clears when a COD order is paid off by another method — see §7.**
- **Completion/dedup guard:** each sub-sequence checks/writes its own `sms_type` row in `sms_logs`, *and* the job additionally does a manual `DB::table('sms_logs')->updateOrInsert(...)` as a deliberate backstop against `TwilioService`'s silent-catch logging failure. Comments in the code explicitly acknowledge this failure mode.
- **Loop risk:** Because the hour checks use `>=` rather than an exact match, and the cron runs every minute, an order that fails to get its `sms_logs` row written (the acknowledged silent-catch scenario) will be **retried every single minute** until the write succeeds — a real infinite-retry risk if the `sms_logs` table or DB connection is degraded.
- **Merge fields:** `{{customer_name}}`, `{{store_name}}`, `{{delivery_date}}`, `{{payment_link}}` (no time field).

### 4.6 `SalesFunnelBeforeEventJob` / `SalesFunnelAfterEventJob`
- **Purpose:** Configurable, admin-built multi-step SMS funnels tied to a delivery window (before/after), used for CRM-style nurture/marketing-adjacent sequences on top of rental orders.
- **Entry trigger:** Cron every 15 minutes. **Gated by `config('app.sales_funnel_flag')`, sourced from `env('SALES_FUNNEL_ENABLED', false)`.** Repo default is `false`; the local `.env` (untracked, not authoritative for production) has it set to `true`. **Production/staging value must be verified manually — this determines whether §10's overlap finding is live today.**
- **Entry conditions:** `OrderProduct` matching the funnel's product list, `delivery_status = 'Pending'`, order not soft-deleted, delivery timestamp (`delivery_date` + `delivery_time`) falling inside the step's computed offset window, further filtered by `funnel_order_type` (cod/paid/all).
- **Dedup/loop guard:** `whereDoesntHave('funnelLogs', ...)` scoped to `(sales_funnel_id, sales_funnel_step_id)` — a dedicated `order_product_funnel_logs` table, separate from `sms_logs`. On completion, a log row is written with `status = Sent|Failed` **regardless of outcome**, which is what prevents re-send on the next 15-minute tick.
- **Payment-stop integration:** `StopCodFunnelsOnPaymentListener` (fires on `PaymentInitiateEvent`) calls `FunnelLifecycleService::handleCodToPaidConversion()`, which writes "Stopped" rows to `order_product_funnel_logs` when a COD order converts to paid — **this is a working stop mechanism for the Sales Funnel system specifically** (unlike the POD reminder job, which has no equivalent).
- **Cross-system awareness: NONE.** Does not check `sms_logs`, does not know about the 4 rental-reminder jobs, and vice versa. See §10.

### 4.7 `SendSmsBroadcastEventJob` (+ `SmsAudienceResolver`, `SmsBroadcastEvent`)
- **Purpose:** One-off/scheduled CRM marketing-style broadcast to a tag-filtered customer list.
- **Entry trigger:** Admin "send now" (`SmsBroadcastService::sendNow()`) or `ProcessScheduledSmsBroadcastsJob` (every minute, for scheduled broadcasts).
- **Audience resolution (`SmsAudienceResolver`):** `Customer::where('status','Active')` base query, filtered by include/exclude tags (`any`/`all` matching), phone presence/validity, and de-duplicated by normalized phone number. **No order, invoice, pipeline-stage, or delivery/pickup awareness whatsoever** — purely a customer+tag+phone filter, frozen into `sms_broadcast_recipients` at send time so the "preview" and "actual send" can never disagree.
- **Dedup/loop guard:** recipients are a **frozen snapshot** (`sms_broadcast_recipients`), each row's `status` (pending/sent/failed) tracked independently; the audience is never re-resolved mid-send, so a customer completing/cancelling an order mid-broadcast has no effect (this is by design for broadcast consistency, not a bug).
- **Completion:** `finalize()` sets aggregate counts and the parent event's `status` (Sent/PartiallySent/Failed) + `completed_at`. Resume-safe on interruption.

### 4.8 Terms & Conditions flow (`SendDailyPendingTermsReminderJob` → `SendTermsRequestJob` / `TermsService`)
- **Purpose:** Get customer e-signature/acceptance of rental terms before delivery.
- **Entry triggers:** Checkout (`PostController.php`, sends #1 immediate + #2 at +2hr) and a daily 06:45 catch-up job dispatching send #3 for any order still `terms_status = Pending` delivering today.
- **Dedup/loop guard:** per-send-number columns on `Order` (`terms_first_sent_at`/`terms_second_sent_at`/`terms_third_sent_at`) — `TermsService::sendTermsRequest()` bails if the relevant column is already set, and always re-checks `terms_accepted_at`/`terms_status` before sending. **This is the one flow in the codebase with a clean, column-based dedup design** — worth using as the template for fixing the others (see §12).

### 4.9 `SendSmsListener` (checkout COD notification)
- **Purpose:** Immediate SMS confirmation for COD ("Pay on Delivery") orders at checkout.
- **Trigger:** `OrderPlacedEvent`, fired from `Front/Checkout/PostController.php`. Picked up via Laravel's automatic event-listener discovery (type-hinted parameter) — **no explicit registration found in any `EventServiceProvider`.** This is an availability risk: if `event:cache` is ever built in an environment/step that doesn't include this discovery pass, this SMS silently stops firing with no error surfaced anywhere.
- **Entry conditions:** `paymentMethod === COD`, `is_rental && (is_store || is_truck)`, and the relevant settings toggle + non-empty template.
- **Completion:** logs an `OrderHistoryAction::CodSmsNotification` row on success; only an error log on failure (no order-side failure record).
- **Downstream dependency:** `SendPodPaymentReminderJob`'s first sub-sequence requires this listener's resulting `sms_logs` row (`sms_type = COD_ORDER_NOTIFICATION`) to exist before it will send the payment-link follow-up.

### 4.10 Manual admin controllers
- **`ResendPodPaymentLinkController`:** gated only by "order has a pending COD payment." No rate limit — an admin can trigger repeatedly with no cooldown. Uses its own `sms_type` (`POD_PAYMENT_LINK_MANUAL_RESEND`), so it does not interact with the automated job's dedup guard (which keys on `POD_PAYMENT_LINK`) — the two can layer without limit.
- **`SendTermsAndConditionsController`:** gated by `terms_status = 'Pending'`. Sets `last_terms_sms_sent_at` on success, but **this field is never read anywhere as a guard** — dead write, no actual cooldown effect.

---

## 5. Trigger Analysis (What Starts / What Stops)

| Automation | Starts | Should stop on | Does that stop mechanism exist? | Is it monitored? | Is customer actually removed/excluded? |
|---|---|---|---|---|---|
| Delivery day-before | Cron 15:00 | Delivery marked complete/rescheduled, or order deleted | `delivery_status` leaving `'Pending'`; soft-delete scope | No alerting | **Yes** (has sms_logs guard too) |
| Delivery same-day | Cron 07:00 | Same as above | Only status-based, no sms_logs guard | No | Yes, but resettable (§8) — no idempotency floor |
| Return day-before | Cron 15:00 | Pickup complete/rescheduled | Status-based only | No | Yes, but resettable, no dedup guard |
| Return same-day | Cron 07:00 | Same | Status-based only | No | Yes, but resettable, no dedup guard |
| POD payment link/reminders (4x) | Cron every minute | **Invoice/payment paid** | Query checks `payment_method=COD AND status=Pending` only — **never clears on cross-method payment** (§7) | No | **No — confirmed root cause of reported bug** |
| Sales Funnel before/after | Cron every 15 min | COD→Paid conversion (via listener), delivery complete | `StopCodFunnelsOnPaymentListener` + `funnelLogs` dedup — **works**, but only for the payment-conversion case; no check against the 4 rental-reminder jobs | No | Yes for payment-conversion; no for cross-system overlap (§10) |
| Terms request sequence | Checkout event + daily 06:45 | Terms accepted/declined/exempt | `terms_accepted_at`/`terms_status` check + per-send-number columns | No | Yes — cleanest flow in the codebase |
| Checkout COD notice | `OrderPlacedEvent` | One-time, no repeat expected | N/A (single fire) | No explicit listener registration to monitor | N/A |
| CRM broadcast | Admin action / scheduled | Broadcast completes | Frozen recipient snapshot + per-recipient status | No | Yes (by design, no order awareness needed) |
| Manual resend controllers | Admin click | N/A | No cooldown/rate limit on either | No | N/A — human-triggered, not a stop-condition problem |

---

## 6. Completion Logic Audit (Highest Priority)

Per the audit brief's priority ordering, here is the verdict for each required "stop point":

| Event | Expected stop trigger | Actual stop trigger | Verdict |
|---|---|---|---|
| Payment completed (COD → paid via another method) | COD `order_payments` row transitions out of `Pending` | **Never happens** — fix exists in source, commented out (`ReceivePaymentController.php` ~lines 284-290) | ❌ **Missing — confirmed bug, root cause** |
| Payment completed (COD confirmed as paid directly) | `ConfirmPaymentController` flips the COD row itself | Works correctly for this one specific path | ✅ Works (narrow case only) |
| Invoice paid | No single `paid_at` column exists on `Order`; "paid" is derived per-row from `order_payments` | Correct for the direct-COD-confirm path; broken for cross-method settlement (same root cause as above) | ❌ Partially broken |
| Order/delivery completed | `delivery_status` moves off `'Pending'` (admin/dispatch action) | Works when status isn't reset afterward | ⚠️ Works, but fragile (see §8 reset paths) |
| Pickup completed | `pickup_status` moves off `'Pending'` | Same as above | ⚠️ Works, but fragile |
| Delivery completed (order-level cancel) | An order-level `Cancelled` status | **No order-level cancel feature exists at all** — `orders.status` column exists in the migration but is not `$fillable` anywhere and no controller ever sets it; a related `FunnelLifecycleService::REASON_ORDER_CANCELLED` constant is defined but referenced nowhere (dead code for a never-wired feature) | ❌ Feature does not exist — see §14 |
| Subscription activated | N/A — no subscription concept found in this codebase (not a rental-subscription business model) | N/A | Not applicable to this system |
| Customer reaches intended outcome (generic) | Depends on automation; see rows above | Mixed — some clean (terms flow, Sales Funnel payment-stop), some broken (POD), some fragile (rental reminders) | Mixed |

---

## 7. Payment Workflow Findings (Root Cause — Highest Priority)

**Trace: Payment Event → Invoice Status → Automation Trigger → Exit Logic**

1. Customer receives a COD ("Pay on Delivery") order at checkout → `order_payments` row created with `payment_method = COD`, `status = Pending`.
2. Customer later pays by a *different* method (cash at delivery, card over the phone, admin-entered card charge, etc.) via `ReceivePaymentController` (admin "Receive Payment" modal) or `Api/V1/Orders/PaymentController` (mobile/customer app). Both use `AuthorizeNetService` synchronously inside a `DB::beginTransaction()`, create a *new* `order_payments` row with `status = Paid`/`PartialPayment`, and fire `PaymentInitiateEvent`.
3. `StopCodFunnelsOnPaymentListener` handles that event and calls `FunnelLifecycleService::handleCodToPaidConversion()` — **but this method only writes "Stopped" rows to the Sales Funnel's own `order_product_funnel_logs` table.** It never touches `order_payments.status`.
4. **The original COD row's `status` remains `'Pending'` forever.** The intended fix is present in source as a commented-out block in `ReceivePaymentController.php` (~lines 284-290):
   ```php
   // When full payment is recorded via any method other than COD itself,
   // the original COD Pending row must also be closed — otherwise the
   // POD SMS reminder job still sees a COD+Pending row and keeps firing.
   // if ($targetStatus === OrderPaymentStatus::Paid) {
   //     $order->payments()
   //         ->where('payment_method', OrderPaymentMethod::COD->value)
   //         ->where('status', OrderPaymentStatus::Pending->value)
   //         ->update(['status' => OrderPaymentStatus::Paid->value]);
   // }
   ```
   The comment shows a developer **already diagnosed this exact bug** and wrote the fix, then did not enable it.
5. `SendPodPaymentReminderJob`'s all 4 sub-sequences gate on `whereHas('payments', fn($q) => $q->where('payment_method','COD')->where('status','Pending'))`. This condition is now permanently true for the converted order, so **the job keeps sending `POD_PAYMENT_LINK`, `POD_DAY_BEFORE`, `POD_FINAL_REMINDER`, and `POD_LAST_DITCH` SMS — including live payment links — after the customer has already paid.** This exactly matches the reported symptom ("customer pays, automation continues sending payment reminders/links").
6. **Secondary, opposite-direction bug from the same root cause:** `SendDeliveryDayBeforeRentalReminderJob` excludes orders via `whereDoesntHave('order.payments', COD+Pending)`. For a converted order, this exclusion is now *permanent* — the day-before delivery reminder (a legitimate, wanted logistics message) will **never** send for that order, even though the delivery is real and scheduled. One root cause, two symptoms (unwanted sends on one job, wrongly-suppressed sends on another).
7. **No queue-level or DB-level locking** (`lockForUpdate()`) exists anywhere in this payment-write / SMS-read interaction. The practical race-condition risk from concurrent read/write is narrow (standard READ COMMITTED visibility lag, not corruption), but was not designed for and is unverified either way — flagged as a secondary, lower-confidence concern, not a primary finding.
8. **Webhook architecture note:** there is no Stripe/Authorize.net inbound webhook controller in this codebase at all. All payment capture is synchronous, initiated from an admin or customer-app request — this simplifies the audit (no async webhook race to trace) but also means there's no independent "payment settled" event source outside these two controllers; both must be fixed identically.

**Root cause classification:** Logic error / incomplete implementation (fix written, never enabled). Not a webhook failure, not a tag failure, not a pipeline failure (no pipeline exists) — a straightforward missed status-cascade update.

---

## 8. Order/Delivery/Pickup Workflow Findings

### 8.1 Status model
`delivery_status` and `pickup_status` on `OrderProduct` are plain enum-backed string columns (`['Pending', 'Completed', 'Reschedule', 'Close as Completed']`, migration `2026_02_02_193704_change_enum_to_order_products_table.php`) — **not** backed by a PHP enum class (unlike `OrderPaymentStatus`). All writes are manual, admin/dispatch-staff-initiated:

| Writer | Values set | Path |
|---|---|---|
| `AssignEquipmentController` | `Completed` | Admin delivery-checklist completion |
| `RemoveEquipmentController` | **`Pending`, unconditionally** | Admin "remove equipment" (undo) — no prior-state check |
| `UpdateProductScheduleController` | Any of the 4 values, admin-selected | Admin schedule-edit form; also auto-clears `Reschedule → Pending` when a new date is submitted |
| `Api/V1/Dispatch/UpdateStatusController` | `Pending`, `Completed` | Dispatch mobile app |
| `Api/V1/Orders/Schedules/UpdateController` | `Pending`, `Completed` | Admin-app schedule update |

### 8.2 Order-level cancellation does not exist
`orders.status` (`Pending/In Progress/Completed/Cancelled`) is defined in the migration but is **not** `$fillable` on the `Order` model, and no controller anywhere sets it. `FunnelLifecycleService::REASON_ORDER_CANCELLED` is defined but referenced nowhere else — dead code for a cancellation flow that was apparently planned but never wired up. **All effective "cancel"/"reschedule" behavior today happens at the `OrderProduct` level** via `delivery_status`/`pickup_status = 'Reschedule'` or `'Close as Completed'`, both of which correctly move the row off `'Pending'` and correctly stop all 4 reminder jobs (verified: reschedule cascades `pickup_status = 'Reschedule'` too, with an explicit code comment explaining the cascade logic; "Close as Completed" is a documented admin-only bulk-closure path per `docs/checklist-system-audit/CHECKLIST_EXEMPT_ADMIN_CLOSURE.md`).

Order **soft-delete** (`BulkDeleteController` → `Order::deleting` cascades to soft-delete every `OrderProduct`) is correctly excluded by Eloquent's default soft-delete global scope in all 4 reminder jobs (none call `withTrashed()`).

**Verdict:** the "SMS continues after order cancelled/rescheduled" failure mode, as literally described in the brief, **does not reproduce** for the reschedule/close/delete paths — those are handled correctly. The real duplicate-send risk here is different: **status resets**, below.

### 8.3 Confirmed duplicate-send mechanism: status reset back to `'Pending'`
Two admin-facing code paths reset `delivery_status`/`pickup_status` back to `'Pending'` even from `'Completed'`:

1. **`RemoveEquipmentController::__invoke()`** — unconditionally sets both `delivery_status` and `pickup_status` to `'Pending'` whenever an admin removes assigned equipment, with no check of the prior value. If `delivery_date`/`pickup_date` still falls within a reminder job's date window, this re-arms `SendDeliverySameDayRentalReminderJob`, `SendReturnDayBeforeRentalReminderJob`, and `SendReturnSameDayRentalReminderJob` — **none of which have an `sms_logs` dedup guard** — causing a genuine duplicate customer SMS. (`SendDeliveryDayBeforeRentalReminderJob` would be blocked by its guard if it already fired once for that order.)
2. **`UpdateProductScheduleController::__invoke()`** — the schedule-edit endpoint validates `delivery_status` as `in:Pending,Completed,Reschedule,Close as Completed`, meaning an admin can explicitly move status backward from `Completed` to `Pending` through the normal edit form, with a code branch (`else if ($orderProduct->delivery_status === 'Pending')`) that also force-resets `pickup_status = 'Pending'` alongside it.

**Root cause classification:** Missing idempotency guard (3 of 4 jobs), combined with legitimate admin workflows (undo an equipment assignment, correct a schedule) that have a side effect of re-arming those same 3 jobs.

---

## 9. Pickup Time Merge Code Findings

**Confirmed root cause, two independent locations:**

1. **`app/Helpers/CartHelper.php` (~lines 224-248)** — the rental-variant switch statement:
   ```php
   switch ($variant) {
       case 'weekend':
           $addDays = 3;
           $deliveryTime = '14:00:00';
           $pickupTime = '09:00:00';   // <-- bug: not differentiated for Weekend Special
           break;
       case 'weekly':
           ...
           $deliveryTime = '09:00:00';
           $pickupTime = '09:00:00';
           break;
       case 'monthly':
           ... same pattern ...
       default:
           ... same pattern ...
   }
   ```
   Delivery time is correctly special-cased for Weekend Special (`14:00:00` vs. `09:00:00` elsewhere). **Pickup time is not** — every variant, including `weekend`, gets `09:00:00`. This is the exact source of "Weekend Special orders cannot be picked up at the standard pickup time" — it's a one-branch omission, not a design-level gap.

2. **`database/seeders/Configurations/SettingSeeder.php`** — SMS template *prose* compounds the problem:
   - The two delivery-side templates (`rental_delivery_day_before_store_message`, `rental_delivery_same_day_store_message`) do mention "Weekend Specials start at 2:00 PM" — but as **literal hardcoded text**, not a merge field, so it can't reflect a computed value even once one exists.
   - The four return/pickup-side templates (day-before/same-day × truck/store) hardcode "by 9:00 AM" with **no Weekend Special exception mentioned at all** — consistent with the code-level bug above.

3. **No time-of-day merge field exists anywhere.** All 5 SMS jobs that reference a date (`{{delivery_date}}`/`{{return_date}}`) format it `Carbon::parse(...)->format('M d, Y')` — date only. `OrderProduct.delivery_time`/`pickup_time` columns exist and are populated (used only for the Sales Funnel jobs' window computation `TIMESTAMP(delivery_date, delivery_time)`), but **none of the 5 messaging jobs read them.**

4. **No rental-type / store-hours model exists to build a correct fix on top of.** There is no `rental_type` column or enum on `Order`/`OrderProduct` — "Weekend Special" is purely an ephemeral cart-time `$variant` string, not persisted as its own column (it's implicit in the delivery/pickup date spacing and pricing). A `business_hours`/`BusinessHours` service exists (`config/waitlist.php`, `app/Services/WaitList/BusinessHours.php`) but is scoped exclusively to the Waitlist push-notification feature and is never consulted by delivery/pickup time computation — so nothing today checks whether a computed pickup time (e.g., a Weekend Special Sunday pickup) actually falls within store operating hours.

### Recommended merge-field strategy

- Persist the rental variant as a real column (e.g., `OrderProduct.rental_type` enum: `daily|weekly|monthly|weekend`) at order-creation time instead of only deriving it implicitly, so downstream jobs/templates can branch on it without re-deriving it from date math.
- Add `{{pickup_time}}` and `{{delivery_time}}` merge fields to all relevant templates, sourced from `OrderProduct.pickup_time`/`delivery_time` (already-populated columns), formatted consistently (e.g., `g:i A`).
- Fix `CartHelper.php`'s `case 'weekend':` branch to set a distinct, correct `$pickupTime` for Weekend Special (business must confirm the correct value — this audit found no documented "correct" Weekend Special pickup time to substitute, only the confirmed absence of one).
- Route the computed `$pickupTime`/`$deliveryTime` through `BusinessHours`-style validation (extend that service beyond Waitlist scope, or build an equivalent) so no rental variant can compute a pickup/delivery time outside actual store operating hours, especially for Sunday (currently "closed" per `config/waitlist.php`) or before the Saturday close window.
- Update the four return/pickup SMS templates in `SettingSeeder.php` (and the live settings values, not just seeder defaults) to reference `{{pickup_time}}` instead of hardcoded "9:00 AM" prose.

---

## 10. Duplicate Automation Analysis

### 10.1 Rental reminders vs. Sales Funnel — confirmed structural overlap
`SendDeliverySameDayRentalReminderJob` and `SalesFunnelBeforeEventJob`/`AfterEventJob` both filter on the same column (`delivery_status = 'Pending'`) and effectively the same date (`delivery_date`, with the funnel jobs additionally windowing by `delivery_time` at 15-minute granularity). Nothing prevents a product from being both `is_default_funnel = true` (the rental-reminder trigger) and attached to an active custom Sales Funnel (`sales_funnel_products` pivot) targeting the same delivery window. The two systems write to **different** log tables (`sms_logs` vs. `order_product_funnel_logs`) and neither reads the other's. **If a product is in both, the customer receives two independently-timed "your delivery is today"-style SMS from two unrelated systems.**

- This overlap is only live if `SALES_FUNNEL_ENABLED` is `true` at runtime. Repo default (`config/app.php`) is `false`; the local dev `.env` (untracked, not authoritative) has it `true`. **The production/staging value must be checked directly against the deployed environment** — this audit could not verify it from the repository alone.

### 10.2 Checkout COD notice vs. POD payment-link reminder — intentional, not a bug
`SendPodPaymentReminderJob`'s first sub-sequence deliberately depends on the checkout listener's `COD_ORDER_NOTIFICATION` `sms_logs` row (required to exist, ≥1 minute old) before sending the follow-up `POD_PAYMENT_LINK` message. This is a designed two-step sequence, not an accidental duplicate. The only real risk here is the acknowledged silent-catch logging failure (see §4.5) causing an unintended re-fire on a later minute-tick.

### 10.3 Manual resend vs. automated POD reminders — no shared guard
`ResendPodPaymentLinkController` (admin-triggered) uses a distinct `sms_type` (`POD_PAYMENT_LINK_MANUAL_RESEND`) from the automated job's `POD_PAYMENT_LINK`, so the automated dedup guard is blind to manual resends and vice versa — an admin can click "resend" any number of times with zero cooldown, independent of what the automated job has already sent.

### 10.4 Same-day COD orders can legitimately receive 3+ SMS in one day
A COD/Pending, same-day-delivery order can receive, all within one calendar day: `DELIVERY_SAME_DAY_COD` (07:00 logistics message, "call us to confirm availability"), `POD_FINAL_REMINDER` (≥09:00, "pay now: {{payment_link}}"), and `POD_LAST_DITCH` (≥16:00, "pay here: {{payment_link}}") — on top of the prior day's `COD_ORDER_NOTIFICATION` and `POD_PAYMENT_LINK`. Each message is individually purpose-distinct (logistics vs. payment collection) and not literally duplicate wording, but there is **no cross-system daily send-count throttle**, so this reads as uncoordinated/spammy from the customer's perspective even when every individual job is behaving "correctly" per its own logic.

### 10.5 No CRM-style enrollment/re-enrollment bug exists
Because this system has no pipeline/opportunity/tag-workflow-enrollment table, "duplicate enrollment" in the GHL sense doesn't apply. The equivalent failure mode here — confirmed — is the **status-reset mechanism** in §8.3, which is the correct framing for remediation purposes.

### 10.6 Observers — not a contributing factor
Only one model observer exists that touches Order/OrderProduct/Payment at all: `OrderProductObserver`, which auto-assigns equipment and explicitly skips when status is `'Reschedule'`. It does not send SMS and does not itself reset or interact with the reminder jobs. No `Order` observer or `Payment` observer exists. This rules out observers as a hidden contributor to any of the findings above.

---

## 11. Root Cause Analysis (Consolidated)

| ID | Root cause | Automations affected | Category |
|---|---|---|---|
| RC-1 | Commented-out COD-row status-cascade fix in `ReceivePaymentController.php` | `SendPodPaymentReminderJob` (all 4 sub-sequences); `SendDeliveryDayBeforeRentalReminderJob` (opposite-direction suppression) | Incomplete implementation |
| RC-2 | Missing `sms_logs` dedup guard on 3 of 4 rental-reminder jobs | `SendDeliverySameDayRentalReminderJob`, `SendReturnDayBeforeRentalReminderJob`, `SendReturnSameDayRentalReminderJob` | Missing idempotency guard |
| RC-3 | `RemoveEquipmentController` unconditional status reset to `Pending` | Same 3 jobs above (re-arming trigger) | Missing precondition check |
| RC-4 | `UpdateProductScheduleController` allows admin-driven backward status transition (`Completed → Pending`) | Same 3 jobs above | Missing validation / workflow guard |
| RC-5 | `CartHelper.php` `case 'weekend':` pickup-time branch omission | Weekend Special pickup SMS content (all templates referencing return/pickup time) | Copy/paste logic error |
| RC-6 | No time-of-day merge field in any SMS job | All 5 date-referencing jobs | Missing feature |
| RC-7 | No cross-awareness between Sales Funnel engine and rental-reminder jobs | `SalesFunnelBeforeEventJob`/`AfterEventJob` vs. rental reminders | Architectural gap — two independently-built systems |
| RC-8 | No cross-system daily-send throttle | POD reminders + delivery-same-day COD message | Architectural gap |
| RC-9 | `TwilioService::sendSms()`'s `sms_logs` write is silently swallowed on failure | `SendPodPaymentReminderJob` (mitigated by its own backstop write), all other jobs (unmitigated) | Error handling gap |
| RC-10 | No explicit `EventServiceProvider` registration for `SendSmsListener` (relies on auto-discovery) | Checkout COD notice | Operational/availability risk, not currently manifesting as a bug |

---

## 12. Risk Assessment / Failure Analysis

| Issue ID | Severity | Description | Root Cause | Business Impact | Likelihood | Customer Impact | Technical Risk | Recommended Fix | Complexity |
|---|---|---|---|---|---|---|---|---|---|
| F-1 | **Critical** | Payment reminders/links continue after COD order paid via another method | RC-1 | Customer confusion, brand trust damage, possible duplicate payment attempts | High (every cross-method COD settlement) | High | Low (fix already written) | Uncomment/enable the `ReceivePaymentController.php` status-cascade block; add equivalent to `Api/V1/Orders/PaymentController.php` | Low |
| F-2 | High | Delivery/return same-day and day-before reminders can duplicate-send after admin resets status | RC-2, RC-3, RC-4 | Customer annoyance, appears unprofessional | Medium (depends on admin action frequency) | Medium | Low-Medium | Add `sms_logs`-based dedup guard (mirror the day-before delivery job's existing pattern) to the other 3 jobs; add precondition checks to the two reset paths | Low-Medium |
| F-3 | High | Weekend Special pickup time wrong (hardcoded 9:00 AM) | RC-5 | Customers show up at wrong time / miss pickup window; operational disruption at stores | Confirmed, occurring on every Weekend Special order | High | Low (once correct value determined) | Fix `CartHelper.php` branch; add `{{pickup_time}}` merge field | Low-Medium |
| F-4 | Medium | Sales Funnel and rental reminders can double-message the same delivery | RC-7 | Perceived spam, brand trust | Depends on `SALES_FUNNEL_ENABLED` runtime value — **must verify production** | Medium if flag is on | Medium (architectural) | Add mutual exclusion (shared dedup table or a shared "daily order communication" ledger) | Medium-High |
| F-5 | Medium | No cross-system daily SMS throttle for COD same-day orders | RC-8 | Perceived spam | Confirmed, occurring for every same-day COD delivery | Low-Medium | Medium (architectural) | Add a lightweight per-order-per-day SMS cap/coordinator | Medium |
| F-6 | Low-Medium | Silent `sms_logs` write failures can cause per-minute retry storms | RC-9 | Could mask real delivery failures, or cause the retry-storm duplicate-send risk described in §4.5 | Low (requires DB degradation) | Low normally, high impact if it occurs | Medium | Surface write failures (log/alert) instead of silent catch; keep the POD job's backstop pattern but apply consistently | Low |
| F-7 | Low | `SendSmsListener` relies on auto-discovery, no explicit registration | RC-10 | Silent total loss of checkout COD notice if discovery ever fails | Low | High if it ever occurs (total silent failure) | Low | Add explicit registration in an `EventServiceProvider` or `AppServiceProvider::boot()` for defense-in-depth | Low |
| F-8 | Low | Manual resend controllers have no cooldown/rate limit | RC-3/RC-4 pattern (admin-driven, not automation-driven) | Admin could inadvertently spam a customer | Low | Low-Medium | Low | Add a simple cooldown (e.g., 5-10 min) on both manual resend controllers | Low |
| F-9 | Informational | No order-level Cancelled status/feature exists despite migration column and dead-code constant | RC — feature gap, not a bug per se | None today (nothing relies on it) but blocks building a clean order-level "stop all automations" switch | N/A | N/A | Low | Decide whether to build this properly (recommended — see Roadmap) or remove the dead column/constant | Medium |

---

## 13. Recommendations

### Critical
- **F-1**: Enable the commented-out COD-status-cascade fix in `ReceivePaymentController.php`, and add the equivalent logic to `Api/V1/Orders/PaymentController.php` (the other order-payment write path) so both are consistent. This is the single highest-value fix in this audit.

### High
- **F-2**: Add `sms_logs`-keyed dedup guards to `SendDeliverySameDayRentalReminderJob`, `SendReturnDayBeforeRentalReminderJob`, `SendReturnSameDayRentalReminderJob`, matching the pattern already used successfully in `SendDeliveryDayBeforeRentalReminderJob`.
- **F-2 (workflow-side)**: Add a check in `RemoveEquipmentController` and `UpdateProductScheduleController` to prevent (or explicitly confirm/log) resetting `delivery_status`/`pickup_status` back to `Pending` when a reminder for that date has already been sent (query `sms_logs` before resetting, or surface a warning to the admin).
- **F-3**: Fix the `CartHelper.php` Weekend Special pickup-time branch. Requires a business decision on the correct pickup time before implementation (this audit found no documented correct value — only the confirmed absence of differentiation).

### Medium
- **F-4**: Add a shared exclusion mechanism between the Sales Funnel engine and the rental-reminder jobs (e.g., a shared "communications sent today for this order" table both systems check) — or, at minimum, exclude `is_default_funnel` products from also being eligible for custom Sales Funnels covering the same event type, if that overlap isn't intentional (confirm with the business).
- **F-5**: Introduce a per-order-per-day SMS cap or a coordination layer so POD payment-chase messages and delivery-logistics messages don't compound into 3+ texts in one day.
- **F-9**: Decide whether to properly implement order-level Cancelled status (recommended, as a durable "stop everything for this order" switch that all SMS jobs can check with one clause) or formally remove the dead `orders.status`/`REASON_ORDER_CANCELLED` scaffolding to avoid future confusion.

### Low / Quick Wins
- **F-6**: Replace `TwilioService`'s silent try/catch around the `sms_logs` write with logged/alerted failures.
- **F-7**: Explicitly register `SendSmsListener` rather than relying solely on auto-discovery.
- **F-8**: Add a short cooldown to both manual-resend admin controllers.
- Remove (or time-box with a clear removal date) the `SendPodPaymentReminderJob` hardcoded `created_at < '2026-06-21'` cutover guard once no pre-cutover orders remain active.

### Long-Term / Architecture
- Persist `rental_type` as a real column on `OrderProduct` instead of an ephemeral cart-time variant string, and extend the `BusinessHours` service (currently Waitlist-only) to validate all computed delivery/pickup times against actual store hours.
- Add `{{pickup_time}}`/`{{delivery_time}}` merge fields across all relevant templates once the underlying time values are correct.
- Consider a single shared "SMS eligibility" service/table that every customer-facing SMS job consults, so future automations don't each reinvent (or omit) dedup logic independently — this is the architectural fix underlying most of F-2, F-4, and F-5.

---

## 14. Priority Matrix

| Priority | Issues |
|---|---|
| P0 (immediate) | F-1 |
| P1 (this sprint) | F-2, F-3 |
| P2 (next sprint) | F-4, F-5, F-9 |
| P3 (backlog / hardening) | F-6, F-7, F-8, cutover-guard cleanup |

---

## 15. Implementation Roadmap (Suggested — engineering to confirm effort)

1. **Week 1:** F-1 (payment-status cascade fix) — highest impact, lowest complexity, code already exists. Deploy behind a quick regression test (see §16) given it touches live payment-completion logic.
2. **Week 1-2:** F-2 dedup guards on the 3 unguarded rental-reminder jobs; F-2 workflow-side guard on the two reset controllers.
3. **Week 2-3:** F-3 pickup-time fix, pending business confirmation of correct Weekend Special pickup time; add `{{pickup_time}}` merge field.
4. **Week 3-4:** F-4/F-5 cross-system throttling — requires the most design work since it spans two independently-built systems (Sales Funnel + rental reminders + POD reminders).
5. **Ongoing/backlog:** F-6 through F-9 and the architectural recommendations in §13.

---

## 16. Testing Strategy

- **F-1:** Unit/feature test: create a COD order, settle it via a non-COD payment method through both `ReceivePaymentController` and `Api/V1/Orders/PaymentController`, assert the original COD `order_payments` row's status flips to `Paid`, and assert `SendPodPaymentReminderJob` no longer selects that order in any of its 4 sub-sequences.
- **F-2:** Feature test per job: seed an order already matching the job's date/status filter with an existing `sms_logs` row of the matching `sms_type`, assert the job does not re-send. Separately, test that `RemoveEquipmentController`/`UpdateProductScheduleController` resets are either blocked or produce a second guarded (not duplicate) run.
- **F-3:** Once the correct Weekend Special pickup time is defined, unit-test `CartHelper.php`'s switch statement for all 4 variants asserting both `delivery_time` and `pickup_time`.
- **F-4/F-5:** Integration test seeding a product that is both `is_default_funnel` and Sales-Funnel-attached, with `SALES_FUNNEL_ENABLED=true`, asserting only one SMS is sent for the shared event (post-fix).
- Regression run of the full existing `tests/Feature` suite (contains prior fix coverage — see `docs/checklist-system-audit/P3_8_BASELINE_CHARACTERIZATION_TESTS.md` and `P3_TD16_TRANSACTION_SAFE_FAILURE_TESTS.md` for the pattern this codebase already uses for payment/status-transition regression tests).

## 17. Regression Checklist

- [ ] COD order settled via card/cash → no further POD reminder SMS sent; day-before delivery reminder still sends correctly (opposite-direction regression from F-1).
- [ ] COD order confirmed paid directly (existing `ConfirmPaymentController` path) → unaffected by the new cascade logic (no double-update).
- [ ] Equipment removed from a completed delivery → reminder does not silently re-fire without an explicit admin acknowledgment/log.
- [ ] Admin edits schedule and moves status backward → same guard applies.
- [ ] Weekend Special order → pickup time in SMS matches the newly-corrected value; Daily/Weekly/Monthly unaffected.
- [ ] Sales Funnel flag toggled on in a staging environment → no duplicate "delivery today" message for a product in both systems.
- [ ] Manual POD resend → still works for legitimate support use, now rate-limited.
- [ ] Terms flow (unchanged by this audit's fixes) → confirm no regression, since it's the reference-quality flow other fixes are modeled on.

## 18. Monitoring & Alerting Recommendations

- Alert on `sms_logs` insert failures (currently silent) — this closes F-6 and gives visibility into the exact failure mode `SendPodPaymentReminderJob`'s comments already describe as a known risk.
- Add a daily report of "orders with a COD-Pending payment row older than N days despite having a later Paid/PartialPayment row" — this would have caught F-1 directly and should remain as an ongoing safety net even after the fix ships, since it validates the cascade fix keeps working over time.
- Add a metric/alert for per-customer SMS volume per day exceeding a threshold (e.g., >3 in 24 hours) as a cheap, generic backstop against future instances of F-4/F-5-style overlap, independent of which system caused it.
- Track `SALES_FUNNEL_ENABLED`'s actual value per environment in a visible ops dashboard/config-audit — this flag silently determines whether F-4 is live, and this audit could not verify production's value from the repository alone.

---

## 19. Final Conclusions

The reported symptom — "automation continues sending payment reminders/links after the customer has paid" — has a single, precisely located, already-diagnosed-but-unshipped root cause (§7, F-1). This is not a systemic architecture failure; it is one commented-out block. Fixing it is low-complexity and should be the first change made.

Beyond that headline finding, this audit surfaced a second, independent class of problems: three of the four rental delivery/pickup reminder jobs have no re-send protection at all, and two ordinary admin actions can reset the exact status field those jobs depend on — meaning duplicate sends are possible today even in scenarios unrelated to payment. The fix pattern already exists in the codebase (the fourth reminder job's `sms_logs` guard, and the Terms flow's column-based guard) and should simply be extended to the other three.

The Weekend Special pickup-time complaint traced to a single missed branch in `CartHelper.php`, compounded by hardcoded (non-merge-field) template prose — a contained, well-understood fix pending one business decision (the correct pickup time to use).

The remaining findings (Sales Funnel/rental-reminder overlap, lack of cross-system daily throttling) are architectural gaps between two systems that were built independently and never designed to be aware of each other. These are real but lower-urgency than F-1–F-3, and their live impact depends on a runtime flag (`SALES_FUNNEL_ENABLED`) whose production value this audit could not verify from the repository and must be checked manually before prioritizing F-4/F-5 work.

Finally, this codebase does not implement GoHighLevel-style pipelines, opportunities, or tag-based workflow enrollment — any remediation plan should be scoped around this app's actual architecture (Eloquent status columns, a dedicated `sms_logs` audit table, a bespoke `SalesFunnel` step engine, and a separate tag-based broadcast system), not GHL concepts that don't exist here.

### What could not be independently verified (requires follow-up before acting)
- The actual `SALES_FUNNEL_ENABLED` value in staging/production (repo/local `.env` is not authoritative).
- Real-world frequency/incident history of the `lockForUpdate`-absent race condition noted in §7.
- Contents of `docs/checklist-system-audit/CHECKLIST_EXEMPT_ADMIN_CLOSURE.md` and `P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md` (referenced by code comments but not opened during this audit — may contain additional relevant context on the "Close as Completed" path).
- The business-correct pickup time value for Weekend Special orders (this audit confirms only that no differentiation exists today, not what the target value should be).
- Front-end/admin UI button labels for delivery/pickup completion actions (not present in this backend repository; may live in a separate frontend codebase).
