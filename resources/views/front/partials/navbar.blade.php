
 <nav
     class="text-dark border-b-[1px] border-b-neutral-800 fixed w-full mt-[0px] z-[9999] bg-neutral-800 top-0 bg-opacity-100 ">
     <div class="container !px-0">
         <div class="max-w-7xl mx-auto py-1 flex items-center  px-4">
             <a href="#" class="1/6">
                 <img src="{{ asset('storage/front/images/logo.png') }}" alt=""
                     class="h-full md:w-23 w-40">
             </a>
             <div class="flex space-x-4 5/6 w-full items-center justify-end text-white">
                 <ul class="lg:flex hidden">
                     <li class=" px-5 active group-[.active]:font-bold group">
                         <a href="{{ route('front.home.index') }}"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">Home</a>
                     </li>
                     <li class="px-5 group">
                         <a href="javascript:void(0)"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Equipment
                             Rentals</a>
                         <ul
                             class=" absolute min-w-32 pt-6 pb-5 top-18 right-4 border translate-y-2 bg-neutral-800/90 border-neutral-800/90  opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-y-4 gap-x-2 before:content-[''] before:h-7 before:absolute before:top-[-1.75rem] before:w-full ">

                             @foreach ($frontCategoryTree as $category)
                                 <li
                                     class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                     <a href="{{ route('front.categories.index', $category->slug) }}"
                                         class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">
                                         {{ $category->title }}
                                     </a>
                                     @if ($category->childCategories->isNotEmpty())
                                         <ul
                                             class="absolute w-auto min-w-32 pt-4 pb-0 top-3 border translate-y-2 bg-black/70 opacity-0 invisible group-hover/item:opacity-100 group-hover/item:visible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-4">
                                             @foreach ($category->childCategories as $child)
                                                 <li class="flex w-full whitespace-nowrap">
                                                     <a href="{{ route('front.categories.sub-category', ['slug'=>$category->slug,'childCategorySlug'=>$child->slug]) }}"
                                                         class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">
                                                         {{ $child->title }}
                                                     </a>
                                                 </li>
                                             @endforeach
                                         </ul>
                                     @endif
                                 </li>
                             @endforeach

                         </ul>
                     </li>
                     <li class=" px-5 group">
                         <a href="javascript:void(0)"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out group-[.active]:font-bold">How
                             It Works</a>
                     </li>
                     <li class=" px-5">
                         <a href="{{ route('front.faqs.index') }}"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Faq</a>
                     </li>
                     <li class=" px-5">
                         <a href="{{ route('front.contact-us.index') }}"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Contact
                             Us</a>
                     </li>
                     <li class=" px-5">
                         <a href="{{ route('front.auth.login.index') }}"
                             class="hover:text-yellow-400 text-sm transition-all duration-300 ease-in-out">Log
                             In</a>
                     </li>
                 </ul>
             </div>
             <div>
                 <div class="flex items-center">
                     <a href="javascript:void(0)" class=" toggleCart px-3 py-2 rounded-full relative text-center me-5">
                         <i class="fa-solid fa-bag-shopping text-2xl text-white"></i>
                         <span
                            class="bg-yellow-400 w-5 h-5 rounded-full absolute -top-1 -right-0 text-xs text-center font-bold">0</span>
                     </a>
                     <a href="javascript:void(0)" id="toggleHeader"
                         class=" w-5 h-14 pt-6 flex justify-center me-4 block lg:hidden">
                         <span class="relative"></span>
                     </a>
                 </div>
             </div>
         </div>

         <!-- mobile menu -->
         <div id="headeroffcanvas"
             class="offcanvas-header-body left-0 md:left-auto md:ml-4 md:right-auto lg:hidden opacity-0 absolute md:max-w-2xl top-16 sm:top-17 bottom-0 lg:max-w-md w-full max-h-0 bg-neutral-800/90 text-white shadow-lg z-[9999] transform translate-y-full">
             <div class="relative w-full h-full overflow-y-auto ">
                 <div class="py-4 md:px-4 h-[calc(100vh-80px)] overflow-y-auto">
                     <ul class="lg:hidden block text-center">
                         <li class=" py-3 active group-[.active]:font-bold group">
                             <a href="{{ route('front.home.index') }}"
                                 class="hover:text-yellow-400 px-5 text-sm group-[.active]:font-bold transition-all duration-300 ease-in-out">Home</a>
                         </li>


                         <li class=" py-3 group relative">
                             <a href="javascript:void(0)" id="menuToggleBtn"
                                 class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">Equipment
                                 Rentals</a>
                             <ul id="dropdownMenu"
                                 class="bg-neutral-800 text-center  transition-all duration-300 ease-in-out z-10
                                 grid gap-y-4">

                                 <li class="py-3 group relative">
                                     <a href="javascript:void(0)"
                                         class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">Equipment
                                         Rentals</a>
                                     <ul
                                         class="bg-neutral-800 text-center transition-all duration-300 ease-in-out z-10 grid gap-y-4">
                                         @foreach ($frontCategoryTree as $category)
                                             <li
                                                 class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                                 <a href=""
                                                     class="hover:text-yellow-400 w-full px-5 text-sm transition-all duration-300 ease-in-out">
                                                     {{ $category->title }}
                                                 </a>
                                                 @if ($category->childCategories->isNotEmpty())
                                                     <ul class=" w-auto !pb-0 border translate-y-2 bg-black/60 invisible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-4 ">
                                                         @foreach ($category->childCategories as $child)
                                                             <li class="flex w-full whitespace-nowrap">
                                                                 <a href=""
                                                                     class="hover:text-yellow-400 w-full px-5 text-sm transition-all duration-300 ease-in-out">
                                                                     {{ $child->title }}
                                                                 </a>
                                                             </li>
                                                         @endforeach
                                                     </ul>
                                                 @endif
                                             </li>
                                         @endforeach
                                     </ul>
                                 </li>

                             </ul>
                         </li>

                         <li class=" py-3">
                             <a href="{{ route('front.contact-us.index') }}"
                                 class="hover:text-yellow-400 px-5 text-sm transition-all duration-300 ease-in-out">Contact</a>
                         </li>
                         <li class=" py-3">
                             <a href="{{ route('front.faqs.index') }}"
                                 class="hover:text-yellow-400 px-5text-sm transition-all duration-300 ease-in-out">FAQs</a>
                         </li>
                     </ul>
                 </div>
             </div>
         </div>
         <!-- mobile menu end -->

         <!--  Offcanvas Cart -->
         <div id="cartoffcanvas"
         class="offcanvas-cart-body absolute top-17 right-0 overflow-hidden md:top-17 max-w-0 lg:top-20 opacity-0 md:max-w-sm w-0 h-screen bg-white/90  shadow-lg z-[9999] transform translate-x-full transition-all duration-300 ease-in-out">
            <div class="relative w-full h-[calc(100vh-80px)]">
                 <div class="px-9 pt-15 pb-15 overflow-y-scroll h-full w-full">

                    <p class="text-center py-10 text-gray-600">Your cart is empty.</p>

                 </div>
             </div>
         </div>
     </div>
 </nav>
