<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;

class EditController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $submission = Submission::where('unique_id', $uniqueId)->firstOrFail();

        return view('admin.crm.kabba_ai_customers.edit', compact('submission'));
    }
}
