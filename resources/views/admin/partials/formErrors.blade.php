@if ($errors->any())
    <div class="text-red-500 hover:border-red-500 py-2">
        <div class="alert-text">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li class="">{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
        <div class="clear"></div>
    </div>
@endif
