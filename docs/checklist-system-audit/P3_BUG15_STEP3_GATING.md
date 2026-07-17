# BUG-15 — Checklist Master edit Step 3 gating consolidation

**Date:** 2026-07-15
**Scope:** BUG-15 only. No other Phase 3 item (TD-16, P3-6, P3-8, etc.) touched.

---

## Root cause

`resources/views/admin/checklist_management/checklist_master/edit.blade.php` had **two separate `DOMContentLoaded` script blocks** that both touched `continueStep3Btn.disabled`:

1. An earlier block (restoring the previously-selected Rental Ready and Customer Admin template radios/summary text on page load) unconditionally set:
   ```js
   document.getElementById('continueStep3Btn').disabled = true;
   ```
   whenever a customer admin template was already assigned and restored — the **opposite** of the correct behavior, and the opposite of the analogous Step 2 line directly above it (`continueStep2Btn.disabled = false` in the same situation), strongly suggesting a copy-paste boolean-flip bug.

2. A later block (added by CLEAN-9, P3-4) correctly computed the button's state from `hiddenCustomerTemplateId.value` — enabled when a template is assigned, disabled otherwise — and set the DOM property accordingly.

Because both blocks register via `document.addEventListener('DOMContentLoaded', ...)`, and browsers fire same-event listeners in registration order, block 2 always ran after block 1 and silently overrode its incorrect disable. **The correct final state existed only as a side effect of script ordering, not by design** — a future edit reordering these two `<script>` tags, or simplifying either one without noticing the interaction, could have silently reintroduced a genuinely disabled Continue button for a record with a perfectly valid, already-assigned template.

## Fix

Consolidated all Step 3 button state management into a single named function, `setStep3ContinueButtonState(hasCustomerTemplateSelected)`, defined once inside the block that already owns `continue3Btn`/`hiddenCustomerTemplateId`:

```js
function setStep3ContinueButtonState(hasCustomerTemplateSelected) {
    continue3Btn.disabled = !hasCustomerTemplateSelected;
    if (hasCustomerTemplateSelected) {
        continue3Btn.classList.remove('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
        continue3Btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
    } else {
        continue3Btn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
        continue3Btn.classList.add('bg-gray-200', 'text-gray-500', 'cursor-not-allowed');
    }
}
```

Called from exactly two places:
- On initial load: `setStep3ContinueButtonState(Boolean(hiddenCustomerTemplateId.value));`
- On template selection (radio `change` handler): `setStep3ContinueButtonState(true);`

The earlier block's contradictory `document.getElementById('continueStep3Btn').disabled = true;` line was **removed entirely** — that block's remaining job is only to restore the selected radio and summary text, nothing about button state. No script now depends on `DOMContentLoaded` listener registration order for this button; `continue3Btn.disabled` is assigned in exactly one place in the entire file.

## Before / after behavior

| Scenario | Before | After |
|---|---|---|
| Edit page, customer admin template already assigned | Enabled — but only because the later script block happened to run after the earlier (contradictory) one | Enabled — via `setStep3ContinueButtonState(true)` at initial load, the only code path that decides this |
| Edit page, no customer admin template assigned (data edge case; 0 of 37 live records today) | Disabled at initial load (same mechanism as above, no ordering risk introduced by this state) | Disabled at initial load — unchanged, now via the same single function |
| User selects a Customer Admin template radio | Enabled via the radio `change` handler's own inline enable logic | Enabled via `setStep3ContinueButtonState(true)` — same outcome, now sharing the one function instead of duplicating the enable logic |
| Step 2 gating, server-side `UpdateRequest` validation, selected-template state persistence (`hiddenCustomerTemplateId` value), step navigation (`goToStep()`), any backend controller/service | Unchanged | Unchanged — none of this was touched |

Net observable behavior for every real record is **identical** to before. What changed is *how* that behavior is produced: deterministically, from one function, instead of accidentally, from listener-registration order.

## Files changed

