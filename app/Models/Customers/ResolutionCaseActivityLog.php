<?php

namespace App\Models\Customers;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 3.6 — Customer Resolution Operations Center.
 *
 * The Audit Trail's data model — one row per mutation to a
 * {@see ResolutionCase}, following the same generic
 * {action, field, old_value, new_value, user_id} shape already
 * independently proven by DispatchAuditLog/TaskActivityLog elsewhere in
 * this codebase. Written only by {@see \App\Services\ResolutionCenterService}.
 */
class ResolutionCaseActivityLog extends Model
{
    protected $table = 'resolution_case_activity_logs';

    protected $fillable = [
        'resolution_case_id',
        'user_id',
        'action',
        'field',
        'old_value',
        'new_value',
    ];

    public function resolutionCase()
    {
        return $this->belongsTo(ResolutionCase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
