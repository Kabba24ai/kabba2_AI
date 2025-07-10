<?php

namespace App\Http\Controllers\Front\Customer\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Request
use App\Http\Requests\Front\Customer\Profile\PostRequest;
use App\Models\Customers\Customer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;


class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PostRequest $request)
    {

        // dd($request->validated());


        $validated = $request->validated();
        $customer = Auth::guard('customer')->user();

        DB::beginTransaction();

        try {
            $customer->first_name = $validated['first_name'];
            $customer->last_name  = $validated['last_name'];
            $customer->phone      = $validated['number'] ?? null;
            $customer->save();

            DB::commit();

            return redirect()->route('front.customer.profile.index')->with('success', 'Profile updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->withErrors(['error' => 'Something went wrong during update.']);
        }
    }
}
