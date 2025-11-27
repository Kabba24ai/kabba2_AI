<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\Part;

class GetSuppliersController extends Controller
{
    public function __invoke()
    {
        $suppliers = Supplier::with('state')->get();

        $data = $suppliers->map(function ($supplier) {
            $parts = Part::where('primary_part_supplier_id', $supplier->unique_id)
                ->orWhere('alt_1_part_supplier_id', $supplier->unique_id)
                ->orWhere('alt_2_part_supplier_id', $supplier->unique_id)
                ->get()
                ->map(function ($part) {
                    return [
                        'name'     => $part->part_name,
                        'category' => optional($part->category)->name ?? '—',
                        'price'    => (float) $part->primary_part_cost,
                        'status'   => $part->dni ? 'DNI'
                            : ($part->stock_level > 0 ? 'In Stock' : 'Out of Stock'),
                        'part'     => $part->primary_part_number ?? '—',
                        'id'       => $part->unique_id,
                    ];
                });

            return [
                'name'    => $supplier->name,
                'contact' => $supplier->primary_contact_name ?? '—',
                'phone'   => $supplier->primary_contact_phone ?? $supplier->phone ?? '—',
                'email'   => $supplier->email ?? '—',
                'address' => $supplier->full_address ?? '—',
                'parts'   => $parts,
            ];
        });

        
        return response()->json($data);
    }
}
