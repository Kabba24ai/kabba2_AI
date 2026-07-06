<?php

namespace App\Http\Controllers\Admin\Documents\Text;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Documents\SaveDocumentTextRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveDocumentTextRequest $request)
    {
        foreach ($request->validated() as $key => $value) {
            if ($setting = Setting::where('setting_name', $key)->where('setting_type', 'Price List Settings')->first()) {
                $setting->setting_value = $value ?? '';
                $setting->save();
            }
        }

        flash('Price List document text updated.')->success();

        return redirect()->route('admin.documents.price-list.text');
    }
}
