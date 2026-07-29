<?php

namespace Tests\Unit\Dispatch;

use App\Services\Dispatch\DispatchReorderService;
use PHPUnit\Framework\TestCase;

/**
 * Pure sequencing rules for the Dispatch drag-and-drop board. No database.
 *
 * Convention in these tests: "D:x" is a delivery-leg card with uid x, "R:x" a
 * return-leg card. Helpers build/flatten the ['uid','leg'] item shape.
 */
class DispatchReorderServiceTest extends TestCase
{
    private DispatchReorderService $svc;

    protected function setUp(): void
    {
        $this->svc = new DispatchReorderService();
    }

    /** @param string[] $tokens e.g. ['D:a','R:b'] */
    private function items(array $tokens): array
    {
        return array_map(function ($t) {
            [$leg, $uid] = explode(':', $t, 2);
            return ['uid' => $uid, 'leg' => $leg === 'D' ? 'delivery' : 'return'];
        }, $tokens);
    }

    /** Flatten items back to tokens for readable assertions. */
    private function tokens(array $items): array
    {
        return array_map(
            fn ($it) => ($it['leg'] === 'delivery' ? 'D' : 'R') . ':' . $it['uid'],
            $items
        );
    }

    public function test_reorder_deliveries_within_driver_pins_the_return(): void
    {
        // Route: D:a, R:x, D:b, D:c  →  drag D:c to the front of the delivery column.
        $current = $this->items(['D:a', 'R:x', 'D:b', 'D:c']);
        $newLegOrder = ['c', 'a', 'b']; // delivery column, new order

        $result = $this->svc->reconcileSeparate($current, $newLegOrder, 'delivery');

        // Delivery slots (positions 0,2,3) get c,a,b in order; R:x stays at slot 1.
        $this->assertSame(['D:c', 'R:x', 'D:a', 'D:b'], $this->tokens($result));
    }

    public function test_reorder_returns_within_driver_pins_deliveries(): void
    {
        $current = $this->items(['D:a', 'R:x', 'D:b', 'R:y']);
        $newLegOrder = ['y', 'x']; // return column reordered

        $result = $this->svc->reconcileSeparate($current, $newLegOrder, 'return');

        $this->assertSame(['D:a', 'R:y', 'D:b', 'R:x'], $this->tokens($result));
    }

    public function test_departure_removes_card_and_keeps_the_rest(): void
    {
        // D:b leaves this driver; remaining delivery column is [a, c].
        $current = $this->items(['D:a', 'R:x', 'D:b', 'D:c']);
        $result = $this->svc->reconcileSeparate($current, ['a', 'c'], 'delivery', null, 'b');

        $this->assertSame(['D:a', 'R:x', 'D:c'], $this->tokens($result));
    }

    public function test_arrival_inserts_after_its_leg_predecessor(): void
    {
        // Driver B currently has D:a, R:x, D:b. D:n arrives and is dropped between
        // a and b in the delivery column → new leg order [a, n, b].
        $current = $this->items(['D:a', 'R:x', 'D:b']);
        $result = $this->svc->reconcileSeparate($current, ['a', 'n', 'b'], 'delivery', 'n', null);

        // n is inserted right after a (its predecessor), then slots are filled in order.
        $this->assertSame(['D:a', 'D:n', 'R:x', 'D:b'], $this->tokens($result));
    }

    public function test_arrival_at_front_when_first_in_leg_order(): void
    {
        $current = $this->items(['R:x', 'D:a']);
        $result = $this->svc->reconcileSeparate($current, ['n', 'a'], 'delivery', 'n', null);

        $this->assertSame(['D:n', 'R:x', 'D:a'], $this->tokens($result));
    }

    public function test_arrival_into_empty_leg_column(): void
    {
        // Driver has only returns; a delivery arrives.
        $current = $this->items(['R:x', 'R:y']);
        $result = $this->svc->reconcileSeparate($current, ['n'], 'delivery', 'n', null);

        $this->assertSame(['D:n', 'R:x', 'R:y'], $this->tokens($result));
    }

    /** @param array<string,string|null> $dates keyed by token e.g. ['R:x'=>'2026-07-30'] */
    private function dateMap(array $dates): array
    {
        $out = [];
        foreach ($dates as $tok => $d) {
            [$leg, $uid] = explode(':', $tok, 2);
            $out[$uid . '|' . ($leg === 'D' ? 'delivery' : 'return')] = $d;
        }
        return $out;
    }

