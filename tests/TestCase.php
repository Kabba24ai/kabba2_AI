<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot this worktree explicitly.
     *
     * Laravel's default testing bootstrap uses Application::inferBasePath(),
     * which resolves to /home/rentnking/public_html on this server and would
     * otherwise load the production application and production configuration.
     */
    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Databases that destructive test operations (migrate:fresh, migrations,
     * truncation) are ever allowed to touch. Exact names, deliberately NOT a
     * substring rule — a stray database named e.g. 'rentnking_testing_copy'
     * must not pass. Add new entries only for databases intentionally
     * provisioned for this repository's test suite.
     */
    private const APPROVED_TEST_DATABASES = [
        'rentnking_kabba_testing',
        'rc_kabba_testing',
    ];

    /**
     * Guard placement note — this MUST stay in setUpTraits(), not in
     * beforeRefreshingDatabase(). Every test class declares
     * `use RefreshDatabase;` directly, and a method inserted by a trait
     * overrides the same-named method inherited from a parent class, so a
     * beforeRefreshingDatabase() defined here would be silently shadowed by
     * the trait's empty stub in every test class and never execute.
     * setUpTraits() is defined on the framework base (via
     * InteractsWithTestCaseLifecycle), which no test class re-uses, so this
     * override genuinely runs — after the application boots, and before
     * refreshDatabase() can invoke migrate:fresh.
     */
    protected function setUpTraits()
    {
        // OrderFinancialActivity memoizes per PHP request; test processes
        // run many "requests" with recycled auto-increment ids, so the memo
        // must be flushed per test or a prior test's verdict would leak
        // onto an unrelated order that happens to share its id.
        \App\Services\Orders\OrderFinancialActivity::flushMemo();

        $uses = array_flip(class_uses_recursive(static::class));

        if (
            isset($uses[RefreshDatabase::class])
            || isset($uses[DatabaseMigrations::class])
            || isset($uses[DatabaseTruncation::class])
        ) {
            $this->guardApprovedTestingDatabase();
        }

        return parent::setUpTraits();
    }

    /**
     * Fail-fast refusal to run destructive database work outside a verified
     * testing environment. Exists because a real incident on the test server
     * bootstrapped PHPUnit with the production .env (APP_ENV=production,
     * database rentnking_kabba2): CSRF rejected every test POST, unscoped
     * queries read historical production rows, and only the absence of the
     * testing schema prevented migrate:fresh from destroying production
     * tables. This guard's job is to refuse unsafe execution loudly — never
     * to repair or override the environment, and it must not be weakened to
     * make a misconfigured run proceed.
     */
    private function guardApprovedTestingDatabase(): void
    {
        $environment = app()->environment();
        $connection = config('database.default');
        $configuredDatabase = config("database.connections.{$connection}.database");

        if (
            $environment !== 'testing'
            || ! is_string($configuredDatabase)
            || ! in_array($configuredDatabase, self::APPROVED_TEST_DATABASES, true)
        ) {
            throw new RuntimeException(
                'REFUSING TO RUN DATABASE TESTS: '
                . "environment='{$environment}', "
                . "connection='{$connection}', "
                . "configured_database='" . var_export($configuredDatabase, true) . "'. "
                . 'Tests require APP_ENV=testing and an explicitly approved testing database. '
                . 'Correct the environment; do not weaken this guard.'
            );
        }

        // Second level: the name the connection actually resolves (catches
        // connection-level overrides that diverge from the configured value).
        $resolvedDatabase = DB::connection($connection)->getDatabaseName();

        if (! in_array($resolvedDatabase, self::APPROVED_TEST_DATABASES, true)) {
            throw new RuntimeException(
                'REFUSING TO RUN DATABASE TESTS: '
                . "the resolved database '{$resolvedDatabase}' is not an approved testing database. "
                . 'Correct the database configuration; do not weaken this guard.'
            );
        }
    }
}
