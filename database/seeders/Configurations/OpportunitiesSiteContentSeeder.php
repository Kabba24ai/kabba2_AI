<?php

namespace Database\Seeders\Configurations;

use Illuminate\Database\Seeder;
use App\Models\Configurations\OpportunitiesSiteContent;

class OpportunitiesSiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $updateExisting = config('app.seeders.existing_settings_update');

        $sections = [
            [
                'section_key' => 'careers_header',
                'title' => 'Employment Opportunities',
                'content' => "At Rent 'n King , you're not just filling a role — you're joining a tight-knit team...",
                'is_active' => true,
            ],
            [
                'section_key' => 'careers_footer',
                'title' => 'Equal Opportunity Employer',
                'content' => "Rent 'n King abc is an Equal Opportunity Employer...",
                'is_active' => true,
            ],
            [
                'section_key' => 'acknowledgement',
                'title' => 'Acknowledgement',
                'content' => "By submitting this application, I certify that all information provided...",
                'is_active' => true,
            ],
        ];

        foreach ($sections as $section) {

            // Create if not exists
            $item = OpportunitiesSiteContent::firstOrCreate(
                ['section_key' => $section['section_key']], // unique key
                $section
            );

            // Update only if allowed
            if ($updateExisting && !$item->wasRecentlyCreated) {
                $item->update([
                    'title' => $section['title'],
                    'content' => $section['content'],
                    'is_active' => $section['is_active'],
                ]);
            }
        }
    }
}