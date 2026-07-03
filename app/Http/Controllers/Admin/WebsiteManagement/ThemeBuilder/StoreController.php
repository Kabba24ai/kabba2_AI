<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder;

use App\Http\Controllers\Controller;
use App\Services\Website\ThemeService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private ThemeService $theme) {}

    public function __invoke(Request $request, string $group)
    {
        if (!in_array($group, ThemeService::groups(), true)) {
            abort(404);
        }

        // Pull only the keys that belong to this group
        $schema = ThemeService::groupSchema($group);
        $keys   = array_keys($schema);

        // Basic validation: each field is nullable string (media IDs are ints stored as string)
        $rules = [];
        foreach ($schema as $key => $cfg) {
            $rules[$key] = match ($cfg['type']) {
                'boolean'  => 'nullable|boolean',
                'color'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{3,8}$/',
                'media'    => 'nullable|integer',
                'code'     => 'nullable|string',
                'textarea' => 'nullable|string|max:5000',
                default    => 'nullable|string|max:500',
            };
        }

        // Exclude boolean rules from normal required validation — checkboxes aren't submitted when unchecked
        $booleans = collect($schema)->filter(fn ($c) => $c['type'] === 'boolean')->keys()->all();
        $nonBools = array_diff($keys, $booleans);

        $validated = $request->validate(
            array_intersect_key($rules, array_flip($nonBools))
        );

        // Merge boolean values manually (absent = '0')
        foreach ($booleans as $bKey) {
            $validated[$bKey] = $request->boolean($bKey) ? '1' : '0';
        }

        $this->theme->saveGroup($group, $validated);

        flash()->success(ucfirst(str_replace('_', ' ', $group)) . ' settings saved.');

        return redirect()->route('admin.website-management.theme-builder.show', $group);
    }
}
