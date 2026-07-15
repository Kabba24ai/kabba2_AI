<?php

namespace App\Http\Controllers\Admin\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TermsAndConditions\UpdateHeaderRequest;
use App\Models\Configurations\Setting;

class UpdateHeaderController extends Controller
{
    /**
     * Save the Rental Agreement Header lines shown at the top of the
     * customer-facing rental agreement (order signing page).
     *
     * Storage is unchanged from the retired Branding editor — the same
     * 'Website Management Branding' settings rows — so existing data and
     * the front-end consumer keep working without migration.
     */
    public function __invoke(UpdateHeaderRequest $request)
    {
        foreach ($request->validated() as $key => $value) {
            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Website Management Branding',
                ],
                [
                    'setting_value' => $value,
                ]
            );
        }

        flash()->success(__('Rental Agreement Header updated successfully.'));

        return redirect()->route('admin.terms-and-conditions.index');
    }
}
