<?php

namespace App\Http\Controllers\Admin\Crm\MessageManagement\EmailTemplate;

use App\Http\Controllers\Controller;
use App\Models\Customers\EmailTemplate;
use Exception;

class FetchController extends Controller
{
    public function __invoke($categoryId)
    {
        try {
            $templates = EmailTemplate::where('email_category_id', $categoryId)
                ->where('status', 'Active')
                ->orderBy('name', 'ASC')
                ->get();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email templates not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }
}
