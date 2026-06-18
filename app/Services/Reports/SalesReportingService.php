<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Orders\OrderProduct;

/**
 * Centralized reporting engine — shared by all Sales Reports.
 *
 * Every future report starts here via baseQuery() then adds its own
 * selects/aggregations. This is the single source of truth for filter
 * logic, date-range resolution, and the "finalized orders only" constraint.
 */
class SalesReportingService
{
    /**
     * Build a filterable base query over order_products joined to orders,
     * products, and stores. Only non-deleted orders are included.
     *
     * Callers add their own selects/aggregations on top of this.
     */
    public function baseQuery(array $filters = []): Builder
    {
        $query = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('stores', 'stores.id', '=', 'order_products.delivery_store_id')
            // Use a subquery to grab each product's primary (lowest-id) category —
            // a product may belong to multiple categories; we pick one to avoid row duplication.
            ->leftJoinSub(
                \DB::table('product_category_children')
                    ->select('product_id', \DB::raw('MIN(product_category_id) as primary_category_id'))
                    ->groupBy('product_id'),
                'pcc',
                'pcc.product_id',
                '=',
                'order_products.product_id'
            )
            ->leftJoin('product_categories as pc', 'pc.id', '=', 'pcc.primary_category_id')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_products.deleted_at');

        return $this->applyFilters($query, $filters);
    }

    /**
     * Apply all report filters to an existing builder.
     * Safe to call independently for sub-queries.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Date range
        [$start, $end] = $this->resolveDateRange($filters);
        if ($start && $end) {
            $query->whereBetween('orders.order_date', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        }

        // Store
        if (!empty($filters['store'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_products.delivery_store_id', $filters['store'])
                  ->orWhere('order_products.pickup_store_id', $filters['store']);
            });
        }

        // Item type (maps to products.product_type)
        if (!empty($filters['item_type']) && $filters['item_type'] !== 'all') {
            $query->where('products.product_type', ucfirst($filters['item_type']));
        }

        // Category
        if (!empty($filters['category'])) {
            $query->where('pcc.primary_category_id', $filters['category']);
        }

        // Product (dependent on category when category is set)
        if (!empty($filters['product'])) {
            $query->where('order_products.product_id', $filters['product']);
        }

        // Damage Waiver — stored as a value inside product_data.product_rental_items JSON array
        if (!empty($filters['damage_waiver']) && $filters['damage_waiver'] !== 'all') {
            $hasDw = "JSON_SEARCH(order_products.product_data, 'one', 'rental_damage_waiver', NULL, '$.product_rental_items') IS NOT NULL";
            if ($filters['damage_waiver'] === 'only') {
                $query->whereRaw($hasDw);
            } elseif ($filters['damage_waiver'] === 'exclude') {
                $query->whereRaw(str_replace('IS NOT NULL', 'IS NULL', $hasDw));
            }
        }

        // Track Insurance
        if (!empty($filters['track_insurance']) && $filters['track_insurance'] !== 'all') {
            $hasTi = "JSON_SEARCH(order_products.product_data, 'one', 'rental_track_insurance', NULL, '$.product_rental_items') IS NOT NULL";
            if ($filters['track_insurance'] === 'only') {
                $query->whereRaw($hasTi);
            } elseif ($filters['track_insurance'] === 'exclude') {
                $query->whereRaw(str_replace('IS NOT NULL', 'IS NULL', $hasTi));
            }
        }

        // Delivery — service_method stored in product_data JSON
        if (!empty($filters['delivery']) && $filters['delivery'] !== 'all') {
            if ($filters['delivery'] === 'only') {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) = 'Delivery'");
            } elseif ($filters['delivery'] === 'exclude') {
                $query->where(function ($q) {
                    $q->whereRaw("JSON_EXTRACT(order_products.product_data, '$.service_method') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) != 'Delivery'");
                });
            }
        }

        // Shipping — service_method stored in product_data JSON
        if (!empty($filters['shipping']) && $filters['shipping'] !== 'all') {
            if ($filters['shipping'] === 'only') {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) = 'Shipping'");
            } elseif ($filters['shipping'] === 'exclude') {
                $query->where(function ($q) {
                    $q->whereRaw("JSON_EXTRACT(order_products.product_data, '$.service_method') IS NULL")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(order_products.product_data, '$.service_method')) != 'Shipping'");
                });
            }
        }

        // Payment status — matches against the most recent order_payment record
        // 'paid'    = Card / Cash / Online / Cheque / Other + COD with status='Paid' (all realized)
        // 'pod'     = COD with status != 'Paid' (collected on delivery, not yet realized)
        // 'account' = Account method (not realized until CustomerAccount payment received)
        $paymentStatus = $filters['payment_status'] ?? 'paid';
        if ($paymentStatus !== 'all') {
            $query->whereExists(function ($sub) use ($paymentStatus) {
                $sub->selectRaw('1')
                    ->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'orders.id')
                    ->whereRaw('order_payments.id = (SELECT MAX(op2.id) FROM order_payments op2 WHERE op2.order_id = orders.id)');

                if ($paymentStatus === 'paid') {
                    // Exclude Account; include all non-COD methods + COD that has been collected
                    $sub->where('order_payments.payment_method', '!=', 'Account')
                        ->where(function ($q) {
                            $q->where('order_payments.payment_method', '!=', 'COD')
                              ->orWhere(function ($q2) {
                                  $q2->where('order_payments.payment_method', 'COD')
                                     ->where('order_payments.status', 'Paid');
                              });
                        });
                } elseif ($paymentStatus === 'pod') {
                    // COD only where payment has NOT yet been collected
                    $sub->where('order_payments.payment_method', 'COD')
                        ->where('order_payments.status', '!=', 'Paid');
                } elseif ($paymentStatus === 'account') {
                    $sub->where('order_payments.payment_method', 'Account');
                }
            });
        }

        return $query;
    }

    /**
     * Resolve filter inputs to [Carbon $start, Carbon $end] (or [null, null]).
     */
    public function resolveDateRange(array $filters): array
    {
        $range = $filters['date_range'] ?? null;
        $now   = Carbon::now();

        if ($range === 'custom') {
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                return [
                    Carbon::parse($filters['start_date'])->startOfDay(),
                    Carbon::parse($filters['end_date'])->endOfDay(),
                ];
            }
            return [null, null];
        }

        return match ($range) {
            'today'     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7'    => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30'   => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'mtd'       => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'qtd'       => [$now->copy()->startOfQuarter(), $now->copy()->endOfDay()],
            'ytd'       => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default     => [null, null],
        };
    }

    /**
     * Build date-range label for display (e.g. "Jun 1 – Jun 17, 2026").
     */
    public function dateRangeLabel(array $filters): string
    {
        [$start, $end] = $this->resolveDateRange($filters);
        if (!$start || !$end) {
            return 'All Time';
        }
        if ($start->isSameDay($end)) {
            return $start->format('M j, Y');
        }
        if ($start->year === $end->year) {
            return $start->format('M j') . ' – ' . $end->format('M j, Y');
        }
        return $start->format('M j, Y') . ' – ' . $end->format('M j, Y');
    }
}
