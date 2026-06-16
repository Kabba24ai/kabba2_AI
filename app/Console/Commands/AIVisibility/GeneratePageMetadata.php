<?php

namespace App\Console\Commands\AIVisibility;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Services\AIVisibility\AIPageMetadataGenerator;
use Illuminate\Console\Command;

class GeneratePageMetadata extends Command
{
    protected $signature = 'ai:generate-page-metadata
                            {--type=        : Filter by page type: product or category}
                            {--id=          : Generate for a specific record ID}
                            {--all          : Generate for all published products and categories}
                            {--force        : Regenerate even if source hash is unchanged}';

    protected $description = 'Generate or regenerate AI page metadata (JSON-LD schema) for products and categories.';

    public function handle(AIPageMetadataGenerator $generator): int
    {
        $type  = $this->option('type');
        $id    = $this->option('id');
        $all   = $this->option('all');
        $force = $this->option('force');

        if (!$type && !$all) {
            $this->error('Provide --type=product|category or --all');
            return self::FAILURE;
        }

        if ($force) {
            $this->clearHashes($type, $id);
        }

        $generated = 0;
        $skipped   = 0;
        $failed    = 0;

        if ($all || $type === 'product') {
            [$g, $s, $f] = $this->runProducts($generator, $id);
            $generated += $g; $skipped += $s; $failed += $f;
        }

        if ($all || $type === 'category') {
            [$g, $s, $f] = $this->runCategories($generator, $id);
            $generated += $g; $skipped += $s; $failed += $f;
        }

        $this->newLine();
        $this->table(
            ['Generated', 'Skipped (unchanged)', 'Failed'],
            [[$generated, $skipped, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runProducts(AIPageMetadataGenerator $generator, ?string $id): array
    {
        $query = Product::published()
            ->with(['media', 'mediaChildren.media', 'categories', 'relatedProducts']);

        if ($id) {
            $query->where('id', $id);
        }

        $generated = $skipped = $failed = 0;

        $this->info('Generating metadata for products...');
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->chunk(50, function ($products) use ($generator, &$generated, &$skipped, &$failed, $bar) {
            foreach ($products as $product) {
                $before = \App\Models\AIVisibility\AiPageMetadata::findForProduct($product->id);
                $result = $generator->generateForProduct($product);
                $after  = $result ? \App\Models\AIVisibility\AiPageMetadata::findForProduct($product->id) : null;

                if (!$result) {
                    $failed++;
                } elseif ($before && $before->generated_from_hash === $after?->generated_from_hash && $before->last_generated_at == $after?->last_generated_at) {
                    $skipped++;
                } else {
                    $generated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        return [$generated, $skipped, $failed];
    }

    private function runCategories(AIPageMetadataGenerator $generator, ?string $id): array
    {
        $query = ProductCategory::published()
            ->with(['media', 'publishedProducts.media', 'parentCategory']);

        if ($id) {
            $query->where('id', $id);
        }

        $generated = $skipped = $failed = 0;

        $this->info('Generating metadata for categories...');
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $query->chunk(50, function ($categories) use ($generator, &$generated, &$skipped, &$failed, $bar) {
            foreach ($categories as $category) {
                $before = \App\Models\AIVisibility\AiPageMetadata::findForCategory($category->id);
                $result = $generator->generateForCategory($category);
                $after  = $result ? \App\Models\AIVisibility\AiPageMetadata::findForCategory($category->id) : null;

                if (!$result) {
                    $failed++;
                } elseif ($before && $before->generated_from_hash === $after?->generated_from_hash && $before->last_generated_at == $after?->last_generated_at) {
                    $skipped++;
                } else {
                    $generated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        return [$generated, $skipped, $failed];
    }

    private function clearHashes(?string $type, ?string $id): void
    {
        $query = \App\Models\AIVisibility\AiPageMetadata::query();

        if ($type) {
            $query->where('page_type', $type);
        }
        if ($id) {
            $query->where('page_id', $id);
        }

        $query->update(['generated_from_hash' => null]);
        $this->line('Hashes cleared — full regeneration will run.');
    }
}
