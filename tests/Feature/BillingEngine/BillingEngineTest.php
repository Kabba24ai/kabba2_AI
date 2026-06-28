<?php

namespace Tests\Feature\BillingEngine;

use App\Enums\Billing\BillingChargeStatus;
use App\Enums\Billing\BillingChargeType;
use App\Enums\Billing\BillingSourceEvent;
use App\Enums\Billing\BillingSourceModule;
use App\Events\Admin\Billing\BillingChargeCreatedEvent;
use App\Http\DataObjects\BillingChargeRequest;
use App\Models\Orders\BillingCharge;
use App\Services\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────────

    private function makeRequest(array $overrides = []): BillingChargeRequest
    {
        return new BillingChargeRequest(
            type:       $overrides['type']       ?? BillingChargeType::Fuel->value,
            orderId:    $overrides['orderId']    ?? 1,
            customerId: $overrides['customerId'] ?? 1,
            amount:     $overrides['amount']     ?? 75.00,
            taxType:    $overrides['taxType']    ?? 'free',
            orderProductId:      $overrides['orderProductId']     ?? null,
            responsiblePersonId: $overrides['responsiblePersonId'] ?? null,
            notes:               $overrides['notes']              ?? null,
            sourceModule:        $overrides['sourceModule']       ?? null,
            sourceEvent:         $overrides['sourceEvent']        ?? null,
            sourceReferenceType: $overrides['sourceReferenceType'] ?? null,
            sourceReferenceId:   $overrides['sourceReferenceId']  ?? null,
            metadata:            $overrides['metadata']           ?? null,
            idempotencyKey:      $overrides['idempotencyKey']     ?? null,
            customerAccountId:   $overrides['customerAccountId']  ?? null,
            taxAmount:           $overrides['taxAmount']          ?? null,
        );
    }

    // ── Creation ───────────────────────────────────────────────────────────

    public function test_charge_creates_billing_charge_row(): void
    {
        $request = $this->makeRequest(['amount' => 75.00]);

        $charge = BillingEngine::charge($request);

        $this->assertInstanceOf(BillingCharge::class, $charge);
        $this->assertDatabaseHas('billing_charges', [
            'unique_id'           => $charge->unique_id,
            'billing_charge_type' => BillingChargeType::Fuel->value,
            'status'              => BillingChargeStatus::Pending->value,
            'amount'              => 75.00,
            'parent_order_id'     => 1,
            'customer_id'         => 1,
        ]);
    }

    public function test_charge_generates_unique_id_with_blc_prefix(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        $this->assertStringStartsWith('BLC', $charge->unique_id);
    }

    public function test_charge_status_defaults_to_pending(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        $this->assertEquals(BillingChargeStatus::Pending, $charge->status);
        $this->assertTrue($charge->isPending());
    }

    public function test_charge_fires_billing_charge_created_event(): void
    {
        Event::fake([BillingChargeCreatedEvent::class]);

        $charge = BillingEngine::charge($this->makeRequest());

        Event::assertDispatched(BillingChargeCreatedEvent::class, function ($event) use ($charge) {
            return $event->charge->unique_id === $charge->unique_id;
        });
    }

    public function test_charge_stores_all_source_tracking_fields(): void
    {
        $request = $this->makeRequest([
            'sourceModule'        => BillingSourceModule::AdminFuelCharge->value,
            'sourceEvent'         => BillingSourceEvent::AdminFuelChargeCreated->value,
            'sourceReferenceType' => 'OrderProduct',
            'sourceReferenceId'   => 42,
        ]);

        $charge = BillingEngine::charge($request);

        $this->assertEquals(BillingSourceModule::AdminFuelCharge->value, $charge->source_module);
        $this->assertEquals(BillingSourceEvent::AdminFuelChargeCreated->value, $charge->source_event);
        $this->assertEquals('OrderProduct', $charge->source_reference_type);
        $this->assertEquals(42, $charge->source_reference_id);
    }

    // ── Mobile metadata ────────────────────────────────────────────────────

    public function test_mobile_fuel_charge_stores_metadata(): void
    {
        $request = $this->makeRequest([
            'sourceModule'      => BillingSourceModule::MobileChecklist->value,
            'sourceEvent'       => BillingSourceEvent::ReturnChecklistFuelCharge->value,
            'metadata'          => [
                'fuel_initial_reading' => '3/4',
                'fuel_final_reading'   => '1/4',
                'submitted_by_user_id' => 7,
            ],
        ]);

        $charge = BillingEngine::charge($request);

        $this->assertEquals('3/4', $charge->metadata['fuel_initial_reading']);
        $this->assertEquals('1/4', $charge->metadata['fuel_final_reading']);
        $this->assertEquals(7, $charge->metadata['submitted_by_user_id']);
    }

    public function test_mobile_charge_is_identified_as_mobile_originated(): void
    {
        $charge = BillingEngine::charge($this->makeRequest([
            'sourceModule' => BillingSourceModule::MobileChecklist->value,
        ]));

        $this->assertTrue($charge->isMobileOriginated());
    }

    public function test_admin_charge_is_not_identified_as_mobile_originated(): void
    {
        $charge = BillingEngine::charge($this->makeRequest([
            'sourceModule' => BillingSourceModule::AdminFuelCharge->value,
        ]));

        $this->assertFalse($charge->isMobileOriginated());
    }

    public function test_mobile_damage_metadata_can_be_stored(): void
    {
        $request = $this->makeRequest([
            'type'         => BillingChargeType::Damage->value,
            'sourceModule' => BillingSourceModule::MobileChecklist->value,
            'sourceEvent'  => BillingSourceEvent::ReturnChecklistDamageCharge->value,
            'metadata'     => [
                'checklist_question_ids' => ['CQ-AAAA-1111', 'CQ-BBBB-2222'],
                'submitted_by_user_id'   => 7,
            ],
        ]);

        $charge = BillingEngine::charge($request);

        $this->assertEquals(BillingChargeType::Damage, $charge->billing_charge_type);
        $this->assertCount(2, $charge->metadata['checklist_question_ids']);
        $this->assertEquals('CQ-AAAA-1111', $charge->metadata['checklist_question_ids'][0]);
    }

    public function test_convenience_constructor_mobile_return_fuel_sets_all_fields(): void
    {
        $request = BillingChargeRequest::mobileReturnFuel(
            orderId: 10,
            customerId: 20,
            orderProductId: 42,
            amount: 50.00,
            submittedByUserId: 7,
            fuelInitialReading: '3/4',
            fuelFinalReading: '1/4',
        );

        $this->assertEquals(BillingChargeType::Fuel->value, $request->type);
        $this->assertEquals(BillingSourceModule::MobileChecklist->value, $request->sourceModule);
        $this->assertEquals(BillingSourceEvent::ReturnChecklistFuelCharge->value, $request->sourceEvent);
        $this->assertEquals('OrderProduct', $request->sourceReferenceType);
        $this->assertEquals(42, $request->sourceReferenceId);
        $this->assertEquals('mobile_checklist:42:fuel:1/4', $request->idempotencyKey);
        $this->assertEquals('3/4', $request->metadata['fuel_initial_reading']);
    }

    // ── Phase 5D: customer_account_id and tax_amount ──────────────────────

    public function test_charge_stores_customer_account_id_when_provided(): void
    {
        $charge = BillingEngine::charge($this->makeRequest(['customerAccountId' => 99]));

        $this->assertEquals(99, $charge->customer_account_id);
        $this->assertDatabaseHas('billing_charges', ['customer_account_id' => 99]);
    }

    public function test_charge_stores_tax_amount_when_provided(): void
    {
        $charge = BillingEngine::charge($this->makeRequest(['taxAmount' => 18.50]));

        $this->assertEquals(18.50, $charge->tax_amount);
        $this->assertDatabaseHas('billing_charges', ['tax_amount' => 18.50]);
    }

    public function test_charge_defaults_tax_amount_to_zero_when_not_provided(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        $this->assertEquals(0.0, $charge->tax_amount);
    }

    // ── Idempotency ────────────────────────────────────────────────────────

    public function test_second_charge_with_same_idempotency_key_returns_existing(): void
    {
        $key = 'mobile_checklist:42:fuel:1/4';

        $first  = BillingEngine::charge($this->makeRequest(['idempotencyKey' => $key]));
        $second = BillingEngine::charge($this->makeRequest(['amount' => 999.00, 'idempotencyKey' => $key]));

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals($first->unique_id, $second->unique_id);
        $this->assertEquals(1, BillingCharge::where('idempotency_key', $key)->count());
    }

    public function test_idempotent_charge_does_not_fire_event_on_second_call(): void
    {
        $key = 'mobile_checklist:42:fuel:1/4';

        Event::fake([BillingChargeCreatedEvent::class]);

        BillingEngine::charge($this->makeRequest(['idempotencyKey' => $key]));
        BillingEngine::charge($this->makeRequest(['idempotencyKey' => $key]));

        Event::assertDispatchedTimes(BillingChargeCreatedEvent::class, 1);
    }

    public function test_charges_without_idempotency_key_are_always_created(): void
    {
        BillingEngine::charge($this->makeRequest(['idempotencyKey' => null]));
        BillingEngine::charge($this->makeRequest(['idempotencyKey' => null]));

        $this->assertEquals(2, BillingCharge::count());
    }

    public function test_find_by_idempotency_key_returns_existing_charge(): void
    {
        $key    = 'mobile_checklist:42:fuel:1/4';
        $charge = BillingEngine::charge($this->makeRequest(['idempotencyKey' => $key]));

        $found = BillingEngine::findByIdempotencyKey($key);

        $this->assertNotNull($found);
        $this->assertEquals($charge->id, $found->id);
    }

    public function test_find_by_idempotency_key_returns_null_when_not_found(): void
    {
        $found = BillingEngine::findByIdempotencyKey('no-such-key');

        $this->assertNull($found);
    }

    // ── Status transitions ─────────────────────────────────────────────────

    public function test_mark_paid_transitions_status(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        BillingEngine::markPaid($charge);

        $this->assertEquals(BillingChargeStatus::Paid, $charge->fresh()->status);
        $this->assertTrue($charge->fresh()->isPaid());
    }

    public function test_mark_resolved_transitions_status_and_sets_note(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        BillingEngine::markResolved($charge, 'Waived — equipment damage was pre-existing', userId: 1);

        $fresh = $charge->fresh();
        $this->assertEquals(BillingChargeStatus::Resolved, $fresh->status);
        $this->assertStringContainsString('pre-existing', $fresh->notes);
    }

    public function test_mark_uncollectible_transitions_status(): void
    {
        $charge = BillingEngine::charge($this->makeRequest());

        BillingEngine::markUncollectible($charge, userId: 1);

        $this->assertEquals(BillingChargeStatus::Uncollectible, $charge->fresh()->status);
    }

    // ── Enums ──────────────────────────────────────────────────────────────

    public function test_all_charge_types_have_labels(): void
    {
        foreach (BillingChargeType::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }

    public function test_all_source_modules_have_labels(): void
    {
        foreach (BillingSourceModule::cases() as $module) {
            $this->assertNotEmpty($module->label());
        }
    }

    public function test_all_source_events_have_labels(): void
    {
        foreach (BillingSourceEvent::cases() as $event) {
            $this->assertNotEmpty($event->label());
        }
    }

    public function test_mobile_source_modules_are_identified_correctly(): void
    {
        $this->assertTrue(BillingSourceModule::MobileChecklist->isMobileOriginated());
        $this->assertTrue(BillingSourceModule::MobileOfflineSync->isMobileOriginated());
        $this->assertTrue(BillingSourceModule::ReturnChecklist->isMobileOriginated());
        $this->assertFalse(BillingSourceModule::AdminFuelCharge->isMobileOriginated());
        $this->assertFalse(BillingSourceModule::RentalExtension->isMobileOriginated());
    }

    public function test_fuel_and_damage_are_mobile_eligible_charge_types(): void
    {
        $this->assertTrue(BillingChargeType::Fuel->isMobileEligible());
        $this->assertTrue(BillingChargeType::Damage->isMobileEligible());
        $this->assertFalse(BillingChargeType::Extension->isMobileEligible());
        $this->assertFalse(BillingChargeType::ServiceTicket->isMobileEligible());
    }

    public function test_extension_is_the_only_type_that_creates_child_order(): void
    {
        $this->assertTrue(BillingChargeType::Extension->createsChildOrder());

        foreach (BillingChargeType::cases() as $type) {
            if ($type !== BillingChargeType::Extension) {
                $this->assertFalse($type->createsChildOrder(), "{$type->value} should not create child order");
            }
        }
    }

    public function test_source_event_maps_to_expected_module(): void
    {
        $this->assertEquals(
            BillingSourceModule::MobileChecklist,
            BillingSourceEvent::ReturnChecklistFuelCharge->expectedModule()
        );
        $this->assertEquals(
            BillingSourceModule::AdminFuelCharge,
            BillingSourceEvent::AdminFuelChargeCreated->expectedModule()
        );
        $this->assertEquals(
            BillingSourceModule::RentalExtension,
            BillingSourceEvent::RentalExtensionCreated->expectedModule()
        );
    }
}
