<?php

namespace App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Tutorials\SystemLogicDocument;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $doc = SystemLogicDocument::forModule('dispatch')->findOrFail($id);

        $validated = $request->validate([
            'section_key' => 'required|string|max:128',
            'title'       => 'required|string|max:255',
            'summary'     => 'nullable|string|max:1000',
            'logic_body'  => 'nullable|string',
            'status'      => 'required|in:active,under_review,deprecated',
            'visibility'  => 'required|in:internal_admin,customer_visible,developer_only',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $doc->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        $doc->load('creator', 'updater');

        return response()->json([
            'success'  => true,
            'message'  => 'Logic note updated.',
            'document' => $doc,
        ]);
    }
}
