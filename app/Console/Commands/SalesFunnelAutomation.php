<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;

use App\Models\Customers\SalesFunnel;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\TwilioService;

class SalesFunnelAutomation extends Command
{
    protected $signature = 'sales:funnel-automation';
    protected $description = 'Automate sales funnel progression and lead nurturing';
    private const MATCH_WINDOW_MINUTES = 5;

    public function handle()
    {
        $logger = Log::channel('sales_funnel');

        try {
            $twilio = app(TwilioService::class);
        } catch (\Throwable $e) {
            $logger->error('Failed to initialize Twilio service', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Unable to initialize SMS service.');
            return self::FAILURE;
        }

        $logger->info('=== Sales funnel automation START ===', [
            'run_at' => now()->toDateTimeString(),
        ]);

        $this->info('Starting sales funnel automation...');

        // 1) Load active funnels
        $funnels = SalesFunnel::query()
            ->where('status', "Active")
            ->with('products')
            ->get();

        $logger->info('Active funnels loaded', [
            'count' => $funnels->count(),
        ]);

        foreach ($funnels as $funnel) {
            try {
                if (!$this->shouldRunFunnelNow($funnel)) {
                    $logger->info('Skipped funnel (invalid trigger configuration)', [
                        'funnel_id' => $funnel->id,
                        'unique_id' => $funnel->unique_id,
                        'trigger_event' => $funnel->trigger_event,
                        'timing' => $funnel->trigger_event_timing,
                    ]);
                    continue;
                }

                $expectedEventMoment = $this->resolveExpectedEventMoment($funnel);
                if ($expectedEventMoment === null) {
                    $logger->warning('Unable to resolve target moment for funnel', [
                        'funnel_id' => $funnel->id,
                        'unique_id' => $funnel->unique_id,
                        'trigger_event' => $funnel->trigger_event,
                        'timing' => $funnel->trigger_event_timing,
                    ]);
                    continue;
                }

                $productIds = $this->funnelProductIds($funnel);

                if ($productIds->isEmpty()) {
                    $logger->info('Skipped funnel (no products selected)', [
                        'funnel_id' => $funnel->id,
                        'unique_id' => $funnel->unique_id,
                    ]);
                    continue;
                }

                $logger->info('Processing funnel', [
                    'funnel_id' => $funnel->id,
                    'unique_id' => $funnel->unique_id,
                    'trigger_event' => $funnel->trigger_event,
                    'timing' => $funnel->trigger_event_timing,
                    'product_filter_count' => $productIds->count(),
                ]);

                $targets = $this->fetchOrdersForFunnel($funnel, $expectedEventMoment, $productIds);
                $dueTargets = $targets->filter(function ($target) use ($funnel) {
                    return $this->shouldSendTargetNow($funnel, $target['event_at']);
                })->values();

                $logger->info('Funnel targets evaluated', [
                    'funnel_id' => $funnel->id,
                    'expected_event_at' => $expectedEventMoment->toDateTimeString(),
                    'candidate_count' => $targets->count(),
                    'due_count' => $dueTargets->count(),
                ]);

                // 3) Perform action on each due order
                foreach ($dueTargets as $target) {
                    $this->performFunnelAction(
                        $funnel,
                        $target['order'],
                        $logger,
                        $twilio,
                        [
                            'event_at' => $target['event_at']->toDateTimeString(),
                            'meta' => $target['meta'] ?? [],
                        ]
                    );
                }

            } catch (\Throwable $e) {
                $logger->error('Error processing funnel', [
                    'funnel_id' => $funnel->id ?? null,
                    'unique_id' => $funnel->unique_id ?? null,
                    'error' => $e->getMessage(),
                ]);

                // Keep command running for other funnels
                continue;
            }
        }

        $logger->info('=== Sales funnel automation END ===', [
            'run_at' => now()->toDateTimeString(),
        ]);

        $this->info('Sales funnel automation completed successfully!');
        return self::SUCCESS;
    }

    /**
     * Decide if funnel should run at this moment.
     * Adjust rules to your exact meaning of trigger_event_timing + minute/hour/date fields.
     */
    private function shouldRunFunnelNow(SalesFunnel $funnel): bool
    {
        $validEvents = ['Rental Start Date', 'New Lead Added'];
        $validTiming = ['Before Event', 'After Event'];

        return in_array($funnel->trigger_event, $validEvents, true)
            && in_array($funnel->trigger_event_timing, $validTiming, true);
    }

    private function fetchOrdersForFunnel(SalesFunnel $funnel, Carbon $expectedEventMoment, Collection $productIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        [$windowStart, $windowEnd] = $this->matchingWindow($expectedEventMoment);

        return match ($funnel->trigger_event) {
            'Rental Start Date' => $this->fetchRentalTargets($windowStart, $windowEnd, $productIds),
            'New Lead Added' => $this->fetchLeadTargets($windowStart, $windowEnd, $productIds),
            default => collect(),
        };
    }

    private function fetchRentalTargets(Carbon $windowStart, Carbon $windowEnd, Collection $productIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        $timezone = $this->appTimezone();
        $dates = collect([
            $windowStart->copy()->setTimezone($timezone)->toDateString(),
            $windowEnd->copy()->setTimezone($timezone)->toDateString(),
        ])->sort()->values();

        $products = OrderProduct::query()
            ->with(['order.shippingAddress', 'order.billingAddress'])
            ->whereBetween('delivery_date', [$dates->first(), $dates->last()])
            ->whereNotNull('delivery_date')
            ->where('product_data->product_type', 'Rental')
            ->whereIn('product_id', $productIds->all())
            ->get();

        return $products
            ->map(function (OrderProduct $product) use ($windowStart, $windowEnd) {
                $eventMoment = $this->buildEventMoment($product->delivery_date, $product->delivery_time);

                if ($eventMoment === null || ! $eventMoment->between($windowStart, $windowEnd) || $product->order === null) {
                    return null;
                }

                return [
                    'order' => $product->order,
                    'event_at' => $eventMoment,
                    'meta' => [
                        'order_product_id' => $product->id,
                        'order_product_unique_id' => $product->unique_id,
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    private function fetchLeadTargets(Carbon $windowStart, Carbon $windowEnd, Collection $productIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        $windowStartUtc = $windowStart->copy()->setTimezone('UTC');
        $windowEndUtc = $windowEnd->copy()->setTimezone('UTC');

        $orders = Order::query()
            ->with(['shippingAddress', 'billingAddress'])
            ->whereBetween('created_at', [$windowStartUtc, $windowEndUtc])
            ->whereHas('products', function ($query) use ($productIds) {
                $query->whereIn('product_id', $productIds->all());
            })
            ->get();

        return $orders->map(function (Order $order) {
            $eventMoment = ($order->created_at instanceof Carbon
                ? $order->created_at->copy()
                : Carbon::parse($order->created_at))
                ->setTimezone($this->appTimezone());

            return [
                'order' => $order,
                'event_at' => $eventMoment,
                'meta' => [],
            ];
        });
    }

    private function resolveExpectedEventMoment(SalesFunnel $funnel): ?Carbon
    {
        $now = Carbon::now($this->appTimezone());
        $offset = $this->resolveOffsetInterval($funnel);

        return match ($funnel->trigger_event_timing) {
            'Before Event' => $now->copy()->add($offset),
            'After Event' => $now->copy()->sub($offset),
            default => null,
        };
    }

    private function shouldSendTargetNow(SalesFunnel $funnel, Carbon $eventMoment): bool
    {
        $offset = $this->resolveOffsetInterval($funnel);

        $sendMoment = match ($funnel->trigger_event_timing) {
            'Before Event' => $eventMoment->copy()->sub($offset),
            'After Event' => $eventMoment->copy()->add($offset),
            default => null,
        };

        if ($sendMoment === null) {
            return false;
        }

        [$windowStart, $windowEnd] = $this->matchingWindow($sendMoment);
        $now = Carbon::now($this->appTimezone());

        return $now->between($windowStart, $windowEnd);
    }

    private function resolveOffsetInterval(SalesFunnel $funnel): CarbonInterval
    {
        $days = (int) ($funnel->date_value ?? 0);
        $hours = (int) ($funnel->hour_value ?? 0);
        $minutes = (int) ($funnel->minute_value ?? 0);

        return CarbonInterval::days($days)->hours($hours)->minutes($minutes);
    }

    private function matchingWindow(Carbon $targetMoment): array
    {
        $minutes = (int) (self::MATCH_WINDOW_MINUTES);
        $minutes = max(1, $minutes);

        return [
            $targetMoment->copy()->subMinutes($minutes),
            $targetMoment->copy()->addMinutes($minutes),
        ];
    }

    private function buildEventMoment(?string $date, ?string $time): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $timePart = $time ?: '00:00:00';

        try {
            return Carbon::parse($date . ' ' . $timePart, $this->appTimezone());
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function appTimezone(): string
    {
        return 'Asia/Kolkata';
        // return 'America/Chicago';
    }

    private function funnelProductIds(SalesFunnel $funnel): Collection
    {
        if (! $funnel->relationLoaded('products')) {
            $funnel->loadMissing('products');
        }

        return $funnel->products
            ->pluck('id')
            ->filter()
            ->values();
    }

    /**
     * Perform the action for this funnel on a specific order.
     */
    private function performFunnelAction(SalesFunnel $funnel, Order $order, $logger, TwilioService $twilio, array $context = []): void
    {
        $message = trim((string) $funnel->description);

        if ($message === '') {
            $logger->info('Skipped funnel action due to empty message', [
                'funnel_id' => $funnel->id,
                'order_id' => $order->id,
                'context' => $context,
            ]);
            return;
        }

        $phone = $this->resolveOrderPhone($order);

        if (!$phone) {
            $logger->warning('No phone number found for order', [
                'funnel_id' => $funnel->id,
                'order_id' => $order->id,
                'context' => $context,
            ]);
            return;
        }

        $response = $twilio->sendSms($phone, $message);

        if (($response['success'] ?? false) === true) {
            $logger->info('Funnel SMS sent', [
                'funnel_id' => $funnel->id,
                'order_id' => $order->id,
                'phone' => $phone,
                'twilio_sid' => $response['sid'] ?? null,
                'context' => $context,
            ]);
        } else {
            $logger->warning('Failed to send funnel SMS', [
                'funnel_id' => $funnel->id,
                'order_id' => $order->id,
                'phone' => $phone,
                'error' => $response['message'] ?? 'unknown',
                'context' => $context,
            ]);
        }
    }

    private function resolveOrderPhone(Order $order): ?string
    {
        $order->loadMissing('shippingAddress', 'billingAddress');

        return $order->customer_phone
            ?? $order->shippingAddress?->phone
            ?? $order->billingAddress?->phone;
    }
}
