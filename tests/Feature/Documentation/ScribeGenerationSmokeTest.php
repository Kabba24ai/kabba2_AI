<?php

namespace Tests\Feature\Documentation;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * P3-2A (Phase 3 follow-up to SEC-1): an end-to-end smoke test that actually
 * runs `scribe:generate` across the whole app.
 *
 * This is the exact command that surfaced the original SEC-1 bug in real
 * usage — `OrderTermsStatus::getValues()` did not exist, and nothing in the
 * automated test suite ever exercised bodyParameters(), only Scribe's own
 * generation pass did. Running the real command here (not a reflection-based
 * approximation) is the closest possible guarantee that a documentation
 * build failure is caught in CI before it reaches a real environment.
 *
 * Output is redirected to a scratch directory so this test does not disturb
 * any developer's locally-generated public/docs; both public/docs and
 * .scribe are already gitignored, so no tracked files are affected either
 * way. See docs/checklist-system-audit/P3_2_SEC1_STATUS_VALIDATION.md.
 */
class ScribeGenerationSmokeTest extends TestCase
{
    public function test_scribe_generate_completes_successfully_across_the_whole_app(): void
    {
        $scratchOutputPath = storage_path('framework/testing/scribe-smoke-test-docs');

        config(['scribe.static.output_path' => 'storage/framework/testing/scribe-smoke-test-docs']);

        try {
            $exitCode = Artisan::call('scribe:generate', ['--force' => true]);

            $this->assertSame(
                0,
                $exitCode,
                "scribe:generate exited with code {$exitCode}. Output:\n" . Artisan::output()
            );
        } finally {
            if (File::isDirectory($scratchOutputPath)) {
                File::deleteDirectory($scratchOutputPath);
            }
        }
    }
}
