<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Templates\StoreRequest;
use App\Models\MaintenanceManagement\Template;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $template = Template::create([
            ...$request->validated(),
            'created_by' => Auth::user()->name
        ]);

        // Attach parts with sort order
        if ($request->filled('parts')) {
            foreach ($request->parts as $index => $partId) {
                $template->parts()->attach($partId, ['sort_order' => $index + 1]);
            }
        }

        return redirect()
            ->route('admin.templates.index')
            ->with('success', 'Template created successfully.');
    }
}
