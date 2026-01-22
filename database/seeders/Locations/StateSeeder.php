<?php

namespace Database\Seeders\Locations;

use Illuminate\Database\Seeder;

// Models
use App\Models\Locations\State;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['name' => 'Alabama', 'slug' => 'Alabama', 'abbreviation' => 'AL'],
            ['name' => 'Alaska', 'slug' => 'Alaska', 'abbreviation' => 'AK'],
            ['name' => 'Arizona', 'slug' => 'Arizona', 'abbreviation' => 'AZ'],
            ['name' => 'Arkansas', 'slug' => 'Arkansas', 'abbreviation' => 'AR'],
            ['name' => 'California', 'slug' => 'California', 'abbreviation' => 'CA'],
            ['name' => 'Colorado', 'slug' => 'Colorado', 'abbreviation' => 'CO'],
            ['name' => 'Connecticut', 'slug' => 'Connecticut', 'abbreviation' => 'CT'],
            ['name' => 'Delaware', 'slug' => 'Delaware', 'abbreviation' => 'DE'],
            ['name' => 'District of Columbia', 'slug' => 'District-of-Columbia', 'abbreviation' => 'DC'],
            ['name' => 'Florida', 'slug' => 'Florida', 'abbreviation' => 'FL'],
            ['name' => 'Georgia', 'slug' => 'Georgia', 'abbreviation' => 'GA'],
            ['name' => 'Hawaii', 'slug' => 'Hawaii', 'abbreviation' => 'HI'],
            ['name' => 'Idaho', 'slug' => 'Idaho', 'abbreviation' => 'ID'],
            ['name' => 'Illinois', 'slug' => 'Illinois', 'abbreviation' => 'IL'],
            ['name' => 'Indiana', 'slug' => 'Indiana', 'abbreviation' => 'IN'],
            ['name' => 'Iowa', 'slug' => 'Iowa', 'abbreviation' => 'IA'],
            ['name' => 'Kansas', 'slug' => 'Kansas', 'abbreviation' => 'KS'],
            ['name' => 'Kentucky', 'slug' => 'Kentucky', 'abbreviation' => 'KY'],
            ['name' => 'Louisiana', 'slug' => 'Louisiana', 'abbreviation' => 'LA'],
            ['name' => 'Maine', 'slug' => 'Maine', 'abbreviation' => 'ME'],
            ['name' => 'Montana', 'slug' => 'Montana', 'abbreviation' => 'MT'],
            ['name' => 'Nebraska', 'slug' => 'Nebraska', 'abbreviation' => 'NE'],
            ['name' => 'Nevada', 'slug' => 'Nevada', 'abbreviation' => 'NV'],
            ['name' => 'New Hampshire', 'slug' => 'New-Hampshire', 'abbreviation' => 'NH'],
            ['name' => 'New Jersey', 'slug' => 'New-Jersey', 'abbreviation' => 'NJ'],
            ['name' => 'New Mexico', 'slug' => 'New-Mexico', 'abbreviation' => 'NM'],
            ['name' => 'New York', 'slug' => 'New-York', 'abbreviation' => 'NY'],
            ['name' => 'North Carolina', 'slug' => 'North-Carolina', 'abbreviation' => 'NC'],
            ['name' => 'North Dakota', 'slug' => 'North-Dakota', 'abbreviation' => 'ND'],
            ['name' => 'Ohio', 'slug' => 'Ohio', 'abbreviation' => 'OH'],
            ['name' => 'Oklahoma', 'slug' => 'Oklahoma', 'abbreviation' => 'OK'],
            ['name' => 'Oregon', 'slug' => 'Oregon', 'abbreviation' => 'OR'],
            ['name' => 'Maryland', 'slug' => 'Maryland', 'abbreviation' => 'MD'],
            ['name' => 'Massachusetts', 'slug' => 'Massachusetts', 'abbreviation' => 'MA'],
            ['name' => 'Michigan', 'slug' => 'Michigan', 'abbreviation' => 'MI'],
            ['name' => 'Minnesota', 'slug' => 'Minnesota', 'abbreviation' => 'MN'],
            ['name' => 'Mississippi', 'slug' => 'Mississippi', 'abbreviation' => 'MS'],
            ['name' => 'Missouri', 'slug' => 'Missouri', 'abbreviation' => 'MO'],
            ['name' => 'Pennsylvania', 'slug' => 'Pennsylvania', 'abbreviation' => 'PA'],
            ['name' => 'Rhode Island', 'slug' => 'Rhode-Island', 'abbreviation' => 'RI'],
            ['name' => 'South Carolina', 'slug' => 'South-Carolina', 'abbreviation' => 'SC'],
            ['name' => 'South Dakota', 'slug' => 'South-Dakota', 'abbreviation' => 'SD'],
            ['name' => 'Tennessee', 'slug' => 'Tennessee', 'abbreviation' => 'TN'],
            ['name' => 'Texas', 'slug' => 'Texas', 'abbreviation' => 'TX'],
            ['name' => 'Utah', 'slug' => 'Utah', 'abbreviation' => 'UT'],
            ['name' => 'Vermont', 'slug' => 'Vermont', 'abbreviation' => 'VT'],
            ['name' => 'Virginia', 'slug' => 'Virginia', 'abbreviation' => 'VA'],
            ['name' => 'Washington', 'slug' => 'Washington', 'abbreviation' => 'WA'],
            ['name' => 'West Virginia', 'slug' => 'West-Virginia', 'abbreviation' => 'WV'],
            ['name' => 'Wisconsin', 'slug' => 'Wisconsin', 'abbreviation' => 'WI'],
            ['name' => 'Wyoming', 'slug' => 'Wyoming', 'abbreviation' => 'WY'],
        ];

        $updateFlag = false;

        foreach ($states as $state) {

            $model = State::firstOrCreate(
                ['abbreviation' => $state['abbreviation']],
                $state
            );

            // If it already existed, update it with the latest data
            if ($updateFlag) {
                $model->update($state);
            }
        }
    }
}
