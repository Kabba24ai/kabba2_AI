{{--
    Opens a combined-load container. The CALLER emits the matching </div></div> at the
    end of the load's run (on $job->..._load_close). Kept as a direct child of
    .dc-scroll-section so the drag system treats the whole load as one static block
    (single items reorder around it; members inside are not individually draggable).

    Styling uses plain .dc-load* classes (see the inline <style> in index.blade.php) so
    it survives even though front-end assets are not rebuilt on deploy.

    Props: $load (DispatchLoad), $count (int members), $leg ('delivery'|'return').
--}}
<div class="dc-load" data-load-id="{{ $load?->unique_id }}" data-load-leg="{{ $leg }}">
    <div class="dc-load-head">
        <span class="dc-load-chip">LOAD · {{ $count }}</span>
        <span class="dc-load-sub">one dispatch</span>
        <button type="button" class="dc-load-assign dc-load-btn" title="Assign this whole load to a driver">Assign</button>
        <button type="button" class="dc-load-ungroup dc-load-btn dc-load-btn-danger" title="Ungroup this load">Ungroup</button>
    </div>
    <div class="dc-load-body">
