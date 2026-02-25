<footer class=" bg-neutral-800 text-white py-8 border-t no-print">
    <div class="container md:px-0">
        <div class="mx-auto px-4 lg:px-8 lg:flex lg:items-center flex flex-wrap gap-8 justify-between">
            <div class="lg:flex gap-4 w-5/5 md:w-2/5 lg:w-1/5 items-center">
                <div class=" mt-4 lg:mt-0 leading-[1.6]">
                    <h3 class="font-bold text-white text-base mb-2 ">Quick Links</h3>
                    <a href="{{ route('front.home.index') }}" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Home</a>
                    <a href="javascript:void(0)" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Equipment Rentals</a>
                    <a href="{{ route('front.faqs.index') }}" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Faq</a>
                </div>
            </div>

            <div class="w-5/5 md:w-1/5 lg:w-1/5 md:mt-0">
                <h3 class="font-bold text-white text-base mb-2 ">Other Links</h3>
                <a href="{{ route('front.contact-us.index') }}" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Contact Us</a>
                <a href="{{ route('front.terms-and-conditions.general') }}" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Terms & Conditions</a>
                <a href="{{ route('front.privacy-policy.index') }}" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Privacy Policy</a>
                <a href="{{ config('app.domains.opportunities') }}" target="_blank" class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Employment Opportunities</a>

            </div>

            @php
            $settings = \App\Helpers\ConfigurationHelper::getSettings('Social Media Settings');

            $socialIcons = [
            'facebook_page_link' => 'fab fa-facebook-f',
            'twitter_page_link' => 'fab fa-x-twitter',
            'instagram_page_link' => 'fab fa-instagram',
            'linkedin_page_link' => 'fab fa-linkedin-in',
            'youtube_page_link' => 'fab fa-youtube',
            'tiktok_page_link' => 'fab fa-tiktok',
            'pinterest_page_link' => 'fab fa-pinterest-p',
            'snapchat_page_link' => 'fab fa-snapchat-ghost',
            ];

            // Check if at least one link exists
            $hasAnyLink = false;
            foreach ($socialIcons as $key => $icon) {
            if (!empty($settings[$key])) {
            $hasAnyLink = true;
            break;
            }
            }
            @endphp


            <div class="w-5/5 md:w-1/5 lg:w-1/5 md:mt-0">
                @if ($hasAnyLink)
                <div class="md:float-right">
                    <h3 class="font-bold text-white text-base mb-2">Follow Us</h3>

                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4 text-neutral-200/60">

                        @foreach ($socialIcons as $settingKey => $iconClass)
                        @if (!empty($settings[$settingKey]))
                        <a href="{{ $settings[$settingKey] }}" target="_blank"
                            class="w-10 h-10 flex items-center justify-center text-neutral-200/60 border border-neutral-200/40 rounded-md hover:text-white hover:border-white transition">
                            <i class="{{ $iconClass }} text-lg"></i>
                        </a>
                        @endif
                        @endforeach

                    </div>
                </div>
                @endif
            </div>


        </div>
    </div>
    <h2 class="text-sm text-center mt-4 text-neutral-200/60">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</h2>
    </div>
</footer>
