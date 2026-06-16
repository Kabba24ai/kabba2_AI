{{--
    AI Page Metadata Partial
    ───────────────────────
    Outputs JSON-LD structured data into <head> for AI search engine discoverability.
    Called via @push('schema') from product and category page templates.

    Expected variables:
      $pageType  — 'product' | 'category' | 'home'
      $pageModel — Product | ProductCategory | null
--}}
@php
    use App\Models\AIVisibility\AiPageMetadata;
    use App\Services\AIVisibility\AIPageMetadataGenerator;
    use App\Services\AIVisibility\SchemaBuilder;

    $metadata = null;

    try {
        if ($pageType === 'product' && !empty($pageModel?->id)) {
            $metadata = AiPageMetadata::findForProduct($pageModel->id);

            if (!$metadata) {
                $generator = app(AIPageMetadataGenerator::class);
                $metadata  = $generator->generateForProduct($pageModel);
            }
        } elseif ($pageType === 'category' && !empty($pageModel?->id)) {
            $metadata = AiPageMetadata::findForCategory($pageModel->id);

            if (!$metadata) {
                $generator = app(AIPageMetadataGenerator::class);
                $metadata  = $generator->generateForCategory($pageModel);
            }
        } elseif ($pageType === 'home') {
            $metadata = AiPageMetadata::findForHome();

            if (!$metadata) {
                $generator = app(AIPageMetadataGenerator::class);
                $metadata  = $generator->generateForHome();
            }
        }
    } catch (\Throwable $e) {
        // Never break the page if AI layer fails
        $metadata = null;
    }
@endphp

@if (!empty($metadata?->schema_json))
    @foreach ($metadata->schema_json as $schema)
        @if (!empty($schema))
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
        @endif
    @endforeach
@endif

{{-- AI-readable semantic summary — inside a hidden accessible element, not spammy hidden text --}}
@if (!empty($metadata?->ai_summary))
    <meta name="ai-summary" content="{{ $metadata->ai_summary }}">
@endif
@if (!empty($metadata?->ai_service_type))
    <meta name="ai-service-type" content="{{ $metadata->ai_service_type }}">
@endif
@if (!empty($metadata?->ai_keywords))
    <meta name="ai-keywords" content="{{ implode(', ', $metadata->ai_keywords) }}">
@endif
