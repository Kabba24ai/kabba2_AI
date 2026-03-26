<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;

class DeleteController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $submission = Submission::where('unique_id', $uniqueId)->firstOrFail();

        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
