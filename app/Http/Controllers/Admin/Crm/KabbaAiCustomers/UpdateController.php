<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'failed', 'completed'])],
            'setup_status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission = Submission::where('unique_id', $uniqueId)->firstOrFail();


        $submission->update([
            'status' => $validated['status'],
            'setup_status' => $validated['setup_status'],
            'comment' => $validated['comment'] ?? null,
        ]);

        flash('Demo fields updated successfully.')->success();

        return redirect()->route('admin.crm.kabba-ai-customers.index');
    }
}
