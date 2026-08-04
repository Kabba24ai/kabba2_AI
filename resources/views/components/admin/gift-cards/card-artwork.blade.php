{{--
    APPROVED GIFT CARD ARTWORK — reproduction of the signed-off design.

    Every dimension, colour and type size below comes from the approved
    design. They are not tuning knobs. The only things that change at render
    time are the merge fields (branding from Company Settings, values from
    the card) and the accent colour, which the design itself exposes.

    Do not "improve" the hierarchy, drop a field, or re-flow this for small
    screens. The whole card scales as one piece via `transform` precisely so
    that it stays the approved design at every size. See CardArtwork.php.
--}}

@once
    @push('css')
        {{--
            Archivo (text) and Instrument Serif (display) are the approved
            faces. The fallbacks are listed so a card still renders sanely if
            the font host is unreachable — the layout holds, only the
            lettering degrades.
        --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap"
            rel="stylesheet">

        <style>
            /* ── Scaling frame ───────────────────────────────────────────
               The artwork is authored once at 1012 × 638 and scaled whole.
               The frame reserves the scaled footprint so surrounding layout
               is not disturbed by the transform. */
            .gcx-frame        { position: relative; overflow: hidden; flex: none; }
            .gcx-scale        { transform-origin: top left; }

            /* ── Card body ───────────────────────────────────────────────
               CR80: 3.375in × 2.125in = 1.588. Authored 1012 × 638 = 1.586. */
            .gcx-card {
                width: 1012px;
                height: 638px;
                box-sizing: border-box;
                position: relative;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border-radius: 34px;
                background: #f5f1e9;                       /* cream body    */
                border: 4px solid var(--gcx-accent);       /* orange border */
                box-shadow: 0 30px 60px -30px rgba(0, 0, 0, .35);
                font-family: 'Archivo', Helvetica, Arial, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            /* Hairline inset, stopping above the band. */
            .gcx-hairline {
                position: absolute;
                top: 12px; left: 12px; right: 12px; bottom: 103px;
                border: 1px solid rgba(23, 21, 15, .13);
                border-radius: 22px;
                pointer-events: none;
            }

            /* ── Header: rule — tagline — rule ───────────────────────────── */
            .gcx-header  { padding: 30px 76px 0; display: flex; align-items: center; gap: 20px; position: relative; }
            .gcx-rule    { flex: 1; height: 1px; background: var(--gcx-accent); opacity: .45; }
            .gcx-tagline {
                font-family: 'Instrument Serif', Georgia, serif;
                font-style: italic;
                font-size: 31px;
                line-height: 1.2;
                color: #c9762a;
                text-align: center;
                text-wrap: pretty;
            }

            /* ── Body: logo | rule | value ───────────────────────────────── */
            .gcx-body { flex: 1; display: flex; align-items: center; padding: 4px 56px 22px; box-sizing: border-box; position: relative; }

            /* LOGO CLEAR ZONE — fixed 300 × 250. Swap the mark, nothing reflows. */
            .gcx-logo-zone { width: 300px; height: 250px; flex: none; display: flex; align-items: center; justify-content: center; }
            .gcx-logo-zone img { max-width: 100%; max-height: 100%; object-fit: contain; }
            .gcx-logo-fallback {
                font-family: 'Instrument Serif', Georgia, serif;
                font-size: 40px; line-height: 1.15; text-align: center;
                color: #17150f; padding: 0 8px;
            }

            .gcx-divider { width: 1px; height: 200px; background: rgba(23, 21, 15, .12); margin: 0 44px; flex: none; }

            .gcx-value   { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; min-width: 0; }
            .gcx-eyebrow { font-size: 15px; letter-spacing: .4em; text-transform: uppercase; color: #a2988a; font-weight: 600; }
            .gcx-amount  { display: flex; align-items: baseline; gap: 4px; color: #17150f; }
            .gcx-currency {
                font-family: 'Instrument Serif', Georgia, serif;
                font-size: 62px; line-height: 1;
                color: var(--gcx-accent);
                align-self: flex-start; margin-top: 26px;
            }
            .gcx-figure {
                font-family: 'Instrument Serif', Georgia, serif;
                font-size: 178px; line-height: .86; letter-spacing: -.02em;
            }
            .gcx-message {
                font-family: 'Instrument Serif', Georgia, serif;
                font-style: italic; font-size: 24px; line-height: 1.3;
                color: #7d766b; text-align: center; max-width: 520px;
                text-wrap: pretty;
            }

            /* ── Band: recipient / sender | card number / support ─────────── */
            .gcx-band {
                background: var(--gcx-accent);
                padding: 18px 56px;
                display: flex; justify-content: space-between; align-items: center; gap: 40px;
            }
            .gcx-band-people { display: flex; gap: 40px; min-width: 0; }
            .gcx-band-field  { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
            .gcx-band-label  { font-size: 11px; letter-spacing: .22em; text-transform: uppercase; color: rgba(30, 18, 6, .6); font-weight: 700; }
            .gcx-band-value  { font-size: 25px; font-weight: 600; color: #17120a; line-height: 1.15; white-space: nowrap; }

            .gcx-band-right  { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex: none; }
            .gcx-chip {
                display: flex; align-items: baseline; gap: 9px; white-space: nowrap;
                background: rgba(255, 252, 246, .85); border-radius: 8px; padding: 6px 13px;
            }
            .gcx-chip-label  { font-size: 10px; letter-spacing: .22em; text-transform: uppercase; color: #9b8f7c; font-weight: 700; }
            .gcx-chip-number { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 23px; letter-spacing: .1em; color: #17120a; font-weight: 600; }
            .gcx-support     { font-size: 16px; font-weight: 500; color: rgba(23, 18, 10, .85); white-space: nowrap; }
        </style>
    @endpush
@endonce

<div {{ $attributes->merge(['class' => 'gcx-frame']) }}
     style="width: {{ $width() }}px; height: {{ $height() }}px;">
    <div class="gcx-scale" style="transform: scale({{ $scale }});">
        <div class="gcx-card" style="--gcx-accent: {{ $accentColor }};">

            <div class="gcx-hairline"></div>

            <div class="gcx-header">
                <div class="gcx-rule"></div>
                <div class="gcx-tagline">{{ $tagline }}</div>
                <div class="gcx-rule"></div>
            </div>

            <div class="gcx-body">
                {{-- LOGO CLEAR ZONE — 300 × 250, fixed --}}
                <div class="gcx-logo-zone">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}">
                    @else
                        {{-- No branding logo configured: the zone still holds
                             its exact footprint so the layout is identical. --}}
                        <div class="gcx-logo-fallback">{{ $brandName }}</div>
                    @endif
                </div>

                <div class="gcx-divider"></div>

                <div class="gcx-value">
                    <div class="gcx-eyebrow">Gift Card</div>
                    <div class="gcx-amount">
                        <span class="gcx-currency">$</span>
                        <span class="gcx-figure">{{ $amount }}</span>
                    </div>
                    @if (filled($message))
                        <div class="gcx-message">{{ $message }}</div>
                    @endif
                </div>
            </div>

            <div class="gcx-band">
                <div class="gcx-band-people">
                    <div class="gcx-band-field">
                        <div class="gcx-band-label">For</div>
                        <div class="gcx-band-value">{{ $recipient }}</div>
                    </div>
                    <div class="gcx-band-field">
                        <div class="gcx-band-label">From</div>
                        <div class="gcx-band-value">{{ $sender }}</div>
                    </div>
                </div>

                <div class="gcx-band-right">
                    <div class="gcx-chip">
                        <div class="gcx-chip-label">Card No.</div>
                        <div class="gcx-chip-number">{{ $cardNumber }}</div>
                    </div>
                    @if (filled($support))
                        <div class="gcx-support">{{ $support }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
