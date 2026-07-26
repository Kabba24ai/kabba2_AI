<?php

namespace App\Events\Credit;

use App\Services\Credit\CreditThresholdSnapshot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired — via DB::afterCommit, so only after the financial posting durably
 * commits — when a posted transaction pushes an approved credit account's
 * outstanding balance over its limit (a first crossing OR further exposure
 * while already over). The listener records the durable event and creates or
 * appends the management-review task; it never blocks or reverses the posting.
 */
class CreditThresholdExceededEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CreditThresholdSnapshot $snapshot)
    {
    }
}
