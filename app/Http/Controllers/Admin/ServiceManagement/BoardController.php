<?php

namespace App\Http\Controllers\Admin\ServiceManagement;

use App\Http\Controllers\Controller;

/**
 * Service Operations Board — the consolidated, technician-swimlane dashboard
 * for all service work. The page is a thin shell; the live board is the
 * OperationsBoard Livewire component (mirrors how the Queue Line board mounts).
 */
class BoardController extends Controller
{
    public function __invoke()
    {
        return view('admin.service_management.board');
    }
}
