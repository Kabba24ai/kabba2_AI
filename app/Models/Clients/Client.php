<?php

namespace App\Models\Clients;

use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Client extends Model
{
    protected $fillable = [
        'unique_id',
        'name',
        'code',
        'api_url',
        'admin_url',
        'front_url',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->unique_id = ModelHelper::generateUniqueID($model, 'CLT');
        });
    }


     /*
    |--------------------------------------------------------------------------
    | Create Or Update Client By Domain
    |--------------------------------------------------------------------------
    */

    public static function createOrUpdateByDomain(
        string $applicationCode,
        Request $request
    ): self {

        /*
        |--------------------------------------------------------------------------
        | Format Code
        |--------------------------------------------------------------------------
        */

        $applicationCode = strtoupper(
            $applicationCode
        );

        /*
        |--------------------------------------------------------------------------
        | Current Admin URL
        |--------------------------------------------------------------------------
        |
        | Sent From Admin Panel
        |
        */

        $currentAdminUrl = $request->input(
            'current_admin_url'
        );

        Log::info('Client Sync Started', [

            'application_code' => $applicationCode,

            'current_admin_url' => $currentAdminUrl,

            'request_host' => $request->getHost(),

            'request_ip' => $request->ip(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | Validate URL
        |--------------------------------------------------------------------------
        */

        if (!$currentAdminUrl) {

            Log::error('Missing current_admin_url');

            throw new \Exception(
                'Current admin URL is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parse URL
        |--------------------------------------------------------------------------
        */

        $parsedUrl = parse_url($currentAdminUrl);

        $scheme = $parsedUrl['scheme'] ?? 'https';

        $host = $parsedUrl['host'] ?? null;

        if (!$host) {

            Log::error('Invalid current_admin_url', [
                'url' => $currentAdminUrl,
            ]);

            throw new \Exception(
                'Invalid admin URL.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Base Domain
        |--------------------------------------------------------------------------
        */

        $baseDomain = preg_replace(
            '/^admin\./',
            '',
            $host
        );

        Log::info('Base Domain Generated', [
            'base_domain' => $baseDomain,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Generate URLs
        |--------------------------------------------------------------------------
        */

        $adminUrl = $scheme . '://admin.' . $baseDomain . '/';

        $apiUrl = $scheme . '://api.' . $baseDomain . '/api/admin/v1/';

        $frontUrl = $scheme . '://front.' . $baseDomain . '/';

        Log::info('URLs Generated', [

            'admin_url' => $adminUrl,

            'api_url' => $apiUrl,

            'front_url' => $frontUrl,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Find Existing Client
        |--------------------------------------------------------------------------
        */

        $client = self::where(
            'admin_url',
            $adminUrl
        )->first();

        Log::info('Checking Existing Client', [
            'exists' => !!$client,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Existing Client
        |--------------------------------------------------------------------------
        */

        if ($client) {

            $client->update([

                'code' => $applicationCode,

            ]);

            Log::info('Client Updated Successfully', [

                'client_id' => $client->id,

                'unique_id' => $client->unique_id,

                'code' => $applicationCode,

            ]);

            return $client->fresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Create New Client
        |--------------------------------------------------------------------------
        */

        $client = self::create([

            'name' => ucfirst($baseDomain),

            'code' => $applicationCode,

            'admin_url' => $adminUrl,

            'api_url' => $apiUrl,

            'front_url' => $frontUrl,

        ]);

        Log::info('Client Created Successfully', [

            'client_id' => $client->id,

            'unique_id' => $client->unique_id,

            'code' => $applicationCode,

        ]);

        return $client;
    }

}
