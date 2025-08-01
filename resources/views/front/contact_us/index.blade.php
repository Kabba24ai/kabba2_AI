@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-light border-b-0">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full border-b-4 border-b-[#231e41] pb-4">
                    <div class="flex justify-between items-center ">
                        <h1 class="header-title">Contact</h1>
                    </div>
                </div>
            </div>

            <div class="lg:flex gap-2 pb-[60px] md:pt-[60px] pt-[20px]">
                <div class="lg:w-2/5">
                    <div class="mb-3">
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold pb-[30px]">Speak with a Human</h1>
                        <p>Skip the frustrating menus and speak with a human. <i>(We might be on the phone with others when you call but we’ll always call you back as quickly as possible)</i></p>
                    </div>
                    <div class="grid md:grid-cols-2 gap-2">
                        <div>
                            <p class="text-[18px] md:text-[20px] font-bold mb-3 mt-6 lg:mb-5 lg:mt-10 "><i class="fa-solid fa-location-dot"></i> ADDRESS</p>
                            <p>*Coming Soon <br> Clarksville</p>
                        </div>
                        <div>
                            <p class="text-[18px] md:text-[20px] font-bold mb-3 mt-6 lg:mb-5 lg:mt-10 "><i class="fa-solid fa-location-dot"></i> ADDRESS</p>
                            <p>10296 Highway 46 <br> Bon Aqua, TN 37025</p>
                        </div>
                        <div>
                            <p class="text-[18px] md:text-[20px] font-bold mb-3 mt-6 lg:mb-5 lg:mt-10 "><i class="fa-solid fa-phone"></i> PHONE</p>
                            <a href="#" class="hover:text-black">(615) 815-6734</a>
                        </div>
                    </div>
                    <div>
                        <div>
                            <p class="text-[18px] md:text-[20px] font-bold mb-3 mt-6 lg:mb-5 lg:mt-10 "><i class="fa-solid fa-envelope"></i> EMAIL</p>
                            <a href="#" class="hover:text-black"> SalesAndSupport@RentnKing.com</a>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/5 md:hidden lg:block"></div>
                <div class="lg:w-2/5 pt-[40px] lg:pt-0">
                    <div class="p-6 bg-white">
                        <div class="border-2 border-dotted p-8">
                            <h4 class="text-[22px] font-bold text-center mb-8">Send Message</h4>
                            <form action="#">
                                <div class="relative z-0 w-full mb-6 group">
                                    <input type="text" name="name" id="name" class="block bg-light py-2.5 px-0 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " />
                                    <label for="name" class=" z-1 px-4 absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-100 peer-focus:-translate-y-8 peer-focus:-translate-x-2.5 peer-focus:text-black">
                                        Name
                                    </label>
                                </div>
                                <div class="relative z-0 w-full mb-6 group">
                                    <input type="email" name="email" id="email" class="block bg-light py-2.5 px-0 w-full text-sm text-gray-900 border-0 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " />
                                    <label for="email" class=" z-1 px-4 absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-100 peer-focus:-translate-y-8 peer-focus:-translate-x-2.5 peer-focus:text-black">
                                        Email
                                    </label>
                                </div>

                                <div class="relative z-0 w-full mb-6 group">
                                    <textarea name="message" id="message" rows="4"
                                        class="block bg-light py-2.5 px-0 w-full text-sm text-gray-900 border-0  appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                        placeholder=" "></textarea>
                                    <label for="message"
                                        class="absolute px-4 text-sm text-gray-500 duration-300 transform -translate-y-8 scale-100 top-3 z-1 origin-[0]
                                                peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                                peer-focus:scale-100 peer-focus:-translate-y-8 peer-focus:-translate-x-2.5 peer-focus:text-black">
                                        Message
                                    </label>
                                </div>
                                <div class="text-center text-[12px] text-[#6f6f87]">
                                    <p>*We promise not to disclose your <br> personal information to third parties.</p>
                                </div>
                                <div class="w-full mt-5 text-center">
                                    <button type="button" class="border-0 bg-yellow-400  font-medium flex px-6 py-3 mt-4 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300  transition-all duration-500 ease-in-out mx-auto">
                                        <i class="mr-3 fa-solid fa-arrow-right text-base lg:text-lg leading-4  -rotate-45 border-2 rounded-full link-icon border-purple"></i>
                                        Send
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-y-8 lg:gap-y-0 md:gap-x-8 pb-[60px]">
                <div class="">
                    <iframe class="w-full h-[350px]" src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d51392.03439129321!2d-87.382049!3d36.384942!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8864cf8a6f6ab5d7%3A0x777f7a577f1ba46!2s4385%20State%20Hwy%2048%2C%20Cumberland%20Furnace%2C%20TN%2037051!5e0!3m2!1sen!2sus!4v1748251727369!5m2!1sen!2sus" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="">
                    <iframe class="w-full h-[350px]" src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d51669.41461336342!2d-87.31099!3d35.963151!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8864a6af8e0bbc07%3A0xc7fe7f5df5520587!2s10296%20TN-46%2C%20Bon%20Aqua%2C%20TN%2037025!5e0!3m2!1sen!2sus!4v1748252621577!5m2!1sen!2sus" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

        </div>
    </section>
@endsection

