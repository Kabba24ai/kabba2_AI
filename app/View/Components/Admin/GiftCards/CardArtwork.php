<?php

namespace App\View\Components\Admin\GiftCards;

use App\Helpers\ConfigurationHelper;
use App\Models\Configurations\Setting;
use App\Models\GiftCards\GiftCard;
use Illuminate\View\Component;

/**
 * The approved gift card artwork, populated from live data.
 *
 * ── THIS IS A REPRODUCTION, NOT A DESIGN ──────────────────────────────────
 *
 * The layout, proportions, palette, type scale and hierarchy come from the
 * approved design and are not this component's to change. What this class
 * does is resolve the MERGE FIELDS — branding from Company Settings, values
 * from the card itself — and hand them to the Blade view, which contains the
 * artwork verbatim.
 *
 * If the brand changes, Company Settings changes; nobody edits the artwork.
 * If a card's value changes, the ledger changes; nobody edits the artwork.
 * Anything that would require editing the artwork is a design decision, and
 * design decisions are approved elsewhere.
 *
 * ── WHY THE CARD IS BUILT AT A FIXED PIXEL SIZE ───────────────────────────
 *
 * CR80 is a physical standard: 3.375in × 2.125in, a 1.588 ratio. The artwork
 * is authored once at 1012 × 638 px (1.586) and scaled as a whole via
 * `transform`, rather than being re-laid-out per context.
 *
 * That is deliberate. A responsive re-flow would let the type scale, the
 * logo clear zone and the band drift apart at different sizes, and the card
 * would stop being the approved design at every size except the one it was
 * checked at. Scaling the finished artwork keeps every proportion locked
 * whether it renders in a detail page, a confirmation screen or, later, a
 * PDF.
 *
 * ── THE LOGO CLEAR ZONE ───────────────────────────────────────────────────
 *
 * 300 × 250 px, fixed, `object-fit: contain`. A different rental brand's
 * mark drops in without touching the layout — which is the point of a clear
 * zone, and the reason it is a fixed box rather than an intrinsic image.
 */
class CardArtwork extends Component
{
    /** The authored size of the artwork. Everything else scales from here. */
    public const NATURAL_WIDTH = 1012;

    public const NATURAL_HEIGHT = 638;

    public string $brandName;

    public string $tagline;

    public ?string $logoUrl;

    public string $accentColor;

    public string $amount;

    public string $recipient;

    public string $sender;

    public string $message;

    public string $cardNumber;

    public string $support;

    /**
     * @param  GiftCard|null  $card    Live card. Null renders the design with
     *                                 sample values, for a preview screen that
     *                                 has no card yet.
     * @param  float  $scale           1.0 renders at the authored 1012 × 638.
     * @param  array  $overrides       Draft values for a live "what will this
     *                                 card look like" preview, before the card
     *                                 exists. Never used for a saved card.
     */
    public function __construct(
        public ?GiftCard $card = null,
        public float $scale = 1.0,
        array $overrides = [],
    ) {
        $company = ConfigurationHelper::getSettings('Company Settings');

        $this->brandName = $overrides['brand_name']
            ?? ($company['company_name'] ?? config('app.name'));

        // Tagline, accent and logo are gift-card branding, kept in Company
        // Settings alongside the rest of the brand rather than hardcoded, so
        // the artwork carries whichever brand the install belongs to.
        $this->tagline = $overrides['tagline']
            ?? $this->setting('gift_card_tagline')
            ?? '“Rent Everything… Live Like a King”';

        $this->accentColor = $this->normalizeHex(
            $overrides['accent_color'] ?? $this->setting('gift_card_accent_color')
        ) ?? '#e08c42';

        $this->logoUrl = $overrides['logo_url'] ?? ConfigurationHelper::getBrandingLogo();

        // Website and phone are the ones already maintained for the company;
        // the card must not invent a second set that can drift from them.
        $website = trim((string) ($company['main_url'] ?? ''));
        $phone = trim((string) ($company['company_main_phone'] ?? ''));
        $this->support = $overrides['support']
            ?? trim(implode(' · ', array_filter([$website, $phone])));

        // ── Card values ───────────────────────────────────────────────────
        //
        // The amount shown is the card's CURRENT balance, not its face value.
        // A card that has been partly spent is worth what is left on it, and
        // showing the original value on a $0 card would be a lie printed on
        // a financial instrument.
        $this->amount = $overrides['amount']
            ?? ($card ? $this->money($card->availableBalance()) : '100');

        $this->recipient = $overrides['recipient']
            ?? ($card?->recipient_name ?: $card?->recipient?->full_name ?: '—');

        $this->sender = $overrides['sender']
            ?? ($card?->sender_name ?: '—');

        $this->message = $overrides['message']
            ?? ($card?->message ?: '');

        $this->cardNumber = $overrides['card_number']
            ?? ($card?->card_number ?? 'GC-0000-0000');
    }

    public function width(): float
    {
        return self::NATURAL_WIDTH * $this->scale;
    }

    public function height(): float
    {
        return self::NATURAL_HEIGHT * $this->scale;
    }

    private function setting(string $name): ?string
    {
        $value = Setting::where('setting_type', 'Company Settings')
            ->where('setting_name', $name)
            ->value('setting_value');

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * A colour typed into a settings field reaches CSS, so it is validated
     * rather than trusted — an unvalidated value here is a style-attribute
     * injection point, not a cosmetic defect.
     */
    private function normalizeHex(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_starts_with($value, '#') ? $value : '#'.$value;

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1
            ? $value
            : null;
    }

    /**
     * Whole dollars print bare, cents print in full — "100", not "100.00",
     * but "87.50" rather than "88".
     */
    private function money(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0)
            : number_format($value, 2);
    }

    public function render()
    {
        return view('components.admin.gift-cards.card-artwork');
    }
}
