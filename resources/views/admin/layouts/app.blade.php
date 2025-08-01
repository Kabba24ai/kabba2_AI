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

    <title>@yield('title', config('app.name')) | {{ config('app.name') }}</title>

    <!-- Core Css -->
    @vite(['resources/admin/css/app.css', 'resources/admin/js/app.js'])
    <meta name="vite-app-css" content="{{ Vite::asset('resources/css/app.css') }}">

    @stack('css')
    <style>
        .active {
            color: #fff !important;
        }
    </style>
</head>

<body x-data="{
    page: 'blank',
    'loaded': true,
    'darkMode': false,
    'stickyMenu': false,
    'sidebarToggle': false,
    'scrollTop': false
}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{ 'dark bg-gray-900': darkMode === true }">

    <!-- ===== Preloader Start ===== -->
    @include('admin.partials.preloader')
    <!-- ===== Preloader End ===== -->

    <!-- ===== Page Wrapper Start ===== -->
    <div class="flex h-screen overflow-hidden">
        <!-- ===== Sidebar Start ===== -->
        @include('admin.partials.sidebar')
        <!-- ===== Sidebar End ===== -->

        <!-- ===== Content Area Start ===== -->
        <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Small Device Overlay Start -->
            <div :class="sidebarToggle ? 'block lg:hidden' : 'hidden'" class="fixed z-9 h-screen w-full bg-gray-900/50">
            </div>

            <!-- Small Device Overlay End -->

            <!-- ===== Header Start ===== -->
            @include('admin.partials.header')
            <!-- ===== Header End ===== -->

            <!-- ===== Main Content Start ===== -->
            <main>
                {{-- //max-w-(--breakpoint-2xl) --}}
                <div class="p-4 mx-auto md:p-6">
                    <!-- Breadcrumb Start -->
                    {{-- <div x-data="{ pageName: `Blank Page` }">
                        @include('admin.partials.breadcrumb')
                    </div> --}}
                    <!-- Breadcrumb End -->

                    @yield('content')

                </div>
            </main>
            <!-- ===== Main Content End ===== -->
        </div>
        <!-- ===== Content Area End ===== -->
    </div>
    <!-- ===== Page Wrapper End ===== -->
    @stack('js')
    <script src="https://cdn.tiny.cloud/1/5yx0xg5esob3um1tly8mle5l9c7q5ot2xanovbxz5xafaklv/tinymce/8/tinymce.min.js"
        referrerpolicy="origin" crossorigin="anonymous"></script>
    <script>
        tinymce.init({
            selector: 'textarea.tinymce',
            plugins: 'link table lists code',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link table | code |  shortcodes',
            setup: function(editor) {
                // Add custom button
                // editor.ui.registry.addButton('customer_initials', {
                //     text: 'Add Customer Initials',
                //     icon: 'insert', // optional icon
                //     onAction: function () {
                //         editor.insertContent('[customer_initials][/customer_initials]');
                //     }
                // });
                // Optional: dropdown menu for multiple shortcodes
                editor.ui.registry.addMenuButton('shortcodes', {
                    text: 'Shortcodes',
                    fetch: function(callback) {
                        callback([{
                                type: 'menuitem',
                                text: 'Product Terms',
                                onAction: () => editor.insertContent(
                                    '[product_terms][/product_terms]')
                            },
                            {
                                type: 'menuitem',
                                text: 'Customer Initials',
                                onAction: () => editor.insertContent(
                                    '[customer_initials][/customer_initials]')
                            }
                        ]);
                    }
                });
            },
            skin_url: '/tinymce/skins/ui/oxide',
            content_css: [
                '/tinymce/skins/content/default/content.css',
                document.querySelector('meta[name="vite-app-css"]')?.content || ''
            ],
            license_key: 'gpl',
        });
    </script>
    <script>
        window.Laravel = {
            success: @json(session('success')),
            error: @json(session('error'))
        };
    </script>
</body>

</html>
