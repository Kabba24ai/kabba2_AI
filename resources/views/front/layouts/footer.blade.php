<!-- Footer -->
<footer class=" bg-[#222] text-[#fff] py-8 border-t-[1px]">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
        <div class="mx-auto px-4 lg:flex gap-6 items-center flex flex-wrap gap-8 justify-between">
            <div class="lg:flex gap-4 md:w-5/5 lg:w-2/5 items-center">
                <div class=" mt-[15px] lg:mt-0 leading-[1.6]">
                    <h3 class="font-bold text-[#fff] text-[15px] mb-[10px] ">Contact Us</h3>
                    <a href="{{ route('front.contact') }}"
                        class="text-[#ccc] hover:text-yellow-400 text-[14px] flex gap-2 transition-all duration-300 ease-in-out">
                        Phone: (615) 815-6734
                    </a>
                    <p class="text-[#ccc] text-[14px] flex gap-2">Email: support@rentnking.com </p>
                    <p class="text-[#ccc] text-[14px] flex gap-2"> Address: 123 Main St, Dickson, TN</p>
                </div>
            </div>
            <div class="md:w-5/5 lg:w-1/5 mt-[15px] lg:mt-0">
                <h3 class="font-bold text-[#fff] text-[15px] mb-[10px] ">Quick Links</h3>
                <a href="{{ route('index') }}" class="hover:text-yellow-400 text-[14px] text-[#ccc] transition-all duration-300 ease-in-out flex gap-2">Home</a>
                <a href="{{ route('front.faq') }}" class="hover:text-yellow-400 text-[14px] text-[#ccc] transition-all duration-300 ease-in-out flex gap-2">Faq</a>
                <a href="javascript:void(0)" class="hover:text-yellow-400 text-[14px] text-[#ccc] transition-all duration-300 ease-in-out flex gap-2">Privacy Policy</a>
            </div>
            <div class="md:w-5/5 lg:w-1/5">
                <div class="float-right">
                    <h3 class="font-bold text-[#fff] text-[15px] mb-[10px] ">Follow Us</h3>
                    <p class="text-[#ccc] text-[14px] flex gap-2"><a href="javascript:void(0)">Facebook</a></p>
                    <p class="text-[#ccc] text-[14px] flex gap-2"><a href="javascript:void(0)">Instagram</a></p>
                    <p class="text-[#ccc] text-[14px] flex gap-2"><a href="javascript:void(0)">YouTube</a></p>
                </div>
            </div>
        </div>
        <h2 class="text-[13px] md:text-[13px] text-center mt-4 text-[#ccc]">© 2025 Rent 'n King. All rights reserved.</h2>
    </div>
</footer>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

@vite([
'resources/front/assets/js/jquery-3.6.0.min.js',
'resources/front/assets/js/swiper-bundle.min.js',
'resources/front/assets/js/flowbite.min.js',
'resources/front/assets/js/custom.js',
'resources/front/assets/js/signature_pad.umd.min.js',
])
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.faq-btn');

        buttons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Toggle 'active' class on button
                btn.classList.toggle('active');

                // Handle accordion body
                const targetId = btn.getAttribute('data-accordion-target');
                const body = document.querySelector(targetId);

                if (body.classList.contains('hidden')) {
                    // Open accordion body
                    body.classList.remove('max-h-0');
                    body.classList.add('max-h-[1000px]'); // Add custom classes
                } else {
                    // Close accordion body
                    body.classList.add('max-h-0');
                    body.classList.remove('max-h-[1000px]'); // Remove custom classes
                }
            });
        });
    });
</script>


</html>