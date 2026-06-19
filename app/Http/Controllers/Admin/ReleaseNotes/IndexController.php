<?php

namespace App\Http\Controllers\Admin\ReleaseNotes;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;

class IndexController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $files = $this->latestSlug();

        if (!$files) {
            return redirect()->route('admin.release-notes.show', ['slug' => 'not-found']);
        }

        return redirect()->route('admin.release-notes.show', ['slug' => $files]);
    }

    private function latestSlug(): ?string
    {
        $docsPath = base_path('docs');

        if (!File::isDirectory($docsPath)) {
            return null;
        }

        $files = collect(File::files($docsPath))
            ->filter(fn($f) => $f->getExtension() === 'md')
            ->sortByDesc(fn($f) => $f->getMTime());

        $latest = $files->first();

        return $latest ? pathinfo($latest->getFilename(), PATHINFO_FILENAME) : null;
    }
}
