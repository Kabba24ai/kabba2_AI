@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div id="breadcrumbs" class="pt-[100px] pb-[20px] ">
                <div class="w-full">
                    <div class="flex justify-between items-center ">
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">FAQs
                        </h1>
                        <ul
                            class="bg-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="index.php" class="opacity-25">Home</a>
                            </li>
                            <li>
                                <a href="faq.php">FAQs</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="text-[26px] md:text-[30px] font-bold  mb-10 mt-10 text-[#231E41]">Damage Waiver Details</h2>
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-x-4  [&>*:nth-child(1)]:border-t lg:[&>*:nth-child(2)]:border-t">
                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-1">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-1" aria-expanded="false"
                                aria-controls="accordion-flush-body-1">
                                <span class="text-left">Is a Damage Waiver "Insurance"?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-1"
                            class="hidden overflow-hidden max-h-0 transform transition-[max-height] duration-500 ease-in-out"
                            aria-labelledby="accordion-flush-heading-1">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">A Damage Waiver is not insurance and does
                                    not provide unlimited liability protection for rented equipment.</p>
                                <p class="text-gray-500 dark:text-gray-400">The Damage Waiver is an optional provision
                                    offered to the Renter that provides additional protection against certain damages that
                                    may occur during the normal use of the equipment.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-3">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-3" aria-expanded="false"
                                aria-controls="accordion-flush-body-3">
                                <span class="text-left">What is NOT covered by the Damage Waiver?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-3" class="hidden" aria-labelledby="accordion-flush-heading-3">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">The Damage Waiver does not cover all
                                    potential damages and is not a substitute for insurance. Specifically, the waiver does
                                    not cover:</p>
                                <p class="text-gray-500 dark:text-gray-400 ml-6 mb-3"><b>Intentional damage:</b>Any
                                    deliberate, reckless or incompetent handling of the equipment. </p>
                                <p class="text-gray-500 dark:text-gray-400 ml-6 mb-3"><b>Damage to excluded items:</b>As
                                    outlined in this agreement, certain items such as hoses, hose couplings, specific
                                    attachments, and "thrown tracks" due to operator error are not covered.</p>
                                <p class="text-gray-500 dark:text-gray-400 ml-6 mb-3"><b>Third-party liability:</b> Any
                                    claims involving damage to third parties or property while using the rented equipment.
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 ml-6 mb-3"><b>Negligence or misuse:</b>Any damage
                                    caused by improper operation, carelessness, or use of the equipment outside of the
                                    recommended guidelines. </p>
                                <p class="text-gray-500 dark:text-gray-400 ml-6 mb-3"><b>Acts of theft:</b>Lost or stolen
                                    equipment.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-2">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-2" aria-expanded="false"
                                aria-controls="accordion-flush-body-2">
                                <span class="text-left">What does the Damage Waiver cover?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-2" class="hidden" aria-labelledby="accordion-flush-heading-2">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">The Damage Waiver provides limited
                                    protection for damages resulting from ordinary wear and tear or accidental damage that
                                    occurs during the normal operation of the rented equipment, as long as the equipment is
                                    used according to the manufacturer's guidelines and within the scope of its intended
                                    purpose.</p>
                                <p class="text-gray-500 dark:text-gray-400 mb-3">Examples of covered items under normal use
                                    include: </p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400">General wear and tear on moving parts.
                                </p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400">Damage resulting from typical usage
                                    scenarios. </p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400">Hydraulic hoses and couplings that
                                    fail due to normal wear and usage. </p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400">Minor dents, scratches, tooth wear due
                                    to normal usage.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="lg:pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="text-[26px] md:text-[30px] font-bold  mb-10 mt-10 text-[#231E41]">Rental Schedules / Cancelations
            </h2>
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-x-4 [&>*:nth-child(1)]:border-t lg:[&>*:nth-child(2)]:border-t">
                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-4">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-4" aria-expanded="false"
                                aria-controls="accordion-flush-body-4">
                                <span class="text-left">What is your policy on reschedules and cancellations?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-4" class="hidden" aria-labelledby="accordion-flush-heading-4">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">We offer <i><strong>free
                                            reschedules</strong></i> for any rental, allowing you to adjust your rental
                                    dates without any additional cost. If you need to cancel your rental, it must be done
                                    more than 24 hours before the scheduled rental time. Cancellations made within this
                                    window will incur a 4.5% credit card fee and a $5 transaction fee if a credit card was
                                    used for the original booking.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-black-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-black-400">

                        <h2 id="accordion-flush-heading-7">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-black-400 gap-3"
                                data-accordion-target="#accordion-flush-body-7" aria-expanded="false"
                                aria-controls="accordion-flush-body-">
                                <span class="text-left">When can I expect my delivery?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-7" class="hidden" aria-labelledby="accordion-flush-heading-7">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">Our store begins processing deliveries at
                                    <i><strong>7:00 AM</strong></i> , but the exact time your order arrives depends on
                                    several factors, including:</p>
                                <p class="text-gray-500 dark:text-gray-400 mb-3 ml-6"><strong>~ Order Placement:</strong>
                                    Deliveries are processed on a first-in, first-out basis, meaning orders placed earlier
                                    will generally be delivered first.</p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400"><strong>~ Availability of
                                        Equipment:</strong> The equipment you’ve rented may depend on its return from a
                                    previous rental, which can impact delivery times.</p>
                                <p class="text-gray-500 ml-6 mb-3 dark:text-gray-400"><strong>~ Loading, Rigging, and
                                        Transport Time:</strong> These factors also contribute to the delivery schedule, so
                                    while we aim for efficiency, your equipment may not arrive exactly at <strong>9:00
                                        AM</strong></p>
                                <p class="text-gray-500 mb-3 dark:text-gray-400">Regardless of the actual delivery time,
                                    <strong>your rental officially starts at 9:00 AM.</strong> </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="pb-[50px]">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <h2 class="text-[26px] md:text-[30px] font-bold  mb-10 mt-10 text-[#231E41]">Cleaning Fees</h2>
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-x-4 [&>*:nth-child(1)]:border-t lg:[&>*:nth-child(2)]:border-t">
                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-8">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-8" aria-expanded="false"
                                aria-controls="accordion-flush-body-8">
                                <span class="text-left">Are there cleaning fees associated when renting?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-8" class="hidden" aria-labelledby="accordion-flush-heading-8">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">In most cases, there is no separate
                                    cleaning fee included in the rental price. However, you are responsible for returning
                                    the rented machine in the same clean condition you received it in. This means: </p>
                                <p class="mb-3 text-gray-500 dark:text-gray-400">~ Removing all debris, dirt, and leftover
                                    materials from the machine's body, attachments, and internal components (where
                                    applicable). </p>
                                <p class="mb-3 text-gray-500 dark:text-gray-400">~ Cleaning the tracks of tracked machines.
                                    Packed dirt is never considered “clean” and must be physically removed and hosed out.
                                </p>
                                <p class="mb-3 text-gray-500 dark:text-gray-400">~ Following any specific cleaning
                                    instructions provided at the time of rental for certain machines (e.g., emptying floor
                                    sanders, degreasing pressure washers). </p>
                                <p class=" mb-3 text-gray-500 dark:text-gray-400">~ Returning the machine free of excessive
                                    wear and tear beyond normal use.</p>
                                <p class="mb-3 text-gray-500 dark:text-gray-400">~ Closed cab machines must be wiped down
                                    and swept clean of dirt.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">

                        <h2 id="accordion-flush-heading-9">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-9" aria-expanded="false"
                                aria-controls="accordion-flush-body-9">
                                <span class="text-left">What happens if I return the machine excessively dirty?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-9" class="hidden" aria-labelledby="accordion-flush-heading-9">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400">If upon return, our team finds the machine
                                    significantly dirty or with leftover materials that require additional cleaning, a
                                    cleaning fee will be applied based on the time and effort required to bring it back to
                                    its original state as detailed in the terms and conditions for each product. This fee
                                    will be charged directly to your payment method on file and due immediately.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <div id="accordion-flush" data-accordion="open"
                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                        data-inactive-classes="text-black-500 dark:text-gray-400">
                        <h2 id="accordion-flush-heading-10">
                            <button type="button"
                                class="faq-btn flex items-center justify-between w-full py-5 font-medium rtl:text-right text-black-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3"
                                data-accordion-target="#accordion-flush-body-10" aria-expanded="false"
                                aria-controls="accordion-flush-body-10">
                                <span class="text-left">How can I avoid cleaning fees?</span>
                                <span class="sb-plus-minus-toggle sb-collapsed"></span>
                            </button>
                        </h2>
                        <div id="accordion-flush-body-10" class="hidden" aria-labelledby="accordion-flush-heading-10">
                            <div class="py-5 border-b border-gray-200 dark:border-gray-700">
                                <p class="mb-3 text-gray-500 dark:text-gray-400"><strong>Pre-paid cleaning:</strong> For a
                                    small fee, you can opt for our pre-paid cleaning service. This covers standard cleaning
                                    procedures and ensures you won't be charged a cleaning fee unless the machine is
                                    returned in extremely dirty or damaged condition.</p>

                                <p class="text-gray-500 dark:text-gray-400 mb-3"><strong>Simple cleaning: </strong>For most
                                    rentals, a quick wipe-down to remove any visible dirt, debris, or leftover materials is
                                    sufficient.</p>

                                <p class="text-gray-500 dark:text-gray-400 mb-3"><strong>Car Wash / Power wash</strong> -
                                    There is a self serve car wash near by and these work very well for cleaning equipment
                                    if you do not have cleaning equipment at the jobsite where the machines are used</p>

                                <p class="text-gray-500 dark:text-gray-400 mb-3"><strong>Heavy-duty cleaning needs:
                                    </strong>If you anticipate using the machine for a project that will result in heavy
                                    soiling, consider purchasing disposable liners or drop cloths (available for purchase at
                                    Rent 'n King) to minimize cleanup on the machine itself.</p>
                                <p class="text-gray-500 dark:text-gray-400 mb-3"><strong>Ask for help! </strong>Our team is
                                    happy to provide additional cleaning tips or answer any questions you may have about
                                    specific machines.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection
