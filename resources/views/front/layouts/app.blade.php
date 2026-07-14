<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <!-- Favicon icon-->
    <link rel="shortcut icon" href="{{ $favicon ?? $logo }}">

    @hasSection('full_title')
    <title>@yield('full_title')</title>
    @else
    <title>@yield('title', config('app.name')) - {{ config('app.name') }}</title>
    @endif
    @stack('meta')
    @stack('schema')


    @vite([
        'resources/front/assets/css/app.css',
        'resources/front/assets/js/app.js',
    ])

    @stack('css')
</head>

<body class="bg-white text-gray-800 font-sans overflow-x-hidden">


    {{-- navbar --}}
    @include('front.partials.navbar')

    @yield('content')

    {{-- Global feature strip + footer — one source of truth (Website Mgmt → Footer),
         rendered on every public page. A page that must not show them (payment,
         embedded, print) declares @section('no_global_footer', '1'). --}}
    @hasSection('no_global_footer')
    @else
        @include('front.partials.feature_strip')
        @include('front.partials.footer_v2')
    @endif

    @stack('js')

    <!-- ===========================================
            Section: Set Global Date Format
    ========================================== -->
    <script>
        window.appDateFormat = "{{ config('app.date.date_format') }}";
        window.Laravel = {
            success: @json(session('success')),
            error: @json(session('error'))
        };
    </script>
</body>
</html>
