<?php

namespace Database\Seeders\TermsAndConditions;

use Illuminate\Database\Seeder;
use App\Models\TermsAndConditions\Terms;
use Illuminate\Support\Str;

class TermsSeeder extends Seeder
{
    /**
     * Seed a global Terms & Conditions record.
     */
    public function run(): void
    {
        $terms =[
                'title' => 'Global Terms & Conditions',
                'content' => '<p><span style="font-size: 10pt;">This Rental Contract (“Agreement”) is made and entered into between Development 360, inc</span> <span style="font-size: 10pt; font-family: Calibri, sans-serif;">, DBA Rent ‘n King, </span> <span style="font-size: 10pt;">(“Owner”) with its principal place of business located at </span> <span style="color: #000000;"><span style="font-size: 10pt;">4385 SR-48, Charlotte, TN 37036 and/or 10296 Highway 46, Bon Aqua, TN 37025, </span> </span><span style="font-size: 10pt;">and (“Renter”).</span></p>',
                'signature_block' => "<p>IN WITNESS WHEREOF, the parties have executed this Agreement as of the date first written above.</p>
<p>OWNER: <br>Development 360, inc  <br>DBA Rent 'n King</p>
<p>RENTER: <br>By: [customer_name][/customer_name]<br>Signature: [customer_signature][/customer_signature]</p>",
                'is_global' => 'Yes',
                'status' => 'Published',
                'seo_title' => '',
                'seo_description' => ''
            ];

        if (!Terms::global()->first()) {
            Terms::create($terms);
        }
    }
}
