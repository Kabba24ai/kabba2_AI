<?php

namespace App\Http\Controllers\Admin\OrderManagement\QueueLine;

use App\Http\Controllers\Controller;

/**
 * Queue Line page shell — all board behavior lives in the Livewire
 * component (App\Livewire\QueueLine\Board); eligibility rules live in
 * QueueLineEligibility; state writes in QueueLineService.
 */
class IndexController extends Controller
{
    public function __invoke()
    {
        return view('admin.order_management.queue_line.index', [
            'title' => 'Queue Line',
        ]);
    }
}
