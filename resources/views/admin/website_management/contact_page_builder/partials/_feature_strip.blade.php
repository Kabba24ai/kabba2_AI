{{-- Feature Strip wrapper — delegates entirely to Home Builder partial with contact builder route prefix --}}
@include('admin.website_management.home_page_builder.partials._feature_strip', [
    'routePrefix' => $routePrefix,
])
