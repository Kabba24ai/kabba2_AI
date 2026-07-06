<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Industry Presets for the Customer Price List move from static config
 * (config/price_list_presets.php) to admin-manageable tables. Presets stay
 * a selection convenience only — generation is always driven by the final
 * category ids the staff member submits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('price_list_preset_product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_preset_id')
                ->constrained(table: 'price_list_presets', indexName: 'plp_category_preset_fk')
                ->cascadeOnDelete();
            $table->foreignId('product_category_id')
                ->constrained(table: 'product_categories', indexName: 'plp_category_category_fk')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['price_list_preset_id', 'product_category_id'], 'plp_preset_category_unique');
        });

        $this->seedConfigPresets();
    }

    /**
     * One-time carry-over of the Phase 1 config presets so live environments
     * keep their working presets. Category assignment reuses the old slug /
     * slug-keyword matching against the categories present at migration time;
     * unmatched presets are created empty for admins to fill in.
     */
    private function seedConfigPresets(): void
    {
        $presets = [
            [
                'name'          => 'Dirt Work / Grading',
                'description'   => 'Skid steers, excavators, dozers, rollers & compaction, attachments',
                'slugs'         => ['skid-steers', 'excavators', 'dozers', 'rollers', 'compaction'],
                'slug_keywords' => ['skid-steer', 'excavator', 'dozer', 'roller', 'compact', 'attachment'],
            ],
            [
                'name'          => 'Tree Work / Arborist',
                'description'   => 'Chippers, stump grinders, mini skids, brush cutters, trailers, attachments',
                'slugs'         => ['wood-chippers', 'stump-grinders', 'mini-skids', 'brush-cutters', 'trailers'],
                'slug_keywords' => ['chipper', 'stump', 'mini-skid', 'brush', 'trailer', 'attachment'],
            ],
            [
                'name'          => 'Plumbing / Utility',
                'description'   => 'Trenchers, mini excavators, compaction, concrete saws, pumps, attachments',
                'slugs'         => ['trenchers', 'mini-excavators', 'compaction', 'concrete-saws', 'pumps'],
                'slug_keywords' => ['trencher', 'mini-excavator', 'compact', 'concrete-saw', 'saw', 'pump', 'attachment'],
            ],
            [
                'name'          => 'Home Building / Construction',
                'description'   => 'Skid steers, excavators, telehandlers, lifts, compaction, attachments',
                'slugs'         => ['skid-steers', 'excavators', 'telehandlers', 'lifts', 'compaction'],
                'slug_keywords' => ['skid-steer', 'excavator', 'telehandler', 'lift', 'compact', 'attachment'],
            ],
        ];

        $categories = DB::table('product_categories')->get(['id', 'slug']);

        foreach ($presets as $preset) {
            $presetId = DB::table('price_list_presets')->insertGetId([
                'name'        => $preset['name'],
                'description' => $preset['description'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $matched = $categories->filter(function ($category) use ($preset) {
                $slug = (string) $category->slug;

                if (in_array($slug, $preset['slugs'], true)) {
                    return true;
                }

                foreach ($preset['slug_keywords'] as $keyword) {
                    if ($keyword !== '' && str_contains($slug, $keyword)) {
                        return true;
                    }
                }

                return false;
            });

            foreach ($matched as $category) {
                DB::table('price_list_preset_product_category')->insert([
                    'price_list_preset_id' => $presetId,
                    'product_category_id'  => $category->id,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_preset_product_category');
        Schema::dropIfExists('price_list_presets');
    }
};
