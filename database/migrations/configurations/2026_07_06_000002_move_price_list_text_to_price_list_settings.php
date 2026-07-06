<?php

use App\Models\Configurations\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Price List document text belongs to the Price List module, not Company
 * identity: move the value message and disclaimer into their own
 * 'Price List Settings' group (edited from Products → Price List →
 * Document Text) and add the editable document title.
 * Company Settings keeps reusable identity values only.
 */
return new class extends Migration
{
    private const TYPE = 'Price List Settings';

    public function up(): void
    {
        Setting::whereIn('setting_name', ['price_list_value_message', 'price_list_disclaimer'])
            ->update(['setting_type' => self::TYPE]);

        Setting::updateOrCreate(
            ['setting_name' => 'price_list_title'],
            [
                'setting_type'    => self::TYPE,
                'setting_title'   => 'Price List — Document Title',
                'value_type'      => 'text',
                'setting_value'   => 'Rental Price List',
                'placeholder'     => 'Title printed in the document header',
                'sort_order'      => 1,
                'is_secure_field' => 0,
                'is_encrypted'    => 0,
            ]
        );

        Setting::where('setting_name', 'price_list_value_message')->update(['sort_order' => 2]);
        Setting::where('setting_name', 'price_list_disclaimer')->update(['sort_order' => 3]);
    }

    public function down(): void
    {
        Setting::whereIn('setting_name', ['price_list_value_message', 'price_list_disclaimer'])
            ->update(['setting_type' => 'Company Settings']);

        Setting::where('setting_name', 'price_list_title')->delete();
    }
};
