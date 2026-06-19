<?php

namespace App\Http\Controllers\Admin\ReleaseNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ShowController extends Controller
{
    public function __invoke(string $slug): Response
    {
        // Guard against path traversal
        if (!preg_match('/^[\w\-]+$/', $slug)) {
            abort(404);
        }

        $docsPath = base_path('docs');
        $filePath = $docsPath . DIRECTORY_SEPARATOR . $slug . '.md';

        $allFiles = $this->allDocFiles($docsPath);
        $content  = null;
        $notFound = false;

        if (File::exists($filePath)) {
            $raw     = File::get($filePath);
            $content = Str::markdown($raw, [
                'html_input'         => 'escape',
                'allow_unsafe_links' => false,
            ]);
        } else {
            $notFound = true;
        }

        return response()->view('admin.release_notes.index', [
            'allFiles'  => $allFiles,
            'slug'      => $slug,
            'content'   => $content,
            'notFound'  => $notFound,
        ]);
    }

    private function allDocFiles(string $docsPath): \Illuminate\Support\Collection
    {
        if (!File::isDirectory($docsPath)) {
            return collect();
        }

        return collect(File::files($docsPath))
            ->filter(fn($f) => $f->getExtension() === 'md' && str_starts_with($f->getFilename(), 'new-features-improvements-'))
            ->sortByDesc(fn($f) => $f->getMTime())
            ->map(function ($file) {
                $slug  = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $label = $this->labelFromSlug($slug);

                return [
                    'slug'     => $slug,
                    'label'    => $label,
                    'date'     => date('M j, Y', $file->getMTime()),
                    'filename' => $file->getFilename(),
                ];
            })
            ->values();
    }

    private function labelFromSlug(string $slug): string
    {
        // "new-features-improvements-2026-06-19" → "New Features & Improvements"
        // Strip trailing date (YYYY-MM-DD) if present
        $label = preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $slug);
        $label = str_replace('-', ' ', $label);
        $label = ucwords($label);
        $label = str_ireplace('And', '&', $label);

        return $label;
    }
}
