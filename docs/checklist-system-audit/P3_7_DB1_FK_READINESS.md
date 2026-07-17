# P3-7 / DB-1 — Foreign key readiness audit: `customer_admin_templates.equipment_category_id`

**Date:** 2026-07-15
**Type:** Read-only investigation only. **No production code or database data was modified to produce this document.**
**Verdict: READY** (no cleanup required) — see §6.

---

## 1. Inspection

### Migration history

`customer_admin_templates` has exactly one migration touching this column, from creation, never altered since:

```php
// database/migrations/checklist_mangement/customer_admin/2025_08_27_111450_customer_admin_templates.php
$table->string('equipment_category_id')->nullable();
```

No later migration ever converted it — confirmed by grepping every migration file for `equipment_category_id` and finding only the original `create` migration for this table.

### Current schema (live dev DB `rc_kabba_7_7_26`, confirmed via `SHOW CREATE TABLE`)

```sql
`equipment_category_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
```
No FK, no index, no numeric constraint. `product_categories.id` is `bigint unsigned AUTO_INCREMENT` — the target type any FK conversion must match.

### `CustomerAdminTemplate` model

```php
protected $fillable = ['template_name', 'description', 'equipment_category_id', 'active_template', 'unique_id'];

public function equipmentCategory()
{
    return $this->belongsTo(ProductCategory::class, 'equipment_category_id');
}
```
The model already declares a `belongsTo` relation — this works today via raw column matching regardless of the missing DB-level constraint, and needs no code change if a real FK is added (Eloquent doesn't care whether the underlying column is `varchar` or `bigint unsigned` as long as the values compare correctly).

### All reads/writes of `equipment_category_id` (Customer Admin side)

- `Templates\StoreController.php` / `Templates\UpdateController.php` — write `$validated['equipment_category']` straight into `equipment_category_id`, no casting.
- `Templates\StoreRequest.php` / `Templates\UpdateRequest.php` — validation rule is `'equipment_category' => 'required'` only. **No `exists:product_categories,id` rule** — identical to the Rental Ready side (see below), so this is existing, established precedent, not an outlier to "fix" as part of this item.
- `TemplateCrudService.php` (shared `store()`/`update()`/`copy()`) — has no tree-specific logic for this field; `copy()` uses `$template->replicate()`, which copies the existing (valid) value as-is.
- `ChecklistMaster.php` — a separate, unrelated `equipment_category_id` column on a different table (already a real FK since that table's creation — see §1 Rental Ready comparison below).
- View layer (`customer_admin/partials/_tab_templates.blade.php`) — the `equipment_category` select is populated from `ProductCategory::getHierarchy()`, which returns `[id => "---- Title"]` (confirmed by reading the method) — the form can only submit a real `ProductCategory` id through the UI. Direct API/raw POST bypass is still possible in theory, which is exactly why the SQL audit in §2 is the authoritative check, not this code-path reasoning alone.

### Related Rental Ready implementation (for comparison — the existing precedent to follow)

`rental_ready_checklist_templates.equipment_category_id` started identically (`string(...)->nullable()`), then was converted by a **second, later migration**:

```php
// database/migrations/checklist_mangement/rental_ready/2025_08_21_162850_update_equipment_category_in_rental_ready_checklist_templates.php
Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
    $table->dropColumn('equipment_category_id');
});
Schema::table('rental_ready_checklist_templates', function (Blueprint $table) {
    $table->foreignId('equipment_category_id')
          ->nullable()
          ->constrained('product_categories')
          ->nullOnDelete()
          ->after('description');
});
```
This is the exact template to mirror for Customer Admin — same table shape, same nullable semantics, same target table.

A third data point, `checklist_masters.equipment_category_id`, was built as a real FK from its own creation migration, also using `nullOnDelete()`:
```php
$table->unsignedBigInteger('equipment_category_id')->nullable();
...
$table->foreign('equipment_category_id')->references('id')->on('product_categories')->nullOnDelete();
```

**All existing `equipment_category_id → product_categories` foreign keys in this codebase use `nullOnDelete()`**, except one unrelated, semantically-different case (`equipment_intelligence_rules.equipment_category_id`, which is `NOT NULL` and uses `cascadeOnDelete()` — appropriate there because that field is mandatory and tightly scoped to its category, not comparable to the templates' optional-category semantics).

An existing characterization test (`TemplateCrudCharacterizationTest.php`, from PR-B4.3) already documents and locks in today's divergence:
- `test_rental_ready_template_store_rejects_a_nonexistent_equipment_category_id` — Rental Ready's real FK throws a `QueryException`, caught generically, flashes an error.
- `test_customer_admin_template_store_accepts_a_nonexistent_equipment_category_id` — Customer Admin's plain string column stores the bogus value as-is.

Adding the FK will require updating the second test's expectation (it would then behave like the first) — a known, contained follow-up for whenever the actual migration PR happens, not for this audit.

---

## 2. Read-only SQL run (against `rc_kabba_7_7_26`)

```sql
SELECT COUNT(*) AS total_rows FROM customer_admin_templates;
-- 33

SELECT COUNT(*) AS null_count FROM customer_admin_templates WHERE equipment_category_id IS NULL;
-- 0

SELECT COUNT(*) AS empty_string_count FROM customer_admin_templates WHERE equipment_category_id = '';
-- 0

