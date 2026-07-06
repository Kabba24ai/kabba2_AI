{{--
    Wait List banner — include with an $equipment model (checks exact-ID
    demand AND its category) or a $categoryId (category demand only).
    Renders nothing when no active demand exists.
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
                $q->orWhere(fn ($w) => $w
                    ->where('request_type', WaitListRequestType::SpecificEquipment->value)
                    ->whereHas('items', fn ($i) => $i->where('equipment_id', $bannerEquipment->id)));
                $applied = true;
            }
            if ($bannerCategoryId) {
                $q->orWhere(fn ($w) => $w
                    ->where('request_type', WaitListRequestType::Category->value)
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
