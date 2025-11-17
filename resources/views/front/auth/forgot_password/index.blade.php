@extends('front.layouts.app')


@section('content')
<section class="transform transition-all duration-300 ease-in-out md:border-l-30 md:border-l-white md:border-r-30 md:border-r-white bg-light">
    <div class="container md:px-0">
        <div id="breadcrumbs" class="pt-100 pb-5 ">
            <div class="w-full">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-y-2 md:gap-y-0">
                    <h1 class="header-title">Forgot Password</h1>
                    <ul
                        class="px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative">
                        <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-1">
                            <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="cursor-not-allowed">Forgot Password</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="transform transition-all duration-300 ease-in-out  border-b-0">
    <div class="container">
        <div class="lg:flex gap-2 pb-60 md:pt-[60px] pt-5">
            <div class="lg:w-1/5"></div>
            <div class="lg:w-3/5">
                <main class="flex items-center justify-center p-2 md:p-4 lg:p-8 bg-white shadow rounded">
                    <div class="border-dotted border-4 border-gray w-full p-2 md:p-4 lg:p-8">
                        <h2 class="text-xl font-semibold text-center mb-6">Forgot Password</h2>
                        <form class="space-y-4" method="POST" id="">

                            <div class="relative z-0 w-full my-6 group">
                                <input class="block bg-light py-3 px-4 w-full text-sm text-gray-900 border-0 appearance-none focus:border-b focus:outline-none focus:ring-0 focus:border-yellow-400 peer" type="email" name="email" id="email" required="1" placeholder="" autocomplete="off">
                                <label for="email" class=" z-1 px-6 absolute text-base text-gray-500 duration-300 transform -translate-y-8 scale-75 top-2 origin-[0] peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-8 -translate-x-2.5 peer-focus:text-black">
                                    Email
                                </label>
                            </div>
                            <div class="w-full my-6 hidden" id="otp-section">
                                <label class="block text-md text-black mb-3 text-center font-medium">
                                    Enter OTP
                                </label>

                                <div class="flex justify-center gap-3">
                                    <input type="text" maxlength="1" class="otp-input bg-light w-12 h-12 md:w-14 md:h-14 text-center text-xl border border-yellow-400
                                        focus:border-yellow-400 focus:outline-none" />

                                    <input type="text" maxlength="1" class="otp-input bg-light w-12 h-12 md:w-14 md:h-14 text-center text-xl border border-yellow-400
                                        focus:border-yellow-400 focus:outline-none" />

                                    <input type="text" maxlength="1" class="otp-input bg-light w-12 h-12 md:w-14 md:h-14 text-center text-xl border border-yellow-400
                                        focus:border-yellow-400 focus:outline-none" />

                                    <input type="text" maxlength="1" class="otp-input bg-light w-12 h-12 md:w-14 md:h-14 text-center text-xl border border-yellow-400
                                        focus:border-yellow-400 focus:outline-none" />
                                </div>
                            </div>

                            <div class="w-full mt-5">
                                <div class="flex justify-center gap-4">

                                    <!-- Send Button -->
                                    <button type="submit" id="checkEmailBtn"
                                        class="border-0 bg-yellow-400 font-medium flex px-6 py-3 items-center leading-4 rounded-lg text-sm hover:bg-yellow-300 transition-all duration-500 ease-in-out">
                                        <svg class="svg-inline--fa fa-arrow-right -rotate-45 border-2 border-purple leading-4 lg:text-sm link-icon mr-3 rounded-full text-base text-purple" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-right"
                                            role="img" viewBox="0 0 448 512">
                                            <path fill="currentColor" d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"></path>
                                        </svg>
                                        Submit
                                    </button>

                                    <!-- Resend Button -->
                                    <button type="button"
                                        class="border-0 bg-yellow-400 font-medium flex px-6 py-3 items-center leading-4
                                            rounded-lg text-sm hover:bg-yellow-300 transition-all duration-500 ease-in-out">
                                        Resend
                                    </button>

                                </div>
                            </div>
                        </form>

                    </div>
                </main>
            </div>
            <div class="lg:w-1/5"></div>
        </div>
    </div>
</section>

@endsection


@push('js')
<script>
    const inputs = document.querySelectorAll(".otp-input");

    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            // Move to next input automatically
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (e) => {
            // Move to previous on Backspace
            if (e.key === "Backspace" && input.value === "" && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', () => {


        const resendBtn = document.querySelector("button[type='button']");

        function showResendButton() {
            resendBtn.classList.remove("hidden");
        }

        function startResendTimer(seconds) {
            resendBtn.disabled = true;
            resendBtn.innerText = `Resend (${seconds})`;

            let timer = setInterval(() => {
                seconds--;
                resendBtn.innerText = `Resend (${seconds})`;

                if (seconds <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.innerText = "Resend";
                }
            }, 1000);
        }



        const checkEmailBtn = document.getElementById("checkEmailBtn");
        const otpSection = document.getElementById("otp-section");

        checkEmailBtn.addEventListener("click", function(event) {
            event.preventDefault();

            let email = document.getElementById("email").value;

            if (email.trim() === "") {
                notyf.error('Please enter your email');
                return;
            }

            fetch("{{ route('front.auth.forgot-password.sendOtp') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(res => res.json())
                .then(data => {

                    if (!data.success) {

                        if (data.cooldown) {
                            startResendTimer(data.remaining);
                        }

                        notyf.error(data.message);
                        return;
                    }

                    otpSection.classList.remove("hidden");
                    showResendButton();
                    startResendTimer(60);

                    notyf.success("OTP sent!");
                });
        });


    });
</script>


@endpush