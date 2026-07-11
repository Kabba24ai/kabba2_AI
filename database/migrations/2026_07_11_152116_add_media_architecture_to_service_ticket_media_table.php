<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_ticket_media', function (Blueprint $table) {
            // Links a media row to the specific record it documents (e.g. a
            // ServiceTicketNote). Nullable: ticket-level media (existing
            // rows, and categories like Complaint Evidence that are shown
            // ticket-wide rather than per-record) simply leaves this unset.
            $table->nullableMorphs('attachable');

            // Coarse ticket section (Complaint/Notes/Diagnostic/Repair/...),
            // distinct from the finer-grained `category`. Drives dedicated
            // storage folder layout and the default retention class.
            $table->string('workflow_stage')->nullable()->after('category');

            // Independent of workflow_stage so a future retention policy
            // can diverge per class without another schema change. All
            // classes currently behave identically (kept indefinitely).
            $table->string('retention_class')->nullable()->after('workflow_stage');

            // Which filesystem disk file_path resolves against. Existing
            // rows live on the shared public_asset disk; new uploads go to
            // the dedicated service_media disk (see config/filesystems.php).
            $table->string('disk')->default('public_asset')->after('file_path');
        });

        // Backfill existing rows so every record carries the new metadata,
        // without moving any physical files (they stay on public_asset).
        DB::table('service_ticket_media')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                [$stage, $retention] = match ($row->category) {
                    'complaint_evidence', 'customer_damage_documentation' => ['complaint', 'service_evidence'],
                    'note'                                                => ['notes', 'service_note'],
                    'before_repair', 'during_repair', 'after_repair'      => ['repair', 'repair'],
                    'warranty_documentation'                              => ['warranty', 'warranty'],
                    default                                               => ['general', 'general'],
                };

                DB::table('service_ticket_media')->where('id', $row->id)->update([
                    'workflow_stage'  => $stage,
                    'retention_class' => $retention,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_ticket_media', function (Blueprint $table) {
            // dropMorphs (not dropColumn) so the composite index is dropped
            // before the columns it references — plain dropColumn errors on
            // SQLite when a column is still part of an index.
            $table->dropMorphs('attachable');
            $table->dropColumn(['workflow_stage', 'retention_class', 'disk']);
        });
    }
};
