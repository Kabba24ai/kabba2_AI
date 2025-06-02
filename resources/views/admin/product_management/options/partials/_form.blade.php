{{-- Rental Options List Form Partial --}}

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    {{-- Name --}}
    <div>
        {{ html()->label('Name', 'name')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required') }}
        {{ html()->text('name', old('name'))->id('name')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('name'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-required' => 'true',
                'data-parsley-maxlength' => 240,
                'autocomplete' => 'off',
                'placeholder' => 'Enter Name',
            ]) }}
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Type --}}
    <div>
        {{ html()->label('Type', 'type')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required') }}
        {{ html()->select('type', ['Rental' => 'Rental', 'Retail' => 'Retail'], old('type'))->id('type')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('type'),
            ])->required()->placeholder('Select Type') }}
        @error('type')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Status --}}
    <div>
        {{ html()->label('Status', 'status')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300') }}
        {{ html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', 'Active'))->id('status')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('status'),
            ])->required() }}
        @error('status')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Description --}}
<div class="mb-8">
    {{ html()->label('Description', 'description')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300') }}
    {{ html()->textarea('description', old('description'))->id('description')->attributes([
            'rows' => 3,
            'maxlength' => 1000,
            'data-parsley-maxlength' => 1000,
            'placeholder' => 'Enter Description',
        ])->class([
            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('description'),
        ]) }}
    @error('description')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

{{-- Options Table --}}
<div x-data="optionsList()" x-cloak>
    <!-- Options Table -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-md overflow-x-auto">
        <div class="p-4">
            <h4 class="font-medium text-gray-800 dark:text-white mb-1">Options</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Add and configure individual options for this list
            </p>

            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 dark:bg-gray-800 text-xs uppercase">
                    <tr>
                        <th class="whitespace-normal px-3 py-2">#</th>
                        <th class="whitespace-normal px-3 py-2">Drag</th>
                        <th class="whitespace-normal px-3 py-2">Label</th>
                        <th class="whitespace-normal px-3 py-2">Daily</th>
                        <th class="whitespace-normal px-3 py-2">W/E Spcl.</th>
                        <th class="whitespace-normal px-3 py-2">Weekly</th>
                        <th class="whitespace-normal px-3 py-2">Monthly</th>
                        <th class="whitespace-normal px-3 py-2">Charged Per Order</th>
                        <th class="whitespace-normal px-3 py-2">Value</th>
                        <th class="whitespace-normal px-3 py-2">Comment</th>
                        <th class="whitespace-normal px-3 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-table" x-ref="sortableBody">
                    <template x-for="(option, index) in options" :key="option.key">
                        <tr class="bg-white dark:bg-gray-950 border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2" x-text="index + 1"></td>
                            <td class="px-3 py-2 text-center cursor-move">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <circle cx="5" cy="5" r="1.5" />
                                    <circle cx="10" cy="5" r="1.5" />
                                    <circle cx="5" cy="10" r="1.5" />
                                    <circle cx="10" cy="10" r="1.5" />
                                    <circle cx="5" cy="15" r="1.5" />
                                    <circle cx="10" cy="15" r="1.5" />
                                </svg>
                            </td>
                            <td class="px-3 py-2 w-60">
                                <input type="text" :name="`options[${index}][label]`" x-model="option.label"
                                    class="w-full rounded-md border px-2 py-1 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="Option Name" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" :name="`options[${index}][daily]`" value="0"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" :name="`options[${index}][weekend]`" value="0"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" :name="`options[${index}][weekly]`" value="0"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" :name="`options[${index}][monthly]`" value="0"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                            </td>
                            <td class="px-3 py-2 w-40">
                                <select :name="`options[${index}][charged]`"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                    <option value="Unlimited">Unlimited</option>
                                    <option value="1 Time Max">1 Time Max</option>
                                </select>
                            </td>
                            <td class="px-3 py-2 w-32">
                                <select :name="`options[${index}][value]`" x-model="option.value"
                                    class="w-full rounded-md border px-2 py-1 border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                    <option value="Blank">Blank</option>
                                    <option value="Checked">Checked</option>
                                </select>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <button type="button" @click="option.value === 'Checked' && openCommentModal(index)"
                                    :disabled="option.value !== 'Checked'"
                                    :class="[
                                        option.value !== 'Checked' ? 'text-gray-300 cursor-not-allowed' : (option
                                            .comment ? 'text-blue-600' : 'text-gray-400'),
                                        'transition hover:text-blue-700'
                                    ]">
                                    <x-heroicon-o-chat-bubble-left-right class="w-5 h-5" />
                                </button>

                                <input type="hidden" :name="`options[${index}][comment]`" :value="option.comment">
                                <input type="hidden" :name="`options[${index}][accept_label]`"
                                    :value="option.accept_label">
                                <input type="hidden" :name="`options[${index}][decline_label]`"
                                    :value="option.decline_label">
                            </td>
                            <td class="px-3 py-2 text-center text-red-600 cursor-pointer" @click="removeOption(index)">✕
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="mt-3 text-sm text-blue-600 hover:underline cursor-pointer" @click="addOption()">+ Add new row
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Start each new Options List with 4 blank rows by
                default</p>
        </div>
    </div>

    <!-- Comment Modal -->
    <div x-show="showCommentModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/40 px-4"
        x-transition x-cloak>
        <div @click.away="closeCommentModal"
            class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Option Comment & Buttons</h3>

            <div
                class="bg-blue-50 text-blue-700 border border-blue-200 p-3 text-sm rounded dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700">
                <strong>Logic:</strong> If there is no comment added to a Pre-Checked option, then the pop-up does not
                appear when the customer unchecks the option. Only if there is a comment does the comment pop-up appear.
            </div>

            <!-- Comment Text -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment Text</label>
                <textarea x-model="options[currentCommentIndex].comment"
                    class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    rows="3" placeholder="Enter Comment Text..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Leave blank to disable the pop-up confirmation for this option.
                </p>
            </div>

            <!-- If comment is empty -->
            <template x-if="!options[currentCommentIndex].comment">
                <div class="bg-yellow-100 text-yellow-800 p-3 rounded text-sm dark:bg-yellow-900 dark:text-yellow-200">
                    <strong>No Pop-up:</strong> Since there's no comment, customers can uncheck this option without any
                    confirmation pop-up.
                </div>
            </template>

            <!-- Buttons & Preview – only visible when comment is entered -->
            <template x-if="options[currentCommentIndex]?.comment">
                <div class="space-y-4">
                    <!-- Button Labels -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Decline
                                Button Text (Red)</label>
                            <input type="text" x-model="options[currentCommentIndex].decline_label"
                                class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="No Thanks, I'll Take The Risk">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Accept
                                Button
                                Text (Green)</label>
                            <input type="text" x-model="options[currentCommentIndex].accept_label"
                                class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="Keep Safety Harness">
                        </div>
                    </div>

                    <!-- Pop-up Preview -->
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-md p-4 mt-2 bg-white dark:bg-gray-800">
                        <p class="text-sm text-gray-800 dark:text-white mb-2">Customer Pop-up Preview:</p>
                        <div class="space-y-3">
                            <p class="text-sm text-gray-700 dark:text-gray-300"
                                x-text="options[currentCommentIndex].comment"></p>
                            <div class="flex gap-2">
                                <button type="button" class="bg-red-600 text-white px-4 py-1 rounded text-sm"
                                    x-text="options[currentCommentIndex].decline_label || 'No Thanks'">
                                </button>
                                <button type="button" class="bg-green-600 text-white px-4 py-1 rounded text-sm"
                                    x-text="options[currentCommentIndex].accept_label || 'Yes, Keep It'">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>


            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <button type="button" @click="closeCommentModal"
                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">
                    Cancel
                </button>
                <button type="button" @click="closeCommentModal"
                    class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">
                    Save Comment
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        function optionsList() {
            return {
                options: [],
                showCommentModal: false,
                currentCommentIndex: null,

                init() {
                    // Ensure 4 default rows
                    if (this.options.length === 0) {
                        for (let i = 0; i < 4; i++) {
                            this.addOption();
                        }
                    }

                    // Enable sortable
                    new Sortable(this.$refs.sortableBody, {
                        handle: '.cursor-move',
                        animation: 150,
                        onEnd: (evt) => {
                            const movedItem = this.options.splice(evt.oldIndex, 1)[0];
                            this.options.splice(evt.newIndex, 0, movedItem);
                        }
                    });
                },

                addOption() {
                    this.options.push({
                        key: Date.now() + Math.random(),
                        label: '',
                        comment: '',
                        decline_label: "No Thanks, I'll Take The Risk",
                        accept_label: "Keep Safety Harness"
                    });
                },

                removeOption(index) {
                    if (this.options.length > 1) {
                        this.options.splice(index, 1);
                    }
                },

                openCommentModal(index) {
                    this.currentCommentIndex = index;
                    this.showCommentModal = true;
                },

                closeCommentModal() {
                    this.showCommentModal = false;
                    this.currentCommentIndex = null;
                }
            };
        }
    </script>
@endpush
