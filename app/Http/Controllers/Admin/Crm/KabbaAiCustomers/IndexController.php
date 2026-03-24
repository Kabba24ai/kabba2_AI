<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            $query = Submission::query()
                ->when($request->filled('search'), function ($builder) use ($request) {
                    $search = trim((string) $request->search);

                    $builder->where(function ($inner) use ($search) {
                        $inner->where('unique_id', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    });
                });

            $perPage = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $submissions = $query->latest('id')->paginate($perPageVal)->withQueryString();

            $html = view('admin.crm.kabba_ai_customers.partials._table', compact('submissions'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'total' => $submissions->total(),
            ]);
        }

        return view('admin.crm.kabba_ai_customers.index');
    }
}
