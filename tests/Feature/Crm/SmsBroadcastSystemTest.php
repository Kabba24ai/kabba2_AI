<?php

namespace Tests\Feature\Crm;

use App\Enums\Communication\SmsBroadcastStatus;
use App\Jobs\ArchiveCompletedSmsBroadcastsJob;
use App\Jobs\ProcessScheduledSmsBroadcastsJob;
use App\Jobs\SendSmsBroadcastEventJob;
use App\Models\Customers\Customer;
use App\Models\Customers\SmsAudience;
use App\Models\Customers\SmsBroadcast;
use App\Models\Customers\SmsBroadcastEvent;
use App\Models\Customers\SmsCategory;
use App\Models\Customers\Tag;
use App\Models\Iam\Personnel\User;
use App\Services\Crm\SmsAudienceResolver;
use App\Services\Crm\SmsBroadcastService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CRM SMS rebuild — wizard-based broadcast system.
 *
 * Library = reusable content (sms_broadcasts). Broadcasts = events with a
 * frozen message snapshot + frozen recipient package. No test ever sends
 * a real SMS: TwilioService is container-swapped everywhere.
 */
class SmsBroadcastSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private SmsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'sms-sys-admin', 'employee_code' => '96',
            'first_name' => 'Sms', 'last_name' => 'Admin',
            'email' => 'sms-sys@test.local', 'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->category = SmsCategory::create(['name' => 'General Broadcasts']);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function makeMessage(string $name = 'Post Pounder Promo', string $content = 'Post pounders back in stock!'): SmsBroadcast
    {
        return SmsBroadcast::create([
            'sms_cat_id' => $this->category->id, 'name' => $name,
            'description' => $content, 'created_by' => $this->admin->id,
        ]);
    }

    private function makeCustomer(string $first, ?string $phone, array $tagIds, string $status = 'Active'): Customer
    {
        return Customer::create([
            'first_name' => $first, 'last_name' => 'Customer',
            'email' => strtolower($first) . uniqid() . '@example.com',
            'status' => $status, 'phone' => $phone, 'tags' => json_encode($tagIds),
        ]);
    }

    /** Runs the wizard endpoints: message step + audience step. Returns the draft. */
    private function makeDraft(SmsBroadcast $message, array $audience = []): SmsBroadcastEvent
    {
        $r1 = $this->postJson(route('admin.crm.message-management.broadcast-wizard.draft'), [
            'wizard_step' => 1, 'sms_broadcast_id' => $message->id,
        ])->assertOk()->json();

        $this->postJson(route('admin.crm.message-management.broadcast-wizard.draft'), array_merge([
            'event_id' => $r1['event_id'], 'wizard_step' => 3,
            'audience_type' => 'tags', 'positive_mode' => 'any',
            'include_tags' => [], 'exclude_tags' => [],
        ], $audience))->assertOk();

        return SmsBroadcastEvent::findOrFail($r1['event_id']);
    }

    private function queueDraft(SmsBroadcastEvent $event): SmsBroadcastEvent
    {
        $this->postJson(route('admin.crm.message-management.broadcast-wizard.queue', $event->id))
            ->assertOk()->assertJson(['success' => true]);

        return $event->refresh();
    }

    private function fakeTwilio(?callable $resultForPhone = null): void
    {
        $resultForPhone ??= fn ($to) => ['success' => true, 'message' => 'SMS sent', 'sid' => 'SM-' . $to];
        $twilio = $this->createMock(TwilioService::class);
        $twilio->method('sendSms')->willReturnCallback(
            fn (string $to, string $m, array $o = [], array $c = []) => $resultForPhone($to),
        );
        $this->app->instance(TwilioService::class, $twilio);
    }

    // ═════════════════════════ Message Library ═════════════════════════

    public function test_library_message_create_edit_copy_archive_delete(): void
    {
        // Create
        $this->post(route('admin.crm.message-management.sms-broadcast.store'), [
            'sms_cat_id' => $this->category->id, 'name' => 'Spring Special', 'description' => '10% off weekly.',
        ])->assertOk()->assertJsonPath('status', 'success');
        $message = SmsBroadcast::where('name', 'Spring Special')->firstOrFail();
        $this->assertEquals($this->admin->id, $message->created_by);

        // Edit
        $this->put(route('admin.crm.message-management.sms-broadcast.update', $message->id), [
            'sms_cat_id' => $this->category->id, 'name' => 'Spring Special v2', 'description' => '15% off weekly.',
        ])->assertOk();
        $this->assertEquals('Spring Special v2', $message->refresh()->name);

        // Copy — fresh, unarchived
        $message->update(['archived_at' => now()]);
        $this->get(route('admin.crm.message-management.sms-broadcast.copy', $message->id))->assertRedirect();
        $copy = SmsBroadcast::where('name', 'Spring Special v2 (Copy)')->firstOrFail();
        $this->assertNull($copy->archived_at);

        // Archive toggle
        $this->post(route('admin.crm.message-management.sms-broadcast.archive', $copy->id))
            ->assertOk()->assertJson(['archived' => true]);
        $this->assertNotNull($copy->refresh()->archived_at);
        $this->post(route('admin.crm.message-management.sms-broadcast.archive', $copy->id))
            ->assertOk()->assertJson(['archived' => false]);

        // Delete
        $this->delete(route('admin.crm.message-management.sms-broadcast.delete', $copy->id));
        $this->assertDatabaseMissing('sms_broadcasts', ['id' => $copy->id]);
    }

    public function test_library_listing_filters_by_category_and_archive_state(): void
    {
        $other = SmsCategory::create(['name' => 'Boom Lifts']);
        $inCat = $this->makeMessage('Boom Msg');
        $inCat->update(['sms_cat_id' => $other->id]);
        $this->makeMessage('General Msg');
        $archived = $this->makeMessage('Old Msg');
        $archived->update(['archived_at' => now()]);

        $html = $this->get(route('admin.crm.message-management.sms-broadcast.index', [
            'sms_bro_filter_category' => $other->id,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('html');
        $this->assertStringContainsString('Boom Msg', $html);
        $this->assertStringNotContainsString('General Msg', $html);
        $this->assertStringNotContainsString('Old Msg', $html);

        $html = $this->get(route('admin.crm.message-management.sms-broadcast.index', ['archived' => 1]),
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('html');
        $this->assertStringContainsString('Old Msg', $html);
        $this->assertStringNotContainsString('General Msg', $html);
    }

    public function test_library_never_shows_broadcast_statuses(): void
    {
        $this->makeMessage('Clean Message');

        $html = $this->get(route('admin.crm.message-management.sms-broadcast.index'),
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('html');

        foreach (['Pending', 'Scheduled', 'Sending', 'Partially', 'Sent Date'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }

    public function test_message_created_from_wizard_is_a_normal_library_message(): void
    {
        // The wizard's Add New Message subflow posts to the same store
        // endpoint and selects the returned id client-side.
        $response = $this->post(route('admin.crm.message-management.sms-broadcast.store'), [
            'sms_cat_id' => $this->category->id, 'name' => 'Wizard Born', 'description' => 'Created mid-wizard.',
        ])->assertOk()->json();

        $this->assertDatabaseHas('sms_broadcasts', ['id' => $response['broadcast']['id'], 'name' => 'Wizard Born']);
    }

    // ═════════════════════════ Audience Rules ═════════════════════════

    public function test_resolver_matches_any_and_all_modes_with_exclusions(): void
    {
        $boom = Tag::create(['name' => 'Boom Lift']);
        $comm = Tag::create(['name' => 'Commercial']);
        $dnm  = Tag::create(['name' => 'Do Not Market']);

        $bothTags   = $this->makeCustomer('Both', '6165550001', [$boom->id, $comm->id]);
        $boomOnly   = $this->makeCustomer('BoomOnly', '6165550002', [$boom->id]);
        $excluded   = $this->makeCustomer('Optout', '6165550003', [$boom->id, $comm->id, $dnm->id]);
        $unrelated  = $this->makeCustomer('None', '6165550004', []);

        $resolver = app(SmsAudienceResolver::class);

        // ANY
        $any = $resolver->resolve(['base' => 'tags', 'mode' => 'any', 'include' => [$boom->id, $comm->id], 'exclude' => [$dnm->id]]);
        $this->assertEqualsCanonicalizing(
            [$bothTags->id, $boomOnly->id],
            $any['recipients']->pluck('customer.id')->all(),
        );
        $this->assertEquals(3, $any['stats']['positive_count']);
        $this->assertEquals(1, $any['stats']['excluded_by_tags']);

        // ALL
        $all = $resolver->resolve(['base' => 'tags', 'mode' => 'all', 'include' => [$boom->id, $comm->id], 'exclude' => [$dnm->id]]);
        $this->assertEquals([$bothTags->id], $all['recipients']->pluck('customer.id')->all());

        // All eligible + exclusions
        $allBase = $resolver->resolve(['base' => 'all', 'include' => [], 'exclude' => [$dnm->id]]);
        $this->assertEqualsCanonicalizing(
            [$bothTags->id, $boomOnly->id, $unrelated->id],
            $allBase['recipients']->pluck('customer.id')->all(),
        );
    }

    public function test_resolver_handles_string_tags_phones_and_dupes(): void
    {
        $vip = Tag::create(['name' => 'VIP']);

        $intTagged    = $this->makeCustomer('Inty', '6165550011', [$vip->id]);
        $stringTagged = $this->makeCustomer('Stringy', '(616) 555-0012', [(string) $vip->id]);
        $this->makeCustomer('Dupe', '6165550011', [$vip->id]);       // same number as Inty
        $this->makeCustomer('NoPhone', null, [$vip->id]);
        $this->makeCustomer('BadPhone', '123', [$vip->id]);
        $this->makeCustomer('Inactive', '6165550015', [$vip->id], 'Inactive');

        $result = app(SmsAudienceResolver::class)->resolve(['base' => 'tags', 'mode' => 'any', 'include' => [$vip->id], 'exclude' => []]);

        $this->assertEqualsCanonicalizing(
            [$intTagged->id, $stringTagged->id],
            $result['recipients']->pluck('customer.id')->all(),
        );
        $this->assertEquals(['+16165550011', '+16165550012'], $result['recipients']->pluck('phone')->sort()->values()->all());
        $this->assertEquals(1, $result['stats']['missing_phone']);
        $this->assertEquals(1, $result['stats']['invalid_phone']);
        $this->assertEquals(1, $result['stats']['duplicates_removed']);
        $this->assertEquals(2, $result['stats']['final_count']);
    }

    public function test_saved_audience_stores_rules_only_and_resolves_current_data(): void
    {
        $vip = Tag::create(['name' => 'VIP']);

        $this->postJson(route('admin.crm.message-management.audiences.store'), [
            'name' => 'VIP Everyone', 'base_all' => false, 'positive_mode' => 'any',
            'include_tags' => [$vip->id], 'exclude_tags' => [],
        ])->assertOk()->assertJson(['success' => true]);

        $audience = SmsAudience::where('name', 'VIP Everyone')->firstOrFail();
        $this->assertEquals([$vip->id], $audience->include_tag_ids);

        // Resolves against CURRENT data — a customer tagged after saving is included
        $this->makeCustomer('Early', '6165550021', [$vip->id]);
        $this->assertEquals(1, app(SmsAudienceResolver::class)->resolve($audience->spec())['stats']['final_count']);

        $this->makeCustomer('Late', '6165550022', [$vip->id]);
        $this->assertEquals(2, app(SmsAudienceResolver::class)->resolve($audience->spec())['stats']['final_count']);
    }

    // ═════════════════════ Wizard + Prepared Package ═════════════════════

    public function test_wizard_draft_queue_freezes_package_and_sends_nothing(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $customer = $this->makeCustomer('Frozen', '6165550031', [$vip->id]);
        $message  = $this->makeMessage();

        $draft = $this->makeDraft($message, ['include_tags' => [$vip->id]]);
        $this->assertEquals(SmsBroadcastStatus::Draft, $draft->status);
        $this->assertEquals(['VIP'], $draft->include_tag_names);

        // Server-side preview count endpoint (canonical logic)
        $this->getJson(route('admin.crm.message-management.broadcast-wizard.preview-audience', [
            'audience_type' => 'tags', 'positive_mode' => 'any', 'include_tags' => [$vip->id],
        ]))->assertOk()->assertJsonPath('stats.final_count', 1);

        $event = $this->queueDraft($draft);

        $this->assertEquals(SmsBroadcastStatus::AwaitingConfirmation, $event->status);
        $this->assertEquals(1, $event->recipient_count);
        $this->assertEquals($message->description, $event->message_content);
        $this->assertDatabaseHas('sms_broadcast_recipients', [
            'sms_broadcast_event_id' => $event->id,
            'customer_id'            => $customer->id,
            'phone'                  => '+16165550031',
            'status'                 => 'pending',
        ]);

        // Creating + queueing transmits NOTHING
        Queue::assertNothingPushed();
    }

    public function test_draft_can_be_continued_and_zero_recipient_queue_is_rejected(): void
    {
        $message = $this->makeMessage();
        $emptyTag = Tag::create(['name' => 'Nobody']);
        $draft = $this->makeDraft($message, ['include_tags' => [$emptyTag->id]]);

        // Continue Wizard page loads the draft
        $this->get(route('admin.crm.message-management.broadcast-wizard.create', ['event' => $draft->id]))
            ->assertOk();

        // Queueing with zero eligible recipients is rejected; stays Draft
        $this->postJson(route('admin.crm.message-management.broadcast-wizard.queue', $draft->id))
            ->assertStatus(422);
        $this->assertEquals(SmsBroadcastStatus::Draft, $draft->refresh()->status);
        $this->assertEquals(0, $draft->recipients()->count());
    }

    public function test_frozen_package_ignores_later_message_and_tag_changes(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $customer = $this->makeCustomer('Locked', '6165550041', [$vip->id]);
        $message  = $this->makeMessage('Original', 'Original content.');

        $event = $this->queueDraft($this->makeDraft($message, ['include_tags' => [$vip->id]]));

        // Library message edited AFTER queueing
        $message->update(['name' => 'Renamed', 'description' => 'Totally different content.']);
        // New customer tagged AFTER queueing
        $this->makeCustomer('Latecomer', '6165550042', [$vip->id]);
        // Existing recipient untagged AFTER queueing
        $customer->update(['tags' => json_encode([])]);

        $event->refresh();
        $this->assertEquals('Original content.', $event->message_content);
        $this->assertEquals(1, $event->recipients()->count());
        $this->assertEquals($customer->id, $event->recipients()->first()->customer_id);

        // Transmission uses the frozen content, not the library
        $sentBodies = [];
        $twilio = $this->createMock(TwilioService::class);
        $twilio->method('sendSms')->willReturnCallback(function ($to, $body) use (&$sentBodies) {
            $sentBodies[] = $body;
            return ['success' => true, 'message' => 'SMS sent', 'sid' => 'SM-x'];
        });
        $this->app->instance(TwilioService::class, $twilio);

        app(SmsBroadcastService::class)->sendNow($event);
        (new SendSmsBroadcastEventJob($event->id))->handle();

        $this->assertEquals(['Original content.'], $sentBodies);
        $this->assertEquals(SmsBroadcastStatus::Sent, $event->refresh()->status);
    }

    public function test_deleting_library_message_leaves_historical_broadcast_intact(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Hist', '6165550051', [$vip->id]);
        $message = $this->makeMessage('Doomed', 'Historic content.');

        $event = $this->queueDraft($this->makeDraft($message, ['include_tags' => [$vip->id]]));

        $message->delete();

        $event->refresh();
        $this->assertNull($event->sms_broadcast_id);
        $this->assertEquals('Doomed', $event->message_name);
        $this->assertEquals('Historic content.', $event->message_content);
        $this->assertEquals(1, $event->recipients()->count());
    }

    // ═════════════════════ Final Send Wizard ═════════════════════

    public function test_send_now_is_the_only_transmission_authorization(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Now', '6165550061', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        // Nothing dispatched before authorization
        Queue::assertNothingPushed();

        $this->post(route('admin.crm.message-management.broadcast-queue.authorize', $event->id), [
            'timing' => 'now',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals(SmsBroadcastStatus::Sending, $event->refresh()->status);
        Queue::assertPushed(SendSmsBroadcastEventJob::class, fn ($job) => $job->eventId === $event->id);
    }

    public function test_schedule_and_schedule_only_edit_keep_package_frozen(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Sched', '6165550071', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));
        $originalRecipientIds = $event->recipients()->pluck('id')->all();

        $when = now()->addDays(2)->setSeconds(0);
        $this->post(route('admin.crm.message-management.broadcast-queue.authorize', $event->id), [
            'timing' => 'scheduled', 'scheduled_at' => $when->toDateTimeString(),
        ])->assertRedirect()->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals(SmsBroadcastStatus::Scheduled, $event->status);
        $this->assertEquals($when->toDateTimeString(), $event->scheduled_at->toDateTimeString());
        Queue::assertNothingPushed();

        // Schedule-only edit: same recipient rows, no rebuild
        $newWhen = now()->addDays(5)->setSeconds(0);
        $this->post(route('admin.crm.message-management.broadcast-queue.reschedule', $event->id), [
            'scheduled_at' => $newWhen->toDateTimeString(),
        ])->assertRedirect()->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals($newWhen->toDateTimeString(), $event->scheduled_at->toDateTimeString());
        $this->assertEquals(SmsBroadcastStatus::Scheduled, $event->status);
        $this->assertEquals($originalRecipientIds, $event->recipients()->pluck('id')->all());
    }

    public function test_scheduler_releases_due_broadcasts(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Due', '6165550081', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        app(SmsBroadcastService::class)->schedule($event, now()->subMinute());
        $future = $this->queueDraft($this->makeDraft($this->makeMessage('Later'), ['include_tags' => [$vip->id]]));
        app(SmsBroadcastService::class)->schedule($future, now()->addDay());

        (new ProcessScheduledSmsBroadcastsJob())->handle();

        $this->assertEquals(SmsBroadcastStatus::Sending, $event->refresh()->status);
        $this->assertEquals(SmsBroadcastStatus::Scheduled, $future->refresh()->status);
        Queue::assertPushed(SendSmsBroadcastEventJob::class, 1);
    }

    public function test_editing_message_or_audience_rebuilds_and_requires_reconfirmation(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Edit', '6165550091', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        // Rebuild (explicit edit) discards the package, returns to Draft
        $this->post(route('admin.crm.message-management.broadcast-queue.rebuild', $event->id))
            ->assertRedirect(route('admin.crm.message-management.broadcast-wizard.create', ['event' => $event->id]));

        $event->refresh();
        $this->assertEquals(SmsBroadcastStatus::Draft, $event->status);
        $this->assertEquals(0, $event->recipients()->count());
        $this->assertEquals(0, $event->recipient_count);

        // Authorization refuses drafts — re-queue + reconfirm required
        $this->post(route('admin.crm.message-management.broadcast-queue.authorize', $event->id), [
            'timing' => 'now',
        ])->assertRedirect()->assertSessionHas('error');

        // Re-queue re-freezes and reopens the final send path
        $this->queueDraft($event);
        $this->assertEquals(SmsBroadcastStatus::AwaitingConfirmation, $event->refresh()->status);
        $this->assertEquals(1, $event->recipients()->count());
    }

    // ═════════════════════ Transmission Safety ═════════════════════

    public function test_partial_failure_is_reported_and_one_failure_never_stops_the_run(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Good', '6165550101', [$vip->id]);
        $this->makeCustomer('Bad', '6165550102', [$vip->id]);
        $this->makeCustomer('AlsoGood', '6165550103', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        $this->fakeTwilio(fn ($to) => $to === '+16165550102'
            ? ['success' => false, 'message' => 'Carrier rejected']
            : ['success' => true, 'message' => 'SMS sent', 'sid' => 'SM-ok']);

        app(SmsBroadcastService::class)->sendNow($event);
        (new SendSmsBroadcastEventJob($event->id))->handle();

        $event->refresh();
        $this->assertEquals(SmsBroadcastStatus::PartiallySent, $event->status);
        $this->assertEquals(2, $event->sent_count);
        $this->assertEquals(1, $event->failed_count);
        $this->assertEquals('Carrier rejected', $event->recipients()->where('status', 'failed')->first()->error_message);
    }

    public function test_interrupted_send_resumes_from_prepared_package(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $a = $this->makeCustomer('A', '6165550111', [$vip->id]);
        $this->makeCustomer('B', '6165550112', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        // Simulate an interrupted run: one recipient already sent
        $event->recipients()->where('customer_id', $a->id)->update([
            'status' => 'sent', 'twilio_sid' => 'SM-before-crash', 'sent_at' => now(),
        ]);
        $event->update(['status' => SmsBroadcastStatus::Sending, 'sending_started_at' => now()]);

        // Tag changes mid-flight must not matter
        $this->makeCustomer('Newcomer', '6165550113', [$vip->id]);

        $attempted = [];
        $this->fakeTwilio(function ($to) use (&$attempted) {
            $attempted[] = $to;
            return ['success' => true, 'message' => 'SMS sent', 'sid' => 'SM-resume'];
        });

        (new SendSmsBroadcastEventJob($event->id))->handle();

        // Only the remaining pending recipient was attempted; already-sent kept its record
        $this->assertEquals(['+16165550112'], $attempted);
        $event->refresh();
        $this->assertEquals(SmsBroadcastStatus::Sent, $event->status);
        $this->assertEquals(2, $event->sent_count);
        $this->assertEquals('SM-before-crash', $event->recipients()->where('customer_id', $a->id)->first()->twilio_sid);
        $this->assertEquals(2, $event->recipients()->count()); // newcomer never added
    }

    public function test_job_refuses_unauthorized_events_and_handles_missing_twilio(): void
    {
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Guard', '6165550121', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));

        // Not authorized (Awaiting Confirmation) — job must do nothing
        $this->fakeTwilio();
        (new SendSmsBroadcastEventJob($event->id))->handle();
        $this->assertEquals(SmsBroadcastStatus::AwaitingConfirmation, $event->refresh()->status);
        $this->assertEquals('pending', $event->recipients()->first()->status);

        // Twilio unconfigured — graceful failure
        $this->app->bind(TwilioService::class, function () {
            throw new \InvalidArgumentException('Twilio credentials are not fully configured.');
        });
        $event->update(['status' => SmsBroadcastStatus::Sending]);
        (new SendSmsBroadcastEventJob($event->id))->handle();

        $event->refresh();
        $this->assertEquals(SmsBroadcastStatus::Failed, $event->status);
        $this->assertStringContainsString('Twilio is not configured', $event->recipients()->first()->error_message);
    }

    // ═════════════════════ History / Archive / Delete ═════════════════════

    private function completedEvent(SmsBroadcastStatus $status = SmsBroadcastStatus::Sent, ?\DateTimeInterface $completedAt = null): SmsBroadcastEvent
    {
        return SmsBroadcastEvent::create([
            'name' => 'Historic ' . uniqid(), 'message_name' => 'Msg', 'message_content' => 'Body',
            'audience_type' => 'tags', 'positive_mode' => 'any',
            'recipient_count' => 3, 'sent_count' => 3,
            'status' => $status, 'completed_at' => $completedAt ?? now(),
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_completed_broadcasts_cannot_be_deleted_but_cancelled_stays_in_history(): void
    {
        Queue::fake();
        $sent = $this->completedEvent();
        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $sent->id))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('sms_broadcast_events', ['id' => $sent->id]);

        // Cancel an awaiting broadcast — remains as a Cancelled record
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Cxl', '6165550131', [$vip->id]);
        $event = $this->queueDraft($this->makeDraft($this->makeMessage(), ['include_tags' => [$vip->id]]));
        $this->post(route('admin.crm.message-management.broadcast-queue.cancel', $event->id))->assertRedirect();
        $this->assertEquals(SmsBroadcastStatus::Cancelled, $event->refresh()->status);

        // Cancelled cannot be normally deleted either
        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $event->id))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('sms_broadcast_events', ['id' => $event->id]);
    }

    public function test_draft_and_awaiting_can_delete_scheduled_needs_destructive_flag(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Del', '6165550141', [$vip->id]);

        $draft = $this->makeDraft($this->makeMessage('D1'), ['include_tags' => [$vip->id]]);
        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $draft->id))->assertRedirect();
        $this->assertDatabaseMissing('sms_broadcast_events', ['id' => $draft->id]);

        $awaiting = $this->queueDraft($this->makeDraft($this->makeMessage('D2'), ['include_tags' => [$vip->id]]));
        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $awaiting->id))->assertRedirect();
        $this->assertDatabaseMissing('sms_broadcast_events', ['id' => $awaiting->id]);

        $scheduled = $this->queueDraft($this->makeDraft($this->makeMessage('D3'), ['include_tags' => [$vip->id]]));
        app(SmsBroadcastService::class)->schedule($scheduled, now()->addDay());

        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $scheduled->id))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('sms_broadcast_events', ['id' => $scheduled->id]);

        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', $scheduled->id), )
            ->assertSessionHas('error');
        $this->delete(route('admin.crm.message-management.broadcast-queue.delete', [$scheduled->id, 'destructive' => 1]));
        $this->assertDatabaseMissing('sms_broadcast_events', ['id' => $scheduled->id]);
    }

    public function test_seven_day_auto_archive(): void
    {
        $old       = $this->completedEvent(SmsBroadcastStatus::Sent, now()->subDays(8));
        $recent    = $this->completedEvent(SmsBroadcastStatus::PartiallySent, now()->subDays(3));
        $cancelled = SmsBroadcastEvent::create([
            'name' => 'Old cancel', 'message_content' => 'Body', 'audience_type' => 'tags',
            'status' => SmsBroadcastStatus::Cancelled, 'cancelled_at' => now()->subDays(9),
            'created_by' => $this->admin->id,
        ]);

        (new ArchiveCompletedSmsBroadcastsJob())->handle();

        $this->assertNotNull($old->refresh()->archived_at);
        $this->assertNotNull($cancelled->refresh()->archived_at);
        $this->assertNull($recent->refresh()->archived_at);

        // Archive view lists them; active view does not
        $html = $this->get(route('admin.crm.message-management.broadcast-queue.index', ['view' => 'archive']))->assertOk()->getContent();
        $this->assertStringContainsString($old->name, $html);
        $html = $this->get(route('admin.crm.message-management.broadcast-queue.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString($old->name, $html);
        $this->assertStringContainsString($recent->name, $html);
    }

    // ═════════════════════ Screens render ═════════════════════

    public function test_operational_screens_render(): void
    {
        Queue::fake();
        $vip = Tag::create(['name' => 'VIP']);
        $this->makeCustomer('Render', '6165550151', [$vip->id]);
        $message = $this->makeMessage();
        $event = $this->queueDraft($this->makeDraft($message, ['include_tags' => [$vip->id]]));

        // Library page: new structure, old send modal gone
        $html = $this->get(route('admin.crm.message-management.index'))->assertOk()->getContent();
        $this->assertStringContainsString('SMS Message Library', $html);
        $this->assertStringContainsString('Broadcast Queue', $html);
        $this->assertStringContainsString('Create New SMS Message', $html);
        $this->assertStringNotContainsString('send-new-sms-broadcast', $html);
        $this->assertStringNotContainsString('Messages List', $html);
        // Sibling tabs preserved
        $this->assertStringContainsString('Email Broadcast', $html);
        $this->assertStringContainsString('SMS Funnel Content', $html);

        // Wizard page
        $html = $this->get(route('admin.crm.message-management.broadcast-wizard.create'))->assertOk()->getContent();
        $this->assertStringContainsString('What do you want to send?', $html);
        $this->assertStringContainsString('Add to Send Queue', $html);

        // Queue page with status badge + Review & Send action
        $html = $this->get(route('admin.crm.message-management.broadcast-queue.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Awaiting Confirmation', $html);
        $this->assertStringContainsString('Review &amp; Send', $html);

        // Final send wizard
        $html = $this->get(route('admin.crm.message-management.broadcast-queue.send', $event->id))->assertOk()->getContent();
        $this->assertStringContainsString('Send Immediately', $html);
        $this->assertStringContainsString('Schedule for Later', $html);

        // Broadcast detail
        $this->get(route('admin.crm.message-management.broadcast-queue.show', $event->id))
            ->assertOk()->assertSee('Frozen Message Content');
    }

    public function test_guests_are_redirected(): void
    {
        auth()->logout();

        $this->get(route('admin.crm.message-management.broadcast-queue.index'))->assertRedirect(route('admin.auth.login'));
        $this->post(route('admin.crm.message-management.broadcast-wizard.draft'), [])->assertRedirect(route('admin.auth.login'));
    }
}
