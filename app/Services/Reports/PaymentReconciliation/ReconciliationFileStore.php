<?php

namespace App\Services\Reports\PaymentReconciliation;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Transient server-side hold for an uploaded Authorize.Net settlement file, so
 * the reconciliation wizard can filter and export results without forcing the
 * operator to re-upload on every action (browsers do not resend a file input
 * after a page render). Stored on the private local disk under a random token;
 * the token round-trips through a hidden form field.
 */
class ReconciliationFileStore
{
    private const DIR = 'anet-reconciliation';

    /** Persist raw settlement-file contents; returns the retrieval token. */
    public function store(string $contents): string
    {
        $token = (string) Str::uuid();
        Storage::disk('local')->put(self::DIR . "/{$token}.txt", $contents);

        return $token;
    }

    /** Fetch previously-stored contents by token; null if unknown/invalid. */
    public function retrieve(?string $token): ?string
    {
        // Strict UUID shape — prevents any path traversal via the token field.
        if (!$token || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
            return null;
        }

        $path = self::DIR . "/{$token}.txt";

        return Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;
    }
}
