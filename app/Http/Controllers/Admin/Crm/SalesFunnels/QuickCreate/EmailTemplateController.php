<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate;

use App\Http\Controllers\Controller;
use App\Models\Customers\EmailTemplate;
use Illuminate\Http\Request;

/**
 * Inline quick-create for Email Template from the Funnel Step modal.
 */
class EmailTemplateController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:email_categories,id'],
            'subject'     => ['required', 'string', 'max:500'],
            'body'        => ['nullable', 'string'],
        ]);

        try {
            $template = EmailTemplate::create([
                'email_category_id' => $validated['category_id'],
                'name'              => $validated['name'],
                'subject'           => $validated['subject'],
                'body'              => $validated['body'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email template created.',
                'data'    => [
                    'id'      => $template->id,
                    'name'    => $template->name,
                    'subject' => $template->subject,
                    'body'    => $template->body,
                    'content' => ($template->subject ? $template->subject . "\n\n" : '') . ($template->body ?? ''),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create email template.',
            ], 500);
        }
    }
}
