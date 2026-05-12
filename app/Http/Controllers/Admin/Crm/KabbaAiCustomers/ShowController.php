<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $submission = Submission::where('unique_id', $uniqueId)->firstOrFail();

        return view('admin.crm.kabba_ai_customers.show', compact('submission'));
    }
}
