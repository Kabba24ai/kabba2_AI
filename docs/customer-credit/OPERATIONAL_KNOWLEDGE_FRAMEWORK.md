# Operational Knowledge Framework

Phase 3.4 — Customer Resolution Center
Date: 2026-07-04
Branch: `raj_development`

---

## Purpose

Phase 3.3 built one working scenario (Cancellation / Refund) directly — its decision logic, questions, and audit fields were all specific to that one workflow. This phase generalizes that into a reusable framework so that **every future Resolution Center scenario can be added by implementing one interface and registering one class**, without touching `ResolutionCenterService`, the routes, the migration, or any existing scenario's behavior.

This is a **framework for encoding approved company policy into guided questions and recommendations** — not AI, not automation. Every scenario built on this framework must keep the same core principle Phase 3.3 established: *the system guides, the employee decides, nothing happens automatically.*

## The Nine Elements

Every scenario is a class implementing `App\Services\ResolutionCenter\ResolutionScenario`, which declares exactly the nine elements this phase's mission specifies:

| Element | Interface method | Reference implementation (Cancellation/Refund) |
|---|---|---|
| **Scenario** | `key()`, `label()` | `cancellation_refund`, "Cancellation / Refund" |
| **Trigger** | `trigger()` | "Customer requests cancellation of a rental order." |
| **Required Questions** | `questions()` | Can reschedule? → Would credit satisfy? → Payment method? |
| **Business Rules** | `businessRules()` | Reschedule-first, Credit-second, Refund-last; no reschedule/refund fee exists in this system |
| **Recommendation Logic** | `recommend(array $answers): ?ResolutionRecommendation` | Delegates to the untouched `ResolutionDecisionEngine` |
| **Available Actions** | `availableActions()` | Free Reschedule (manual), Issue Store Credit (service), Standard Refund (manual), Waive Refund Fee (manual) |
| **Permission Requirements** | `permissionRequirements()` | `resolution_center.view`/`.use`/`.override`/`.view_audit_history`, `customer_credit.grant` |
| **Audit Requirements** | `auditRequirements()` | user, date, customer, order, recommendations shown, decision selected, manager override, notes, outcome |
| **Completion Criteria** | `completionCriteria()` | pending, completed, cancelled |

## Architecture

```
ResolutionScenario (interface)          — the 9-element contract every scenario implements
├── ResolutionQuestion (value object)   — one question in a scenario's guided flow
├── ScenarioAction (value object)       — one action a recommendation can lead to; 'service' or 'manual'
├── ResolutionRecommendation (value object, unchanged from Phase 3.3)
└── CancellationRefundScenario          — the reference implementation, wrapping the untouched
                                           Phase 3.3 ResolutionDecisionEngine

ResolutionScenarioRegistry              — key => class map; the one place a new scenario is added
ResolutionCenterService                 — generic orchestration; resolves a case's scenario from
                                           the registry instead of hardcoding one engine
ResolutionCase (model)                  — gained `scenario_key`; a `scenario()` accessor resolves
                                           the governing ResolutionScenario for introspection
```

### Why `CancellationRefundScenario` wraps `ResolutionDecisionEngine` instead of replacing it

`ResolutionDecisionEngine` (Phase 3.3) already has 8 passing, specific unit tests proving its branching logic is correct. Rewriting that logic directly inside a new class would mean re-proving correctness from scratch and would risk introducing a subtle behavioral difference during the rewrite. Instead, `CancellationRefundScenario::recommend()` is a thin adapter: it translates the framework's generic `array $answers` into `ResolutionDecisionEngine`'s concrete parameters, calls the untouched engine, and returns its result unchanged. This makes the refactor's correctness trivially verifiable — `git diff` shows zero changes to `ResolutionDecisionEngine.php`, `ResolutionPolicy.php`, or `ResolutionRecommendation.php` — and a new test (`CancellationRefundScenarioTest`) directly asserts the scenario's output is identical to the engine's output for every terminal branch.

### Why only one schema change (`scenario_key`)

