<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Example supplier data
        $suppliers = [
            [
                'name' => 'TechFlow Solutions',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentclass" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white"><rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line></svg>',
                'class' => 'from-purple-500 to-violet-600',
                'city' => 'New York, USA',
                'main_phone' => '+1 (555) 123-4567',
                'main_email' => 'orders@globalmanuf.com',
                'category' => 'Software / IT',
                'parts' => ['UI Components', 'API Gateway', 'Dashboard Module'],
                'tags' => ['#Design', '#Client', '#Maintenance'],
                'status' => 'Active',
            ],
            [
                'name' => 'Global Manufacturing Co.',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white"><path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"></path><path d="M17 18h1"></path><path d="M12 18h1"></path><path d="M7 18h1"></path></svg>',
                'class' => 'from-gray-500 to-slate-600',
                'city' => 'Chicago, USA',
                'main_phone' => '+1 (555) 987-6543',
                'main_email' => 'info@ecosupply.com',
                'category' => 'Equipment Mfg.',
                'parts' => ['Engine Oil Filter', 'Brake Pads - Front', 'Transmission Fluid'],
                'tags' => ['#Design', '#Client', '#Maintenance'],
                'status' => 'Inactive',
            ],
            [
                'name' => 'EcoSupply Partners',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentclass" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white"><rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line></svg>',
                'class' => 'purple',
                'city' => 'Portland, USA',
                'main_phone' => '+1 (555) 456-7890',
                'main_email' => 'sales@premiummaterials.com',
                'category' => 'General Materials',
                'parts' => ['Packaging Materials', 'Adhesive Components'],
                'tags' => ['#Design', '#Client', '#Maintenance'],
                'status' => 'Active',
            ],
            [
                'name' => 'Premium Materials Ltd',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentclass" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white"><rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line></svg>',
                'class' => 'grey',
                'city' => 'London, UK',
                'main_phone' => '+44 20 7123 4567',
                'main_email' => 'hello@digitalinnovations.com',
                'category' => 'Packaging / Components',
                'parts' => ['Circuit Assembly', 'LED Display Panel', 'PCB Unit'],
                'tags' => ['#Design', '#Client', '#Maintenance'],
                'status' => 'Pending',
            ],
            [
                'name' => 'Reliable Services Corp',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentclass" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-white"><rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line></svg>',
                'class' => 'purple',
                'city' => 'Toronto, Canada',
                'main_phone' => '+1 (555) 321-9876',
                'main_email' => 'contact@reliableservices.com',
                'category' => 'UI/UX • Consulting • Support',
                'parts' => ['Design System', 'Client Portal', 'Maintenance Plan'],
                'tags' => ['#Design', '#Client', '#Maintenance'],
                'status' => 'Active',
            ],
        ];

        return view('admin.maintenance_management.suppliers.index', compact('suppliers'));
    }
}
