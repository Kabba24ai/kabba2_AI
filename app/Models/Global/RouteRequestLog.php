<?php

namespace App\Models\Global;

use Illuminate\Database\Eloquent\Model;

/**
 * Diagnostic record of one shared-routing estimate attempt. Privacy-safe by
 * construction (see the migration) — never stores keys, headers, full customer
 * addresses, or raw provider payloads. Mirrors the OpenAILog/SMSLog convention.
 */
class RouteRequestLog extends Model
{
    protected $fillable = [
        'consumer',
        'provider',
        'status',
        'http_status',
        'duration_ms',
        'origin_reference',
        'destination_reference',
        'notes',
    ];
}
