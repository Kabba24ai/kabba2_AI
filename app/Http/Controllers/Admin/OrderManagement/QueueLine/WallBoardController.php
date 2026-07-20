<?php

namespace App\Http\Controllers\Admin\OrderManagement\QueueLine;

use App\Http\Controllers\Controller;

/**
 * Wall-board presentation of the Queue Line — the SAME Livewire Board
 * component in wallboard mode inside a minimal chrome-free layout. No
 * separate eligibility, state, or action logic exists here; the mode flag
 * changes presentation scale and poll cadence only. Authenticated like
 * every admin route — no public kiosk access.
 */
class WallBoardController extends Controller
{
    public function __invoke()
    {
        return view('admin.order_management.queue_line.wallboard', [
            'title' => 'Queue Line Wall Board',
        ]);
    }
}