SELECT COUNT(*) AS non_numeric_count FROM customer_admin_templates
WHERE equipment_category_id IS NOT NULL AND equipment_category_id != '' AND equipment_category_id NOT REGEXP '^[0-9]+$';
-- 0

SELECT cat.equipment_category_id, COUNT(*) AS cnt
FROM customer_admin_templates cat
LEFT JOIN product_categories pc ON pc.id = CAST(cat.equipment_category_id AS UNSIGNED)
WHERE pc.id IS NULL
GROUP BY cat.equipment_category_id;
-- 0 rows (no orphaned/missing category references)

SELECT equipment_category_id, COUNT(*) AS cnt
FROM customer_admin_templates
GROUP BY equipment_category_id
ORDER BY CAST(equipment_category_id AS UNSIGNED);
-- 22 distinct values, all in the range 1-31, counts 1-4 each (full table below)

SELECT equipment_category_id, LENGTH(equipment_category_id) AS len, CHAR_LENGTH(TRIM(equipment_category_id)) AS trimmed_len
FROM customer_admin_templates
WHERE LENGTH(equipment_category_id) != CHAR_LENGTH(TRIM(equipment_category_id))
   OR equipment_category_id != CAST(CAST(equipment_category_id AS UNSIGNED) AS CHAR);
-- 0 rows (no whitespace, no leading zeros, no non-canonical numeric formatting)
```

### Distinct values (all 33 rows, full breakdown)

| equipment_category_id | count |
|---|---|
| 1 | 3 |
| 4 | 4 |
| 5 | 1 |
| 6 | 1 |
| 7 | 3 |
| 8 | 1 |
| 10 | 1 |
| 11 | 1 |
| 13 | 2 |
| 14 | 1 |
| 17 | 2 |
| 18 | 1 |
| 19 | 1 |
| 20 | 1 |
| 21 | 1 |
| 22 | 2 |
| 23 | 1 |
| 24 | 1 |
| 25 | 2 |
| 26 | 1 |
| 30 | 1 |
| 31 | 1 |

**Every one of the 33 rows would pass a foreign-key constraint today, with zero exceptions.**

### `product_categories` schema check

`SHOW CREATE TABLE product_categories` confirms: `id bigint unsigned AUTO_INCREMENT`, **no `deleted_at` column** — this table has no soft-delete concept, so there is no "soft-deleted but still referenced" scenario to check separately from the plain existence check already run above.

---

## 3. Desired FK behavior

| Table | Behavior used | Nullable | Required |
|---|---|---|---|
| `rental_ready_checklist_templates.equipment_category_id` | `nullOnDelete()` | Yes | No |
| `checklist_masters.equipment_category_id` | `nullOnDelete()` | Yes | No |
| `equipment_intelligence_rules.equipment_category_id` (unrelated context) | `cascadeOnDelete()` | No | Yes |

**Recommendation: `nullOnDelete()`**, matching both directly-comparable precedents (Rental Ready templates and ChecklistMaster). `restrictOnDelete()` would be inconsistent with the rest of this codebase's pattern for this exact relationship and would introduce a new failure mode (blocking category deletion) not present anywhere else. `cascadeOnDelete()` is wrong for this semantic — deleting a `ProductCategory` should not delete every Customer Admin template that happens to reference it; the template should simply become uncategorized (`NULL`), exactly as the Rental Ready sibling already does.

---

## 4. Type-conversion / truncation risk

None. Every one of the 33 existing values is a clean, canonical, non-padded numeric string (confirmed by the whitespace/leading-zero check in §2) already within `bigint unsigned`'s range by a wide margin (values are 1-31). Following the Rental Ready migration's exact `dropColumn` + `foreignId()->nullable()->constrained()->nullOnDelete()` pattern would not truncate, reinterpret, or lose any existing value.

**Rollback consideration:** mirroring Rental Ready's `down()` exactly — dropping the FK column and recreating a plain nullable string column — would restore the column shape but **not the original values** (a `dropColumn` discards data; this is the same accepted caveat already present in Rental Ready's own migration, not a new risk introduced here). Since Phase 3 policy is "no migration created yet," this is noted for whenever DB-1's actual migration is authored, not a blocker for this audit.

---

## 5. Cleanup required?

**None.** Zero nulls, zero empty strings, zero non-numeric values, zero orphaned references, zero formatting anomalies. This is the cleanest possible starting state for a FK conversion.

---

## 6. Verdict

**READY.** No data cleanup is required before a foreign-key migration could be authored. The exact migration design to use (once actually created, in a future PR):

```php
Schema::table('customer_admin_templates', function (Blueprint $table) {
    $table->dropColumn('equipment_category_id');
});
Schema::table('customer_admin_templates', function (Blueprint $table) {
    $table->foreignId('equipment_category_id')
          ->nullable()
          ->constrained('product_categories')
          ->nullOnDelete()
          ->after('description');
});
```
— identical in shape to Rental Ready's own precedent, using `nullOnDelete()` for consistency with every other `equipment_category_id → product_categories` relationship in this codebase.

**One follow-up to fold into that future PR, not this audit:** `TemplateCrudCharacterizationTest::test_customer_admin_template_store_accepts_a_nonexistent_equipment_category_id` will need its expectation updated to match Rental Ready's `rejects` test once the FK is actually added.

---

## Confirmation

**NO PRODUCTION CODE OR DATABASE DATA WAS MODIFIED** to produce this document. All SQL executed was read-only (`SELECT`/`SHOW`). No migration was created.
