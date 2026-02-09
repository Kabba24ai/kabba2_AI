<?php

namespace Database\Seeders\Opportunity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Opportunity\OpportunityQuestion;
use App\Models\Opportunity\OpportunityQuestionOption;

class OpportunityQuestionSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * A. Mechanical Experience
         */
        $q1 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'mechanical_experience',
            'question_text' => 'Which best describes your mechanical experience?',
            'required' => 1,
            'answer_type' => 'radio',
            'answer_grid' => '100',
            'display_order' => 1,
            'status' => 1,
        ]);

        $this->options($q1, [
            ['basic_home', 'I perform basic vehicle maintenance and repairs at home (brakes, suspension, electrical, engines, etc.)'],
            ['diesel_1_3', '1–3 years diesel mechanic experience in a professional shop environment'],
            ['diesel_4_plus', '4+ years diesel mechanic experience in a professional shop environment'],
            ['industrial', '1+ years industrial or plant maintenance (motors, pumps, conveyors, hydraulics, etc.)'],
            ['limited', 'I have limited mechanical experience but learn quickly and follow procedures well'],
        ]);

        /**
         * B. Equipment Repaired
         */
        $q2 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'equipment_repair',
            'question_text' => 'Which types of equipment have you worked on to repair / maintain?',
            'sub_text' => 'Select all that apply',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '50',
            'display_order' => 2,
            'status' => 1,
        ]);

        $this->options($q2, [
            ['skid_steers', 'Skid steers / compact track loaders'],
            ['mini_excavators', 'Mini excavators'],
            ['boom_lifts', 'Boom lifts / scissor lifts'],
            ['trailers', 'Trailers (axles, brakes, wiring)'],
            ['small_engines', 'Small engines (gas & diesel)'],
            ['hydraulic_systems', 'Hydraulic systems (hoses, fittings, cylinders)'],
            ['electrical_diagnostics', 'Electrical diagnostics (12V / 24V systems)'],
            ['none', 'None of the above'],
        ]);

        /**
         * C. Equipment Operated
         */
        $q3 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'equipment_operated',
            'question_text' => 'Which types of equipment have you operated?',
            'sub_text' => 'Select all that apply',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '50',
            'display_order' => 3,
            'status' => 1,
        ]);

        $this->options($q3, [
            ['skid_steers', 'Skid steers / compact track loaders'],
            ['mini_excavators', 'Mini excavators'],
            ['bulldozers', 'Bulldozers'],
            ['boom_lifts', 'Boom lifts / scissor lifts'],
            ['stump_grinders', 'Stump Grinders'],
            ['trenchers', 'Trenchers'],
            ['chain_saws', 'Chain Saws'],
            ['telehandlers', 'Telehandlers'],
        ]);

        /**
         * D. Diagnostic Ability
         */
        $q4 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'diagnostic_ability',
            'question_text' => 'When a machine stops working, which best describes you?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '100',
            'display_order' => 4,
            'status' => 1,
        ]);

        $this->options($q4, [
            ['need_help', 'I usually need someone to tell me what to check'],
            ['follow_manual', 'I can follow a checklist or service manual'],
            ['tech_support', 'I work well with factory technical support'],
            ['systematic', 'I can systematically diagnose most issues on my own'],
            ['expert', 'I regularly diagnose problems others can’t'],
        ]);

        /**
         * E. Hydraulics Comfort
         */
        $q5 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'hydraulics_comfort',
            'question_text' => 'How comfortable are you working with hydraulic systems?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '100',
            'display_order' => 5,
            'status' => 1,
        ]);

        $this->options($q5, [
            ['none', 'No experience'],
            ['basic', 'I can identify leaks and replace hoses'],
            ['understand', 'I understand hydraulic flow, pressure, and fittings'],
            ['advanced', 'I diagnose hydraulic issues and rebuild components'],
        ]);

        /**
         * F. Equipment Care
         */
        $q6 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'equipment_care',
            'question_text' => 'Which best describes how you treat equipment?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '100',
            'display_order' => 6,
            'status' => 1,
        ]);

        $this->options($q6, [
            ['report', 'I use it and report issues when something breaks'],
            ['early', 'I notice small issues and report them early'],
            ['prevent', 'I actively prevent damage and correct unsafe use'],
            ['own', 'I treat equipment as if I personally own it'],
        ]);

        /**
         * G. Customer Facing
         */
        $q7 = OpportunityQuestion::create([
            'type' => 'mechanical',
            'question_key' => 'customer_facing',
            'question_text' => 'Have you ever had to explain equipment operation or safety to a customer or coworker?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '25',
            'display_order' => 7,
            'status' => 1,
        ]);

        $this->options($q7, [
            ['regularly', 'Yes, regularly'],
            ['occasionally', 'Occasionally'],
            ['rarely', 'Rarely'],
            ['never', 'Never'],
        ]);

        /**
         * DRIVING RECORD & LICENSE
         */

        /**
         * 1. Reliable Transportation
         */
        $q8 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'reliable_transportation',
            'question_text' => 'I have reliable transportation',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '100',
            'display_order' => 1,
            'status' => 1,
        ]);

        /**
         * 2. Driver License Type
         */
        $q9 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'license_type',
            'question_text' => 'Do you currently have a valid driver’s license?',
            'required' => 1,
            'answer_type' => 'radio',
            'answer_grid' => '25',
            'display_order' => 2,
            'status' => 1,
        ]);

        $this->options($q9, [
            ['no', 'No'],
            ['regular', 'Regular'],
            ['cdl-b', 'CDL-B'],
            ['cdl-a', 'CDL-A'],
        ]);

        /**
         * 3. Trailer Experience
         */
        $q10 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'trailer_experience',
            'question_text' => 'What type of trailers do you have experience pulling?',
            'sub_text' => 'Check all that apply',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '50',
            'display_order' => 3,
            'status' => 1,
        ]);

        $this->options($q10, [
            ['none', 'None'],
            ['equipment_bumper', 'Equipment Trailer - Bumper Pull'],
            ['camper_bumper', 'Camper - Bumper Pull'],
            ['horse_bumper', 'Horse Trailer - Bumper Pull'],
            ['utility', 'Small Utility - Bumper Pull'],
            ['equipment_gooseneck', 'Equipment Trailer - Gooseneck'],
            ['camper_5th', 'Camper - 5th Wheel'],
            ['horse_gooseneck', 'Horse Trailer - Gooseneck'],
        ]);

        /**
         * 4. License State
         */
        $q11 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'license_state',
            'question_text' => 'License State',
            'required' => 0,
            'answer_type' => 'text',
            'answer_grid' => '50',
            'display_order' => 4,
            'status' => 1,
        ]);

        /**
         * 5. Moving Violations
         */
        $q12 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'moving_violations',
            'question_text' => 'How many moving violations have you had in the last 3 years?',
            'required' => 1,
            'answer_type' => 'number',
            'answer_grid' => '50',
            'display_order' => 5,
            'status' => 1,
        ]);

        /**
         * 6. DUI / DWI
         */
        $q13 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'dui_dwi',
            'question_text' => 'In the last 3 years, have you been charged with or convicted of DUI/DWI?',
            'required' => 1,
            'answer_type' => 'radio',
            'answer_grid' => '50',
            'display_order' => 6,
            'status' => 1,
        ]);

        $this->options($q13, [
            ['yes', 'Yes'],
            ['no', 'No'],
        ]);

        /**
         * 7. License Suspension
         */
        $q14 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'license_suspended',
            'question_text' => 'In the last 3 years, has your license been suspended or revoked?',
            'required' => 1,
            'answer_type' => 'radio',
            'answer_grid' => '50',
            'display_order' => 7,
            'status' => 1,
        ]);

        $this->options($q14, [
            ['yes', 'Yes'],
            ['no', 'No'],
        ]);

        /**
         * 8. Insurable
         */
        $q15 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'can_be_insured',
            'question_text' => 'Are you able to be insured to drive company vehicles?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '50',
            'display_order' => 8,
            'status' => 1,
        ]);

        $this->options($q15, [
            ['yes', 'Yes'],
            ['no', 'No'],
        ]);

        /**
         * 9. Driving Notes
         */
        $q16 = OpportunityQuestion::create([
            'type' => 'driving',
            'question_key' => 'driving_notes',
            'question_text' => 'Driving Notes',
            'required' => 0,
            'answer_type' => 'textarea',
            'answer_grid' => '100',
            'display_order' => 9,
            'status' => 1,
        ]);


        /**
         * COMPUTER LITERACY
         */

        /**
         * 1. Computer Skills (General)
         */
        $q17 = OpportunityQuestion::create([
            'type' => 'computer',
            'question_key' => 'computer_skills',
            'question_text' => 'Please indicate your computer skills',
            'sub_text' => 'Check all that apply',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '100',
            'display_order' => 1,
            'status' => 1,
        ]);

        $this->options($q17, [
            ['computer_basic', 'Comfortable using a computer for internet browsing, basic typing, and navigation'],
            ['mobile', 'Comfortable using a smartphone/tablet for work tasks'],
            ['email', 'Regularly communicate via email'],
            ['attachments', 'Comfortable sending attachments'],
            ['texting', 'Comfortable using texting apps for work'],
            ['word', 'Proficient with Word / Google Docs'],
            ['excel', 'Proficient with Excel / Google Sheets'],
            ['files', 'Can create and organize files/folders'],
            ['custom_apps', 'Use custom work applications'],
            ['learning', 'Comfortable learning new software'],
            ['following_steps', 'Comfortable following system steps'],
            ['detail_oriented', 'Detail-oriented with data entry'],
            ['follow_process', 'Can follow processes without skipping steps'],
            ['double_check', 'I double-check my work'],
        ]);

        /**
         * 2. Accounting / Systems Used
         */
        $q18 = OpportunityQuestion::create([
            'type' => 'computer',
            'question_key' => 'systems_used',
            'question_text' => 'Have you used any of these systems?',
            'sub_text' => 'Check all that apply',
            'required' => 0,
            'answer_type' => 'checkbox',
            'answer_grid' => '50',
            'display_order' => 2,
            'status' => 1,
        ]);

        $this->options($q18, [
            ['quickbooks_entry', 'QuickBooks – Data entry only'],
            ['quickbooks_register', 'QuickBooks – Register management'],
            ['quickbooks_bookkeeping', 'QuickBooks – Full bookkeeping'],
            ['quickbooks_accountants', 'QuickBooks – Accountant level'],
            ['quickbooks_cpa', 'QuickBooks – CPA level'],
            ['freshbooks', 'FreshBooks / Other accounting software'],
            ['pos', 'POS system'],
            ['scheduling', 'Scheduling system'],
            ['inventory', 'Inventory system'],
            ['crm', 'CRM / customer database'],
            ['dispatch', 'Dispatch / routing'],
            ['timeclock', 'Time clock system'],
            ['none', 'None of these'],
        ]);

        /**
         * 3. Overall Computer Skill Level
         */
        $q19 = OpportunityQuestion::create([
            'type' => 'computer',
            'question_key' => 'computer_skill_level',
            'question_text' => 'How would you rate your overall computer ability?',
            'required' => 0,
            'answer_type' => 'radio',
            'answer_grid' => '100',
            'display_order' => 3,
            'status' => 1,
        ]);

        $this->options($q19, [
            ['beginner', 'Beginner'],
            ['intermediate', 'Intermediate'],
            ['advanced', 'Advanced'],
        ]);


    }

    private function options(OpportunityQuestion $question, array $options): void
    {
        foreach ($options as $index => [$value, $label]) {
            OpportunityQuestionOption::create([
                'opportunity_question_id' => $question->id,
                'value' => $value,
                'label' => $label,
                'display_order' => $index + 1,
                'status' => 1,
            ]);
        }
    }
}