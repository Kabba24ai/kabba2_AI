<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/default/images/logo.png') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">


    <!-- Core Css -->
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}" />
    @vite('resources/css/app.css')

    <title>Tps | Login</title>
</head>

<body class="DEFAULT_THEME ">
    <main>
        <!-- Main Content -->
        <div
            class="flex flex-col w-full  overflow-hidden relative min-h-screen radial-gradient items-center justify-center g-0 px-4">

            <div class="justify-center items-center w-full card lg:flex max-w-md ">
                <div class=" w-full card-body">
                    <a href="#" class="">
                        <img src="{{ asset('assets/default/images/logo.png') }}" alt="" class="mx-auto h-28 object-scale-down" />
                    </a>
                    <p class="mt-4 mb-4 text-gray-400 text-sm text-center">Welcome</p>

                    @include('flash::message')
                    @include('admin.includes.formErrors')

                    <!-- form -->
                    {{ html()->form()->attributes([
                            'class' => 'form w-100 module_form',
                            'id' => 'kt_sign_in_form',
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                        ])->open() }}
                    <!-- username -->
                    <div class="mb-4">
                        <label for="forUsername" class="block text-sm mb-2 text-gray-400">Username</label>
                        {{ html()->email('email')->attributes([
                                'class' => 'py-3 px-4 block w-full border-gray-200 rounded-xl text-sm focus:border-theme-600 focus:ring-0 ',
                                'autocomplete' => 'off',
                                'autofocus' => true,
                                'id' => 'email',
                                'data-parsley-maxlength' => 240,
                                'maxlength' => 240,
                                'pattern' => '[^+]*',
                            ])->required()->placeholder('Enter Email address') }}
                    </div>
                    <!-- password -->
                    <div class="mb-6">
                        <label for="forPassword" class="block text-sm  mb-2 text-gray-400">Password</label>
                        {{ html()->password('password')->attributes([
                                'class' => 'py-3 px-4 block w-full border-gray-200 rounded-xl text-sm focus:border-theme-600 focus:ring-0',
                                'autocomplete' => 'off',
                                'autofocus' => true,
                            ])->required()->placeholder('Enter Password') }}
                    </div>
                    <!-- checkbox -->
                    {{-- <div class="flex justify-between">
                            <div class="flex">
                                <input type="checkbox"
                                    class="shrink-0 mt-0.5 border-gray-200 rounded-[4px] text-blue-600 focus:ring-blue-500 "
                                    id="hs-default-checkbox" checked>
                                <label for="hs-default-checkbox" class="text-sm text-gray-500 ms-3">Remeber this
                                    Device</label>
                            </div>
                            <a href="../" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Forgot
                                Password ?</a>
                        </div> --}}
                    <!-- button -->
                    <div class="grid my-6">
                        <button type="submit"
                            class="btn bg-theme-500 py-[10px] text-base text-white font-medium hover:bg-theme-700" >Sign In</button>
                    </div>
                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>

        </div>
        <!--end of project-->
    </main>

    <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/parsley.js/2.9.2/parsley.min.js"
        integrity="sha512-eyHL1atYNycXNXZMDndxrDhNAegH2BDWt1TmkXJPoGf1WLlNYt08CSjkqF5lnCRmdm3IrkHid8s2jOUY4NIZVQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>
