<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wall Board') | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('storage/admin/images/favicon.png') }}">
    @vite(['resources/admin/css/app.css', 'resources/admin/js/app.js'])
    @livewireStyles
</head>

{{-- Minimal authenticated shell for dedicated shop monitors: no sidebar, no
     header — every pixel goes to the board. Same auth, same Livewire stack
     as the admin app; only the chrome differs. --}}

<body class="bg-gray-100 min-h-screen">
    <main class="p-4 md:p-6">
        @yield('content')
    </main>

    @livewireScripts
</body>

</html>
