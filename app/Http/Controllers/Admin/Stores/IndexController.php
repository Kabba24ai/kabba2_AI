<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

// Models
use App\Models\Stores\Store;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        // If the request is an AJAX request, return a JSON response
        if ($request->ajax()) {
            // Create an empty collection
            $query = Store::query();

            // Apply filters if any
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('store_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('status')) {
                $status = $request->input('status');
                $query->where('status', $status);
            }

            $perPage = $request->input('per_page', 10);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $stores = $query->latest('id')->paginate($perPageVal)->withQueryString(); // keeps filters in pagination links

            $html = view('admin.stores.partials._table', compact('stores'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        return view('admin.stores.index');
    }
}
