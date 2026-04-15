<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = ProductCategory::getHierarchy();

        $stores = Store::all();

        // Pass enum values to the view
        $statuses = EquipmentCurrentStatus::cases(); // returns all enum cases

        if ($request->filled('past_seven_days') && $request->past_seven_days === '1') {
            $startDate = now()->subDays(6); // 7 days total (including today)
            $endDate = now();
        } else {
            $startDate = now();
            $endDate = $startDate->copy()->addDays(13); // 14 days total (2 weeks)
        }
        $dates = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates[] = $date->copy();
        }
        if ($request->ajax()) {
            $query = Equipment::with(
                'statusUpdatedByUser',
                'productCategory',
                'order',
                'order.customer',
                'store',
                'orderProduct',
                'lastOrderProduct',
                'activeEquipmentRentalReadyTemplate',
                'softAssignments',
                'softAssignments.order',
                'softAssignments.orderProduct',
                'softAssignments.orderProduct.order',
                'overdueOrderProducts'
            )
                ->where('not_for_rent', 0)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($sub) use ($search) {
                        $sub->where('equipment_name', 'like', "%{$search}%")
                            ->orWhere('equipment_id', 'like', "%{$search}%")
                            ->orWhereHas('order', function ($orderQ) use ($search) {
                                $orderQ->where('customer_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('softAssignments', function ($saQ) use ($search) {
                                $saQ->whereHas('order', function ($orderQ2) use ($search) {
                                    $orderQ2->where('customer_name', 'like', "%{$search}%");
                                });
                            });
                    });
                })
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->where('product_category_id', $request->category);
                })
                ->when($request->filled('store'), function ($q) use ($request) {
                    $q->whereHas('lastOrderProduct', function ($q) use ($request) {
                        $q->where('pickup_store_id', $request->store);
                    });
                })
                ->when(
                    $request->filled('equipment_status'),
                    function ($q) use ($request) {
                        $q->whereIn('current_status', $request->equipment_status);
                    },
                    function ($q) {
                        $q->whereIn('current_status', EquipmentCurrentStatus::getValues());
                    },
                )
                ->when($request->filled('assignment_filter'), function ($q) use ($request, $startDate, $endDate) {
                    $filter = $request->assignment_filter;

                    if ($filter == 'assigned') {
                        $q->where(function ($subQ) use ($startDate, $endDate) {
                            // HARD assignment: lastOrderProduct (or orderProduct) in current window
                            $subQ
                                ->whereHas('lastOrderProduct', function ($lop) use ($startDate, $endDate) {
                                    $lop->whereDate('delivery_date', '<=', $endDate)->whereDate('pickup_date', '>=', $startDate);
                                })
                                ->orWhereHas('softAssignments.orderProduct', function ($op) use ($startDate, $endDate) {
                                    $op->whereDate('delivery_date', '<=', $endDate)->whereDate('pickup_date', '>=', $startDate);
                                });
                        });
                    } elseif ($filter == 'assigned_3_days') {
                        $firstThreeDaysEnd = $startDate->copy()->addDays(2);

                        $q->where(function ($subQ) use ($startDate, $firstThreeDaysEnd) {
                            $subQ
                                ->whereHas('lastOrderProduct', function ($lop) use ($startDate, $firstThreeDaysEnd) {
                                    $lop->where(function ($d) use ($startDate, $firstThreeDaysEnd) {
                                        $d->whereDate('delivery_date', '>=', $startDate)
                                            ->whereDate('delivery_date', '<=', $firstThreeDaysEnd)
                                            //   ->where('delivery_status', 'Pending')
                                            ->orWhere(function ($sub) use ($startDate, $firstThreeDaysEnd) {
                                                $sub->whereDate('pickup_date', '>=', $startDate)->whereDate('pickup_date', '<=', $firstThreeDaysEnd);
                                            });
                                    });
                                })
                                ->orWhereHas('softAssignments.orderProduct', function ($op) use ($startDate, $firstThreeDaysEnd) {
                                    $op->where(function ($d) use ($startDate, $firstThreeDaysEnd) {
                                        $d->whereDate('delivery_date', '>=', $startDate)
                                            ->whereDate('delivery_date', '<=', $firstThreeDaysEnd)
                                            //   ->where('delivery_status', 'Pending')
                                            ->orWhere(function ($sub) use ($startDate, $firstThreeDaysEnd) {
                                                $sub->whereDate('pickup_date', '>=', $startDate)->whereDate('pickup_date', '<=', $firstThreeDaysEnd);
                                            });
                                    });
                                });
                        });
                    }
                })
                ->when($request->filled('overdue') && $request->overdue === '1', function ($q) {
                    $q->whereHas('overdueOrderProducts');
                });

            $order = ['damaged', 'maintenance', 'rented', 'available'];
            $query
                ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
                ->select('equipment.*') // keep equipment columns
                //->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
                ->orderBy('product_categories.title', 'asc')
                ->orderBy('equipment_name', 'asc')
                ->orderBy('equipment_id', 'asc');
            $perPage = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $equipment = $query->paginate(max(1, $query->count()))->withQueryString();

            $html = view('admin.order_management.schedule_assignment.partials._table', compact('equipment', 'dates'))->render();

            $scheduleCategoryIds = [];
            if ($request->filled('category')) {
                $selectedCategory = ProductCategory::with('schedulesCategories:id,schedule_assignment_category_id')
                    ->find($request->category);

                $scheduleCategoryIds = collect($selectedCategory?->schedulesCategories?->pluck('id') ?? [])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }



            if (!empty($scheduleCategoryIds)) {
                // Build groups from a cloned query so we can apply schedule category rules independently.
                $groupQuery = Equipment::query()
                    ->where('not_for_rent', 0);

                $groupQuery->whereIn('equipment.product_category_id', $scheduleCategoryIds);

                $groupEquipment = $groupQuery->get();
            }else {
                $groupEquipment = collect([]); // empty collection if no schedule categories to group by
            }


            $groups = collect($groupEquipment)
                ->groupBy('equipment_name')
                ->map(function ($group, $name) {
                    return [
                        'name'        => $name,
                        'total'       => $group->count(),
                        'available'   => $group->filter(fn($e) => $e->status_label === 'Available')->count(),
                        'rented'      => $group->filter(fn($e) => $e->status_label === 'Rented')->count(),
                        'maintenance' => $group->filter(fn($e) => $e->status_label === 'Maint. Hold')->count(),
                        'damaged'     => $group->filter(fn($e) => $e->status_label === 'Damaged')->count(),
                    ];
                })
                ->values();

            return response()->json([
                'html'   => $html,
                'total'  => $equipment->count(),
                'groups' => $groups,
            ]);
        }

        $users = User::active()->orderBy('first_name', 'asc')
            ->get()
            ->map(function ($user) {
                return [
                    'unique_id' => $user->unique_id,
                    'full_name' => $user->full_name,
                ];
            });

        $employees = $users->pluck('full_name', 'unique_id')->prepend('Select Employee', '');

        return view('admin.order_management.schedule_assignment.index', compact('categories', 'stores', 'dates', 'statuses', 'employees'));
    }
}
