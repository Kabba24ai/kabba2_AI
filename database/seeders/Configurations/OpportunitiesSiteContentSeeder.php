<?php

    namespace Database\Seeders\Configurations;

    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use App\Models\Configurations\OpportunitiesSiteContent;

    class OpportunitiesSiteContentSeeder  extends Seeder
    {
        /**
         * Run the database seeds.
         */
        public function run(): void
        {
            OpportunitiesSiteContent::insert([
                [
                    'section_key' => 'careers_header',
                    'title' => 'Employment Opportunities',
                    'content' => "At Rent 'n King , you're not just filling a role — you're joining a tight-knit team that takes pride in hard work, dependable service, and doing things the right way. We rely on each other, and our customers rely on us — which means showing up, being on time, and being consistent matters here. If you enjoy solving problems, working with quality equipment, and being trusted to do meaningful work that people count on, you'll feel right at home.",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 
                [
                    'section_key' => 'careers_footer',
                    'title' => 'Equal Opportunity Employer',
                    'content' => "Rent 'n King abc is an Equal Opportunity Employer All qualified applicants will receive consideration for employment without regard to race, color, religion, sex, national origin, disability status, protected veteran status, or any other characteristic protected by law.",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'section_key' => 'acknowledgement',
                    'title' => 'Acknowledgement',
                    'content' => "By submitting this application, I certify that all information provided is true and complete to the best of my knowledge. I understand that submitting false information may disqualify me from employment or result in termination if discovered after hiring.",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], ]);
        }
    }
