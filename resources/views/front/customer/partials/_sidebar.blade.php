<div class="dash-sidebar">

    <div class="mx-3">
        <div class="flex justify-center w-full gap-x-4 border-b py-4">
            <label for="dropzone-file" class=" w-2/6 relative flex flex-col items-center justify-center w-24 h-23 rounded-full border-2 cursor-pointer bg-gray/50 hover:border-black/50 overflow-hidden group">
                
                <!-- Image Preview -->
                <img id="image-preview" src="{{ asset('storage/front/images/default.jpg') }}"  class="absolute inset-0 w-full h-full object-cover rounded-full z-0" />
        
                <!-- Icon on hover -->
                <div class="z-10 flex flex-col items-center justify-center pt-5 pb-6 w-full h-full rounded-full bg-black/50 bg-opacity-[0.6] opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <i class="fa-solid fa-pen-to-square text-white text-3xl"></i>
                </div>
        
                <input id="dropzone-file" type="file" class="hidden" accept="image/*" />
            </label>
            <div class="w-4/6 flex flex-col gap-y-1">
                <p class="font-bold">{{ auth('customer')->user()->full_name }}</p>
                <p class="text-light-gray text-wrap break-all">{{ auth('customer')->user()->email }}</p>
                <!-- <a href="javascript:void(0)" class="text-yellow-400">Logout</a> -->
            </div>
        </div>
    </div>
    <ul class="mt-5 dashboard-nav">
        <li class="{{ Route::is('front.customer.dashboard.index') ? 'active' : '' }} px-4 py-3 border-b-0 last:border-b ">
            <a href="{{ route('front.customer.dashboard.index') }}" class="w-full flex gap-x-2"><i class="fa-solid fa-home text-xl min-w-6 max-w-6"></i> Overview</a>
        </li>
        <li class="{{ Route::is('front.customer.profile.index') ? 'active' : '' }} px-4 py-3 border-b-0 last:border-b  ">
            <a href="{{ route('front.customer.profile.index') }}" class="w-full flex gap-x-2"> <i class="fa-solid fa-user text-xl min-w-6 max-w-6"></i> Profile</a>
        </li>
        <li class="{{ Route::is('front.customer.orders.index') ? 'active' : '' }}  px-4 py-3 border-b-0 last:border-b  ">
            <a href="{{ route('front.customer.orders.index') }}" class="w-full flex gap-x-2"><i class="fa-solid fa-shopping-cart text-xl min-w-6 max-w-6"></i> Orders</a>
        </li>
        <!-- <li class="  px-4 py-3 border-b-0 last:border-b  ">
            <a href="javascript:void(0)" class="w-full flex gap-x-2"><i class="fa-solid fa-repeat text-xl min-w-6 max-w-6"></i> Order Return Requests</a>
        </li>
        <li class=" px-4 py-3 border-b-0 last:border-b  ">
            <a href="javascript:void(0)" class="w-full flex gap-x-2"><i class="fa-solid fa-location-dot text-xl min-w-6 max-w-6"></i> Address</a>
        </li> -->
        <!-- <li class=" px-4 py-3 border-b-0 last:border-b  ">
            <a href="javascript:void(0)" class="w-full flex gap-x-2"><i class="fa-solid fa-lock text-xl min-w-6 max-w-6"></i> Change Password</a>
        </li> -->

        <li class="px-4 py-3 border-b-0 ">
            <form method="POST" action="{{ route('front.auth.logout.index') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex gap-x-2 text-left">
                    <i class="fa-solid fa-right-from-bracket text-xl min-w-6 max-w-6"></i> Logout
                </button>
            </form>
        </li>

    </ul>
</div>