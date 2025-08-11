
<footer class=" bg-neutral-800 text-white py-8 border-t">
    <div class="container md:px-0">
        <div class="mx-auto px-0 lg:px-4 lg:flex lg:items-center flex flex-wrap gap-8 justify-between">
            <div class="lg:flex gap-4 w-5/5 md:w-2/5 lg:w-2/5 items-center">
                <div class=" mt-4 lg:mt-0 leading-[1.6]">
                    <h3 class="font-bold text-white text-base mb-2 ">Contact Us</h3>
                    <a href="{{ route('front.contact-us.index') }}"
                        class="text-neutral-200/60 hover:text-yellow-400 text-sm flex gap-2 transition-all duration-300 ease-in-out">
                        Phone: {{ $contactUsSettings['mobile'] ?? '' }}
                    </a>
                    <p class="text-neutral-200/60 text-sm flex gap-2">Email: {{ $contactUsSettings['email'] ?? '' }} </p>
                    <p class="text-neutral-200/60 text-sm flex gap-2"> Address: {{ $contactUsSettings['address1'] ?? '' }}</p>
                </div>
            </div>
            <div class="w-5/5 md:w-1/5 lg:w-1/5 md:mt-0">
                <h3 class="font-bold text-white text-base mb-2 ">Quick Links</h3>
                <a href="{{ route('front.home.index') }}" class="hover:text-yellow-400 text-sm text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Home</a>
                <a href="" class="hover:text-yellow-400 text-sm text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Faq</a>
                <a href="javascript:void(0)" class="hover:text-yellow-400 text-sm text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Privacy Policy</a>
            </div>
            <div class="w-5/5 md:w-1/5 lg:w-1/5  md:mt-0">
                <div class="md:float-right">
                    <h3 class="font-bold text-white text-base mb-2 ">Follow Us</h3>
                    <p class="text-neutral-200/60 text-sm flex gap-2"><a href="javascript:void(0)">Facebook</a></p>
                    <p class="text-neutral-200/60 text-sm flex gap-2"><a href="javascript:void(0)">Instagram</a></p>
                    <p class="text-neutral-200/60 text-sm flex gap-2"><a href="javascript:void(0)">YouTube</a></p>
                </div>
            </div>
        </div>
        <h2 class="text-sm text-center mt-4 text-neutral-200/60">© 2025 Rent 'n King. All rights reserved.</h2>
    </div>
</footer>


