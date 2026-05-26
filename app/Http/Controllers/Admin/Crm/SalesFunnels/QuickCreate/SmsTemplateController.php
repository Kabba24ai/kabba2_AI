<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsFunnel;
use Illuminate\Http\Request;

/**
 * Inline quick-create for SMS Message Template from the Funnel Step modal.
 * SmsFunnel is the SMS message / template model (table: sms_funnels).
 */
class SmsTemplateController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:sms_categories,id'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $template = SmsFunnel::create([
                'sms_cat_id'  => $validated['category_id'],
                'name'        => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS template created.',
                'data'    => [
                    'id'          => $template->id,
                    'name'        => $template->name,
                    'description' => $template->description,
                    'content'     => $template->description, // used as preview
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SMS template.',
            ], 500);
        }
    }
}
