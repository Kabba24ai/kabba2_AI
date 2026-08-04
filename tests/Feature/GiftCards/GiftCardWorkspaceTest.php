<?php

namespace Tests\Feature\GiftCards;

use App\Enums\GiftCards\GiftCardStatus;
use App\Enums\Orders\OrderPaymentMethod;
use App\Models\Customers\Customer;
use App\Models\GiftCards\GiftCard;
use App\Models\Iam\Personnel\User;
use App\Services\GiftCards\GiftCardPermissions;
use App\Services\GiftCards\GiftCardService;
use Database\Seeders\Iam\GiftCardPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The workspace: does every screen render, does every write reach the
 * service, and does the permission model actually hold at the HTTP edge.
 *
 * These are deliberately shallow. The accounting is proved in
 * {@see GiftCardServiceTest}; what is unproven until now is that the
 * controllers, routes and views in front of it are wired up and guarded.
 */
class GiftCardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        (new GiftCardPermissionSeeder())->run();

        $this->admin = $this->userWith(array_keys(GiftCardPermissions::all()));
    }

    private function userWith(array $permissions): User
    {
        $user = User::create([
            'first_name' => 'Workspace', 'last_name' => 'Operator',
            'email' => 'gc-workspace-'.uniqid().'@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    private function purchase(float $amount = 500): GiftCard
    {
        return GiftCardService::purchase(
            amount: $amount,
            fundingMethod: OrderPaymentMethod::Cash,
            recipientName: 'Daniel Reyes',
            senderName: 'The Whitfield Family',
            message: 'For the projects you keep talking about.',
            actor: $this->admin,
        );
    }

    // ── Every screen renders ──────────────────────────────────────────────

    public function test_every_workspace_screen_renders(): void
    {
        $card = $this->purchase();

        $screens = [
            route('admin.gift-cards.overview'),
            route('admin.gift-cards.index'),
            route('admin.gift-cards.transactions'),
            route('admin.gift-cards.reporting'),
            route('admin.gift-cards.purchased.create'),
            route('admin.gift-cards.granted.create'),
            route('admin.gift-cards.show', $card->card_number),
        ];

        foreach ($screens as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    /**
     * An install with no cards must render a useful page, not a broken one.
     * Zero states are where a dashboard usually breaks, because they are the
     * state nobody builds against.
     */
    public function test_the_overview_renders_a_zero_state_with_no_cards(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.gift-cards.overview'))
            ->assertOk()
            ->assertSee('No gift cards yet');
    }

    // ── The approved artwork ──────────────────────────────────────────────

    /**
     * The card detail page must show the approved design populated with this
     * card's real values — that artefact is the whole point of the page.
     */
    public function test_the_detail_page_renders_the_approved_artwork_with_live_values(): void
    {
        $card = $this->purchase(250);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.gift-cards.show', $card->card_number));

        $response->assertOk()
            // Structural landmarks of the approved design.
            ->assertSee('gcx-card', false)
            ->assertSee('gcx-logo-zone', false)
            ->assertSee('gcx-band', false)
            // Live merge fields.
            ->assertSee($card->card_number)
            ->assertSee('Daniel Reyes')
            ->assertSee('The Whitfield Family')
            ->assertSee('For the projects you keep talking about.');
    }

    /**
     * The amount printed is the card's CURRENT balance, not its face value.
     * A partly-spent card showing its original value would be a lie printed
     * on a financial instrument.
     */
    public function test_the_artwork_shows_the_remaining_balance_not_the_face_value(): void
    {
        $card = $this->purchase(500);
        GiftCardService::adjustDecrease($card, 300, 'Test spend-down', actor: $this->admin);

        $this->actingAs($this->admin)
            ->get(route('admin.gift-cards.show', $card->card_number))
            ->assertOk()
            ->assertSee('<span class="gcx-figure">200</span>', false);
    }

    // ── Creation ──────────────────────────────────────────────────────────

    public function test_selling_a_card_issues_it_and_lands_on_its_detail_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.gift-cards.purchased.store'), [
                'amount' => 150,
                'funding_method' => OrderPaymentMethod::Cash->value,
                'recipient_name' => 'Ana Whitfield',
                'sender_name' => 'Rent n King',
                'idempotency_key' => 'test-purchase-1',
            ]);

        $card = GiftCard::firstWhere('recipient_name', 'Ana Whitfield');

        $this->assertNotNull($card);
        $response->assertRedirect(route('admin.gift-cards.show', $card->card_number));

        $this->assertSame(150.0, $card->availableBalance());
        $this->assertTrue($card->isPurchased());

        // Real funding, recorded — not a bare payment row.
        $funding = $card->transactions()->first();
        $this->assertSame(OrderPaymentMethod::Cash, $funding->funding_payment_method);
        $this->assertSame('150.00', (string) $funding->funding_cash_amount);
    }

    /**
     * Selling a card must not create a catalogue product, an order, or an
     * order line. That absence is what keeps a card sale out of taxable
     * merchandise revenue — it is structural, not a filter.
     */
    public function test_selling_a_card_creates_no_order_and_no_product(): void
    {
        $ordersBefore = \App\Models\Orders\Order::count();
        $productsBefore = \App\Models\ProductManagement\Product::count();

        $this->actingAs($this->admin)->post(route('admin.gift-cards.purchased.store'), [
            'amount' => 75,
            'funding_method' => OrderPaymentMethod::Cash->value,
            'idempotency_key' => 'test-purchase-no-order',
        ]);

        $this->assertSame($ordersBefore, \App\Models\Orders\Order::count());
        $this->assertSame($productsBefore, \App\Models\ProductManagement\Product::count());
    }

    public function test_granting_a_card_records_the_reason_and_takes_no_money(): void
    {
        $this->actingAs($this->admin)->post(route('admin.gift-cards.granted.store'), [
            'amount' => 60,
            'grant_reason_category' => 'service_recovery',
            'grant_note' => 'Equipment arrived two days late on order 1042.',
            'recipient_name' => 'Marcus Bell',
            'idempotency_key' => 'test-grant-1',
        ])->assertRedirect();

        $card = GiftCard::firstWhere('recipient_name', 'Marcus Bell');

        $this->assertNotNull($card);
        $this->assertTrue($card->isGranted());

        $issuance = $card->transactions()->first();
        $this->assertNull($issuance->funding_payment_method, 'a granted card has no funding tender');
        $this->assertNull($issuance->funding_cash_amount, 'and no cash');
    }

    // ── Permissions hold at the HTTP edge ─────────────────────────────────

    /**
     * The grant form is the one screen that can create money from nothing.
     * A user holding every OTHER gift card permission must still be refused.
     */
    public function test_granting_is_refused_without_the_grant_permission(): void
    {
        $everythingElse = array_values(array_diff(
            array_keys(GiftCardPermissions::all()),
            [GiftCardPermissions::GRANT],
        ));

        $user = $this->userWith($everythingElse);

        $this->actingAs($user)
            ->post(route('admin.gift-cards.granted.store'), [
                'amount' => 500,
                'grant_reason_category' => 'promotion',
                'grant_note' => 'Trying it on without the permission.',
            ])
            ->assertForbidden();

        $this->assertSame(0, GiftCard::count(), 'no value was created');
    }

    public function test_selling_is_refused_without_the_sell_permission(): void
    {
        $user = $this->userWith([GiftCardPermissions::VIEW]);

        $this->actingAs($user)
            ->post(route('admin.gift-cards.purchased.store'), [
                'amount' => 100,
                'funding_method' => OrderPaymentMethod::Cash->value,
            ])
            ->assertForbidden();
    }

    // ── Lifecycle through the HTTP layer ──────────────────────────────────

    public function test_suspend_and_reinstate_move_through_the_service(): void
    {
        $card = $this->purchase();

        $this->actingAs($this->admin)
            ->post(route('admin.gift-cards.suspend', $card->card_number), ['reason' => 'Reported stolen by the recipient.'])
            ->assertRedirect();

        $card->refresh();
        $this->assertSame(GiftCardStatus::Suspended, $card->status);
        $this->assertSame(500.0, $card->availableBalance(), 'suspension does not destroy value');

        $this->actingAs($this->admin)
            ->post(route('admin.gift-cards.reinstate', $card->card_number), ['reason' => 'Dispute resolved in the customer favour.'])
            ->assertRedirect();

        $this->assertTrue($card->fresh()->isRedeemable());
    }

    /**
     * A reason is not optional on any of these. It is what makes the action
     * reviewable afterwards, so an empty one is rejected before the service
     * is even reached.
     */
    public function test_every_lifecycle_action_requires_a_reason(): void
    {
        $card = $this->purchase();

        foreach (['suspend', 'cancel', 'replace'] as $action) {
            $this->actingAs($this->admin)
                ->post(route("admin.gift-cards.{$action}", $card->card_number), ['reason' => ''])
                ->assertSessionHasErrors('reason');
        }

        $this->assertSame(500.0, $card->fresh()->availableBalance());
    }

    public function test_cancelling_writes_the_balance_off_in_the_ledger(): void
    {
        $card = $this->purchase(200);

        $this->actingAs($this->admin)
            ->post(route('admin.gift-cards.cancel', $card->card_number), ['reason' => 'Issued in error — duplicate card.'])
            ->assertRedirect();

        $card->refresh();
        $this->assertSame(GiftCardStatus::Cancelled, $card->status);
        $this->assertSame(0.0, $card->availableBalance());

        $writeOff = $card->transactions()
            ->where('type', \App\Enums\GiftCards\GiftCardTransactionType::Cancellation->value)
            ->first();

        $this->assertNotNull($writeOff, 'the write-off must be visible in the ledger');
        $this->assertSame('-200.00', (string) $writeOff->amount);
    }

    public function test_replacing_redirects_to_the_new_card(): void
    {
        $card = $this->purchase(300);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.gift-cards.replace', $card->card_number), ['reason' => 'Card damaged in the post.']);

        $replacement = GiftCard::where('id', '!=', $card->id)->latest('id')->first();

        $response->assertRedirect(route('admin.gift-cards.show', $replacement->card_number));
        $this->assertSame(300.0, $replacement->availableBalance());
        $this->assertSame(0.0, $card->fresh()->availableBalance());
    }

    public function test_adjustment_requires_the_adjust_permission(): void
    {
        $card = $this->purchase();
        $user = $this->userWith([GiftCardPermissions::VIEW, GiftCardPermissions::REDEEM]);

        $this->actingAs($user)
            ->post(route('admin.gift-cards.adjust', $card->card_number), [
                'direction' => 'increase', 'amount' => 100, 'reason' => 'Trying it on.',
            ])
            ->assertRedirect();

        $this->assertSame(500.0, $card->fresh()->availableBalance(), 'no value was created');
    }

    // ── Point-of-payment lookup ───────────────────────────────────────────

    public function test_lookup_reports_the_live_balance(): void
    {
        $card = $this->purchase(400);

        $this->actingAs($this->admin)
            ->postJson(route('admin.gift-cards.lookup'), ['card_number' => $card->card_number])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'balance' => 400.0,
                'redeemable' => true,
            ]);
    }

    public function test_lookup_refuses_an_unknown_card(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.gift-cards.lookup'), ['card_number' => 'GC-0000-0000'])
            ->assertNotFound();
    }

    /**
     * A card number plus a PIN is a bearer instrument, so the balance is not
     * revealed by the number alone.
     */
    public function test_lookup_will_not_reveal_a_balance_without_the_pin(): void
    {
        $card = $this->purchase();
        $card->forceFill(['pin_hash' => bcrypt('1234')])->saveQuietly();

        $this->actingAs($this->admin)
            ->postJson(route('admin.gift-cards.lookup'), ['card_number' => $card->card_number])
            ->assertStatus(422)
            ->assertJson(['requires_pin' => true])
            ->assertJsonMissingPath('balance');

        $this->actingAs($this->admin)
            ->postJson(route('admin.gift-cards.lookup'), ['card_number' => $card->card_number, 'pin' => '1234'])
            ->assertOk()
            ->assertJson(['balance' => 500.0]);
    }

    // ── Search and filters ────────────────────────────────────────────────

    public function test_the_list_can_be_searched_and_filtered(): void
    {
        $this->purchase();
        GiftCardService::grant(
            amount: 50, reasonCode: 'PROMO', reasonCategory: 'promotion',
            note: 'Promotional giveaway for the spring campaign.',
            recipientName: 'Unique Grantee', actor: $this->admin,
        );

        $this->actingAs($this->admin)
            ->get(route('admin.gift-cards.index', ['search' => 'Unique Grantee']))
            ->assertOk()
            ->assertSee('Unique Grantee')
            ->assertDontSee('Daniel Reyes');

        $this->actingAs($this->admin)
            ->get(route('admin.gift-cards.index', ['class' => 'granted']))
            ->assertOk()
            ->assertSee('Unique Grantee');
    }
}
