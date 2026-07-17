<?php

namespace App\Models\Customers;

use App\Models\Iam\Personnel\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Saved audience — reusable segmentation RULES only, never a customer
 * list. Each use resolves against current CRM data; recipients freeze
 * onto the individual broadcast event when it is queued.
 */
class SmsAudience extends Model
{
    protected $fillable = [
        'name',
        'base_all',
        'positive_mode',
        'include_tag_ids',
        'exclude_tag_ids',
        'created_by',
    ];

    protected $casts = [
        'base_all'        => 'boolean',
        'include_tag_ids' => 'array',
        'exclude_tag_ids' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function spec(): array
    {
        return [
            'base'    => $this->base_all ? 'all' : 'tags',
            'mode'    => $this->positive_mode ?: 'any',
            'include' => $this->include_tag_ids ?? [],
            'exclude' => $this->exclude_tag_ids ?? [],
        ];
    }
}
