 <!-- Navigation -->
 <nav
     class="text-dark border-b-[1px] border-b-[#222] fixed w-full mt-[0px] z-[9999] bg-[#222] top-0 bg-opacity-100 transform transition-all duration-300 ease-in-out">
     <div class="container mx-auto lg:max-w-[1140px] 2xl:max-w-[1320px] md:max-w-[720px]  overflow-hidden md:px-[.7rem]">
         <div class="max-w-7xl mx-auto py-1 flex items-center  px-[15px]">
             <a href="#" class="1/6">
                 <img src="{{ asset('storage/front/images/logo.png') }}" alt=""
                     class="h-full md:w-[90px] w-[160px]">
             </a>
             <div class="flex space-x-4 5/6 w-full items-center justify-end text-[#fff]">
                 <ul class="lg:flex hidden">
                     <li class=" px-[18px] active group-[.active]:font-bold group">
                         <a href="{{ route('front.home.index') }}"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out group-[.active]:font-bold">Home</a>
                     </li>
                     <li class="px-[18px] group">
                         <a href="javascript:void(0)"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out">Equipment
                             Rentals</a>
                         <ul
                             class=" absolute min-w-[120px] pt-[25px] pb-[20px] top-[60px] right-[15px] border-[1px] translate-y-[10px] bg-[#222] border-[#222] bg-opacity-90  opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 ease-in-out z-10 grid grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-y-4 gap-x[0.5rem] before:content-[''] before:h-[30px] before:absolute before:top-[-30px] before:w-full ">

                             @foreach ($frontCategoryTree as $category)
                                 <li
                                     class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                     <a href="{{ route('front.categories.index', $category->slug) }}"
                                         class="hover:text-yellow-400 px-[18px] text-[14px] transition-all duration-300 ease-in-out">
                                         {{ $category->title }}
                                     </a>
                                     @if ($category->childCategories->isNotEmpty())
                                         <ul
                                             class="absolute w-auto min-w-[120px] pt-[15px] pb-[0px] top-[10px] border-[1px] translate-y-[10px] bg-black bg-opacity-90 opacity-0 invisible group-hover/item:opacity-100 group-hover/item:visible transition-all duration-300 ease-in-out z-10 grid grid-flow-col grid-rows-[repeat(4,_auto)] gap-4">
                                             @foreach ($category->childCategories as $child)
                                                 <li class="flex w-full whitespace-nowrap">
                                                     <a href="{{ route('front.categories.sub-category', ['slug'=>$category->slug,'childCategorySlug'=>$child->slug]) }}"
                                                         class="hover:text-yellow-400 px-[18px] text-[14px] transition-all duration-300 ease-in-out">
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
                     <li class=" px-[18px] group">
                         <a href="javascript:void(0)"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out group-[.active]:font-bold">How
                             It Works</a>
                     </li>
                     <li class=" px-[18px]">
                         <a href="{{ route('front.faqs.index') }}"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out">Faq</a>
                     </li>
                     <li class=" px-[18px]">
                         <a href="{{ route('front.contact-us.index') }}"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out">Contact
                             Us</a>
                     </li>
                     <li class=" px-[18px]">
                         <a href="{{ route('front.auth.login.index') }}"
                             class="hover:text-yellow-400 text-[14px] transition-all duration-300 ease-in-out">Log
                             In</a>
                     </li>
                 </ul>
             </div>
             <div>
                 <div class="flex items-center">
                     <a href="javascript:void(0)" class=" toggleCart px-3 py-2 rounded-full relative text-center me-5">
                         <i class="fa-solid fa-bag-shopping text-[25px] text-[#fff]"></i>
                         <span
                             class="bg-yellow-400 w-[20px] h-[20px] rounded-full absolute -top-1 -right-[0px] text-[12px] text-center font-bold">0</span>
                     </a>
                     <a href="javascript:void(0)" id="toggleHeader"
                         class=" w-[20px] h-[55px] pt-[25px] flex justify-center me-4 block lg:hidden">
                         <span class="relative"></span>
                     </a>
                 </div>
             </div>
         </div>

         <!-- mobile menu -->
         <div id="headeroffcanvas"
             class="offcanvas-header-body lg:hidden opacity-0 absolute md:max-w-[700px] top-[80px] sm:top-[68px] bottom-0 lg:max-w-[403px] w-full max-h-[0] bg-[#222] text-[#fff] bg-opacity-90 shadow-lg z-[9999] transform translate-y-full">
             <div class="relative w-full h-full overflow-y-scroll ">
                 <div class="py-[15px] md:px-[15px] h-[calc(100vh-80px)] overflow-y-scroll">
                     <ul class="lg:hidden block text-center">
                         <li class=" py-[10px] active group-[.active]:font-bold group">
                             <a href="{{ route('front.home.index') }}"
                                 class="hover:text-yellow-400 px-[18px] text-[14px] group-[.active]:font-bold transition-all duration-300 ease-in-out">Home</a>
                         </li>
                         <li class=" py-[10px] group relative">
                             <a href="javascript:void(0)" id="menuToggleBtn"
                                 class="hover:text-yellow-400 px-[18px] text-[14px] transition-all duration-300 ease-in-out">Equipment
                                 Rentals</a>
                             <ul id="dropdownMenu"
                                 class="bg-[#222] text-center  transition-all duration-300 ease-in-out z-10
                          grid gap-y-4">

                                 <li class="py-[10px] group relative">
                                     <a href="javascript:void(0)"
                                         class="hover:text-yellow-400 px-[18px] text-[14px] transition-all duration-300 ease-in-out">Equipment
                                         Rentals</a>
                                     <ul
                                         class="bg-[#222] text-center transition-all duration-300 ease-in-out z-10 grid gap-y-4">
                                         @foreach ($frontCategoryTree as $category)
                                             <li
                                                 class="{{ $category->childCategories->isNotEmpty() ? 'group/item relative' : 'flex w-full whitespace-nowrap' }}">
                                                 <a href=""
                                                     class="hover:text-yellow-400 w-full px-[18px] text-[14px] transition-all duration-300 ease-in-out">
                                                     {{ $category->title }}
                                                 </a>
                                                 @if ($category->childCategories->isNotEmpty())
                                                     <ul class="bg-[#333] px-[10px] py-[5px]">
                                                         @foreach ($category->childCategories as $child)
                                                             <li class="flex w-full whitespace-nowrap">
                                                                 <a href=""
                                                                     class="hover:text-yellow-400 w-full px-[18px] text-[14px] transition-all duration-300 ease-in-out">
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
                         <li class=" py-[10px]">
                             <a href="{{ route('front.contact-us.index') }}"
                                 class="hover:text-yellow-400 px-[18px] text-[14px] transition-all duration-300 ease-in-out">Contact</a>
                         </li>
                         <li class=" py-[10px]">
                             <a href="{{ route('front.faqs.index') }}"
                                 class="hover:text-yellow-400 px-[18px]text-[14px] transition-all duration-300 ease-in-out">FAQs</a>
                         </li>
                     </ul>
                 </div>
             </div>
         </div>
         <!-- mobile menu end -->

         <!--  Offcanvas Cart -->
         <div id="cartoffcanvas"
             class="offcanvas-cart-body absolute top-[85px] right-0 overflow-hidden md:top-[70px] max-w-0 lg:top-[79px] opacity-0 md:max-w-[403px] w-0 h-screen bg-white bg-opacity-90 shadow-lg z-[9999] transform translate-x-full transition-all duration-300 ease-in-out">
             <div class="relative w-full h-[calc(100vh-80px)]">
                 <div class="px-[40px] pt-[60px] pb-[60px] overflow-y-scroll h-full w-full">
                     
                    <p class="text-center py-10 text-gray-600">Your cart is empty.</p>
             
                 </div>
             </div>
         </div>
     </div>
 </nav>
