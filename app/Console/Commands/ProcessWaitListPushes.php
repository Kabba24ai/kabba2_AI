<?php

namespace App\Console\Commands;

use App\Services\WaitList\WaitListPushService;
use Illuminate\Console\Command;

class ProcessWaitListPushes extends Command
{
    protected $signature = 'waitlist:process-pushes';

    protected $description = 'Deliver deferred wait list alert pushes at business opening and second pushes for alerts unacknowledged past the configured delay';

    public function handle(WaitListPushService $service): int
    {
        $service->processPending();

        return self::SUCCESS;
    }
}
