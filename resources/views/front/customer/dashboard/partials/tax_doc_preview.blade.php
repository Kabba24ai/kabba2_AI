  @if ($customer->media)
                                    <!-- Left Side: Icon + File Info -->
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h10M7 11h10M7 15h10M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate">{{$customer->media->original_file_name ?? ''}}</div>
                                            <div class="text-gray-500 text-xs">
                                            Status: <span  class="font-medium
                                                {{ $customer->tax_document_status === 'Rejected' ? 'text-red-600' :
                                                ($customer->tax_document_status === 'Approved' ? 'text-green-600' : 'text-yellow-600') }}">
                                                    {{ $customer->tax_document_status ?: 'Pending Review' }}
                                            </span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm p-4 rounded-md">
                                        No tax document has been uploaded yet.
                                    </div>
                                @endif

                                    <!-- Right Side: Actions -->
                                    <div class="flex gap-4 text-sm justify-end sm:justify-start">
                                          @if ($customer->media)
                                        <a href="{{ isset($customer->media) ? $customer->media->getUrl() : 'javascript:void(0)' }}" @if(isset($customer->media))  @endif class="text-blue-600">View</a>
                                        <a href="javascript:void(0)" id="opentaxdocModal" class="text-green-600">Replace</a>
                                          @else
                                        <a href="javascript:void(0)" id="opentaxdocModal" class="text-green-600">Add New</a>

                                         @endif
                                    </div>