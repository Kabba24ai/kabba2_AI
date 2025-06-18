@extends('front.layouts.main')

@section('style')
<!-- style -->
@endsection

@section('main')


<!-- Hero Section -->
<section id="banner" class="pb-[60px] md:pt-[68px] pt-[79px] lg:pt-[79px] transform transition-all duration-300 ease-in-out">
    <div class="max-w-full mx-auto px-0">
        <div class="flex flex-col items-center ">
            <img src="{{ asset('storage/front/images/banner.jpg') }}" class="w-[100%] lg:w-full h-[430px] object-cover object-bottom ml-auto" alt="Equipment 1">
            <h1 class="text-[30px] md:text-[26px] lg:text-[30px] mt-[20px] mx-[30px] text-center md:text-left ">Call For Live Assistance from a Real Person: <a href="contact.php">(615) 815-6734</a> </h1>
        </div>
    </div>
</section>

<!-- Featured Rentals -->
<section class="pb-[60px]">
    <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
        <h2 class="text-[28px] md:text-[2rem] font-bold text-center mb-10  text-[#231E41]">Featured Rentals</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

            @foreach ($category_tree as $category)

            <div class="border py-[20px] lg:py-[16px] lg:px-[16px] rounded-lg text-center shadow-[0_2px_5px_rgba(0,0,0,0.05)] transition-transform duration-300 hover:translate-y-[-5px]">
                <a href="{{ route('front.category.listing',$category->slug) }}" class="text-[#231E41] transition-all duration-300 ease-in-out hover:text-[#000]">
                    <div class="w-full">
                        <img src="{{ Storage::url('media/public_assets/' . $category->media->folder_name . '/' . $category->media->file_name) }}" alt="Skid Steer"
                            class="mx-auto lg:w-full w-[90%] object-contain rounded-[5px] object-cover h-[180px] " />
                    </div>
                    <div>
                        <h3 class="mt-4 font-bold text-[15px] text-[#231E41] transition-all duration-300 ease-in-out hover:text-[#000] underline">{{$category->title}}</h3>
                    </div>
                </a>
            </div>

            @endforeach
        </div>
    </div>
</section>

<section class="pb-[48px] pt-[48px] bg-[#fafafa]">
    <div class="container mx-auto md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] px-[30px] md:px-[.7rem] relative overflow-hidden">
        <h2 class="text-[28px] md:text-[2rem] font-bold text-center mb-[16px] text-[#231E41]">About Rent 'n King</h2>
        <!-- about content -->
        <div class="max-w-[800px] text-center mx-auto">
            <p>Locally owned and committed to 1st-tier customer service that encourages long-term, repeat customers. We’re growing fast to serve you better across multiple locations.</p>
        </div>
    </div>
</section>



@endsection

@section('script')
<!-- script -->
@endsection