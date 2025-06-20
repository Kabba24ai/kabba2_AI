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
                        <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">Terms
                            And Condition</h1>
                        <ul
                            class="bg-yellow-400 px-[20px] py-2 lg:py-3 max-w-full text-[14px] font-medium items-center inline-flex gap-3 relative border-2 border-[#fff]">
                            <li class="tracking-[0] whitespace-nowrap after:content-['/'] after:pl-[5px]">
                                <a href="index.php" class="opacity-25">Home</a>
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
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="max-w-[800px] border p-6 mx-auto my-10 text-center">
                <h2 class="text-[24px] font-bold">Rent ‘n King Rental Agreement</h2>
                <a href="index.php"
                    class="text-[#0d6efd] hover:text-[#000] transition-all duration-500 ease-in-out">www.RentnKing.com</a>
                <p>Development 360, Inc</p>
            </div>
            <h3 class="text-[#ff0000] text-center text-[24px] mb-6">Order ID #100005011</h3>
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
            <h4 class="text-[#ff0000] text-[40px] text-center font-medium leading-[44px] mt-4 mb-10">Your Order is almost
                complete but <br> we need you to complete this agreement form!</h4>
            <div class="flex flex-col gap-y-3 text-[14px]">
                <p>This Rental Contract (“Agreement”) is made and entered into between Development 360, inc , DBA Rent ‘n
                    King, (“Owner”) with its principal place of business located at 4385 SR-48, Charlotte, TN 37036 and/or
                    10296 Highway 46, Bon Aqua, TN 37025, and (“Renter”).</p>
                <p>This Agreement is effective as of the date signed by the Renter.</p>
                <ol class="list-decimal ml-10 flex flex-col gap-y-3">
                    <li>
                        <p>
                            <strong>Equipment:</strong> Owner agrees to rent to Renter, and Renter agrees to rent from
                            Owner, the construction equipment and trailer described in the attached Schedule A
                            (“Equipment”).
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Rental Period:</strong> The rental period for the Equipment shall begin on [START DATE]
                            and end on [END DATE]. Renter shall promptly return the Equipment to Owner on the end date of
                            the rental period, unless otherwise agreed upon in writing by Owner. Early return of equipment
                            does not alter the rental period chosen since the item has been removed from the available
                            rentals and restricted from others renting the item. No refunds are created by an early return
                            without explicit prior agreement by Rent 'n King.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Rental Fees and Payment:</strong> Renter shall pay to Owner the rental fees for the
                            Equipment as set forth in the attached Schedule A (“Rental Fees”). Payment shall be made in
                            advance and in full before the Equipment is released to Renter. Renter shall also be responsible
                            for any additional charges that may arise from the use of the Equipment, including but not
                            limited to fuel and damage repair costs.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Security Deposit:</strong> Renter shall provide a security deposit to Owner in the
                            amount set forth in the attached Schedule A (“Security Deposit”). The Security Deposit shall be
                            used to cover any damages or loss of the Equipment while in Renter’s possession. If the
                            Equipment is returned to Owner in the same condition as when received, the Security Deposit will
                            be refunded to Renter within 14 business days after the Equipment is returned.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Maintenance and Repairs:</strong> Renter shall keep the Equipment in good condition and
                            promptly notify Owner of any repairs that may be required during the rental period. Renter shall
                            not perform any repairs or modifications to the Equipment without Owner’s prior written consent.
                            Owner shall be responsible for all repairs resulting from normal wear and tear on the Equipment.
                            Dents, scratches, broken glass, broken pins, bent metal, broken teeth, missing items, punctured
                            tires, damaged/cut/excessively worn tracks, punctures, jammed/trapped/wound foreign materials
                            such as rocks, metals, wood, plastics are all examples of items NOT considered “normal wear and
                            tear”.
                        </p>
                        <div class="flex flex-col gap-y-4 mt-3">
                            <p><strong>Failing to maintain the equipment in good condition voids the damage waiver.</strong>
                            </p>
                            <div class="ml-4 flex flex-col gap-y-4">
                                <p><strong>Proper Use:</strong> The renter agrees to use the power equipment only for its
                                    intended purpose and in accordance with the manufacturer's instructions. Renter is
                                    responsible for reading the included machine manual and/or watching videos detailing the
                                    correct operation and limitations of the equipment. </p>
                                <ul class="list-disc ms-16 flex flex-col gap-y-2">
                                    <li>
                                        <p>Renters must know their soil conditions and use the equipment appropriate for
                                            these specific conditions to avoid damage. Damage caused by rocks and other
                                            hidden obstacles are the responsibility of the renter.</p>
                                    </li>
                                    <li>
                                        <p>Failure to follow the manufacturer's guidelines could result in voiding the
                                            damage waiver</p>
                                    </li>
                                </ul>
                            </div>
                            <div class="ml-4 flex flex-col gap-y-4">
                                <p><strong>Negligence:</strong> The renter agrees not to use the power equipment in a
                                    negligent or reckless manner that could result in damage to the equipment. Negligence
                                    includes, but is not limited to, the following types of activities:</p>
                                <ul class="list-[circle] ms-16 flex flex-col gap-y-2">
                                    <li>
                                        <p>Operating on an unsafe grade.</p>
                                    </li>
                                    <li>
                                        <p>Operating machines under excessive load that cause overheating or other
                                            mechanical damages.</p>
                                    </li>
                                    <li>
                                        <p>Operating tracked units in a manner that causes the tracks to come off i.e.
                                            turning hard on concrete/asphalt, roots, debris, soft soil or other conditions
                                            where care must be taken to allow the tracks adequate turning radius for
                                            conditions. Excessively short / hard turns are the #1 cause of a “thrown track”.
                                        </p>
                                    </li>
                                    <li>
                                        <p>Operating under the influence of drugs or alcohol.</p>
                                    </li>
                                    <li>
                                        <p>Operating without proper care and maintenance i.e. greasing according to
                                            manufacturer’s specs.</p>
                                    </li>
                                    <li>
                                        <p>Lifting loads heavier than rated by the manufacturer.</p>
                                    </li>
                                    <li>
                                        <p>Operating in conditions where falling objects may contact the equipment.</p>
                                    </li>
                                    <li>
                                        <p>Not wearing proper safety equipment i.e. safety belts, harnesses, hearing
                                            protection, face/eye protection, body protection.</p>
                                    </li>
                                    <li>
                                        <p>Operating at speeds unsuitable for conditions.</p>
                                    </li>
                                    <li>
                                        <p>Operating near water, rivers, lakes, ponds, bogs, flood plains or anywhere near
                                            standing water.</p>
                                    </li>
                                    <li>
                                        <p>Operating in excessive heat or cold.</p>
                                    </li>
                                    <li>
                                        <p>Lack of proper rigging for transport. </p>
                                    </li>
                                    <li>
                                        <p>Hoses and/or hose couplings damaged from over extension of the attachment or
                                            snagging hoses on fixed obstacles / trees / debris. </p>
                                    </li>
                                </ul>
                                <div class="ml-6 flex flex-col gap-y-4">
                                    <p><strong>Cleaning and Maintenance:</strong> The renter agrees to keep the power
                                        equipment clean and well-maintained during the rental period, and to return it in
                                        the same condition it was in at the start of the rental period. When renting a
                                        closed cab machine, the <strong> DOOR MUST REMAIN CLOSED </strong> at all times
                                        during operation! </p>
                                    <ul class="list-[circle] ms-10 flex flex-col gap-y-2">
                                        <li>
                                            <p>Standard Cleaning Fee: $150</p>
                                        </li>
                                        <li>
                                            <p>Extreme Cleaning Fee: $425</p>
                                        </li>
                                        <li>
                                            <p>Closed Cab Cleaning Fee: $675</p>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <p>
                            <strong>Insurance:</strong>
                            Renter shall provide liability insurance coverage for the Equipment in the amount necessary to
                            cover the replacement cost of the Equipment rented or assume full responsibility. Renter shall
                            be responsible for any damage caused to the Equipment or third parties during the rental period.
                            Damage Waiver Protection provides an added layer of Equipment protection but is not a
                            replacement for full coverage insurance.
                        </p>
                        <div class="flex flex-col gap-y-4 mt-3 ml-5">
                            <p>Damage Waiver: Clarification of Coverage</p>
                            <p>A Damage Waiver is not insurance and does not provide unlimited liability protection for
                                rented equipment. The Damage Waiver is an optional provision offered to the Renter that
                                provides additional protection against certain damages that may occur during the normal use
                                of the equipment.</p>
                            <p>What is Covered: The Damage Waiver provides limited protection for damages resulting from
                                ordinary wear and tear or accidental damage that occurs during the normal operation of the
                                rented equipment, as long as the equipment is used according to the manufacturer's
                                guidelines and within the scope of its intended purpose.</p>
                            <p>Examples of covered items under normal use include:</p>
                            <ul class="list-[circle] ml-16 flex flex-col gap-y-3">
                                <li>
                                    <p>General wear and tear on moving parts.</p>
                                </li>
                                <li>
                                    <p>Damage resulting from typical usage scenarios.</p>
                                </li>
                                <li>
                                    <p>Hydraulic hoses and couplings that fail due to normal wear and usage.</p>
                                </li>
                                <li>
                                    <p>Minor dents, scratches, tooth wear due to normal usage.</p>
                                </li>
                            </ul>
                            <p><strong>What is Not Covered:</strong> The Damage Waiver does not cover all potential damages
                                and is not a substitute for insurance. Specifically, the waiver does not cover: </p>
                            <ul class="list-[circle] ml-16 flex flex-col gap-y-3">
                                <li>
                                    <p>
                                        <strong>Intentional damage:</strong> Any deliberate, reckless or incompetent
                                        handling of the equipment. Use of the the equipment in a manner not intended by
                                        design or common industry standards.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Damage to excluded items:</strong>As outlined in this agreement, certain
                                        items such as hoses, hose couplings, specific attachments, and "thrown tracks" due
                                        to operator error are not covered.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Third-party liability:</strong> Any claims involving damage to third parties
                                        or property while using the rented equipment.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Negligence or misuse:</strong> Any damage caused by improper operation,
                                        carelessness, or use of the equipment outside of the recommended guidelines.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Acts of theft:</strong> Lost or stolen equipment.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Operator Created Damage:</strong> Impact with physical objects, thrown
                                        tracks (unless deemed a defective track or poorly maintained i.e. loose, excessively
                                        worn), other track damage from work environment i.e. demolition sites, sharp rocks
                                        or other materials.
                                    </p>
                                </li>
                            </ul>
                            <p>The Renter remains financially responsible for any damages that fall outside of the normal
                                wear and tear covered by the Damage Waiver, as well as any costs associated with repairs or
                                replacement of equipment. The Damage Waiver is simply an added layer of protection and
                                should not be viewed as providing full coverage or releasing the Renter from liability. We
                                strongly encourage renters to assess whether they need additional insurance coverage for
                                certain scenarios, including third-party liability or high-risk usage, which the Damage
                                Waiver does not cover. </p>
                        </div>
                    </li>
                    <li>
                        <p>
                            <strong>Rigging:</strong> Renter is responsible for the proper rigging of all equipment rented.
                            Proper rigging is essential to prevent equipment from shifting or falling during transport. Use
                            high-quality chains, binders, and tie-down straps that are rated for the weight of the
                            equipment. Chains, straps and binders are readily available to rent from Rent 'n King to safely
                            secure the equipment.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Vehicles and Trailers:</strong> Renter must ensure you're using a properly rated trailer
                            and truck to safely transport the load.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Indemnification:</strong> Renter shall indemnify and hold harmless Owner, its agents,
                            employees, and representatives from any and all claims, damages, liabilities, and expenses
                            arising from the use of the Equipment by Renter or any third parties. This includes legal fees,
                            damages, and any financial losses the owner may incur due to third-party claims related to the
                            renter's use of the equipment.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Termination:</strong> This Agreement may be terminated by either party upon 10 days’
                            written notice to the other party. Renter shall return the Equipment to Owner upon termination
                            of the Agreement. All volume discounts will be forfeited and the final rental due will be based
                            upon the Daily Rate times the number of days used by the renter.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Governing Law:</strong> This Agreement shall be governed by and construed in accordance
                            with the laws of the State of Tennessee.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Entire Agreement:</strong> This Agreement constitutes the entire understanding between
                            the parties and supersedes all prior negotiations, understandings, and agreements between the
                            parties. This Agreement may not be modified except in writing signed by both parties.
                        </p>
                    </li>
                    <li>
                        <p>
                            <strong>Binding Effect:</strong> This Agreement shall be binding upon and inure to the benefit
                            of the parties hereto and their respective heirs, legal representatives, successors, and
                            assigns.
                        </p>

                    </li>
                </ol>
                <div>
                    <p><strong>Clause: Damages and Financial Responsibility</strong></p>
                    <ol class="list-decimal ml-8 flex flex-col gap-y-3">
                        <li>
                            <p>The Renter acknowledges and agrees that they shall be solely responsible for any and all
                                damages incurred to the rented equipment during the rental period, including but not limited
                                to physical damage, loss, theft, or any other form of damage. </p>
                        </li>
                        <li>
                            <p>The Renter further acknowledges and agrees that in addition to the cost of repairing or
                                replacing the damaged equipment, they shall be responsible for any administrative time
                                required by the Rental Company to manage and coordinate the repairs, as well as any
                                associated costs incurred.</p>
                        </li>
                        <li>
                            <p>The Renter understands and accepts that they shall also be liable for the cost of foregone
                                rental revenue during the repair or replacement period, calculated at the daily rental rate
                                for the duration it takes to restore the equipment to its original working condition or
                                until a replacement is provided.</p>
                        </li>
                        <li>
                            <p>The Rental Company shall provide an estimate of the damages and associated costs to the
                                Renter within a reasonable time frame after the damages occur. The Renter shall promptly
                                make full payment for all costs incurred, including repair or replacement expenses,
                                administrative time, and foregone rental revenue, within the specified timeframe outlined in
                                the agreement</p>
                        </li>
                        <li>
                            <p>Failure to pay the full cost of damages and associated expenses within the designated
                                timeframe may result in additional penalties, fees, or legal action, as determined by the
                                Rental Company</p>
                        </li>
                        <li>
                            <p>It is the Renter's responsibility to exercise due care and caution when using the rented
                                equipment, and to promptly notify the Rental Company of any damages, loss, or theft that may
                                occur during the rental period.</p>
                        </li>
                        <li>
                            <p>In the event that the rented equipment sustains damage while in the field, requiring the
                                intervention of a service technician for repairs, the Renter shall be responsible for all
                                associated costs of dispatching a field service technician and all appropriate tools and
                                supplies, including any 3rd party services, tools or equipment required to complete the
                                repair or to retrieve the equipment for further repair at a dealership, or at our service
                                center, depending upon which is best suited to resolve the problem. </p>
                        </li>
                        <li>
                            <p>For service calls related to equipment damage, a flat fee of 2 hours will be charged. This
                                $297.00 flat fee covers the initial service call, assessment, and up to 2 hours of repair
                                work performed by the technician. </p>
                        </li>
                        <li>
                            <p>Any additional time required for repairs beyond the initial 2-hour flat fee will be charged
                                on an hourly basis of $124.00.</p>
                        </li>
                        <li>
                            <p>The Renter acknowledges and agrees that all charges associated with service calls and repair
                                work shall be the responsibility of the Renter and will be invoiced accordingly. </p>
                        </li>
                        <li>
                            <p>The service technician will document the start and end times for each service call and repair
                                session accurately. The time spent on repairs beyond the initial 2-hour flat fee will be
                                calculated based on the actual time spent by the service technician.</p>
                        </li>
                        <li>
                            <p>The Renter understands that the service technician will make reasonable efforts to complete
                                the repairs in a timely manner. However, the actual repair time may vary depending on the
                                extent of the damage, availability of parts, and other unforeseen circumstances. </p>
                        </li>
                        <li>
                            <p>The Rental Company reserves the right to determine the need for a service call and the
                                appropriate repair actions to be taken. If the damage is determined to be a result of
                                misuse, negligence, or any actions not covered under the rental agreement terms, the Renter
                                shall be responsible for all associated costs. </p>
                        </li>
                        <li>
                            <p>Renters will not be charged for defective machinery, or failures due to normal wear and tear.
                                It is not possible to assess the cause of the failure / damage without an assessment of the
                                equipment by a qualified technician. </p>
                        </li>
                        <li>
                            <p>The Renter agrees to pay all charges with the card on file and authorizes Owner to use this
                                payment method at the time of notification of fees. </p>
                        </li>
                        <li>
                            <p>Non Sufficient Checks will incur an additional service charge of $45 plus any additional fees
                                required to collect payment.</p>
                        </li>
                        <li>
                            <p>All credit card / debit card reversals (Chargebacks) will incur an additional service charge
                                of $75 plus any additional fees required to collect payment. </p>
                        </li>
                        <li>
                            <p>All Attachments (including brush cutter, soil conditioner (Harley Rake), box blade, forks,
                                bucket, etc.) are not covered under the damage waiver. All damages done to the attachments
                                will be the renters responsibility unless the attachment is defective and warranted by the
                                manufacturer. Normal wear is expected and included with the rental. </p>
                        </li>
                        <li>
                            <p>Hose and Hose Coupling Damage: The Renter is responsible for any damage to hoses and hose
                                couplings due to operator misuse, including but not limited to:</p>
                            <ul class="list-decimal flex flex-col gap-y-3">
                                <li>
                                    <p>Physical Impact: Damage caused by direct impacts with foreign objects or obstacles.
                                    </p>
                                </li>
                                <li>
                                    <p>Overextension: Damage resulting from extending the attachment beyond its operational
                                        limits.</p>
                                </li>
                                <li>
                                    <p>Pinching by Mechanical Force: Damage caused by pinching the hoses during operation or
                                        transportation.</p>
                                </li>
                                <li>
                                    <p>Snags on Trees, Buildings, or Debris: Damage from hoses snagging on obstacles during
                                        operation.</p>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <p>Thrown tracks will be covered if the tracks are defective, excessively worn, or loose but
                                this is rarely the cause of a “thrown track”. Almost unanimously, a thrown track is caused
                                due to operator error i.e. rough conditions and / or too much force used make immediate
                                turns that create excessive side forces.</p>
                        </li>
                    </ol>
                </div>
                <p><strong>After-Hours Returns and Responsibility for Equipment</strong></p>
                <p>If you return equipment after Rent 'n King's normal business hours, you remain fully responsible for the
                    equipment until the business reopens on the next regular business day and a formal inspection and
                    check-in are completed by authorized Rent 'n King personnel.</p>
                <p>The condition of the equipment is <strong> not considered verified </strong>upon your drop-off, nor at
                    any time during closed hours. The equipment's <strong> return condition</strong>, including verification
                    of damages, cleanliness, fuel levels, and completeness of all accessories or attachments, will be
                    evaluated <strong> only during regular business hours </strong>by an authorized Rent 'n King employee.
                    Until this formal inspection is completed, <strong> all risks of loss, theft, damage, or missing
                        equipment remain your sole responsibility.</strong></p>
                <p>No verbal comment, text message, email, photograph, voicemail, or phone call — whether sent or received
                    by any employee, owner, or agent of Rent 'n King — will serve as a waiver of this policy. Only the
                    in-person inspection and official check-in process during regular operating hours can release you from
                    continued responsibility for the rented equipment.</p>
                <p>You, as the customer, agree and understand that:</p>
                <ul class="list-disc ml-10">
                    <li>
                        <p>Responsibility for the equipment continues uninterrupted until Rent 'n King formally accepts and
                            verifies the returned equipment.</p>
                    </li>
                    <li>
                        <p>Any claims regarding the condition of the equipment, alleged return time, or drop-off location
                            will not be accepted without formal inspection and documentation by Rent 'n King staff.</p>
                    </li>
                    <li>
                        <p>In the event the equipment is found damaged, incomplete, lost, or stolen prior to our formal
                            inspection, you will be held fully liable for all repair costs, replacement costs, rental loss
                            charges, and any other related damages.</p>
                    </li>
                </ul>
                <p>It is your responsibility to ensure the equipment is properly secured and safeguarded until Rent 'n King
                    opens and can perform a full evaluation and acceptance of the return.</p>
                <p><strong>Inspection of Equipment</strong></p>
                <p><strong>Pre-Rental Inspection:</strong> Prior to the commencement of the rental period, the Renter shall
                    have the right to conduct a thorough inspection of the equipment. This inspection is intended to allow
                    the Renter to assess and document the condition of the equipment.</p>
                <p><strong>Documentation of Condition:</strong> The Renter may, at their discretion, document the current
                    state of the equipment using photographs, videos, or written descriptions. This documentation should be
                    comprehensive and include any pre-existing damages, wear, or defects. </p>
                <p><strong>Video Documentation:</strong> Additionally, both parties agree to video record the condition of
                    the equipment before and after the rental period. The video documentation shall serve as a supplementary
                    means of capturing the equipment's condition and will be considered alongside any other forms of
                    documentation.</p>
                <p><strong>Notification of Findings:</strong> If the Renter identifies any discrepancies, damages, or
                    concerns during the inspection, they shall promptly notify the Owner in writing before accepting the
                    equipment. The Owner and Renter shall discuss and record any agreed-upon conditions or necessary
                    repairs.</p>
                <p><strong>Acknowledgment by Owner: </strong> The Owner acknowledges the Renter's right to inspect and
                    document the equipment's condition before the rental period begins. The Owner agrees to cooperate with
                    the Renter during this inspection process and to address any valid concerns raised by the Renter.</p>
                <p><strong>Condition at Return:</strong> Upon the return of the equipment at the end of the rental period,
                    the condition will be compared to the documented state at the beginning of the rental. Discrepancies not
                    attributable to normal wear and tear shall be subject to the provisions outlined in the "Damages and
                    Financial Responsibility" section of this agreement.</p>
                <p><strong>Equipment Usage Limits and Additional Charges</strong></p>
                <p><strong>Calendar Day Limits: </strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-10">
                    <li>
                        <p>The Renter acknowledges that there are calendar time limits to the usage of the equipment, as
                            specified below regardless of when the item is picked up by the customer. Exceeding the rental
                            period will result in an additional charge for a full day.</p>
                        <ol class="list-decimal flex flex-col gap-y-3 ml-10 mt-3">
                            <li>
                                <p> Daily Rental = 9:00 AM to 9:00 AM the following day</p>
                            </li>
                            <li>
                                <p>Weekend Special Rental = 2:00 PM – Friday to 9:00 AM - Monday</p>
                            </li>
                            <li>
                                <p>Weekly = 9:00 AM on Start Date to 9:00 AM on the Same Day of the Week i.e. 9:00 AM Monday
                                    to 9:00 AM Monday of the immediately following week. </p>
                            </li>
                            <li>
                                <p>Monthly = 9:00 AM on Start Date to 9:00 AM of the 29th day i.e. 9:00 AM January 1 to 9:00
                                    AM January 29th.</p>
                            </li>
                        </ol>
                    </li>
                </ul>
                <p>The standard rental hours are not altered based upon the time the customer picks up the equipment unless
                    specifically agreed upon by Rent ‘n King management. Once the equipment leaves the lot, whether it is
                    picked up by the customer or leaves for delivery to the customer’s job site, the item is considered
                    “rented” for the time period requested. </p>
                <p><strong>Hourly Usage Limits:</strong></p>
                <p>The Renter acknowledges that there are hourly limits to the usage of the equipment, as specified below.
                    These limits are established to ensure the proper maintenance and longevity of the equipment.</p>
                <ol class="list-decimal flex flex-col gap-y-3 ml-16">
                    <li>
                        <p>Daily Rental = Maximum Machine Time of 8 hours</p>
                    </li>
                    <li>
                        <p>Weekend Special Rental = Maximum Machine Time of 14 hours </p>
                    </li>
                    <li>
                        <p>Weekly Rental = Maximum Machine Time of 40 hours </p>
                    </li>
                    <li>
                        <p>Monthly Rental = Maximum Machine Time of 160 hours </p>
                    </li>
                </ol>
                <p><strong>Exceeding Hourly Limits: </strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-8">
                    <li>
                        <p>If the Renter exceeds the specified hourly limits for the equipment, additional charges will
                            apply. The excess usage will be calculated on a per-hour basis for each hour over the limit.
                        </p>
                    </li>
                    <li>
                        <p>Unpaid rental extensions, whether authorized or unauthorized, will be billed at the standard
                            daily rate. No discounts will be extended for keeping the equipment beyond the original rental
                            term. For example, if you rent for 1-day and then extend the rental to 7-days, you will be
                            charged the daily rate for 7-days versus the discounted weekly rate. Rental extensions need to
                            be paid at the time of the rental extension request and authorization.</p>
                    </li>
                </ul>
                <p><strong>Calculation of Additional Charges:</strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-8">
                    <li>
                        <p>Additional charges for exceeding hourly limits will be calculated by rounding up any partial hour
                            to the next full hour. The applicable per-hour rate for excess usage is calculated by taking the
                            standard rental rate and dividing it by the “Maximum Machine Time” limits to determine the
                            prorated “per hour cost”. </p>
                    </li>
                    <li>
                        <p>Hourly limits exceeded by more than 50% (51%+) will incur a full day charge for the equipment.
                        </p>
                    </li>
                </ul>
                <p><strong>Notification of Excess Usage:</strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-8">
                    <li>
                        <p>Owner will promptly notify the Renter if it is determined that the hourly limits have been
                            exceeded. </p>
                    </li>
                    <li>
                        <p>The Renter agrees to pay the additional charges associated with the excess usage with the card on
                            file and authorizes Owner to use this payment method at the time of notification of the overage
                            fees.</p>
                    </li>
                    <li>
                        <p>All overage fees are due immediately and will be charged to the card on file unless paid by an
                            alternatively approved source of payment.</p>
                    </li>
                </ul>
                <p><strong>Rental Extensions:</strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-8">
                    <li>
                        <p>Rental extensions must be approved and paid at the beginning of the rental extension. While every
                            consideration will be made to honor rental extension requests, rentals cannot be extended simply
                            by “keeping” the rented item longer than the contracted rental duration.</p>
                    </li>
                    <li>
                        <p>Rental Extensions must be requested, and approved, at least 12 hours before the end of the
                            current rental period.</p>
                    </li>
                    <li>
                        <p>Unpaid rental extensions, whether authorized or unauthorized, will be billed at the standard
                            daily rate. No discounts will be extended for keeping the equipment beyond the original rental
                            term. For example, if you rent for 1-day and then extend the rental to 7-days, you will be
                            charged the daily rate for 7-days versus the discounted weekly rate. Rental extensions need to
                            be paid at the time of the rental extension request and authorization.</p>
                    </li>
                    <li>
                        <p>Unpaid / Late Pay Invoices lose all volume discounts and revert back to the Daily Rate times the
                            number of days rented.</p>
                    </li>
                </ul>
                <p><strong>Right to Adjust Limits:</strong></p>
                <ul class="list-disc flex flex-col gap-y-3 ml-8">
                    <li>
                        <p>Owner reserves the right to adjust hourly usage limits if, in its discretion, it deems necessary
                            to safeguard the equipment's optimal functioning and to comply with manufacturer recommendations
                            or for any other reason mutually agreed upon prior to the start of the rental period. </p>
                    </li>
                </ul>
                <p><strong>Force Majeure Clause:</strong></p>
                <p>Neither party shall be liable for any failure or delay in performing its obligations under this Agreement
                    where such failure or delay results from any cause beyond the reasonable control of that party,
                    including but not limited to acts of God, labor disputes, government orders, or other causes.</p>

            </div>
            <div class="flex flex-col gap-y-3 text-[14px] mt-6">
                <h4 class="text-[20px]"><strong>Close Cabs</strong></h4>
                <div class="ml-6 flex flex-col gap-y-3">
                    <p><strong>Cabs</strong></p>
                    <ol class="list-decimal ml-4  flex flex-col gap-y-3">
                        <li>
                            <p><strong>Machine With Closed Cabs:</strong>Renter acknowledges that certain tented equipment
                                provided by Owner is equipped '&ith Closed cabs for the operator's comfort- These Closed
                                cabs include and glass components designed to enhance operator comfort during operation.</p>
                        </li>
                        <li>
                            <p><strong>Operation and Storage Requirements:</strong> Renter agrees to operate and store the
                                rented equipment with the doors of the closed cab securely closed at all times during
                                operation and while the
                                equipment is not in use. Failure to comply with this requirement may result in additional
                                charges and potential liability for damages.</p>
                        </li>
                        <li>
                            <p><strong>Cleaning Fees for Operating/Storage with Door Open:</strong> Renter hereby agrees to
                                an additional cleaning fees if the rented equipment is operated or stored with the doors of
                                the closed cab open. These fees cover the cleaning of cabin filters, AC/heat ducts,
                                mold/mildew, and associated components necessitated by such operation/storage.</p>
                            <ul class="list-disc ml-8 flex flex-col gap-y-3 mt-3">
                                <li>
                                    <p>Average cost to replace cabin filters: <strong> $145</strong></p>
                                </li>
                                <li>
                                    <p>Average cost to clean cabin duct work: <strong>$370</strong></p>
                                </li>
                                <li>
                                    <p>Average cost to clean cabin: <strong>$135</strong></p>
                                </li>
                                <li>
                                    <p>Mold in the cabin / ductwork may form from operating and in dirty conditions and then
                                        “power washing / hosing” the interior to clean it and this will result in the need
                                        to replace many interior components i.e. ductwork, interior body panels, screens,
                                        and more. The cost to remediate this type of damage <strong>often exceeds $2,500 in
                                            parts and labor.</strong></p>
                                </li>
                                <li>
                                    <p>If you think you need to work in a situation that necessitates an “open door
                                        operation”, then rent a machine with an open cab that is built for this type of
                                        exposure!</p>
                                </li>
                            </ul>
                            <div class="flex items-center -ml-10 my-3">
                                <input id="cabs1" type="checkbox"
                                    class="w-4 h-4 mt-2 text-blue-600 bg-gray-100 border-gray-300 rounded-sm">
                                <label for="cabs1" class="w-full pt-3 ms-2 text-sm text-[#ff0000] font-medium">Customer
                                    Approval Required</label>
                            </div>
                        </li>
                        <li>
                            <p><strong>Door and Glass Damage Not Covered: </strong> Renter understands and acknowledges that
                                any damage to the doors or glass components of the closed cab, including but not limited to
                                scratches, cracks, or breakages, is expressly excluded from coverage under the damage waiver
                                specified in the Rental Agreement. Renter is responsible for the repair or replacement costs
                                associated with such damages.</p>
                            <ul class="list-disc ml-8 flex flex-col gap-y-3 mt-3">
                                <li>
                                    <p>Average parts cost to replace the front door glass only: <strong>$375 - $625
                                        </strong></p>
                                </li>
                                <li>
                                    <p>Average parts cost to replace the front door complete: <strong>$2,125 - $3,280
                                        </strong></p>
                                </li>
                                <li>
                                    <p>Average parts cost to replace the side panel glass: <strong>$349 </strong></p>
                                </li>
                                <li>
                                    <p>Labor costs vary based upon the items damaged and multiple other variables but
                                        typically start no less <strong>than $225. </strong></p>
                                </li>
                            </ul>
                            <p>*Actual parts prices and labor costs vary over time and cannot be fully assessed until the
                                time of the damages and repairs.</p>
                            <div class="flex items-center -ml-10 my-3">
                                <input id="cabs1" type="checkbox"
                                    class="w-4 h-4 mt-2 text-blue-600 bg-gray-100 border-gray-300 rounded-sm">
                                <label for="cabs1" class="w-full pt-3 ms-2 text-sm text-[#ff0000] font-medium">Customer
                                    Approval Required</label>
                            </div>
                        </li>
                        <li>
                            <p><strong>Inspection and Reporting:</strong> Renter agrees to inspect the doors and glass
                                components of the closed cab for any pre-existing damage before operation. Any pre-existing
                                damage must be reported to Owner immediately. Failure to report pre-existing damage may
                                result in the Renter being held responsible for repair or replacement costs.</p>
                            <div class="flex items-center -ml-10 mt-3">
                                <input id="cabs1" type="checkbox"
                                    class="w-4 h-4 mt-2 text-blue-600 bg-gray-100 border-gray-300 rounded-sm">
                                <label for="cabs1" class="w-full pt-3 ms-2 text-sm text-[#ff0000] font-medium">Customer
                                    Approval Required</label>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
            <div class="flex flex-col gap-y-3 mt-8  text-[14px]">
                <p>By signing this agreement, the Renter acknowledges and accepts the financial responsibility, and
                    immediate payment, for damages, administrative time, legal expenses, lawyer fees, court costs,
                    collection expenses, parts, transportation, specialized equipment, and foregone rental revenue as
                    described above. </p>
                <p>IN WITNESS WHEREOF, the parties have executed this Agreement as of the date first written above.</p>
                <div>
                    <p>OWNER:</p>
                    <p>Development 360, inc </p>
                    <p>DBA Rent 'n King</p>
                </div>
                <div>
                    <p>RENTER:</p>
                    <P>By: Fake Customer</P>
                </div>
            </div>
            <div class="flex items-center gap-x-2  text-[14px]">
                <p>Signature:</p>
                <button type="button" onclick="openModal()"
                    class="border-0 bg-yellow-400 text-[14px] px-6 py-4 font-medium rounded-lg hover:bg-yellow-300  transition-all duration-500 ease-in-out">
                    CLICK HERE TO SIGN</button>
            </div>
            <div class="flex flex-col gap-y-1 mt-4  text-[14px]">
                <p>4385 SR-48, Charlotte, TN (615) 815-6734</p>
                <p>Rent ’n King is a Brand Name of Development 360, Inc</p>
            </div>
            <div class="border-t mt-8 pt-4">
                <button type="button"
                    class="border-0 bg-yellow-400 text-[14px] px-6 py-4 font-medium rounded-lg hover:bg-yellow-300  transition-all duration-500 ease-in-out">
                    SUBMIT</button>
            </div>

        </div>
    </section>
    <!-- Signature Modal -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden "
        onclick="closeModalOnOutsideClick(event)">
        <div class="signatureModal-box bg-white rounded-lg shadow-lg w-full md:max-w-lg relative max-w-[90%]">
            <div class="flex justify-between p-4 py-2 items-center rounded-t-lg border-b">
                <h5 class="text-lg font-medium text-dark">Signature Pad</h5>
                <button onclick="closeModal()" class="px-4 py-2 text-dark"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-6 py-4">
                <div class="signature-container">
                    <canvas id="signature-pad" class="signature-pad border border-gray-300 "></canvas>
                    <div class=" flex gap-x-4">
                        <button class="bg-[#03aa6d] text-white font-bold py-2 px-4  clear-button border-0"
                            onclick="clearSignature()">Clear</button>
                        <button class="bg-[#03aa6d] text-white font-bold py-2 px-4  save-button border-0"
                            onclick="undoSignature()">Undo</button>
                    </div>
                </div>
            </div>

            <div class="border-t p-6 py-4">
                <div class="text-right flex flex-col md:flex-row whitespace-nowrap justify-end gap-3">
                    <button onclick="saveSignature()"
                        class="border-0 bg-yellow-400 text-[14px] px-6 py-3 rounded-lg font-bold hover:bg-yellow-300  transition-all duration-500 ease-in-out order-2 md:order-1">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Modal control
        function openModal() {
            document.getElementById("modal").classList.remove("hidden");
            initSignaturePad();
        }

        function closeModal() {
            document.getElementById("modal").classList.add("hidden");
        }

        function closeModalOnOutsideClick(event) {
            const modalContent = event.target.closest('.rounded-lg');
            if (!modalContent) {
                closeModal();
            }
        }

        // Signature Pad init
        let signaturePad; // Make globally accessible for clear/undo/save

        function initSignaturePad() {
            // Wait until the SignaturePad library and the canvas are both present
            function setup() {
                const canvas = document.getElementById('signature-pad');
                if (!canvas || typeof SignaturePad === 'undefined') {
                    setTimeout(setup, 100);
                    return;
                }
                // Resize canvas for device pixel ratio
                function resizeCanvas() {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                }
                resizeCanvas();
                // Only create if not already created
                if (!signaturePad) {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255,255,255,1)',
                        penColor: 'rgb(0,0,0)'
                    });
                } else {
                    signaturePad.clear(); // reset for new signature
                }
            }
            setup();
        }

        // These functions can be called by the buttons
        function undoSignature() {
            if (signaturePad) {
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
            if (signaturePad) {
                const canvas = document.getElementById('signature-pad');
                const dataURL = canvas.toDataURL();
                // Use the dataURL as needed, e.g. send to server via AJAX
                console.log(dataURL);
            }
        }

        // Optional: re-size canvas on window resize
        window.addEventListener('resize', function () {
            if (signaturePad) {
                const canvas = document.getElementById('signature-pad');
                if (canvas) {
                    const data = signaturePad.toData();
                    // re-initialize canvas
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear();
                    signaturePad.fromData(data);
                }
            }
        });
    </script>
@endpush

