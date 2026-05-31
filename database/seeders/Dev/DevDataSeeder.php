<?php

namespace Database\Seeders\Dev;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Models
use App\Models\Customers\Customer;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\Orders\OrderAddress;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\Locations\State;

/**
 * DevDataSeeder — populates local dev with realistic equipment, products,
 * customers, and orders (Truck deliveries) for testing the Dispatch / Schedule pages.
 *
 * Safe to run multiple times; uses firstOrCreate where possible.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\Dev\DevDataSeeder"
 */
class DevDataSeeder extends Seeder
{
    // -----------------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------------
    private int $customerCount   = 12;
    private int $ordersPerCustomer = 2;   // orders to create per customer
    // -----------------------------------------------------------------------

    public function run(): void
    {
        $this->command->info('🌱  DevDataSeeder starting…');

        // Ensure base data exists
        $this->ensureBaseData();

        $tn      = State::where('abbreviation', 'TN')->firstOrFail();
        $bonAqua = Store::where('store_name', 'Bon Aqua')->firstOrFail();
        $charlotte = Store::where('store_name', 'Charlotte')->first() ?? $bonAqua;

        // 1. Product categories
        $this->command->info('  → Product categories');
        $categories = $this->seedCategories();

        // 2. Products
        $this->command->info('  → Products');
        $products = $this->seedProducts($categories);

        // 3. Equipment
        $this->command->info('  → Equipment');
        $equipment = $this->seedEquipment($categories, $bonAqua, $charlotte);

        // 4. Customers
        $this->command->info('  → Customers');
        $customers = $this->seedCustomers($tn);

        // 5. Orders + OrderProducts + Addresses + Payments
        $this->command->info('  → Orders');
        $this->seedOrders($customers, $products, $equipment, $tn);

        $this->command->info('✅  DevDataSeeder complete.');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['ProductCategory', ProductCategory::count()],
                ['Product',         Product::count()],
                ['Equipment',       Equipment::count()],
                ['Customer',        Customer::count()],
                ['Order',           Order::count()],
                ['OrderProduct',    OrderProduct::count()],
            ]
        );
    }

    // -----------------------------------------------------------------------
    // Base data (states / stores)
    // -----------------------------------------------------------------------
    private function ensureBaseData(): void
    {
        if (State::count() === 0) {
            (new \Database\Seeders\Locations\StateSeeder)->run();
        }
        if (Store::count() === 0) {
            (new \Database\Seeders\Stores\StoreSeeder)->run();
        }
    }

    // -----------------------------------------------------------------------
    // Categories
    // -----------------------------------------------------------------------
    private function seedCategories(): array
    {
        $data = [
            'Skid Steer'        => 'skid-steer',
            'Mini Excavator'    => 'mini-excavator',
            'Boom Lift'         => 'boom-lift',
            'Scissor Lift'      => 'scissor-lift',
            'Dump Trailer'      => 'dump-trailer',
            'Tractor'           => 'tractor',
            'Attachments'       => 'attachments',
        ];

        $result = [];
        foreach ($data as $title => $slug) {
            $cat = ProductCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'title'   => $title,
                    'slug'    => $slug,
                    'status'  => 'Published',
                ]
            );
            $result[$title] = $cat;
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Products  (one rentable product per main category)
    // -----------------------------------------------------------------------
    private function seedProducts(array $categories): array
    {
        $definitions = [
            [
                'category'     => 'Skid Steer',
                'product_name' => 'Skid Steer – Open Cab',
                'rental_daily' => 325,
                'rental_weekend' => 425,
                'rental_weekly' => 975,
                'rental_monthly'=> 2600,
            ],
            [
                'category'     => 'Skid Steer',
                'product_name' => 'Skid Steer w/Brush Cutter',
                'rental_daily' => 425,
                'rental_weekend' => 525,
                'rental_weekly' => 1150,
                'rental_monthly'=> 3200,
            ],
            [
                'category'     => 'Mini Excavator',
                'product_name' => 'Mini Excavator 1.7T',
                'rental_daily' => 275,
                'rental_weekend' => 375,
                'rental_weekly' => 875,
                'rental_monthly'=> 2200,
            ],
            [
                'category'     => 'Mini Excavator',
                'product_name' => 'Mini Excavator 3.5T',
                'rental_daily' => 375,
                'rental_weekend' => 475,
                'rental_weekly' => 1050,
                'rental_monthly'=> 2800,
            ],
            [
                'category'     => 'Boom Lift',
                'product_name' => '50ft Boom Lift',
                'rental_daily' => 550,
                'rental_weekend' => 700,
                'rental_weekly' => 1600,
                'rental_monthly'=> 4200,
            ],
            [
                'category'     => 'Scissor Lift',
                'product_name' => '26ft Scissor Lift',
                'rental_daily' => 350,
                'rental_weekend' => 450,
                'rental_weekly' => 1100,
                'rental_monthly'=> 2900,
            ],
            [
                'category'     => 'Dump Trailer',
                'product_name' => '14ft Dump Trailer',
                'rental_daily' => 175,
                'rental_weekend' => 225,
                'rental_weekly' => 525,
                'rental_monthly'=> 1400,
            ],
            [
                'category'     => 'Tractor',
                'product_name' => 'Compact Utility Tractor',
                'rental_daily' => 295,
                'rental_weekend' => 395,
                'rental_weekly' => 975,
                'rental_monthly'=> 2500,
            ],
        ];

        $result = [];
        foreach ($definitions as $def) {
            $cat = $categories[$def['category']] ?? null;

            $product = Product::firstOrCreate(
                ['product_name' => $def['product_name']],
                [
                    'product_type'    => 'Rental',
                    'slug'            => Str::slug($def['product_name']),
                    'status'          => 'Published',
                    'rental_daily'    => $def['rental_daily'],
                    'rental_weekend'  => $def['rental_weekend'],
                    'rental_weekly'   => $def['rental_weekly'],
                    'rental_monthly'  => $def['rental_monthly'],
                    'in_store_pickup'     => true,
                    'delivery_and_pickup' => true,
                ]
            );

            // Attach to category
            if ($cat && !$product->categories()->where('product_categories.id', $cat->id)->exists()) {
                $product->categories()->attach($cat->id);
            }

            $result[] = $product;
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Equipment
    // -----------------------------------------------------------------------
    private function seedEquipment(array $categories, Store $store1, Store $store2): array
    {
        $definitions = [
            // Skid Steers
            ['name'=>'Takeuchi TL12 Skid Steer',     'category'=>'Skid Steer',     'brand'=>'Takeuchi', 'model'=>'TL12',    'year'=>2022, 'serial'=>'TL12-0022-001', 'id_label'=>'SS-001', 'store'=>$store1, 'status'=>'available', 'hours'=>412],
            ['name'=>'Bobcat S450 Skid Steer',        'category'=>'Skid Steer',     'brand'=>'Bobcat',   'model'=>'S450',    'year'=>2021, 'serial'=>'S450-0021-001', 'id_label'=>'SS-002', 'store'=>$store1, 'status'=>'available', 'hours'=>688],
            ['name'=>'Bobcat S550 w/Brush Cutter',    'category'=>'Skid Steer',     'brand'=>'Bobcat',   'model'=>'S550',    'year'=>2023, 'serial'=>'S550-0023-001', 'id_label'=>'SS-003', 'store'=>$store2, 'status'=>'available', 'hours'=>205],
            ['name'=>'Caterpillar 262D Skid Steer',   'category'=>'Skid Steer',     'brand'=>'Caterpillar','model'=>'262D', 'year'=>2020, 'serial'=>'CAT-0020-001', 'id_label'=>'SS-004', 'store'=>$store1, 'status'=>'maintenance','hours'=>1340],

            // Mini Excavators
            ['name'=>'Kubota K008-3 Mini Ex',         'category'=>'Mini Excavator', 'brand'=>'Kubota',   'model'=>'K008-3', 'year'=>2022, 'serial'=>'KUB-0022-001', 'id_label'=>'EX-001', 'store'=>$store1, 'status'=>'available', 'hours'=>318],
            ['name'=>'Kubota U35-4 Mini Ex',          'category'=>'Mini Excavator', 'brand'=>'Kubota',   'model'=>'U35-4',  'year'=>2021, 'serial'=>'KUB-0021-002', 'id_label'=>'EX-002', 'store'=>$store2, 'status'=>'available', 'hours'=>544],
            ['name'=>'John Deere 35G Mini Ex',        'category'=>'Mini Excavator', 'brand'=>'John Deere','model'=>'35G',  'year'=>2020, 'serial'=>'JD-0020-001',  'id_label'=>'EX-003', 'store'=>$store1, 'status'=>'damaged',   'hours'=>2100],

            // Boom Lifts
            ['name'=>'JLG 450AJ Boom Lift',           'category'=>'Boom Lift',      'brand'=>'JLG',      'model'=>'450AJ',   'year'=>2021, 'serial'=>'JLG-0021-001', 'id_label'=>'BL-001', 'store'=>$store1, 'status'=>'available', 'hours'=>672],
            ['name'=>'Genie Z-45/25J Boom Lift',      'category'=>'Boom Lift',      'brand'=>'Genie',    'model'=>'Z-45/25J','year'=>2022, 'serial'=>'GEN-0022-001', 'id_label'=>'BL-002', 'store'=>$store2, 'status'=>'available', 'hours'=>299],

            // Scissor Lifts
            ['name'=>'JLG 2646ES Scissor Lift',       'category'=>'Scissor Lift',   'brand'=>'JLG',      'model'=>'2646ES',  'year'=>2022, 'serial'=>'JLG-SL-0022', 'id_label'=>'SL-001', 'store'=>$store1, 'status'=>'available', 'hours'=>158],
            ['name'=>'Genie GS-2632 Scissor Lift',    'category'=>'Scissor Lift',   'brand'=>'Genie',    'model'=>'GS-2632', 'year'=>2021, 'serial'=>'GEN-SL-0021', 'id_label'=>'SL-002', 'store'=>$store1, 'status'=>'rented',    'hours'=>890],

            // Dump Trailers
            ['name'=>'PJ Trailers 14ft Dump',         'category'=>'Dump Trailer',   'brand'=>'PJ Trailers','model'=>'DL',   'year'=>2023, 'serial'=>'PJT-0023-001', 'id_label'=>'DT-001', 'store'=>$store1, 'status'=>'available', 'hours'=>0],
            ['name'=>'BWise 14ft Dump Trailer',       'category'=>'Dump Trailer',   'brand'=>'BWise',    'model'=>'HD14-14','year'=>2022, 'serial'=>'BWI-0022-001', 'id_label'=>'DT-002', 'store'=>$store2, 'status'=>'available', 'hours'=>0],

            // Tractors
            ['name'=>'Kubota BX23S Tractor',          'category'=>'Tractor',        'brand'=>'Kubota',   'model'=>'BX23S',   'year'=>2022, 'serial'=>'KUB-TR-0022', 'id_label'=>'TR-001', 'store'=>$store1, 'status'=>'available', 'hours'=>223],
        ];

        // Detect whether the equipment table has been migrated to 'product_category_id'
        // or still uses the legacy 'category' column (rename migration may not have applied locally)
        $catColumn = \Illuminate\Support\Facades\Schema::hasColumn('equipment', 'product_category_id')
            ? 'product_category_id'
            : 'category';

        $result = [];
        foreach ($definitions as $def) {
            $cat = $categories[$def['category']] ?? null;

            // Check if this equipment_id already exists
            $existing = Equipment::where('equipment_id', $def['id_label'])->first();
            if ($existing) {
                $result[] = $existing;
                continue;
            }

            // Use DB::table to avoid fillable guard and work with the real column name
            $uniqueId = 'EQP-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
            DB::table('equipment')->insert([
                'unique_id'        => $uniqueId,
                'equipment_name'   => $def['name'],
                $catColumn         => $cat?->id,
                'equipment_id'     => $def['id_label'],
                'brand'            => $def['brand'],
                'model'            => $def['model'],
                'model_year'       => $def['year'],
                'serial_number'    => $def['serial'],
                'store_id'         => $def['store']->id,
                'current_status'   => $def['status'],
                'equipment_hours'  => $def['hours'],
                'not_for_rent'     => 0,
                'is_tracked'       => 'Yes',
                'ownership_type'   => 'owned',
                'power_source_type'   => 'diesel',
                'checklist_master'    => '',
                'equipment_parts_list'=> '',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $result[] = Equipment::where('equipment_id', $def['id_label'])->first();
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Customers
    // -----------------------------------------------------------------------
    private function seedCustomers(State $state): array
    {
        $people = [
            ['first'=>'Vince',    'last'=>'Wallace',  'company'=>'Wallace Contracting',      'email'=>'vince.wallace@dev.local',    'phone'=>'(615) 275-9266', 'address'=>'3293 Trace Creek Road',       'city'=>'White Bluff',  'zip'=>'37187'],
            ['first'=>'Matt',     'last'=>'Brown',     'company'=>'Brown Landscaping LLC',    'email'=>'matt.brown@dev.local',       'phone'=>'(614) 403-4258', 'address'=>'0 Taylor Creek Road',         'city'=>'Nunnelly',     'zip'=>'37137'],
            ['first'=>'Sarah',    'last'=>'Henderson', 'company'=>'Henderson Farms',          'email'=>'sarah.henderson@dev.local',  'phone'=>'(615) 812-3344', 'address'=>'441 Pinecrest Drive',         'city'=>'Dickson',      'zip'=>'37055'],
            ['first'=>'James',    'last'=>'Thornton',  'company'=>null,                        'email'=>'james.thornton@dev.local',   'phone'=>'(615) 998-7712', 'address'=>'88 Ridgeway Court',           'city'=>'Burns',        'zip'=>'37029'],
            ['first'=>'Patricia', 'last'=>'Lowe',      'company'=>'Lowe Property Mgmt',       'email'=>'patricia.lowe@dev.local',    'phone'=>'(615) 444-2891', 'address'=>'1622 Harpeth Valley Road',   'city'=>'Pegram',       'zip'=>'37143'],
            ['first'=>'Derek',    'last'=>'Simmons',   'company'=>'Simmons Excavation Inc',   'email'=>'derek.simmons@dev.local',    'phone'=>'(615) 731-0055', 'address'=>'305 Old Highway 70',          'city'=>'Kingston Springs','zip'=>'37082'],
            ['first'=>'Ashley',   'last'=>'Morris',    'company'=>null,                        'email'=>'ashley.morris@dev.local',    'phone'=>'(931) 552-9123', 'address'=>'72 Poplar Street',            'city'=>'Clarksville',  'zip'=>'37040'],
            ['first'=>'Brandon',  'last'=>'Carter',    'company'=>'Carter Construction',       'email'=>'brandon.carter@dev.local',   'phone'=>'(615) 883-6601', 'address'=>'2501 Mack Hatcher Pkwy',     'city'=>'Franklin',     'zip'=>'37064'],
            ['first'=>'Michelle', 'last'=>'Nguyen',    'company'=>null,                        'email'=>'michelle.nguyen@dev.local',  'phone'=>'(615) 299-4478', 'address'=>'19 Ridgetop Lane',            'city'=>'Nashville',    'zip'=>'37201'],
            ['first'=>'Robert',   'last'=>'Jennings',  'company'=>'Jennings Land Solutions',   'email'=>'robert.jennings@dev.local',  'phone'=>'(615) 670-1234', 'address'=>'890 Highway 46 South',        'city'=>'Dickson',      'zip'=>'37055'],
            ['first'=>'Karen',    'last'=>'Sutton',    'company'=>'Sutton Rental Homes',       'email'=>'karen.sutton@dev.local',     'phone'=>'(615) 799-3342', 'address'=>'3 Miller Creek Road',         'city'=>'Fairview',     'zip'=>'37062'],
            ['first'=>'Tyler',    'last'=>'Odom',      'company'=>null,                        'email'=>'tyler.odom@dev.local',       'phone'=>'(615) 512-8877', 'address'=>'556 Cheatham Dam Road',       'city'=>'Ashland City', 'zip'=>'37015'],
        ];

        $result = [];
        foreach (array_slice($people, 0, $this->customerCount) as $p) {
            $customer = Customer::firstOrCreate(
                ['email' => $p['email']],
                [
                    'first_name'   => $p['first'],
                    'last_name'    => $p['last'],
                    'company_name' => $p['company'],
                    'phone'        => $p['phone'],
                    'status'       => 'Active',
                    'is_guest'     => false,
                    'tax_status'   => 'Taxable',
                    'is_credit_account' => false,
                ]
            );

            // Shipping address
            if (!$customer->addresses()->where('type', 'Shipping')->exists()) {
                $customer->addresses()->create([
                    'type'       => 'Shipping',
                    'first_name' => $p['first'],
                    'last_name'  => $p['last'],
                    'email'      => $p['email'],
                    'phone'      => $p['phone'],
                    'address'    => $p['address'],
                    'city'       => $p['city'],
                    'state_id'   => $state->id,
                    'zip_code'   => $p['zip'],
                ]);
            }

            // Billing address (same)
            if (!$customer->addresses()->where('type', 'Billing')->exists()) {
                $customer->addresses()->create([
                    'type'       => 'Billing',
                    'first_name' => $p['first'],
                    'last_name'  => $p['last'],
                    'email'      => $p['email'],
                    'phone'      => $p['phone'],
                    'address'    => $p['address'],
                    'city'       => $p['city'],
                    'state_id'   => $state->id,
                    'zip_code'   => $p['zip'],
                ]);
            }

            $result[] = $customer;
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Orders
    // -----------------------------------------------------------------------
    private function seedOrders(array $customers, array $products, array $equipment, State $state): void
    {
        // Only use available equipment to avoid status conflicts
        $availableEquipment = collect($equipment)->filter(fn($e) => $e->current_status === 'available');

        // Fetch store IDs so order products get assigned to a store (dispatch store-location filter requires this)
        $storeIds = Store::pluck('id')->toArray() ?: [1];

        $paymentStatuses = ['Paid', 'Paid', 'Paid', 'Pending', 'Account'];
        $paymentMethods  = ['Card', 'Card', 'COD', 'Account'];

        // Spread delivery dates: some today, some past, some upcoming
        $dateOffsets = [-7, -5, -3, -2, -1, 0, 0, 1, 2, 3, 5, 7, 10, 14];

        foreach ($customers as $customerIndex => $customer) {
            $shippingAddr = $customer->addresses()->where('type', 'Shipping')->first();

            for ($i = 0; $i < $this->ordersPerCustomer; $i++) {
                $product = $products[($customerIndex * $this->ordersPerCustomer + $i) % count($products)];

                $deliveryOffset = $dateOffsets[($customerIndex * $this->ordersPerCustomer + $i) % count($dateOffsets)];
                $deliveryDate   = Carbon::today()->addDays($deliveryOffset);
                $returnOffset   = rand(3, 7);
                $returnDate     = $deliveryDate->copy()->addDays($returnOffset);

                // Rental price (daily * days)
                $days      = $returnOffset;
                $unitPrice = (float) ($product->rental_daily ?? 325);
                $subTotal  = round($unitPrice * $days, 2);
                $taxAmount = round($subTotal * 0.0925, 2); // 9.25% TN sales tax
                $grandTotal = $subTotal + $taxAmount;

                // Create order (boot method auto-sets unique_id and order_number)
                $order = Order::create([
                    'customer_id'    => $customer->id,
                    'customer_name'  => trim($customer->first_name . ' ' . $customer->last_name),
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'company_name'   => $customer->company_name,
                    'subtotal'       => $subTotal,
                    'tax_amount'     => $taxAmount,
                    'grand_total'    => $grandTotal,
                    'is_tax_exempt'  => 'No',
                    'terms_status'   => 'Accepted',
                    'platform'       => 'Web',
                ]);

                // Shipping address on the order
                OrderAddress::create([
                    'order_id'   => $order->id,
                    'type'       => 'Shipping',
                    'first_name' => $shippingAddr?->first_name ?? $customer->first_name,
                    'last_name'  => $shippingAddr?->last_name  ?? $customer->last_name,
                    'email'      => $customer->email,
                    'phone'      => $customer->phone,
                    'address'    => $shippingAddr?->address  ?? '123 Main St',
                    'city'       => $shippingAddr?->city     ?? 'Nashville',
                    'state_id'   => $state->id,
                    'zip_code'   => $shippingAddr?->zip_code ?? '37201',
                ]);

                // Billing address
                OrderAddress::create([
                    'order_id'   => $order->id,
                    'type'       => 'Billing',
                    'first_name' => $shippingAddr?->first_name ?? $customer->first_name,
                    'last_name'  => $shippingAddr?->last_name  ?? $customer->last_name,
                    'email'      => $customer->email,
                    'phone'      => $customer->phone,
                    'address'    => $shippingAddr?->address  ?? '123 Main St',
                    'city'       => $shippingAddr?->city     ?? 'Nashville',
                    'state_id'   => $state->id,
                    'zip_code'   => $shippingAddr?->zip_code ?? '37201',
                ]);

                // Payment
                $payStatus = $paymentStatuses[($customerIndex + $i) % count($paymentStatuses)];
                $payMethod = $paymentMethods[($customerIndex + $i) % count($paymentMethods)];

                OrderPayment::create([
                    'order_id'         => $order->id,
                    'payment_method'   => $payMethod,
                    'payment_datetime' => now(),
                    'amount'           => $grandTotal,
                    'status'           => $payStatus,
                ]);

                // Order product (delivery Pending, Truck)
                $deliveryStatus = $deliveryDate->isPast() ? 'Completed' : 'Pending';
                $pickupStatus   = ($returnDate->isPast() && $deliveryStatus === 'Completed') ? 'Completed' : 'Pending';

                $productData = [
                    'product_id'   => $product->id,
                    'product_name' => $product->product_name,
                    'product_type' => 'Rental',
                    'rental_type'  => 'daily',
                ];

                // Alternate store assignment across orders
                $storeId = $storeIds[($customerIndex * $this->ordersPerCustomer + $i) % count($storeIds)];

                $orderProduct = OrderProduct::create([
                    'order_id'               => $order->id,
                    'product_id'             => $product->id,
                    'product_name'           => $product->product_name,
                    'price'                  => $unitPrice,
                    'quantity'               => 1,
                    'sub_total'              => $subTotal,
                    'tax'                    => $taxAmount,
                    'total'                  => $grandTotal,
                    'product_data'           => $productData,
                    'service_method'         => 'Delivery',
                    'service_option'         => 'Delivery + Pickup',
                    'delivery_status'        => $deliveryStatus,
                    'delivery_transport_mode'=> 'Truck',
                    'delivery_date'          => $deliveryDate->format('Y-m-d'),
                    'delivery_time'          => $this->randomDeliveryTime(),
                    'delivery_store_id'      => $storeId,
                    'pickup_status'          => $pickupStatus,
                    'pickup_transport_mode'  => 'Truck',
                    'pickup_date'            => $returnDate->format('Y-m-d'),
                    'pickup_time'            => '09:00:00',
                    'pickup_store_id'        => $storeId,
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    private function randomDeliveryTime(): string
    {
        $hours = [7, 8, 9, 10, 11, 12, 13, 14];
        $h = $hours[array_rand($hours)];
        return str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00';
    }

}