- `resources/views/admin/checklist_management/checklist_master/edit.blade.php` — removed the earlier block's contradictory line; consolidated the later block's logic into `setStep3ContinueButtonState()`.
- `tests/Feature/ChecklistManagement/ChecklistMasterEditStep3ValidationTest.php` — updated the 2 existing tests' assertions to match the new function-based markup, and added 3 new tests.
- `docs/checklist-system-audit/PHASE3_IMPLEMENTATION_PLAN.md` — BUG-15 marked ✅ RESOLVED.
- `docs/checklist-system-audit/P3_BUG15_STEP3_GATING.md` (this file) — new.

No server-side code, no `UpdateRequest`, no controllers, no other JavaScript in this file was touched.

## Tests

`tests/Feature/ChecklistManagement/ChecklistMasterEditStep3ValidationTest.php` (5 tests):

1. `test_edit_page_marks_continue_enabled_when_customer_template_already_assigned` — updated to assert the new `setStep3ContinueButtonState(Boolean(hiddenCustomerTemplateId.value));` call site is present for an assigned-template record.
2. `test_edit_page_leaves_continue_disabled_when_no_customer_template_assigned` — updated the same way for a record with `customer_admin_template_id = null`.
3. `test_only_one_step3_gating_implementation_remains` *(new)* — asserts `function setStep3ContinueButtonState(` appears exactly once and `continue3Btn.disabled =` appears exactly once in the rendered page, proving there is only one place that ever assigns the button's disabled state.
4. `test_contradictory_assigned_template_disables_button_logic_is_gone` *(new)* — asserts the exact old line, `document.getElementById('continueStep3Btn').disabled = true;`, is absent from the rendered page for an assigned-template record.
5. `test_template_selection_handler_enables_the_button` *(new)* — asserts the radio `change` handler calls `setStep3ContinueButtonState(true);` rather than duplicating enable logic inline.

### Exact commands run

```
php artisan test --filter=ChecklistMasterEditStep3ValidationTest
php artisan test --filter="ChecklistMasterEditStep3ValidationTest|ChecklistMasterCopyTest|ChecklistManagement"
```

### Pass/fail counts

- New/updated test file alone: **5 passed**, 13 assertions.
- Combined with the full `ChecklistManagement` regression suite plus the P3-4 `ChecklistMasterCopyTest`: **114 passed**, 405 assertions, 0 failures.

## Manual browser smoke test

**Attempted, with an honest limitation to report.** This sandboxed CLI environment has no browser binary, no display, and no browser-automation CLI available (`chrome`, `msedge`, `chromium-cli`, `playwright` — none found on `PATH`), and the task explicitly instructs not to add a browser-testing framework in this PR. A true interactive click-through (open the edit page, observe the button, click it) was therefore **not literally executed** by this session.

What was verified instead, as the closest honest substitute:
1. **Render-level verification** (the 5 automated tests above) — confirms the exact HTML/JS shipped to the browser is correct for both the assigned-template and no-template states, and that the old contradictory statement is gone.
2. **DOM/JS semantics reasoning** — setting the `disabled` **property** on a `<button>` element (as `setStep3ContinueButtonState()` does) is standard, unambiguous browser behavior: a disabled button does not fire `click` events, regardless of whether the handler was attached via an inline `onclick` attribute or `addEventListener`. This is not an assumption specific to this app; it's baseline HTML/DOM behavior in every browser.
3. **Live site reachability confirmed** — `https://admin.kabba.local/` responds `200 OK` via the local Laragon setup, confirming the environment *could* support a manual check.

**Recommendation:** before merging, a developer with normal browser access should do a 2-minute manual check: open the Checklist Master edit page for (a) any existing record (all 37 live records currently have a customer admin template assigned) and confirm Step 3's Continue button is enabled and clickable immediately, and (b) verify via browser devtools that `document.getElementById('continueStep3Btn').disabled` reads `false` in that case. The no-template edge case cannot be manually verified against a real record today since 0 of 37 live records are in that state (confirmed during BUG-15's own prior investigation) — the automated render test is the coverage for that branch.

## Ready for review

**Yes**, with the above manual-smoke-test caveat clearly disclosed rather than silently skipped. Every other required check — consolidation into one named function, removal of the contradictory line, no reliance on listener ordering, no change to Step 2/server-side/navigation/backend behavior — is verified by direct code inspection and the automated test suite. Stopping here per instructions — no TD-16, P3-6, or P3-8 work started.
