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
        'resources/front/assets/css/all.css',
        'resources/front/assets/css/style.css',
        'resources/front/assets/css/swiper-bundle.min.css',
        'resources/front/assets/js/tailwind-browser.js'
    ])

    @stack('css')
</head>

<body class="bg-white text-gray-800 font-sans overflow-x-hidden">

    {{-- navbar --}}
    @include('front.partials.navbar')

    @yield('content')

    {{-- footer --}}
    @include('front.partials.footer')

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

    @vite([
        'resources/front/assets/js/jquery-3.6.0.min.js',
        'resources/front/assets/js/swiper-bundle.min.js',
        'resources/front/assets/js/flowbite.min.js',
        'resources/front/assets/js/custom.js',
        'resources/front/assets/js/signature_pad.umd.min.js'
    ])

    @stack('js')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.faq-btn');

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Toggle 'active' class on button
                    btn.classList.toggle('active');

                    // Handle accordion body
                    const targetId = btn.getAttribute('data-accordion-target');
                    const body = document.querySelector(targetId);

                    if (body.classList.contains('hidden')) {
                        // Open accordion body
                        body.classList.remove('max-h-0');
                        body.classList.add('max-h-[1000px]'); // Add custom classes
                    } else {
                        // Close accordion body
                        body.classList.add('max-h-0');
                        body.classList.remove('max-h-[1000px]'); // Remove custom classes
                    }
                });
            });
        });
    </script>
</body>
</html>
