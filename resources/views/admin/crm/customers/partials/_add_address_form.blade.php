
                <div x-data="{ showAddressModal: false }" x-ref="addressRoot">
                    
                    <!-- Modal -->  
                    <div
                        x-show="showAddressModal"
                        x-transition
                        x-cloak
                        class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
                        <div class="modal-scrollable">

                            <div
                                @click.away="showAddressModal = false"
                                class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-xl  space-y-5 border border-gray-200 dark:border-gray-700 max-h-full overflow-hidden flex flex-col" >
                                <div class="flex justify-between items-center  px-6 pt-4 ">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Add Address</h3>
                                    <button @click="showAddressModal = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white text-xl">&times;</button>
                                </div>
                    
                                <!-- Address form goes here -->
                            <div class="overflow-y-auto flex flex-col gap-y-4 px-6">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4   " >
                                    <!-- {{-- First Name --}} -->
                                    <div>
                                        <label class="text-sm font-medium text-gray-700" for="add_first_name">First name</label>
                                        {!! html()->text('add_first_name', old('add_first_name'))->class([
                                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                            'border-red-500' => $errors->has('add_first_name'),
                                        ])->attributes([
                                            'placeholder' => 'Enter First name',
                                            'id' => 'add_first_name',
                                            'autocomplete' => 'given-name',
                                        ])->required() !!}
                                        @error('add_first_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- {{-- Last Name --}} -->
                                    <div>
                                        <label class="text-sm font-medium text-gray-700" for="add_last_name">Last name</label>
                                        {!! html()->text('add_last_name', old('add_last_name'))->class([
                                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                            'border-red-500' => $errors->has('add_last_name'),
                                        ])->attributes([
                                            'placeholder' => 'Enter Last name',
                                            'id' => 'add_last_name',
                                            'autocomplete' => 'family-name',
                                        ])->required() !!}
                                        @error('add_last_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4   ">
                                    {{-- Phone --}}
                                    <div>
                                        <label class="text-sm font-medium text-gray-700" for="add_phone">Phone</label>
                                        {!! html()->text('add_phone', old('add_phone'))->class([
                                            'masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                            'border-red-500' => $errors->has('add_phone'),
                                        ])->attributes([
                                            'placeholder' => '(123) 456-7890',
                                            'id' => 'add_phone',
                                            'autocomplete' => 'tel',
                                        ])->required() !!}
                                        @error('add_phone')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- {{-- Type --}} -->
                                    <div>
                                        <label class="text-sm font-medium text-gray-700" for="add_type">Type</label>
                                        {!! html()->select('add_type', [
                                            '' => 'Select Type',
                                            'Billing' => 'Billing',
                                            'Shipping' => 'Shipping',
                                        ], old('add_type'))->class([
                                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                                            'border-red-500' => $errors->has('add_type'),
                                        ])->attributes([
                                            'id' => 'add_type',
                                        ])->required() !!}
                                        @error('add_type')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                    <!-- {{-- Address --}} -->
                                <div class="  ">
                                        <label class="text-sm font-medium text-gray-700" for="address">Address</label>
                                        {!! html()->text('<address></address>', old('address'))->class([
                                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                            'border-red-500' => $errors->has('address'),
                                        ])->attributes([
                                            'placeholder' => '10 Downing Street , LONDON',
                                            'id' => 'address',
                                            'autocomplete' => 'street-address',
                                        ])->required() !!}
                                        @error('address')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4  ">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">State</label>
                                            
                                                            {!! html()
                                                                ->select('add_state',
                                                                $states->mapWithKeys(fn ($state) => [$state->id => $state->name])->toArray(),
                                                                    old('add_state', $customer->state ?? '')
                                                                )
                                                                ->id('add_state')
                                                                ->class([
                                                                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                                                                    'border-red-500' => $errors->has('add_state'),
                                                                ])->required()
                                                            !!}

                                                            @error('add_state')
                                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                            @enderror
                                        </div>
                                        {{-- City --}}
                                        <div>
                                                <label class="text-sm font-medium text-gray-700" for="add_city">City</label>
                                                {!! html()->text('add_city', old('add_city'))->class([
                                                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                                    'border-red-500' => $errors->has('add_city'),
                                                ])->attributes([
                                                    'placeholder' => 'Enter city',
                                                    'id' => 'add_city',
                                                    'autocomplete' => 'address-level2',
                                                ])->required() !!}
                                                @error('add_city')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                        </div>

                                            {{-- Zip Code --}}
                                        <div>
                                                <label class="text-sm font-medium text-gray-700" for="add_zip_code">Zip code</label>
                                                {!! html()->number('add_zip_code', old('add_zip_code'))->class([
                                                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                                    'border-red-500' => $errors->has('add_zip_code'),
                                                ])->attributes([
                                                    'placeholder' => 'Enter zip code',
                                                    'id' => 'add_zip_code',
                                                    'autocomplete' => 'postal-code',
                                                ])->required() !!}
                                                @error('add_zip_code')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                        </div>

                                </div>

                            </div>

                                <div class="flex justify-end gap-2 px-6  pb-4">
                                        <button type="button" @click="showAddressModal = false"
                                            class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
                                        <button type="button" id="submitAddressBtn"
                                            class="px-4 py-2 text-sm rounded bg-teal-600 text-white hover:bg-blue-700">Add </button>
                                </div>
                                
                            </div>

                        </div>
                    </div>
                </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const submitBtn = document.getElementById('submitAddressBtn');
                const hiddenInput = document.getElementById('alladdresslist');
                const addressList = document.getElementById('realAddressList');

                const fields = [
                    'add_first_name', 'add_last_name', 'add_phone', 'add_type',
                    'address', 'add_city', 'add_zip_code', 'add_state'
                ];

                let editIndex = null;


                let allAddresses = [];

                function updateAltPlaceholders() {
                    const hasBilling = allAddresses.some(addr => addr.type === 'Billing');
                    const hasShipping = allAddresses.some(addr => addr.type === 'Shipping');

                    document.getElementById('altBillingPlaceholder').style.display = hasBilling ? 'none' : 'block';
                    document.getElementById('altShippingPlaceholder').style.display = hasShipping ? 'none' : 'block';
                }


                try {
                    const val = hiddenInput.value?.trim();
                    allAddresses = val ? JSON.parse(val) : [];

                    updateAltPlaceholders();


                } catch (e) {
                    console.log('Invalid JSON in #alladdresslist', e);
                }

            
                allAddresses.forEach((addr, i) => renderAddressBlock(addr, i));

                function renderAddressBlock(addressObj, index) {
                    const label = addressObj.type === 'Billing' ? 'Billing Address' : 'Delivery Address';
                    const fullText = `${addressObj.address}, ${addressObj.city}, ${addressObj.state} - ${addressObj.zip_code}`;

                    const addressBlock = document.createElement('div');
                    addressBlock.className = 'address-entry';
                    addressBlock.innerHTML = `
                        <span class="text-xs text-gray-500">${label}</span>
                        <div class="flex gap-x-2 mt-1">
                            <x-heroicon-o-map-pin class="w-5 h-5" />
                            <div class="text-sm flex-1">${fullText}</div>
                            <div class="ml-2 flex gap-x-2">
                                <button type="button" class="text-green-600 hover:text-green-800 edit-address" title="Edit">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </button>
                                <button type="button" class="text-red-600 hover:text-red-800 delete-address" title="Delete">
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    `;

                
                    addressBlock.querySelector('.delete-address').addEventListener('click', () => {
                        const idx = [...addressList.children].indexOf(addressBlock);
                        allAddresses.splice(idx, 1);
                        addressBlock.remove();

                        updateAltPlaceholders();


                        hiddenInput.value = JSON.stringify(allAddresses);
                    });

            
                    addressBlock.querySelector('.edit-address').addEventListener('click', () => {
                        const idx = [...addressList.children].indexOf(addressBlock);
                        const data = allAddresses[idx];
                        editIndex = idx;

                        document.getElementById('add_first_name').value = data.first_name;
                        document.getElementById('add_last_name').value = data.last_name;
                    
                        document.getElementById('add_phone').value = data.phone;
                        document.getElementById('add_type').value = data.type;
                        document.getElementById('address').value = data.address;
                        document.getElementById('add_city').value = data.city;
                        document.getElementById('add_zip_code').value = data.zip_code;
                        document.getElementById('add_state').value = data.state_id;

                        const modalRoot = document.querySelector('[x-ref="addressRoot"]');
                        if (modalRoot?._x_dataStack?.[0]) {
                            modalRoot._x_dataStack[0].showAddressModal = true;
                        }
                    });

                    addressList.appendChild(addressBlock);
                }

                submitBtn.addEventListener('click', () => {
                    let isValid = true;

                    fields.forEach(id => {
                        const el = document.getElementById(id);
                        if ($(el).parsley().validate() !== true) {
                            isValid = false;
                        }
                    });

                    if (!isValid) return;

                    const firstName = document.getElementById('add_first_name').value;
                    const lastName = document.getElementById('add_last_name').value;
                
                    const phone = document.getElementById('add_phone').value;
                    const type = document.getElementById('add_type').value;
                    const address = document.getElementById('address').value;
                    const city = document.getElementById('add_city').value;
                    const zip = document.getElementById('add_zip_code').value;
                    const state = document.getElementById('add_state').selectedOptions[0]?.text || '';
                    const state_id = document.getElementById('add_state').value;
                    const label = type === 'Billing' ? 'Billing Address' : 'Delivery Address';

                    const addressObj = {
                        first_name: firstName,
                        last_name: lastName,
                        phone,
                        type,
                        address,
                        city,
                        state,
                        state_id,
                        zip_code: zip,
                        label
                    };

                    if (editIndex !== null) {
                        addressObj.address_id = allAddresses[editIndex].address_id ?? null;

                        allAddresses[editIndex] = addressObj;

                        const block = addressList.children[editIndex];
                        block.querySelector('.text-xs').innerText = label;
                        block.querySelector('.text-sm').innerText = `${address}, ${city}, ${state} - ${zip}`;
                        editIndex = null;
                    } else {
                        allAddresses.push(addressObj);
                        renderAddressBlock(addressObj, allAddresses.length - 1);
                    }

                    hiddenInput.value = JSON.stringify(allAddresses);

                    updateAltPlaceholders();

                    
                    const modalRoot = document.querySelector('[x-ref="addressRoot"]');
                    if (modalRoot?._x_dataStack?.[0]) {
                            modalRoot._x_dataStack[0].showAddressModal = false;
                        }

                    fields.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) input.value = '';
                    });
                });
            });
        </script>
