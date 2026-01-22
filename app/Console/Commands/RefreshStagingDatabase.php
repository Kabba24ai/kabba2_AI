<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'db:refresh-staging', description: 'Replace the current database with the kabba_staging.sql dump.')]
class RefreshStagingDatabase extends Command
{
    /**
     * Artisan signature definition.
     */
    protected $signature = 'db:refresh-staging';

    /**
     * Command description for list output.
     */
    protected $description = 'Replace the current database with the kabba_staging.sql dump when enabled via REFRESH_STAGING_ENABLED.';

    public function handle(): int
    {
        $refreshEnabled = filter_var(env('REFRESH_STAGING_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

        if (! $refreshEnabled) {
            $this->error('Set REFRESH_STAGING_ENABLED=true in your .env file to run this command.');

            return self::FAILURE;
        }

        $sqlPath = database_path('seeders/kabba_staging.sql');

        if (! File::exists($sqlPath)) {
            $this->error("SQL dump not found at {$sqlPath}.");

            return self::FAILURE;
        }

        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        $this->info("Clearing {$database}...");
        if (! $this->clearTables($connection, $database)) {
            return self::FAILURE;
        }

        $this->info('Importing kabba_staging.sql...');

        try {
            $connection->unprepared(File::get($sqlPath));
        } catch (Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Database refreshed from kabba_staging.sql.');

        return self::SUCCESS;
    }

    private function clearTables(Connection $connection, string $database): bool
    {
        try {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');

            $tables = $connection->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tableKey = 'Tables_in_' . $database;

            foreach ($tables as $table) {
                $tableArray = (array) $table;
                $tableName = $tableArray[$tableKey] ?? reset($tableArray); // accommodates different column aliases

                if (! empty($tableName)) {
                    $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
                }
            }
        } catch (Throwable $e) {
            $this->error('Unable to drop tables: ' . $e->getMessage());

            return false;
        } finally {
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return true;
    }
}