    public function test_insert_by_date_places_earliest_at_front(): void
    {
        // The screenshot case: a Jul 27 return dropped onto a list dated 30/28/29 →
        // it must jump to the front, not the bottom.
        $current = $this->items(['R:a', 'R:b', 'R:c']);
        $dates = $this->dateMap([
            'R:a' => '2026-07-30', 'R:b' => '2026-07-28', 'R:c' => '2026-07-29',
            'R:n' => '2026-07-27',
        ]);
        $result = $this->svc->insertByDate($current, ['uid' => 'n', 'leg' => 'return'], $dates);

        $this->assertSame(['R:n', 'R:a', 'R:b', 'R:c'], $this->tokens($result));
    }

    public function test_insert_by_date_places_in_the_middle_by_first_later_date(): void
    {
        // List dated 27, 28, 30; a 29 arrives → lands before the first later date (30).
        $current = $this->items(['R:a', 'R:b', 'R:c']);
        $dates = $this->dateMap([
            'R:a' => '2026-07-27', 'R:b' => '2026-07-28', 'R:c' => '2026-07-30',
            'R:n' => '2026-07-29',
        ]);
        $result = $this->svc->insertByDate($current, ['uid' => 'n', 'leg' => 'return'], $dates);

        $this->assertSame(['R:a', 'R:b', 'R:n', 'R:c'], $this->tokens($result));
    }

    public function test_insert_by_date_appends_when_latest_or_undated(): void
    {
        $current = $this->items(['R:a', 'R:b']);
        $dates = $this->dateMap(['R:a' => '2026-07-27', 'R:b' => '2026-07-28', 'R:n' => '2026-08-05']);
        $this->assertSame(['R:a', 'R:b', 'R:n'], $this->tokens(
            $this->svc->insertByDate($current, ['uid' => 'n', 'leg' => 'return'], $dates)
        ));

        // Undated arriving card sorts last.
        $datesNull = $this->dateMap(['R:a' => '2026-07-27', 'R:b' => '2026-07-28', 'R:n' => null]);
        $this->assertSame(['R:a', 'R:b', 'R:n'], $this->tokens(
            $this->svc->insertByDate($current, ['uid' => 'n', 'leg' => 'return'], $datesNull)
        ));
    }

    public function test_insert_by_date_into_empty_list_is_first(): void
    {
        $result = $this->svc->insertByDate([], ['uid' => 'n', 'leg' => 'delivery'], $this->dateMap(['D:n' => '2026-07-27']));
        $this->assertSame(['D:n'], $this->tokens($result));
    }

    public function test_positions_are_contiguous_1_to_n_across_legs(): void
    {
        $unified = $this->items(['D:a', 'R:x', 'D:b', 'D:c']);
        $positions = $this->svc->positions($unified);

        $this->assertSame([
            ['uid' => 'a', 'leg' => 'delivery', 'priority' => 1],
            ['uid' => 'x', 'leg' => 'return',   'priority' => 2],
            ['uid' => 'b', 'leg' => 'delivery', 'priority' => 3],
            ['uid' => 'c', 'leg' => 'delivery', 'priority' => 4],
        ], $positions);
    }

    public function test_positions_encode_a_pickup_between_two_deliveries(): void
    {
        // The motivating case: a single pickup sequenced between two deliveries.
        // delivery_priority for a=1, b=3 (non-contiguous by design); pickup_priority x=2.
        $unified = $this->items(['D:a', 'R:x', 'D:b']);
        $positions = $this->svc->positions($unified);

        $byUid = [];
        foreach ($positions as $p) {
            $byUid[$p['uid']] = $p;
        }

        $this->assertSame(1, $byUid['a']['priority']);
        $this->assertSame(2, $byUid['x']['priority']);
        $this->assertSame(3, $byUid['b']['priority']);
        // The pickup (x, priority 2) sorts strictly between the two deliveries (1 and 3).
        $this->assertGreaterThan($byUid['a']['priority'], $byUid['x']['priority']);
        $this->assertLessThan($byUid['b']['priority'], $byUid['x']['priority']);
    }
}
