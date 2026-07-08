<?php

namespace App\Models\Customers;

use App\Helpers\ModelHelper;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\ResolutionCenter\ResolutionScenario;
use App\Services\ResolutionCenter\ResolutionScenarioRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 * Phase 3.4 — Operational Knowledge Framework: added `scenario_key` and the
 * `scenario()` accessor so any case can resolve its governing
 * {@see ResolutionScenario} generically.
 * Phase 3.6 — Customer Resolution Operations Center: added `priority`,
 * `status`/`waiting_on` (a separate, purely operational field from
 * `outcome` — see PHASE_3_6_OPERATIONS_AUDIT.md §5 for the exact
 * relationship), `assigned_to_user_id` (the current case owner —
 * reassignable, unlike `responsible_person_id`, which is set once at
 * creation), a best-effort `store_id` snapshot, `completed_at`, and the
 * `activityLogs()` relation backing the Audit Trail.
 *
 * One row per guided resolution session. This model has no business logic
 * of its own beyond relationships and casts — the decision tree lives in
 * each {@see ResolutionScenario} implementation (pure, no persistence) and
 * orchestration lives in {@see \App\Services\ResolutionCenterService} (the
 * only writer to this table), the same "model is data, service is policy"
 * separation `CustomerCredit`/`CustomerCreditService` already maintain.
 */
class ResolutionCase extends Model
{
    use SoftDeletes;

    protected $table = 'resolution_cases';

    protected $fillable = [
        'unique_id',
        'customer_id',
        'order_id',
        'scenario_key',
        'issue',
        'issue_category',
        'priority',
        'balance_snapshot',
        'store_credit_snapshot',
        'payment_method_snapshot',
        'can_reschedule',
        'credit_would_satisfy',
        'recommended_resolution',
        'recommended_next_step',
        'employee_decision',
        'employee_decision_detail',
        'manager_override_user_id',
        'manager_override_reason',
        'notes',
        'outcome',
        'status',
        'waiting_on',
        'credit_issued_id',
        'completed_at',
        'responsible_person_id',
        'assigned_to_user_id',
        'store_id',
    ];

    protected $casts = [
        'balance_snapshot' => 'decimal:2',
        'store_credit_snapshot' => 'decimal:2',
        'can_reschedule' => 'boolean',
        'credit_would_satisfy' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_id)) {
                $model->unique_id = ModelHelper::generateUniqueID($model, 'RC');
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    public function managerOverrideUser()
    {
        return $this->belongsTo(User::class, 'manager_override_user_id');
    }

    public function creditIssued()
    {
        return $this->belongsTo(CustomerCredit::class, 'credit_issued_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Stores\Store::class, 'store_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ResolutionCaseActivityLog::class)->latest('id');
    }

    /**
     * Resolve this case's governing scenario from the registry. Introspection
     * only — e.g. for display (label, business rules) — never used to
     * re-derive or override this case's already-stored recommendation.
     */
    public function scenario(): ResolutionScenario
    {
        return ResolutionScenarioRegistry::get($this->scenario_key);
    }
}