Generalizing storage further — e.g., a generic JSON `answers` column replacing the scenario-specific `can_reschedule`/`credit_would_satisfy` columns — was considered and deliberately deferred. With only one real scenario in existence, designing a generic answer-storage shape now would be guessing at a shape no second scenario has yet validated. `scenario_key` is the one addition genuinely needed today: it lets `ResolutionCenterService` and any future reporting ask "which scenario governs this case?" generically. Everything else about how a scenario stores or asks its questions remains scenario-specific until a second scenario exists to prove what should generalize.

### Why `ResolutionQuestion.dependsOn` is descriptive, not evaluated

The framework's `questions()` method returns an ordered manifest for introspection (documentation, future dynamic rendering, etc.) — it does not drive the actual guided flow today. The real conditional flow (which question to show next, based on prior answers) remains hand-coded in `ResolutionCenterService::recordAnswers()`/the Blade view, exactly as Phase 3.3 built it. Building a generic conditional-flow evaluator now, with only one scenario's shape to generalize from, would be speculative design — deferred until a second scenario's needs are known.

## How a Future Scenario Would Be Added

Using **Equipment Exchange** as a worked example (not implemented — illustrative only):

```php
class EquipmentExchangeScenario implements ResolutionScenario
{
    public const KEY = 'equipment_exchange';

    public function key(): string { return self::KEY; }
    public function label(): string { return 'Equipment Exchange'; }
    public function trigger(): string { return 'Customer reports equipment is faulty or wrong and requests a swap.'; }

    public function questions(): array {
        return [
            new ResolutionQuestion('same_category_available', 'Is a replacement in the same category available at this store?', ResolutionQuestion::TYPE_BOOLEAN),
            new ResolutionQuestion('customer_at_fault', 'Was the equipment issue caused by customer misuse?', ResolutionQuestion::TYPE_BOOLEAN, dependsOn: 'same_category_available'),
            // ...
        ];
    }

    public function businessRules(): array {
        return ['Approved policy statement 1...', 'Approved policy statement 2...'];
        // Sourced from actual approved policy — never invented by this framework.
    }

    public function recommend(array $answers): ?ResolutionRecommendation { /* this scenario's own logic */ }
    public function availableActions(): array { /* e.g. 'Swap Equipment' (manual), 'Issue Store Credit for Downtime' (service) */ }
    public function permissionRequirements(): array { /* likely a new 'equipment_exchange.*' module, or reuse existing ones */ }
    public function auditRequirements(): array { return ['user', 'date', 'customer', 'order', 'recommendations_shown', 'decision_selected', 'manager_override', 'notes', 'outcome']; }
    public function completionCriteria(): array { return ['pending', 'completed', 'cancelled']; }
}
```

Then, exactly one line added to `ResolutionScenarioRegistry`:

```php
protected static array $scenarios = [
    CancellationRefundScenario::KEY => CancellationRefundScenario::class,
    EquipmentExchangeScenario::KEY => EquipmentExchangeScenario::class, // <- the only registry change
];
```

`ResolutionCenterService`, the routes, the migration, and the Cancellation/Refund scenario's behavior all remain untouched.

**The other seven example scenarios named in this phase's mission** — Damage Settlement, Bad Debt Negotiation, Collections, Payment Plans, Pricing Adjustment, Customer Goodwill, Warranty Adjustment — would each follow the identical pattern: a new class implementing `ResolutionScenario`, its own questions/rules/actions grounded in that scenario's actual approved policy (not invented here), and one line in the registry. None are implemented in this phase, per its explicit "do not add new customer scenarios yet" rule — this document exists to prove the shape is ready for them, not to build them.

## What Did Not Change

- `ResolutionDecisionEngine`, `ResolutionPolicy`, `ResolutionRecommendation` — byte-for-byte unchanged (confirmed via `git diff --stat`).
- Every recommendation the Cancellation/Refund workflow produces — confirmed identical via `CancellationRefundScenarioTest`, which asserts the scenario's output equals the raw engine's output for every terminal branch, and via re-running Phase 3.3's own end-to-end validation script unmodified (see `PHASE_3_4_COMPLETION_REPORT.md`).
- `CustomerCreditService` and its model — zero changes.
- The Order Edit screen, its Customer Credit panel, and the CRM Customer Credit tab — untouched.
- Every existing route, controller, and Blade view's public behavior for the Cancellation/Refund workflow — the framework refactor lives entirely inside `ResolutionCenterService`'s internals and one new model accessor; no controller, request, or view needed to change.
