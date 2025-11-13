@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-white md:border-r-[30px] md:border-r-white bg-gray-50">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-8 md:px-0">
            <div id="breadcrumbs" class="pt-24 pb-5 ">
                <div class="w-full">
                    <div class="flex justify-between items-center ">
                        <h1 class="text-2xl md:text-3xl lg:text-4xl tracking-tight leading-[110%] font-bold">Terms
                            And Condition</h1>
                        <ul
                            class="px-5 py-2 lg:py-3 max-w-full text-sm font-medium items-center inline-flex gap-3 relative">
                            <li class="tracking-normal whitespace-nowrap after:content-['/'] after:pl-1.5">
                                <a href="{{ route('front.home.index') }}" class="opacity-25">Home</a>
                            </li>
                            <li>
                                <a href="terms-condition.php">Terms And Condition</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-8 md:px-[.7rem]">
            <div class="max-w-[800px] border p-6 mx-auto my-10 text-center">
                <h2 class="text-[24px] font-bold">Rent ‘n King Rental Agreement</h2>
                <a href="{{ route('front.home.index') }}"
                    class="text-blue-500 hover:text-black transition-all duration-500 ease-in-out">www.RentnKing.com</a>
                <p>Development 360, Inc</p>
            </div>
            <h3 class="text-red-500 text-center text-[24px] mb-6">Order ID {{ $order->order_number }}</h3>
            @if ($order->terms_status->isPending())
                <ul class="inline-block w-full text-center">
                    <li class="w-[33%] inline-block -mr-2 step-item active relative">
                        <i class="fa-solid fa-circle text-[40px] text-[#36c6d3] w-[40px] h-[40px]"></i>
                        <span class="text-[#36c6d3] block mt-3 font-medium">Step 1</span>
                    </li>
                    <li class="w-[33%] inline-block -mr-2 step-item active relative">
                        <i class="fa-solid fa-circle text-[40px] text-[#36c6d3] w-[40px] h-[40px]"></i>
                        <span class="text-[#36c6d3] block mt-3 font-medium">Step 2</span>
                    </li>
                    <li class="w-[33%] inline-block -mr-2 step-item relative">
                        <i class="fa-solid fa-circle text-[40px] text-[#36c6d3] w-[40px] h-[40px]"></i>
                        <span class="text-[#36c6d3] block mt-3 font-medium">Step 3</span>
                    </li>
                </ul>
            @endif
            <form id="customer-order-sign-form" method="post"
                action="{{ route('front.terms-and-conditions.sign', ['orderUniqueId' => $order->unique_id]) }}">
                <div id="terms-dynamic-content" class="flex flex-col gap-y-3 container rich-content">
                    @if ($order->terms_status->isPending())
                        {!!  \App\Helpers\TermsContentHelper::generateTermsContent($order)['terms_content'] !!}
                        {{-- {!! $order->pending_terms_content !!} commented because before sign user may check latest update terms and then he can sign --}}
                    @else
                        {{-- {!! $order->pending_terms_content !!} --}}
                        {!! $order->accepted_terms_content !!}
                    @endif

                    {{-- <div class="flex flex-col gap-y-1 mt-4">
                        @empty(!$contactUsSettings)
                            <p>{{ $contactUsSettings['address1'] ?? '' }}</p>
                            <p>{{ $contactUsSettings['address2'] ?? '' }}</p>
                        @endempty
                    </div> --}}
                    @if ($order->terms_status->isPending())
                        <div class="border-t mt-8 pt-4">
                            <button type="submit" id="submit-button"
                                class="border-0 bg-yellow-400 px-6 py-4 font-medium rounded-lg hover:bg-yellow-300  transition-all duration-500 ease-in-out">
                                SUBMIT</button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Signature Modal -->
    <div id="modal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-start pt-20"
        onclick="closeModalOnOutsideClick(event)">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col relative">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b rounded-t-lg">
                <h2 class="text-lg font-semibold">Signature Pad</h2>
                <button type="button" onclick="closeModal()"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            <div class="p-6 py-4">
                <div class="signature-container">
                    <canvas id="signature-pad" class="signature-pad border border-gray-300 w-full h-32"></canvas>
                    <div class="flex gap-x-4 mt-4">
                        <button
                            class="bg-green-600 text-white font-bold py-2 px-4 clear-button border-0 rounded-md hover:bg-green-700 transition"
                            onclick="clearSignature()">Clear</button>
                        <button
                            class="bg-green-600 text-white font-bold py-2 px-4 save-button border-0 rounded-md hover:bg-green-700 transition"
                            onclick="undoSignature()">Undo</button>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button onclick="saveSignature()"
                    class="border-0 bg-yellow-400 text-[14px] px-6 py-3 rounded-lg font-bold hover:bg-yellow-300 transition-all duration-500 ease-in-out">OK</button>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        const device = "{{ $device ?? 'web' }}";

        if (device === 'mobile') {
            // Open modal automatically for mobile devices
            document.addEventListener('DOMContentLoaded', function() {
                // Hide <nav> and <footer> tags for mobile device
                const nav = document.querySelector('nav');
                const footer = document.querySelector('footer');
                const breadcrumbs = document.getElementById('breadcrumbs');
                if (nav) nav.style.display = 'none';
                if (footer) footer.style.display = 'none';
                if (breadcrumbs) breadcrumbs.style.display = 'none';
            });
        }
        // Modal control
        function openModal() {
            const modal = document.getElementById("modal");
            modal.classList.remove("hidden");
            initSignaturePad();
        }

        function closeModal() {
            const modal = document.getElementById("modal");
            modal.classList.add("hidden");
            // Optionally clear signature when closing
            if (signaturePad) signaturePad.clear();
        }

        function closeModalOnOutsideClick(event) {
            const modalContent = event.target.closest('.max-w-md');
            if (!modalContent) {
                closeModal();
            }
        }

        // Esc key closes modal
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById("modal");
            if (!modal.classList.contains('hidden') && event.key === "Escape") {
                closeModal();
            }
        });

        // Signature Pad init
        let signaturePad = null;

        function initSignaturePad() {
            const canvas = document.getElementById('signature-pad');
            if (!canvas || typeof SignaturePad === 'undefined') return;

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                // Save current content before resizing
                const data = signaturePad ? signaturePad.toData() : null;

                // Resize
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                // Restore content after resize
                if (signaturePad && data) {
                    signaturePad.clear();
                    signaturePad.fromData(data);
                }
            }

            // Initialize SignaturePad if not already created
            if (!signaturePad) {
                resizeCanvas(); // Do this BEFORE initializing SignaturePad
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255,255,255,1)',
                    penColor: 'rgb(0,0,0)'
                });
            } else {
                resizeCanvas();
                signaturePad.clear();
            }

            // Attach resize listener only once
            window.addEventListener('resize', resizeCanvas);
        }

        function undoSignature() {
            if (signaturePad && !signaturePad.isEmpty()) {
                const data = signaturePad.toData();
                if (data.length > 0) {
                    data.pop();
                    signaturePad.fromData(data);
                }
            }
        }

        function clearSignature() {
            if (signaturePad) signaturePad.clear();
        }

        function saveSignature() {
            if (signaturePad && !signaturePad.isEmpty()) {
                const dataURL = signaturePad.toDataURL('image/png');
                // Add hidden input to form for submission
                let input = document.getElementById('signature_input');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'signature';
                    input.id = 'signature_input';
                    document.getElementById('customer-order-sign-form').appendChild(input);
                }
                input.value = dataURL;

                // Show preview and hide sign button
                let previewDiv = document.getElementById('signature-preview-div');
                let preview = document.getElementById('signature-preview');
                let signBtn = document.getElementById('open-signature-btn');
                if (previewDiv && preview && signBtn) {
                    preview.src = dataURL;
                    previewDiv.classList.remove('hidden');
                    signBtn.classList.add('hidden');
                }

                closeModal();
            } else {
                notyf.error('Please draw your signature before saving.');
            }
        }

        document.querySelectorAll('.customer_initials_checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var label = checkbox.closest('label');
                var span = label.querySelector('span');
                if (checkbox.checked) {
                    span.textContent = 'Customer Approved';
                    span.style.color = 'black';
                } else {
                    span.textContent = 'Customer Approval Required';
                    span.style.color = 'red';
                }
            });
        });

        // Close signature preview logic
        function closeSignaturePreview() {
            let previewDiv = document.getElementById('signature-preview-div');
            let preview = document.getElementById('signature-preview');
            let signBtn = document.getElementById('open-signature-btn');
            if (previewDiv && preview && signBtn) {
                preview.src = '';
                previewDiv.classList.add('hidden');
                signBtn.classList.remove('hidden');
            }
            // Optionally clear hidden input
            let input = document.getElementById('signature_input');
            if (input) input.value = '';
        }

        document.getElementById('customer-order-sign-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const action = form.action;
            const submitButton = document.getElementById('submit-button');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }

            apiFetch(action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                }).then(response => {
                if (response.success) {
                    notyf.success(response.message);
                    // Redirect to signed URL
                    window.location.href = response.signed_url;
                } else {
                    notyf.error(response.message);
                }
            }).finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        });
    </script>
@endpush
