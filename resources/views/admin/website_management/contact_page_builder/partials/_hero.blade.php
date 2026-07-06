{{-- Contact Builder hero — same as Home Builder but with the Description field enabled --}}
@include('admin.website_management.home_page_builder.partials._hero', [
    'routePrefix'     => $routePrefix,
    'showDescription' => true,
])
