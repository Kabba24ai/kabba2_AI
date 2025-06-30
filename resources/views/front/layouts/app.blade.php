<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon icon-->
    <link rel="shortcut icon" href="{{ asset('storage/front/images/fav.png') }}">

    <title>@yield('title', config('app.name'))</title>

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

    {{-- footer --}}
    @include('front.partials.footer')

    @stack('js')


    @if (session('success') || session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                @if (session('success'))
                    notyf.success(@json(session('success')));
                @endif
                @if (session('error'))
                    notyf.error(@json(session('error')));
                @endif
            });
        </script>
    @endif
    <!-- ===========================================
            Section: Set Global Date Format
    ========================================== -->
    <script>
        window.appDateFormat = "{{ config('app.date.date_format') }}";
    </script>
</body>
</html>
