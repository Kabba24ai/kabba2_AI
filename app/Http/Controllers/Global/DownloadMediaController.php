<?php

namespace App\Http\Controllers\Global;

use App\Http\Controllers\Controller;
use App\Models\Global\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class DownloadMediaController extends Controller
{
    public function download(string $unique_id)
    {
        try {
            $decryptedId = Crypt::decryptString($unique_id);
        } catch (\Exception $e) {
            abort(404);
        }

        $media = Media::where('unique_id', $decryptedId)->firstOrFail();
        $disk = $media->asset_type === 'Secure Asset' ? 'secure_asset' : 'public_asset';
        $filePath = $media->getFilePath();

        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404);
        }

        return Storage::disk($disk)->download($filePath, $media->original_file_name ?? $media->file_name);
    }

    public function signedDownload(Request $request, string $unique_id)
    {
        $sessionId = $request->query('session');
        if ($sessionId && $sessionId !== $request->session()->getId()) {
            abort(403);
        }

        $media = Media::where('unique_id', $unique_id)->firstOrFail();
        $disk = $media->asset_type === 'Secure Asset' ? 'secure_asset' : 'public_asset';
        $filePath = $media->getFilePath();

        if (!Storage::disk($disk)->exists($filePath)) {
            abort(404);
        }

        return Storage::disk($disk)->download($filePath, $media->original_file_name ?? $media->file_name);
    }
}
