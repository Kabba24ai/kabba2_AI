{{--
    Wait List banner — include with an $equipment model (checks demand for
    the unit's product, plus legacy exact-ID/category demand) or a
    $categoryId (category demand only). Renders nothing when no active
    demand exists.
--}}
@php
    use App\Enums\WaitList\WaitListRequestType;
    use App\Models\WaitList\EquipmentWaitList;

    $bannerEquipment  = $equipment ?? null;
    $bannerCategoryId = $categoryId ?? $bannerEquipment?->product_category_id;

    $waitListDemand = EquipmentWaitList::waiting()
        ->where(function ($q) use ($bannerEquipment, $bannerCategoryId) {
            $applied = false;
            if ($bannerEquipment) {
                // Canonical: this exact unit is among a record's selected units
                $q->orWhereHas('items', fn ($i) => $i->where('equipment_id', $bannerEquipment->id));
                $applied = true;
            }
            if ($bannerEquipment?->assigned_product_id) {
                // Product-era fallback records (no unit selections recorded)
                $q->orWhere(fn ($w) => $w
                    ->whereDoesntHave('items')
                    ->whereHas('selectedProducts',
                        fn ($p) => $p->where('products.id', $bannerEquipment->assigned_product_id)));
                $applied = true;
            }
            if ($bannerCategoryId) {
                // Category-level demand: unified records live in one category;
                // legacy category records match by category alone
                $q->orWhere(fn ($w) => $w
                    ->whereIn('request_type', [
                        WaitListRequestType::Unified->value,
                        WaitListRequestType::Category->value,
                    ])
                    ->where('product_category_id', $bannerCategoryId));
                $applied = true;
            }
            if (!$applied) {
                $q->whereRaw('1 = 0');
            }
        })
        ->count();
@endphp

@if ($waitListDemand > 0 && Route::has('admin.wait-list.index'))
    <a href="{{ route('admin.wait-list.index', ['status' => 'waiting']) }}"
        class="flex items-center gap-3 rounded-xl border-2 border-red-400 bg-red-50 px-4 py-3 mb-5 hover:bg-red-100 transition">
        <x-heroicon-o-bell-alert class="w-6 h-6 text-red-600 shrink-0" />
        <span class="text-sm font-semibold text-red-800">
            WAIT LIST: Active customer demand exists for this equipment/category
            ({{ $waitListDemand }} {{ Str::plural('record', $waitListDemand) }}). Click to view.
        </span>
    </a>
@endif
