<?php

namespace App\Http\Controllers\Admin\Crm\KabbaAiCustomers;

use App\Http\Controllers\Controller;
use App\Models\Authrise\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'first_name'     => ['required', 'string', 'max:255'],
            'last_name'      => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255'],
            'phone_number'   => ['required', 'string', 'max:32'],
            'business_name'  => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'city'           => ['nullable', 'string', 'max:255'],
            'state'          => ['nullable', 'string', 'max:64'],
            'zip_code'       => ['nullable', 'string', 'max:32'],
            'status'         => ['required', Rule::in(['pending', 'failed', 'completed'])],
            'setup_status'   => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
            'comment'        => ['nullable', 'string', 'max:5000'],
        ]);

        $submission = Submission::create([
            'first_name'     => $validated['first_name'],
            'last_name'      => $validated['last_name'],
            'email'          => $validated['email'],
            'phone_number'   => $validated['phone_number'],
            'business_name'  => $validated['business_name'],
            'street_address' => $validated['street_address'],
            'city'           => $validated['city'] ?? null,
            'state'          => $validated['state'] ?? null,
            'zip_code'       => $validated['zip_code'] ?? null,
            'amount'         => 4.95,
            'status'         => $validated['status'],
            'setup_status'   => $validated['setup_status'],
            'comment'        => $validated['comment'] ?? null,
            'password'       => bcrypt(Str::random(16)),
        ]);

        flash('Customer created successfully.')->success();

        return redirect()->route('admin.crm.kabba-ai-customers.index');
    }
}
