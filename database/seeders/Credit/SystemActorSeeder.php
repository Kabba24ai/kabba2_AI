<?php

namespace Database\Seeders\Credit;

use App\Services\Credit\SystemActorResolver;
use Illuminate\Database\Seeder;

/**
 * Provisions the isolated System automation actor used as `created_by` for
 * system-generated review tasks (missing-Primary-Billing-Admin fallback).
 *
 * Idempotent and documented: delegates to SystemActorResolver::user(), which
 * firstOrCreates by email with status=Inactive (blocks login + all active-user
 * selectors), no roles, and an unusable password. Safe to run repeatedly.
 */
class SystemActorSeeder extends Seeder
{
    public function run(): void
    {
        SystemActorResolver::user();
    }
}
