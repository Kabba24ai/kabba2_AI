<?php

namespace App\Http\Controllers\Admin\Configurations\New\MailSendSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\New\MailSendSettings\SaveMailSendRequest;
use App\Models\Configurations\Setting;
use Illuminate\Support\Facades\DB;

class SaveMailSendController extends Controller
{
    public function __invoke(SaveMailSendRequest $request)
    {
        $validated = $request->validated();

        // Define allowed setting name mapping
        $map = [
            'mail_mailer' => 'mail_mailer',
            'mail_host' => 'mail_host',
            'mail_port' => 'mail_port',
            'mail_username' => 'mail_username',
            'mail_password' => 'mail_password',
            'mail_encryption' => 'mail_encryption',
            'mail_from_address' => 'mail_from_address',
            'mail_from_name' => 'mail_from_name',
        ];

        DB::transaction(function () use ($validated, $map) {
            foreach ($validated as $input => $value) {
                // only update if input exists in map
                if (isset($map[$input])) {
                    Setting::where('setting_name', $map[$input])
                        ->update(['setting_value' => $value]);
                }
            }
        });

        flash()->success(__('Mail Send Settings updated successfully.'));
        return redirect()->back();
    }
}
