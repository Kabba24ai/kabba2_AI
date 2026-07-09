<footer class=" bg-neutral-800 text-white py-8 border-t no-print">
    <div class="container md:px-0">
        <div class="mx-auto px-4 lg:px-8 lg:flex lg:items-start flex flex-wrap gap-8 justify-between">

            {{-- Quick Links --}}
            <div class="lg:flex gap-4 w-5/5 md:w-2/5 lg:w-1/5 items-center">
                <div class=" mt-4 lg:mt-0 leading-[1.6]">
                    <h3 class="font-bold text-white text-base mb-2 ">Quick Links</h3>
                    @forelse($footerHp->quickLinks as $link)
                        <a href="{{ $link->button_url }}"
                            class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">
                            {{ $link->title }}
                        </a>
                    @empty
                        <a href="{{ route('front.home.index') }}"
                            class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Home</a>
                        <a href="javascript:void(0)"
                            class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Equipment Rentals</a>
                        <a href="{{ route('front.faqs.index') }}"
                            class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">Faq</a>
                    @endforelse
                </div>
            </div>

            {{-- Other Links --}}
            @if($footerHp->otherLinks->isNotEmpty())
            <div class="w-5/5 md:w-1/5 lg:w-1/5 md:mt-0">
                <h3 class="font-bold text-white text-base mb-2 ">Other Links</h3>
                @foreach($footerHp->otherLinks as $link)
                    <a href="{{ $link->button_url }}"
                        class="hover:text-yellow-400 text-sm mb-1 text-neutral-200/60 transition-all duration-300 ease-in-out flex gap-2">
                        {{ $link->title }}
                    </a>
                @endforeach
            </div>
            @endif

            {{-- Social Links --}}
            @php
                $hasSocialLinks = $footerHp->socialLinks->isNotEmpty();

                // fallback: Social Media Settings
                if (!$hasSocialLinks) {
                    $smSettings = \App\Helpers\ConfigurationHelper::getSettings('Social Media Settings');
                    $smIcons = [
                        'facebook_page_link'  => 'fab fa-facebook-f',
                        'twitter_page_link'   => 'fab fa-x-twitter',
                        'instagram_page_link' => 'fab fa-instagram',
                        'linkedin_page_link'  => 'fab fa-linkedin-in',
                        'youtube_page_link'   => 'fab fa-youtube',
                        'tiktok_page_link'    => 'fab fa-tiktok',
                        'pinterest_page_link' => 'fab fa-pinterest-p',
                        'snapchat_page_link'  => 'fab fa-snapchat-ghost',
                    ];
                    $hasSocialLinks = collect($smIcons)->keys()->contains(fn ($k) => !empty($smSettings[$k]));
                }
            @endphp

            <div class="w-5/5 md:w-1/5 lg:w-1/5 md:mt-0">
                @if($hasSocialLinks)
                    <div class="md:float-right">
                        <h3 class="font-bold text-white text-base mb-2">Follow Us</h3>

                        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4 text-neutral-200/60">

                            @if($footerHp->socialLinks->isNotEmpty())
                                @foreach($footerHp->socialLinks as $link)
                                    <a href="{{ $link->button_url }}"
                                        class="w-10 h-10 flex items-center justify-center text-neutral-200/60 border border-neutral-200/40 rounded-md hover:text-white hover:border-white transition">
                                        <i class="{{ str_starts_with($link->icon ?? '', 'fa') ? 'fab ' : '' }}{{ $link->icon }} text-lg"></i>
                                    </a>
                                @endforeach
                            @else
                                @foreach ($smIcons as $settingKey => $iconClass)
                                    @if (!empty($smSettings[$settingKey]))
                                        <a href="{{ $smSettings[$settingKey] }}"
                                            class="w-10 h-10 flex items-center justify-center text-neutral-200/60 border border-neutral-200/40 rounded-md hover:text-white hover:border-white transition">
                                            <i class="{{ $iconClass }} text-lg"></i>
                                        </a>
                                    @endif
                                @endforeach
                            @endif

                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <div class="container mt-4 text-sm text-neutral-200/60 flex flex-col sm:flex-row items-center sm:justify-between gap-2">

        <div class="mt-4 text-left">
            @if($footerHp->copyrightText)
                {{ $footerHp->copyrightText }}
            @else
                © {{ date('Y') }}
                <a href="javascript:void(0)" rel="noopener noreferrer">
                    {{ $brandingSettings['all_rights_reserved'] ?? $brandingSettings['site_name'] }}
                </a>. All rights reserved.
            @endif
        </div>

        <div class="mt-4 text-white font-bold">
            <span>Powered by :</span>
            <a href="javascript:void(0)" rel="noopener noreferrer" class="text-white font-bold hover:text-yellow-400">
                {{ $footerHp->poweredByText ?? ($brandingSettings['powered_by'] ?? 'kabba.ai') }}
            </a>
        </div>

    </div>
</footer>
