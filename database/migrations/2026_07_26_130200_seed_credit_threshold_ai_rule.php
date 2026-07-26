<?php

use App\Models\Ai\AiRule;
use Database\Seeders\Credit\AiRuleSeeder;
use Database\Seeders\Credit\SystemActorSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Data migration: provision the isolated System actor and seed the first
 * approved AI rule (credit_threshold_exception_review) on deploy.
 *
 * The deploy pipeline runs `migrate --force` but NOT `db:seed`, so this bridges
 * that gap idempotently. It is a no-op under tests (which seed their own
 * fixtures) so it never perturbs suite expectations or user/table counts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        (new SystemActorSeeder())->run();
        (new AiRuleSeeder())->run();
    }

    public function down(): void
    {
        AiRule::where('rule_key', 'credit_threshold_exception_review')->forceDelete();
    }
};
