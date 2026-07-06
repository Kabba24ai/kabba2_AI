{{-- Contact Strip wrapper — delegates entirely to Home Builder partial with contact builder route prefix --}}
@include('admin.website_management.home_page_builder.partials._contact_strip', [
    'routePrefix' => $routePrefix,
])
