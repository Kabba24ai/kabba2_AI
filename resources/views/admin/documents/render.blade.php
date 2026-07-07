{{--
    Kabba Document Generation Framework — shared print shell.
    Owns branding, headers, footers, disclaimers, page formatting (8.5×11),
    and versioning for every generated document. Document bodies render into
    the slot below; business modules never style documents themselves.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title() }} — {{ $document->brand()['name'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { background: #f1f5f9; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
        }

        /* 8.5in × 11in sheet */
        .sheet {
            width: 8.5in;
            min-height: 11in;
            margin: 0.25in auto;
            padding: 0.55in 0.6in 0.85in;
            background: #fff;
            box-shadow: 0 1px 6px rgba(0,0,0,.15);
            position: relative;
        }

        @page { size: letter portrait; margin: 0.45in 0.5in 0.7in; }
        @media print {
            html, body { background: #fff; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
            .page-footer {
                position: fixed;
                bottom: 0; left: 0; right: 0;
                background: #fff;
            }
            body { margin-bottom: 0.5in; }
            thead { display: table-header-group; }
            tr, .keep-together { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
        }

        /* Branding header */
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111827; padding-bottom: 10px; margin-bottom: 14px; }
        .brand-name { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .brand-web  { font-size: 13px; font-weight: 700; margin-top: 2px; }
        .doc-title  { text-align: right; }
        .doc-title h1 { font-size: 18px; font-weight: 700; }
        .generated-at {
            display: inline-block;
            margin-top: 5px;
            padding: 3px 8px;
            border: 1.5px solid #111827;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Repeated per-page notice */
        .page-footer {
            border-top: 1px solid #d1d5db;
            padding-top: 4px;
            margin-top: 18px;
            font-size: 9px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        /* Final page blocks */
        .final-page h2 { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
        .final-page h3 { font-size: 12px; font-weight: 700; margin: 12px 0 4px; }
        .info-grid { display: flex; flex-wrap: wrap; gap: 18px; }
        .info-grid > div { flex: 1 1 220px; }
        .value-message {
            margin: 14px 0;
            padding: 10px 12px;
            border: 1.5px solid #111827;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .disclaimer { font-size: 10px; color: #374151; white-space: pre-line; margin-top: 10px; }

        .print-bar { max-width: 8.5in; margin: 12px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .print-bar button, .print-bar a {
            font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
            padding: 8px 16px; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none;
        }
        .print-bar button.primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    </style>
</head>
<body>
    <div class="print-bar no-print">
        <a href="javascript:history.back()">Back</a>
        <button type="button" class="primary" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <header class="doc-header">
            <div>
                <div class="brand-name">{{ $document->brand()['name'] }}</div>
                <div class="brand-web">{{ $document->brand()['website'] }}</div>
            </div>
            <div class="doc-title">
                <h1>{{ $document->title() }}</h1>
                <span class="generated-at">Generated {{ $document->generatedAt()->format('M j, Y g:i A') }}</span>
            </div>
        </header>

        {{-- Document body --}}
        @include($document->bodyView())

        {{-- Closing sections flow directly after the body (customer-first
             order: promo message, then general info, then disclaimer) — no
             forced page break, so the document uses as few pages as practical --}}
        @php($info = $document->generalInfo())
        @if ($info || $document->disclaimer())
            <section class="final-page" style="margin-top: 20px;">
                @if (!empty($info['value_message']))
                    <p class="value-message keep-together">{{ $info['value_message'] }}</p>
                @endif

                @if ($info)
                    <div class="keep-together">
                        <h2>General Information</h2>
                        <div class="info-grid">
                            <div>
                                @if (!empty($info['phone']))
                                    <h3>{{ $info['phone_label'] ?? 'Phone' }}</h3>
                                    <p>{{ $info['phone'] }}</p>
                                @endif
                                <h3>Website</h3>
                                <p>{{ $document->brand()['website'] }}</p>
                                @if (!empty($info['hours']))
                                    <h3>Hours</h3>
                                    @foreach ($info['hours'] as $line)
                                        <p>{{ $line }}</p>
                                    @endforeach
                                @endif
                            </div>
                            @if (!empty($info['stores']) && $info['stores']->isNotEmpty())
                                <div>
                                    <h3>Store Locations</h3>
                                    @foreach ($info['stores'] as $store)
                                        <p style="margin-bottom: 6px;">
                                            <strong>{{ $store->store_name }}</strong><br>
                                            @if ($store->address){{ $store->address }}@if ($store->city), {{ $store->city }}@endif @if ($store->zip_code){{ $store->zip_code }}@endif<br>@endif
                                            @if ($store->phone){{ $store->phone }}@endif
                                        </p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($document->disclaimer())
                    <h3>Important Pricing Information</h3>
                    <p class="disclaimer">{{ $document->disclaimer() }}</p>
                @endif
            </section>
        @endif

        {{-- Per-page footer: governing notice + versioning --}}
        <footer class="page-footer">
            <span>{{ $document->pageNotice() ?? $document->brand()['website'] }}</span>
            <span>Generated {{ $document->generatedAt()->format('m/d/Y g:i A') }} · Doc {{ $document->key() }} v{{ $document->version() }}</span>
        </footer>
    </div>
</body>
</html>
