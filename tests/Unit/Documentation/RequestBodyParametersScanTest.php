<?php

namespace Tests\Unit\Documentation;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use Tests\TestCase;

/**
 * P3-2A (Phase 3 follow-up to SEC-1): a generic, class-agnostic scan that
 * invokes bodyParameters() on every FormRequest that declares it.
 *
 * bodyParameters() is a documentation-only method invoked solely by Scribe's
 * `scribe:generate` command — never by the real HTTP validation path, and
 * never by any existing test in this suite. SEC-1 shipped a request class
 * whose bodyParameters() called an enum method
 * (OrderTermsStatus::getValues()) that did not exist, and this went
 * undetected until a real Scribe run in production usage. This test would
 * have caught that exact bug, and is intended to catch the same class of bug
 * (any undefined method/property referenced only inside documentation code)
 * in any current or future Request class, not just the one SEC-1 touched.
 * See docs/checklist-system-audit/P3_2_SEC1_STATUS_VALIDATION.md.
 */
class RequestBodyParametersScanTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function requestClassesDeclaringBodyParameters(): array
    {
        $classes = [];

        // NOTE: data providers run before Laravel's TestCase::setUp() bootstraps
        // the application, so app_path()/app() are not available here yet —
        // resolve the directory relative to this test file instead.
        $requestsDir = realpath(dirname(__DIR__, 3) . '/app/Http/Requests');

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($requestsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(
                [$requestsDir . DIRECTORY_SEPARATOR, '/', '.php'],
                ['', '\\', ''],
                $file->getPathname()
            );

            $class = 'App\\Http\\Requests\\' . $relativePath;

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || !$reflection->hasMethod('bodyParameters')) {
                continue;
            }

            // Only classes that declare their own bodyParameters() (not merely
            // inherited) — FormRequest itself does not define one, so any hit
            // here is a real override to check.
            if ($reflection->getMethod('bodyParameters')->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $classes[] = [$class];
        }

        return $classes;
    }

    /**
     * @dataProvider requestClassesDeclaringBodyParameters
     */
    public function test_body_parameters_does_not_throw(string $class): void
    {
        $this->assertTrue(
            is_subclass_of($class, FormRequest::class),
            "{$class} declares bodyParameters() but is not a FormRequest — scan assumption violated, investigate."
        );

        $request = new $class();

        try {
            $result = $request->bodyParameters();
        } catch (\Throwable $e) {
            $this->fail(
                "{$class}::bodyParameters() threw " . get_class($e) . ': ' . $e->getMessage() .
                ' — this is exactly the class of bug found in SEC-1 (an undefined enum method referenced ' .
                'only inside documentation code, never exercised by the real HTTP validation path).'
            );
        }

        $this->assertIsArray($result, "{$class}::bodyParameters() must return an array.");
    }

    public function test_scan_found_at_least_one_class_to_verify_the_scan_itself_is_not_silently_empty(): void
    {
        $this->assertNotEmpty(
            self::requestClassesDeclaringBodyParameters(),
            'The bodyParameters() scan found zero classes — verify app_path(\'Http/Requests\') scanning ' .
            'still resolves correctly; an empty result here would silently stop protecting anything.'
        );
    }
}
