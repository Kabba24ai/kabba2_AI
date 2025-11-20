<?php

namespace App\Http\Controllers\Front\Auth\Register;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// Request
use App\Http\Requests\Front\Auth\Register\PostRequest;
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
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Generate a unique ID for the customer (or you can use ModelHelper as you already do)
            $uniqueId = Str::uuid()->toString();

            $customerData = [
                'unique_id' => $uniqueId,
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ];

            // Create the customer
            $customer = Customer::create($customerData);

            // Login the user after successful registration
            Auth::guard('customer')->login($customer);

            DB::commit();

            // flash('Account registered successfully.')->success();

            return redirect()->route('front.customer.dashboard.index')->with('success', 'Account registered successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            // flash('Registration failed: ' . $e->getMessage())->error();

            return redirect()
                ->back()
                ->withInput()
                ->with('error' , 'Something went wrong during registration: ' . $e->getMessage());
        }
    }
}
