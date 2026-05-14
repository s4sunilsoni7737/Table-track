<div
    class="w-full flex flex-col bg-white dark:border-gray-700 pr-4 px-2 py-4 dark:bg-gray-800 lg:sticky lg:top-0 lg:self-start overflow-hidden rounded-md h-full">
    <style>
        .pos-hover-scrollbar {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .pos-hover-scrollbar::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .pos-hover-scrollbar:hover {
            scrollbar-width: thin;
        }

        .pos-hover-scrollbar:hover::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
    </style>

    {{-- Order Type Indicator --}}
    @if($orderTypeId)
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 dark:text-gray-400">@lang('modules.settings.orderType'):</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ \App\Models\OrderType::find($orderTypeId)?->order_type_name ?? ucfirst($orderType) }}
            </span>

            @if($orderTypeSlug === 'delivery' && $selectedDeliveryApp)
                <span class="text-xs text-gray-500 dark:text-gray-400 mx-2">•</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">Platform:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    @if($selectedDeliveryApp === 'default')
                        Default
                    @else
                        {{ \App\Models\DeliveryPlatform::find($selectedDeliveryApp)?->name ?? 'Unknown' }}
                    @endif
                </span>
            @endif
        </div>

        <button type="button" onclick="changeOrderType()" class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-full transition-all">
            @lang('app.change')
        </button>
    </div>
    @endif

    <div class="overflow-y-auto overflow-x-hidden space-y-2 pr-1 pb-36 pos-hover-scrollbar">
        <div class="mt-2 customer-display-container">
            {{-- Customer info section (shown when customer exists) --}}
            <div id="customer-info-section" class="flex items-center gap-2" style="display: {{ $customerId ? 'flex' : 'none' }};">
                <div id="customer-name" class="font-semibold text-gray-700 dark:text-gray-300">{{ $customer->name ?? '' }}</div>
                @if(user_can('Update Order'))
                    <button id="edit-customer-btn" onclick="showAddCustomerModal({{ $customerId ?? 'null' }})" title="{{__('modules.order.updateCustomerDetails')}}" class="p-1 text-gray-500 transition-colors bg-gray-100 rounded-md hover:text-gray-700 hover:bg-gray-200 rtl:ml-2 ltr:mr-2 dark:text-gray-300 dark:bg-gray-600 dark:hover:text-gray-200 dark:hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Add customer section (shown when no customer) --}}
            <div id="add-customer-section" style="display: {{ $customerId ? 'none' : 'block' }};">
                <a href="javascript:;"
                    onclick="showAddCustomerModal()"
                    class="text-sm underline underline-offset-2 dark:text-gray-300">&plus; @lang('modules.order.addCustomerDetails')</a>
            </div>
        </div>

        <div class="flex justify-between my-2 items-center">
            <div class="font-medium py-2 inline-flex items-center gap-1  dark:text-neutral-200 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-receipt w-4 h-4" viewBox="0 0 16 16"><path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0z"/><path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5"/></svg>

                @if(!isOrderPrefixEnabled())
                    @lang('modules.order.orderNumber') #{{ $orderNumber }}
                @else
                    {{ $formattedOrderNumber }}
                @endif
            </div>


            @if ($orderType == 'dine_in')
                <div class="inline-flex items-center gap-2 dark:text-gray-300 table-display-container">
                    {{-- @if (!auth()->user()->roles->pluck('display_name')->contains('Waiter')) --}}
                        <div id="table-info-section" style="display: {{ $tableNo ? 'flex' : 'none' }};" class="inline-flex items-center gap-2">
                            <svg fill="currentColor" class="w-5 h-5 transition duration-75 group-hover:text-gray-900 dark:text-gray-200 dark:group-hover:text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44.999 44.999" xml:space="preserve"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><path d="m42.558 23.378 2.406-10.92a1.512 1.512 0 0 0-2.954-.652l-2.145 9.733h-9.647a1.512 1.512 0 0 0 0 3.026h.573l-3.258 7.713a1.51 1.51 0 0 0 1.393 2.102c.59 0 1.15-.348 1.394-.925l2.974-7.038 4.717.001 2.971 7.037a1.512 1.512 0 1 0 2.787-1.177l-3.257-7.713h.573a1.51 1.51 0 0 0 1.473-1.187m-28.35 1.186h.573a1.512 1.512 0 0 0 0-3.026H5.134L2.99 11.806a1.511 1.511 0 1 0-2.954.652l2.406 10.92a1.51 1.51 0 0 0 1.477 1.187h.573L1.234 32.28a1.51 1.51 0 0 0 .805 1.98 1.515 1.515 0 0 0 1.982-.805l2.971-7.037 4.717-.001 2.972 7.038a1.514 1.514 0 0 0 1.982.805 1.51 1.51 0 0 0 .805-1.98z"/><path d="M24.862 31.353h-.852V18.308h8.13a1.513 1.513 0 1 0 0-3.025H12.856a1.514 1.514 0 0 0 0 3.025h8.13v13.045h-.852a1.514 1.514 0 0 0 0 3.027h4.728a1.513 1.513 0 1 0 0-3.027"/></svg>
                            <span id="table-code">{{ $tableNo }}</span>

                            @if (user_can('Update Order'))
                                <x-secondary-button onclick="showTableChangeConfirmationModal()" class="text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-gear" viewBox="0 0 16 16">
                                        <path
                                            d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0" />
                                        <path
                                            d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z" />
                                    </svg>
                                </x-secondary-button>
                            @endif
                        </div>
                        <div id="set-table-section" style="display: {{ $tableNo ? 'none' : 'block' }};">
                            <x-secondary-button class="text-xs"
                                onclick="showTableChangeConfirmationModal()" >@lang('modules.order.setTable')
                            </x-secondary-button>
                        </div>
                        <x-secondary-button onclick="showMergeTableModal()" class="ml-2" title="Merge Table">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/>
                            </svg>
                            @lang('modules.order.mergeTables')
                        </x-secondary-button>
                    {{-- @endif --}}
                </div>

            @endif

        </div>

        <div class="flex justify-between items-center gap-2">

            @if ($orderType == 'dine_in')
                <div class="inline-flex items-center gap-1 text-xs dark:text-gray-300">
                    @lang('modules.order.noOfPax') <x-input type="number" step='1' min='1' class="w-16 text-xs"
                        id="noOfPaxInput" value="{{ $noOfPax }}" onchange="updateNoOfPax(this.value)" />
                </div>

                <div class="gap-2 inline-flex items-center">
                    <x-secondary-button class="relative text-xs" onclick="showKotNoteModal()" :title="__('modules.order.addNote')"
                        data-tooltip-target="tooltip-note">
                        @if ($orderNote)
                            <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" fill="currentColor" class="absolute bi bi-circle-fill top-1 right-1 text-skin-base" viewBox="0 0 16 16"><circle cx="8" cy="8" r="8"/></svg>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                        </svg>
                    </x-secondary-button>

                    <div id="tooltip-note" role="tooltip"
                        class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                        @lang('modules.order.addNote')
                        <div class="tooltip-arrow" data-popper-arrow></div>
                    </div>

                    <div class="inline-flex items-center gap-2">
                        @if ($isWaiterLocked)
                            <span
                                class="text-xs w-36 px-2 py-1 border border-gray-300 rounded-md bg-gray-100 dark:text-gray-200 dark:bg-gray-600 dark:border-gray-700 truncate"
                                title="{{ $waiterName }}">
                                {{ $waiterName }}
                            </span>
                        @else
                            <x-select class="text-xs w-36" id="selectWaiterInput" onchange="updateSelectWaiter(this.value)" :disabled="$isWaiterLocked">
                                <option value="">@lang('modules.order.selectWaiter')</option>
                                @foreach ($users as $item)
                                    <option value="{{ $item->id }}" {{ ($selectWaiter && $selectWaiter == $item->id) ? 'selected' : '' }}>{{ $item->name }}</option>
                                @endforeach
                            </x-select>
                        @endif
                    </div>

                </div>
            @endif

            @if ($orderType == 'delivery')
                <div class="gap-2 flex justify-between items-center mb-2">
                    <div class="inline-flex items-center gap-2">
                        <svg class="w-6 h-6 transition duration-75 text-gray-700 dark:text-gray-200 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor" version="1.0" viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 512) scale(.1 -.1)">
                                <path
                                    d="m2605 4790c-66-13-155-48-213-82-71-42-178-149-220-221-145-242-112-552 79-761 59-64 61-67 38-73-13-4-60-24-104-46-151-75-295-249-381-462-20-49-38-91-39-93-2-2-19 8-40 22s-54 30-74 36c-59 16-947 12-994-4-120-43-181-143-122-201 32-33 76-33 106 0 41 44 72 55 159 55h80v-135c0-131 1-137 25-160l24-25h231 231l24 25c24 23 25 29 25 161v136l95-4c82-3 97-6 117-26l23-23v-349-349l-46-46-930-6-29 30c-17 16-30 34-30 40 0 7 34 11 95 11 88 0 98 2 120 25 16 15 25 36 25 55s-9 40-25 55c-22 23-32 25-120 25h-95v80 80h55c67 0 105 29 105 80 0 19-9 40-25 55l-24 25h-231-231l-24-25c-33-32-33-78 0-110 22-23 32-25 120-25h95v-80-80h-175c-173 0-176 0-200-25-33-32-33-78 0-110 24-25 27-25 197-25h174l12-45c23-88 85-154 171-183 22-8 112-12 253-12h220l-37-43c-103-119-197-418-211-669-7-115-7-116 19-142 26-25 29-26 164-26h138l16-69c55-226 235-407 464-466 77-20 233-20 310 0 228 59 409 240 463 464l17 71h605 606l13-62c58-281 328-498 621-498 349 0 640 291 640 640 0 237-141 465-350 569-89 43-193 71-271 71h-46l-142 331c-78 183-140 333-139 335 2 1 28-4 58-12 80-21 117-18 145 11l25 24v351 351l-26 26c-24 24-30 25-91 20-130-12-265-105-317-217l-23-49-29 30c-16 17-51 43-79 57-49 26-54 27-208 24-186-3-227 9-300 87-43 46-137 173-137 185 0 3 10 6 23 6s48 12 78 28c61 31 112 91 131 155 7 25 25 53 45 70 79 68 91 152 34 242-17 27-36 65-41 85-13 46-13 100 0 100 6 0 22 11 35 25 30 29 33 82 10 190-61 290-332 508-630 504-38-1-88-5-110-9zm230-165c87-23 168-70 230-136 55-57 108-153 121-216l6-31-153-4c-131-3-161-6-201-25-66-30-133-96-165-162-26-52-28-66-31-210l-4-153-31 6c-63 13-159 66-216 121-66 62-113 143-136 230-88 339 241 668 580 580zm293-619c7-41 28-106 48-147l36-74-24-15c-43-28-68-59-68-85 0-40-26-92-54-110-30-20-127-16-211 8l-50 14-3 175c-2 166-1 176 21 218 35 67 86 90 202 90h91l12-74zm-538-496c132-25 214-88 348-269 101-137 165-199 241-237 31-15 57-29 59-30s-6-20-17-43c-12-22-27-75-33-117-12-74-12-76-38-71-149 30-321 156-424 311-53 80-90 95-140 55-48-38-35-89 52-204l30-39-28-36c-42-54-91-145-110-208l-18-57-337-3-338-2 6 82c9 112 47 272 95 400 135 357 365 522 652 468zm1490-630c0-254 1-252-83-167-54 53-77 104-77 167s23 114 77 168c84 84 83 86 83-168zm-454 63c18-13 41-46 57-83l26-61-45-19c-75-33-165-52-244-54l-75-1-3 29c-8 72 44 166 113 201 42 22 132 16 171-12zm-2346-63v-80h-120-120v80 80h120 120v-80zm1584-184c80-52 154-84 261-111l90-23 112-483c68-295 112-506 112-540 1-68-21-134-56-171l-26-27-17 48c-29 86-99 159-177 186l-38 13-6 279c-5 297-5 297-64 414-58 113-212 233-328 254-21 4-41 14-44 21-12 32 88 201 111 186 6-4 37-24 70-46zm1099-493 185-433-348-490h-138-138l33 68c40 81 56 176 44 252-8 47-203 894-217 941-4 13 9 17 75 23 80 6 230 44 280 71 14 7 29 10 32 7 4-4 90-202 192-439zm-1323 187c118-22 229-99 275-190 37-74 45-138 45-375v-225h-160-160v115c0 179-47 289-158 369-91 67-141 76-417 76h-244l10 32c5 18 9 72 9 120v88h374c209 0 397-4 426-10zm-319-402c50-15 111-67 135-115 16-32 20-70 24-244l5-205 36-72 35-72h-759-759l7 63c17 164 95 400 165 502 47 68 129 124 215 145 52 13 853 12 896-2zm2114-323c256-67 415-329 350-580-48-184-202-326-390-358-197-34-412 76-500 257-19 39-38 86-41 104l-6 32h80 81l24-53c31-69 86-123 156-156 77-36 192-36 266-1 63 31 124 91 156 155 33 68 34 197 2 267-27 60-95 127-156 157-95 46-229 36-311-22-18-12-26-15-21-6 13 22 126 182 143 202 19 22 86 23 167 2zm-1315-243c39-21 87-99 77-125-6-15-27-17-178-17-193 0-231 7-289 58-35 29-70 78-70 97 0 3 96 5 213 5 187 0 217-2 247-18zm1288-89c51-38 67-70 67-133s-16-95-69-134c-43-33-132-29-179 7-20 15-37 32-37 38 0 5 36 9 80 9 73 0 83 3 105 25 33 32 33 78 0 110-22 22-32 25-105 25-44 0-80 4-80 8 0 12 29 37 65 57 39 21 117 15 153-12zm-397-46c-10-9-11-8-5 6 3 10 9 15 12 12s0-11-7-18zm-2460-217c45-106 169-184 289-184s244 78 289 184l22 50h81 81l-7-32c-13-65-66-159-123-219-186-195-500-195-686 0-57 60-110 154-123 219l-6 32h80 81l22-50zm419 41c0-16-51-50-91-63-30-8-48-8-78 0-40 13-91 47-91 63 0 5 57 9 130 9s130-4 130-9z" />
                            </g>
                        </svg>

                        <x-select class="text-sm w-full" id="selectDeliveryExecutiveInput" onchange="updateSelectDeliveryExecutive(this.value)">
                            <option value="">@lang('modules.order.selectDeliveryExecutive')</option>
                            @foreach ($deliveryExecutives as $item)
                                @php
                                    $isBusy = (bool)($deliveryExecutiveBusyMap[$item->id] ?? false);
                                    $isSelected = (int)($selectedDeliveryExecutive ?? 0) === (int)$item->id;
                                @endphp
                                @if (!$isBusy || $isSelected)
                                    <option value="{{ $item->id }}" {{ $isSelected ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endif
                            @endforeach
                        </x-select>
                    </div>
                </div>
            @endif

            @if(module_enabled('Hotel')  && in_array('Hotel', restaurant_modules()))
                @include('hotel::pos.room-service')
            @endif

            @if ($orderType == 'pickup')
                @php
                    $timeFormat = restaurant()->time_format ?? 'h:i A';
                    $currentDate = $pickupDate ?? now()->format(restaurant()->date_format);
                    $currentTime = $pickupTime ?? now()->format('H:i');
                @endphp

                <div class="flex items-end gap-2 pb-2" id="pickup-datetime-container">
                    <div class="relative flex-1">
                        <x-datepicker
                            id="pickupDateInput"
                            value="{{ $currentDate }}"
                            minDate="{{ $minDate }}"
                            maxDate="{{ $maxDate }}"
                            onchange="window.updatePickupDate && window.updatePickupDate(this.value)"
                            class="pl-4 pr-5 py-2 text-lg text-gray-700 dark:text-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-500 w-full" />
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                            </svg>
                        </div>
                    </div>

                    <div class="relative flex-1" style="overflow: visible;">
                        <x-time-picker
                            id="pickupTimeInput"
                            value="{{ $currentTime }}"
                            onchange="window.updatePickupTime && window.updatePickupTime(this.value)"
                        />
                    </div>
                </div>


            @endif
        </div>

        @php
            // Performance: avoid repeated relation loads + collection scans in each cart row.
            // Build O(1) lookups once per render for existing-order KOT/order items.
            $kotItemLookup = [];
            $orderItemLookup = [];

            if (isset($orderID) && $orderID && isset($orderDetail) && $orderDetail) {
                if (!$orderDetail->relationLoaded('kot')) {
                    $orderDetail->load('kot.items');
                }
                if (!$orderDetail->relationLoaded('items')) {
                    $orderDetail->load('items');
                }

                foreach (($orderDetail->kot ?? collect()) as $kotRow) {
                    foreach (($kotRow->items ?? collect()) as $kotItemRow) {
                        $kotItemLookup[((int)$kotRow->id) . '_' . ((int)$kotItemRow->id)] = $kotItemRow;
                    }
                }

                foreach (($orderDetail->items ?? collect()) as $orderItemRow) {
                    $orderItemLookup[(int)$orderItemRow->id] = $orderItemRow;
                }
            }
        @endphp
        <div class="flex flex-col rounded gap-1 border-t border-gray-50 dark:border-gray-700 p-2">
            @forelse ($orderItemList as $key => $item)
            @php
                // Initialize variables - START WITH FALSE
                $isFreeItem = false;
                $hasStampDiscount = false;
                $stampDiscountAmount = 0;
                $originalAmount = 0;
                $itemFoundInDatabase = false; // CRITICAL: Track if we found item in DB

                // For existing orders, check both kot_items and order_items tables for stamp data
                // PRIORITY: Database values ALWAYS override key patterns
                if (isset($orderID) && $orderID && isset($orderDetail) && $orderDetail) {
                    // Check if this is a KOT item (key format: "kot_{kot_id}_{item_id}")
                    if (strpos($key, 'kot_') !== false) {
                        $keyParts = explode('_', trim($key, '"'));
                        if (count($keyParts) >= 3 && $keyParts[0] === 'kot') {
                            $kotItemId = (int)($keyParts[2] ?? 0);
                            $kotId = (int)($keyParts[1] ?? 0);

                            if ($kotItemId > 0 && $kotId > 0) {
                                $lookupKey = $kotId . '_' . $kotItemId;
                                $kotItem = $kotItemLookup[$lookupKey] ?? null;

                                if ($kotItem) {
                                    $itemFoundInDatabase = true; // Found in database

                                    // STRICT CHECK: Only true if database value is exactly 1, true, or '1'
                                    // Explicitly check for 0, false, null, '0', '' and set to false
                                    $dbFreeValue = $kotItem->getAttribute('is_free_item_from_stamp') ?? $kotItem->is_free_item_from_stamp ?? null;

                                    // Explicitly check: if value is 0, false, null, '0', or empty string, it's NOT free
                                    if ($dbFreeValue === 0 || $dbFreeValue === false || $dbFreeValue === null || $dbFreeValue === '0' || $dbFreeValue === '') {
                                        $isFreeItem = false;
                                    } else {
                                        // Only mark as free if value is exactly 1, true, or '1'
                                        $isFreeItem = ($dbFreeValue === 1 || $dbFreeValue === true || $dbFreeValue === '1');
                                    }

                                    // Check for discount from stamp in kot_items
                                    $discountAmount = (float)($kotItem->getAttribute('discount_amount') ?? $kotItem->discount_amount ?? 0);
                                    $isDiscounted = (bool)($kotItem->getAttribute('is_discounted') ?? $kotItem->is_discounted ?? false);
                                    $hasStampDiscount = $discountAmount > 0 || $isDiscounted || !is_null($kotItem->stamp_rule_id);

                                    // SAFEGUARD: treat item as free only when its amount is actually zero (or nearly zero)
                                    $currentAmount = (float)($kotItem->amount ?? 0);
                                    if ($isFreeItem && $currentAmount > 0.0001) {
                                        $isFreeItem = false;
                                    }

                                    if ($hasStampDiscount && $discountAmount > 0) {
                                        $stampDiscountAmount = $discountAmount;
                                        $originalAmount = (float)($kotItem->amount ?? 0) + $discountAmount;
                                    } elseif ($isFreeItem) {
                                        // For free items, calculate original amount from price
                                        $basePrice = (float)($kotItem->getAttribute('price') ?? $kotItem->price ?? 0);
                                        $modifierPrice = isset($orderItemModifiersPrice[$key]) ? (float)$orderItemModifiersPrice[$key] : 0;
                                        $qty = isset($orderItemQty[$key]) ? (int)$orderItemQty[$key] : 1;
                                        $originalAmount = ($basePrice + $modifierPrice) * $qty;
                                    }
                                }
                            }
                        }
                    }

                    // Check if this is an order_item (key format: "order_item_{id}")
                    // Only check if we haven't found it as a KOT item and it's not already marked as free
                    if (!$itemFoundInDatabase && strpos($key, 'order_item_') !== false) {
                        $keyParts = explode('_', trim($key, '"'));
                        if (count($keyParts) >= 3 && $keyParts[0] === 'order' && $keyParts[1] === 'item') {
                            $orderItemId = (int)($keyParts[2] ?? 0);

                            if ($orderItemId > 0) {
                                $orderItem = $orderItemLookup[$orderItemId] ?? null;
                                if ($orderItem) {
                                    $itemFoundInDatabase = true; // Found in database

                                    // STRICT CHECK: Only true if database value is exactly 1, true, or '1'
                                    // Explicitly check for 0, false, null, '0', '' and set to false
                                    $dbFreeValue = $orderItem->is_free_item_from_stamp ?? null;

                                    // Explicitly check: if value is 0, false, null, '0', or empty string, it's NOT free
                                    if ($dbFreeValue === 0 || $dbFreeValue === false || $dbFreeValue === null || $dbFreeValue === '0' || $dbFreeValue === '') {
                                        $isFreeItem = false;
                                    } else {
                                        // Only mark as free if value is exactly 1, true, or '1'
                                        $isFreeItem = ($dbFreeValue === 1 || $dbFreeValue === true || $dbFreeValue === '1');
                                    }

                                    // Check for discount from stamp
                                    // Note: order_items table only has 'stamp_rule_id' and 'is_free_item_from_stamp' columns
                                    if (!$hasStampDiscount) {
                                        $hasStampDiscount = !is_null($orderItem->stamp_rule_id) && !$isFreeItem;
                                    }

                                    // SAFEGUARD: treat item as free only when its amount is actually zero (or nearly zero)
                                    $currentAmount = (float)($orderItem->amount ?? 0);
                                    if ($isFreeItem && $currentAmount > 0.0001) {
                                        $isFreeItem = false;
                                    }

                                    // For items with stamp discounts, we can't calculate exact discount per item from order_items
                                    // The discount is already deducted from amount field
                                    if ($hasStampDiscount && $stampDiscountAmount == 0) {
                                        // Try to estimate original amount from price field (if available)
                                        $basePrice = $orderItem->price ?? 0;
                                        $modifierPrice = isset($orderItemModifiersPrice[$key]) ? $orderItemModifiersPrice[$key] : 0;
                                        $qty = $orderItemQty[$key] ?? $orderItem->quantity ?? 1;
                                        $estimatedOriginalAmount = ($basePrice + $modifierPrice) * $qty;
                                        // If current amount is less than estimated, there's a discount
                                        $currentAmount = (float)($orderItem->amount ?? 0);
                                        if ($estimatedOriginalAmount > $currentAmount) {
                                            $stampDiscountAmount = $estimatedOriginalAmount - $currentAmount;
                                            $originalAmount = $estimatedOriginalAmount;
                                        }
                                    } elseif ($isFreeItem && $originalAmount == 0) {
                                        // For free items, calculate original amount from price
                                        $basePrice = $orderItem->price ?? 0;
                                        $modifierPrice = isset($orderItemModifiersPrice[$key]) ? $orderItemModifiersPrice[$key] : 0;
                                        $originalAmount = ($basePrice + $modifierPrice) * ($orderItemQty[$key] ?? 1);
                                    }
                                }
                            }
                        }
                    }
                }

                // FALLBACK: Only use key pattern or notes if item was NOT found in database
                // This is ONLY for draft orders or items not yet saved to database
                // CRITICAL: Only check fallback if we didn't find the item in database
                // IMPORTANT: If item was found in database, NEVER use fallback - database value is final
                if (!$itemFoundInDatabase) {
                    // Only check key pattern for draft orders (when orderID might not be set or order is draft)
                    $isDraftOrder = !isset($orderID) || !$orderID || (isset($orderDetail) && $orderDetail && $orderDetail->status === 'draft');
                    if ($isDraftOrder) {
                        // Only check key pattern if not already set from database
                        // Reset to false first to ensure clean state
                        $isFreeItem = false;
                        $isFreeItem = strpos($key, 'free_stamp_') === 0
                            || (isset($itemNotes[$key]) && str_contains($itemNotes[$key] ?? '', __('loyalty::app.freeItemFromStamp')));

                        // SAFEGUARD: for draft items, consider them free only if their current amount is zero (or nearly zero)
                        $draftAmount = (float)($orderItemAmount[$key] ?? 0);
                        if ($isFreeItem && $draftAmount > 0.0001) {
                            $isFreeItem = false;
                        }
                    } else {
                        // For non-draft orders, if item not found in DB, it's definitely NOT free
                        $isFreeItem = false;
                    }
                }
                // FINAL SAFEGUARD: If item was found in database, $isFreeItem is already set correctly above - do NOT override
                // If item was NOT found and it's not a draft order, ensure it's false
                if ($itemFoundInDatabase && !$isFreeItem) {
                    // Explicitly ensure it stays false - database said it's not free
                    $isFreeItem = false;
                }
            @endphp
            <div class="border border-gray-100 dark:border-gray-700 rounded-md p-2 flex flex-col gap-2 {{ $isFreeItem ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20' : ($hasStampDiscount ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20' : '') }}">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-gray-900 dark:text-white text-xs">
                                    {{ $item->item_name }}
                                </span>
                                @if ($isFreeItem)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        @lang('app.freeItem')
                                    </span>
                                @elseif($hasStampDiscount)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        @lang('app.stampDiscount')
                                        @if($stampDiscountAmount > 0)
                                            <span class="ml-1 font-semibold">
                                                (-{{ currency_format($stampDiscountAmount, restaurant()->currency_id) }})
                                            </span>
                                        @endif
                                    </span>
                                @endif
                            </div>

                            @if (isset($orderItemVariation[$key]) && $orderItemVariation[$key])
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                &bull; {{ $orderItemVariation[$key]->variation }}
                            </span>
                            @endif

                            @if (!empty($itemModifiersSelected[$key]))
                                <div class="inline-flex flex-wrap gap-2 text-xs text-gray-600 dark:text-white">
                                    @foreach ($itemModifiersSelected[$key] as $modifierOptionId)
                                        <div
                                            class="inline-flex items-center justify-between text-xs mb-1 py-0.5 px-1 border-l-2 border-blue-500 bg-gray-200 dark:bg-gray-900 rounded-md">
                                            <span
                                                class="text-gray-900 dark:text-white">{{ isset($modifierOptions[$modifierOptionId]) ? $modifierOptions[$modifierOptionId]->name : '' }}</span>
                                            <span
                                                class="text-gray-600 dark:text-gray-300">{{ isset($modifierOptions[$modifierOptionId]) ? currency_format($modifierOptions[$modifierOptionId]->price, $restaurant->currency_id) : '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @php
                            // Livewire method leftover guard: compute display price directly for Blade/AJAX POS.
                            // Base price = variation price (if any) else item price; then add modifiers' total price.
                            $basePrice = 0;
                            if (isset($orderItemVariation[$key]) && $orderItemVariation[$key]) {
                                $basePrice = (float) ($orderItemVariation[$key]->price ?? 0);
                            } else {
                                $basePrice = (float) ($item->price ?? 0);
                            }
                            $modifierPrice = (float) ($orderItemModifiersPrice[$key] ?? 0);
                            $displayPrice = $basePrice + $modifierPrice;
                            // Prefer component amount (reflects live qty changes); fallback to DB if not available
                            $totalAmount = $orderItemAmount[$key] ?? (isset($orderItem) && $orderItem ? (float)($orderItem->amount ?? 0) : 0);
                        @endphp
                        <div class="flex items-center gap-2">
                            @if ($isFreeItem)
                                <div class="flex flex-col items-end">
                                    <div class="text-green-600 dark:text-green-400 text-xs font-bold">
                                        {{ currency_format(0, restaurant()->currency_id) }}
                                    </div>
                                    @if($originalAmount > 0)
                                        <div class="text-[10px] text-gray-400 line-through">
                                            {{ currency_format($originalAmount, restaurant()->currency_id) }}
                                        </div>
                                    @endif
                                </div>
                            @elseif($hasStampDiscount)
                                <div class="flex flex-col items-end">
                                    <div class="text-blue-600 dark:text-blue-400 text-xs font-bold">
                                        {{ currency_format($totalAmount, restaurant()->currency_id) }}
                                    </div>
                                    @if($originalAmount > 0 && $originalAmount > $totalAmount)
                                        <div class="text-[10px] text-gray-400 line-through">
                                            {{ currency_format($originalAmount, restaurant()->currency_id) }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-gray-500 dark:text-gray-400 text-xs">
                                    {{ currency_format($displayPrice, restaurant()->currency_id) }}
                                </div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-bold">
                                    {{ currency_format($totalAmount, restaurant()->currency_id) }}
                                </div>
                            @endif
                        </div>
                    </div>


                </div>

                <div class="flex items-center gap-2 justify-between">


                    @if (!$isFreeItem)
                    <div class="relative inline-flex items-center max-w-[7rem]"
                        wire:key='orderItemQty-{{ $key }}-counter'>
                        <button type="button" onclick="decreaseQty('{{ $key }}')"
                        wire:loading.attr="disabled" wire:loading.class="opacity-50"
                        class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-s-md p-3 h-8 relative">
                            <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                <path stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                            </svg>
                        </button>

                        <input type="text" id="qty-{{ $key }}" value="{{ $orderItemQty[$key] ?? 1 }}"
                            onchange="updateQtyInput('{{ $key }}', this.value)"
                            class="min-w-10 bg-white border-x-0 border-gray-300 h-8 text-center text-gray-900 text-sm block w-full py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            min="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />

                        <button type="button" onclick="increaseQty('{{ $key }}')"
                            class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-e-md p-3 h-8 relative">
                            <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                <path stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                            </svg>
                        </button>
                    </div>
                    @else
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @lang('app.qty') {{ $orderItemQty[$key] ?? 1 }}
                    </div>
                    @endif

                    {{-- Item Note Button/Display --}}
                    <div class="flex-shrink-0">
                        @if(isset($itemNotes[$key]) && !empty($itemNotes[$key]))
                            {{-- Show note preview when note exists --}}
                            <button type="button"
                                onclick="showItemNote('{{ $key }}')"
                                class="group relative inline-flex items-center gap-1.5 px-2 py-1.5 text-xs text-skin-base hover:text-skin-base/80 bg-skin-base/10 dark:bg-skin-base/20 hover:bg-skin-base/20 dark:hover:bg-skin-base/30 rounded-md transition-all duration-200"
                                title="{{ $itemNotes[$key] }}">
                                {{-- Note Icon --}}
                                <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                {{-- Truncated Note Text --}}
                                <span class="truncate max-w-[60px] md:max-w-[100px] lg:max-w-[80px] font-medium">
                                    {{ $itemNotes[$key] }}
                                </span>
                                {{-- Edit Indicator (appears on hover) --}}
                                <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                            </button>
                        @else
                            {{-- Show "Add Note" button when no note exists --}}
                            <button type="button"
                                onclick="showItemNote('{{ $key }}')"
                                class="inline-flex items-center gap-1 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-skin-base dark:hover:text-blue-400 hover:bg-skin-base/10 dark:hover:bg-blue-900/20 rounded-md transition-all duration-200"
                                title="@lang('modules.order.addNote')">
                                {{-- Plus Icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span class="font-medium">@lang('modules.order.addNote')</span>
                            </button>
                        @endif
                    </div>

                    <div>
                        <button
                            class="rounded text-gray-800 dark:text-gray-400 border dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-900/20 p-2 relative"
                            onclick="deleteCartItemHandler('{{ $key }}')">
                            <svg class="w-4 h-4 text-gray-700 dark:text-gray-200" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
            @empty
                <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-12 h-12 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0"/></svg>
                        <div class="text-gray-500 dark:text-gray-400 text-base">
                            @lang('messages.noItemAdded')
                        </div>

                    </div>
                </div>
            @endforelse

        </div>
    </div>

    <div class="border-t border-gray-100 px-2 pt-3 pb-4 space-y-3 shrink-0">
        <div class="h-auto p-4 select-none text-center bg-gray-50 rounded space-y-2 dark:bg-gray-700">

            @if (user_can('Add Discount on POS'))
                <div class="text-left" id="discount-button-container" style="display: {{ count($orderItemList) > 0 ? 'block' : 'none' }};">
                    <x-secondary-button onclick="showAddDiscountModal()" class="text-xs py-1.5 px-3">
                        <svg class="h-4 w-4 text-current me-1" width="16" height="16" viewBox="0 0 16 16"
                            xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                            <path d="m7.25 14.25-5.5-5.5 7-7h5.5v5.5z" />
                            <circle cx="11" cy="5" r=".5" fill="#000" />
                        </svg>
                        @lang('modules.order.addDiscount')
                    </x-secondary-button>

                </div>
            @endif

            <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                <div>
                    @lang('modules.order.totalItem')
                </div>
                <div id="total-items-display">
                    {{ count($orderItemList) }}
                </div>
            </div>
            <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                <div class="flex items-center gap-2">
                    <span>@lang('modules.order.subTotal')</span>
                    @php
                        // Get stamp discount amount from component property or order detail
                        $displayStampDiscountAmount = (float)($stampDiscountAmount ?? 0);
                        $hasFreeStampItems = false;
                        $isOrderPlaced = false;

                        // Check if order is placed (not draft)
                        if ($orderID && $orderDetail) {
                            $isOrderPlaced = $orderDetail->status !== 'draft';
                            // For existing orders, use orderDetail
                            $displayStampDiscountAmount = (float)($orderDetail->stamp_discount_amount ?? $displayStampDiscountAmount);
                            $hasFreeStampItems = $orderDetail->items()->where('is_free_item_from_stamp', true)->exists();
                        } elseif ($orderID) {
                            // Load order if orderID exists but orderDetail doesn't
                            try {
                                $order = \App\Models\Order::find($orderID);
                                if ($order) {
                                    $displayStampDiscountAmount = (float)($order->stamp_discount_amount ?? $displayStampDiscountAmount);
                                    $hasFreeStampItems = $order->items()->where('is_free_item_from_stamp', true)->exists();
                                }
                            } catch (\Exception $e) {
                                // Silently fail
                            }
                        } else {
                            // For new orders, detect free stamp items by key, payload flag, or note token.
                            foreach ($orderItemList as $key => $item) {
                                $note = $itemNotes[$key] ?? '';
                                $rawFreeFlag = is_array($item)
                                    ? ($item['is_free_item_from_stamp'] ?? false)
                                    : ($item->is_free_item_from_stamp ?? false);
                                $isFlaggedFree = in_array($rawFreeFlag, [true, 1, '1', 'true'], true);

                                if (
                                    str_starts_with((string)$key, 'free_stamp_')
                                    || $isFlaggedFree
                                    || str_contains((string)$note, __('loyalty::app.freeItemFromStamp'))
                                    || str_starts_with(strtolower(trim((string)$note)), 'free')
                                ) {
                                    $hasFreeStampItems = true;
                                    break;
                                }
                            }
                        }
                    @endphp
                    <span id="stamp-discount-badge"
                        class="px-1.5 py-0.5 text-xs rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 {{ ($displayStampDiscountAmount > 0 || $hasFreeStampItems) ? '' : 'hidden' }}">
                        <span id="stamp-discount-badge-text">
                            @lang('app.stampDiscount')
                            @if($displayStampDiscountAmount > 0)
                                (-{{ currency_format($displayStampDiscountAmount, $restaurant->currency_id) }})
                            @elseif($hasFreeStampItems)
                                (@lang('app.freeItem'))
                            @endif
                        </span>
                    </span>
                </div>
                <div id="subtotal-display">
                    {{ currency_format($subTotal, $restaurant->currency_id) }}
                </div>
            </div>

            @if(function_exists('module_enabled') && module_enabled('Loyalty'))
                @php
                    // On the dedicated New KOT page, when there are no new KOT items yet,
                    // the loyalty section should start from 0 (do not show main order points).
                    $isNewKotPage = isset($orderDetail) && $orderDetail && $orderDetail->status === 'kot' && request()->routeIs('pos.kot');
                    $hasNewKotItems = isset($orderItemList) && count($orderItemList) > 0;
                    $loyaltyPointsForDisplay = ($isNewKotPage && !$hasNewKotItems) ? 0 : ($loyaltyPointsRedeemed ?? 0);
                    $loyaltyDiscountForDisplay = ($isNewKotPage && !$hasNewKotItems) ? 0.0 : ($loyaltyDiscountAmount ?? 0.0);
                @endphp
                <div id="loyalty-discount-row-blade">
                    @include('loyalty::components.loyalty-discount-display', [
                        'loyaltyPointsRedeemed' => $loyaltyPointsForDisplay,
                        'loyaltyDiscountAmount' => $loyaltyDiscountForDisplay,
                        'currencyId' => $restaurant->currency_id,
                        'showEditIcon' => true,
                        'customer' => $customer ?? null
                    ])
                </div>
                {{-- JS-updatable row (shown when loyalty applied in session; updated by updateTotalsDisplay) --}}
                <div id="loyalty-discount-row-js" class="flex justify-between text-xs text-blue-600 dark:text-blue-400 items-center" style="display: none;">
                    <div class="inline-flex items-center gap-x-1">
                        <span id="loyalty-js-label">@lang('loyalty::app.loyaltyDiscount') (<span id="loyalty-js-points">0</span> @lang('loyalty::app.points'))</span>
                        @if(user_can('Update Order'))
                            <span class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 cursor-pointer ml-1"
                                onclick="if (typeof window.openLoyaltyRedemptionModal === 'function') { window.openLoyaltyRedemptionModal(); }"
                                title="{{ __('Edit loyalty points') }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </span>
                        @endif
                    </div>
                    <div id="loyalty-js-amount" class="text-blue-600 dark:text-blue-400 font-medium">-0</div>
                </div>
            @endif

            <div wire:key="discountAmount" id="discount-row"
            class="flex justify-between text-green-500 text-xs dark:text-green-400"
            style="display: {{ $discountAmount > 0 && $loyaltyPointsRedeemed == 0 ? 'flex' : 'none' }};">
                <div class="inline-flex items-center gap-x-1">@lang('modules.order.discount') <span id="discount-type-display">@if ($discountType == 'percent')
                        ({{ $discountValue }}%)
                    @endif</span>
                    @if(user_can('Add Discount on POS') && user_can('Update Order'))
                        <span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                            onclick="removeCurrentDiscount()" title="@lang('app.remove')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </div>
                <div id="discount-display">
                    -{{ currency_format($discountAmount, $restaurant->currency_id) }}
                </div>
            </div>

            @if ($orderType === 'delivery')
                <div class="flex justify-between items-center text-gray-500 text-xs dark:text-neutral-400" id="delivery-fee-row">
                    <div>
                        @lang('modules.delivery.deliveryFee')
                        <span class="text-xs text-gray-400" id="delivery-fee-note">
                            @if($deliveryFee == 0)
                                (@lang('modules.delivery.freeDelivery'))
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <x-input type="number" step='1' min='0' class="w-16 text-sm"
                            id="deliveryFeeInput" value="{{ $deliveryFee }}" onchange="updateDeliveryFee(this.value)" />
                        </div>
                    </div>
                </div>
            @endif

            <div id="extra-charges-container" style="{{ (count($orderItemList) > 0 && $extraCharges && count($extraCharges) > 0) ? '' : 'display: none;' }}">
                @if (count($orderItemList) > 0 && $extraCharges)
                    @foreach ($extraCharges as $charge)
                        <div
                            class="flex justify-between text-gray-500 text-xs dark:text-neutral-400"
                            data-charge-id="{{ $charge->id }}"
                            data-charge-name="{{ $charge->charge_name }}"
                            data-charge-type="{{ $charge->charge_type }}"
                            data-charge-value="{{ $charge->charge_value }}">
                            <div class="inline-flex items-center gap-x-1">{{ $charge->charge_name }}
                                @if ($charge->charge_type == 'percent')
                                    ({{ $charge->charge_value }}%)
                                @endif
                                @if (user_can('Update Order'))
                                    <span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                                        onclick="removeExtraCharge({{ $charge->id }}, '{{ $orderType }}')">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1" clip-rule="evenodd"/></svg>
                                    </span>
                                @endif
                            </div>
                            <div class="charge-amount-display">
                                {{ currency_format($charge->getAmount($discountedTotal), $restaurant->currency_id) }}
                            </div>
                        </div>
                    @endforeach
                @elseif($orderID && count($orderItemList) > 0 && $extraCharges)
                    @foreach ($extraCharges as $newKotCharge)
                        <div wire:key="extraCharge-newKot-{{ $loop->index }}"
                            class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                            <div class="inline-flex items-center gap-x-1">{{ $newKotCharge->charge_name }}
                                @if ($newKotCharge->charge_type == 'percent')
                                    ({{ $newKotCharge->charge_value }}%)
                                @endif
                            </div>
                            <div>
                                {{ currency_format($newKotCharge->getAmount($discountedTotal), $restaurant->currency_id) }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Taxes Section - Always render container -->
            @if ($taxMode == 'order')
                <div id="order-taxes-container">
                @foreach ($taxes as $item)
                    <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400" data-tax-name="{{ $item->tax_name }}" data-tax-percent="{{ $item->tax_percent }}">
                        <div>
                            {{ $item->tax_name }} ({{ $item->tax_percent }}%)
                        </div>
                        <div>
                            {{ currency_format(($item->tax_percent / 100) * $taxBase, $restaurant->currency_id) }}
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div id="item-taxes-container">
                @php
                    // Show item-wise tax breakdown above total tax
                    $taxTotals = [];
                    $isInclusive = $restaurant->tax_inclusive ?? false;

                    foreach ($orderItemTaxDetails as $item) {
                        $qty = $item['qty'] ?? 1;
                        if (!empty($item['tax_breakup'])) {
                            foreach ($item['tax_breakup'] as $taxName => $taxInfo) {
                                if (!isset($taxTotals[$taxName])) {
                                    $taxTotals[$taxName] = [
                                        'percent' => $taxInfo['percent'],
                                        'amount' => 0,
                                    ];
                                }
                                $taxTotals[$taxName]['amount'] += $taxInfo['amount'] * $qty;
                            }
                        }
                    }
                @endphp
                @if (!empty($taxTotals))
                    @foreach ($taxTotals as $taxName => $taxInfo)
                        <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                            <div>
                                {{ $taxName }} ({{ $taxInfo['percent'] }}%)
                            </div>
                            <div>
                                {{ currency_format($taxInfo['amount'], $restaurant->currency_id) }}
                            </div>
                        </div>
                    @endforeach
                    <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                        <div>
                            @lang('modules.order.totalTax')
                            @if ($isInclusive)
                                <span class="text-xs text-gray-400">(@lang('modules.settings.taxInclusive'))</span>
                            @else
                                <span class="text-xs text-gray-400">(@lang('modules.settings.taxExclusive'))</span>
                            @endif
                        </div>
                        <div id="total-tax-display">
                            {{ currency_format($totalTaxAmount, $restaurant->currency_id) }}
                        </div>
                    </div>
                @endif
                </div>
            @endif

            <div class="flex justify-between text-sm font-bold text-gray-900 pt-1.5 border-t border-gray-200 mt-1 dark:text-neutral-300">
                <div>
                    @lang('modules.order.total')
                </div>
                <div id="total-display" class="text-skin-base text-base">
                    {{ currency_format($total, $restaurant->currency_id) }}
                </div>
            </div>
        </div>

        <div class="h-auto pt-3 select-none text-center w-full">
            @php
                $isNewKotPage = $orderDetail && $orderDetail->status === 'kot' && !request()->boolean('show-order-detail');
            @endphp

            @if ((!$orderID || ($orderID && $orderDetail && $orderDetail->status !== 'draft')) && !$isNewKotPage)
                <div class="flex gap-3 mb-3">
                    <button class="pos-order-action-btn rounded bg-yellow-600 hover:bg-yellow-700 text-white w-full p-2 relative text-sm" onclick="saveOrder('draft')"
                        id="saveDraftBtn">
                        <span id="saveDraftBtnText">@lang('modules.order.saveAsDraft')</span>
                        <span id="saveDraftBtnLoading" class="hidden">
                            <svg class="animate-spin inline -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.saving')
                        </span>
                    </button>
                </div>
            @endif
            @if (in_array('KOT', restaurant_modules()))
                <div class="flex gap-3">
                    <button class="pos-order-action-btn rounded bg-gray-700 text-white w-full p-2 relative text-xs"
                        onclick="saveKotActionOnce('kot')" id="saveKotBtn">
                        <span id="saveKotBtnText">@lang('modules.order.kot')</span>
                        <span id="saveKotBtnLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-1 h-4 w-4 inline-flex text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.kot')
                        </span>
                    </button>
                    <button class="pos-order-action-btn rounded bg-gray-700 text-white w-full p-2 relative text-xs"
                        onclick="saveKotActionOnce('kot', 'print')" id="saveKotPrintBtn">
                        <span id="saveKotPrintBtnText">@lang('modules.order.kotAndPrint')</span>
                        <span id="saveKotPrintBtnLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.kotAndPrint')
                        </span>
                    </button>
                    <button class="pos-order-action-btn rounded bg-gray-700 text-white w-full p-2 relative text-xs"
                        onclick="saveKotActionOnce('kot','bill','payment', 'print')" id="saveKotBillPaymentBtn">
                        <span id="saveKotBillPaymentBtnText">@lang('modules.order.kotBillAndPayment')</span>
                        <span id="saveKotBillPaymentBtnLoading" class="hidden">
                            <svg class="animate-spin inline-flex -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.kotBillAndPayment')
                        </span>
                    </button>
                </div>
            @endif
            @if (!$orderID || ($orderID && $orderDetail && $orderDetail->status == 'draft'))
                <div class="flex gap-3 mt-3">
                    <button class="pos-order-action-btn rounded bg-skin-base text-white w-full p-2 relative text-xs"
                        onclick="saveOrder('bill')" id="saveBillBtn">
                        <span id="saveBillBtnText">@lang('modules.order.bill')</span>
                        <span id="saveBillBtnLoading" class="hidden">
                            <svg class="animate-spin inline-flex items-center -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.bill')
                        </span>
                    </button>
                    <button class="pos-order-action-btn rounded bg-green-500 text-white w-full p-2 relative text-xs"
                        onclick="saveOrder('bill', 'payment')" id="saveBillPaymentBtn">
                        <span id="saveBillPaymentBtnText">@lang('modules.order.billAndPayment')</span>
                        <span id="saveBillPaymentBtnLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-flex items-center" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.billAndPayment')
                        </span>
                    </button>
                    <button class="pos-order-action-btn rounded bg-blue-500 text-white w-full p-2 relative text-xs"
                        onclick="saveOrder('bill', 'print')" id="saveBillPrintBtn">
                        <span id="saveBillPrintBtnText">@lang('modules.order.createBillAndPrintReceipt')</span>
                        <span id="saveBillPrintBtnLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            @lang('modules.order.createBillAndPrintReceipt')
                        </span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Reservation Confirmation Modal -->
    <div id="reservationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="text-lg font-semibold">@lang('modules.order.reservationConfirmation')</h3>
                    </div>
                    <button type="button" onclick="closeReservationModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
            <div class="space-y-4">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        @lang('modules.order.tableHasReservation')
                    </h3>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>@lang('modules.order.reservationFor'): <strong>{{ $reservationCustomer?->name ?? 'N/A' }}</strong></p>
                        <p>@lang('modules.order.reservationTime'): <strong>{{ $reservation?->reservation_date_time?->translatedFormat(dateFormat() . ' ' . timeFormat()) ?? 'N/A' }}</strong></p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
                        @lang('modules.order.isThisSameCustomer')
                    </p>
                </div>
            </div>
            </div>
            <div class="px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                <button type="button" onclick="closeReservationModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.cancel')</button>
                <div class="flex gap-2">
                    <button type="button" onclick="confirmDifferentCustomer()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('modules.order.differentCustomer')</button>
                    <button type="button" onclick="confirmSameCustomer()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">@lang('modules.order.sameCustomer')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Change Confirmation Modal -->
    <div id="tableChangeConfirmationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <h3 class="text-lg font-semibold">@lang('modules.order.changeTable')</h3>
                    </div>
                    <button type="button" onclick="closeTableChangeConfirmationModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="px-6 py-4">
            <div class="space-y-4">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-amber-100" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        @lang('modules.order.confirmTableChange')
                    </h3>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>@lang('modules.order.currentTable'): <strong>{{ $tableNo }}</strong></p>
                        @if($pendingTable)
                            <p>@lang('modules.order.changeTo'): <strong>{{ $pendingTable->table_code }}</strong></p>
                        @endif
                        <p class="mt-2">@lang('modules.order.tableChangeMessage')</p>
                    </div>
                </div>

                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-sm text-amber-700 dark:text-amber-300 text-center">
                        @lang('modules.order.tableChangeWarning')
                    </p>
                </div>
            </div>
            </div>
            <div class="px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" onclick="cancelTableChange()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.cancel')</button>
                <button type="button" onclick="confirmTableChange()" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700">@lang('modules.order.changeTable')</button>
            </div>
        </div>
    </div>


    <!-- Merge Table Modal -->
    <div id="mergeTableModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.order.mergeTables')</h3>
                    </div>
                    <button type="button" onclick="closeMergeTableModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-4" id="mergeTableModalContent">
                <div class="text-center py-8">
                    <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">@lang('app.loading')</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" onclick="closeMergeTableModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.close')</button>
                <button type="button" id="mergeTablesButton" onclick="mergeSelectedTables()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" disabled>
                    <span id="mergeButtonText">@lang('modules.order.mergeTables')</span>
                    <span id="mergeButtonLoading" class="hidden inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @lang('modules.order.merging')
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
