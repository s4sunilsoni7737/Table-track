<div class="relative overflow-x-hidden" id="pos-container">

    {{-- Include MultiPOS registration and status handling --}}
    @if(module_enabled('MultiPOS') && in_array('MultiPOS', restaurant_modules()))
        @include('multipos::partials.pos-registration', [
            'hasPosMachine' => $hasPosMachine,
            'machineStatus' => $machineStatus,
            'posMachine' => $posMachine,
            'limitReached' => $limitReached,
            'limitMessage' => $limitMessage,
            'shouldBlockPos' => $shouldBlockPos
        ])
    @endif

    {{-- Only render POS content if not blocked by registration/pending/declined --}}
    @if(!$shouldBlockPos)
        {{-- Restaurant availability banner (outside operating hours) --}}
        @if(!empty($showRestaurantClosedBanner) && !empty($restaurantClosedMessage))
            <div class="p-2">
                <div class="w-full p-3 text-sm font-medium text-center text-red-700 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                    {{ $restaurantClosedMessage }}
                </div>
            </div>
        @endif

        {{-- Order Type Selection Modal - Using Livewire Component --}}
        @if(!$orderTypeId)
            @livewire('forms.order-type-selection')
        @endif


        <div class="flex flex-col lg:flex-row flex-1 gap-x-2 min-h-0 h-full w-full">
            <div class="w-full lg:w-8/12 flex-shrink-0">
                @include('pos.menu')
            </div>
            <div id="order-items-container" class="w-full lg:w-4/12 flex-shrink-0 flex flex-col">
                @php
                    $showOrderDetail = request()->boolean('show-order-detail');
                @endphp

                @if (!$orderDetail || ($orderDetail && $orderDetail->status == 'draft'))
                    @include('pos.kot_items')
                @elseif($orderDetail && $orderDetail->status == 'kot')
                    @php
                        // Get current KOT ID for print functionality
                        $currentKot = $orderDetail->kot()->orderBy('created_at', 'desc')->first();
                        $currentKotId = $currentKot ? $currentKot->id : null;
                    @endphp
                    @if($currentKotId)
                    <script>
                        // Set current KOT ID for print functionality
                        window.currentKotId = {{ $currentKotId }};
                    </script>
                    @endif
                    @if($showOrderDetail)
                        @include('pos.order_items')
                    @else
                        @include('pos.kot_items')
                    @endif
                @elseif($orderDetail && in_array($orderDetail->status, ['billed', 'paid', 'payment_due']))
                    @include('pos.order_detail')
                @endif
            </div>
        </div>

        {{-- Variation Modal --}}
        <div id="variationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-gray-200">@lang('modules.menu.itemVariations')</h3>
                        <button type="button" onclick="closeVariationModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="variationModalContent"></div>
                </div>
            </div>
        </div>

        @if(module_enabled('Hotel')  && in_array('Hotel', restaurant_modules()))
            @include('hotel::pos.show-stay')
        @endif

        {{-- KOT Note Modal --}}
        <div id="kotNoteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-gray-200">@lang('modules.order.addNote')</h3>
                        <button type="button" onclick="closeKotNoteModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div>
                        <label for="orderNote" class="block text-sm font-medium text-gray-700 dark:text-gray-300">@lang('modules.order.orderNote')</label>
                        <textarea id="orderNote" data-gramm="false" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" rows="2"></textarea>
                        <div id="orderNoteError" class="mt-2 text-red-600 text-sm" style="display: none;"></div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-secondary-button type="button" onclick="closeKotNoteModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.cancel')</x-secondary-button>
                        <x-button type="button" onclick="saveKotNote()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">@lang('app.save')</x-button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Note Modal --}}
        <div id="itemNoteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-gray-200">@lang('modules.order.addNote')</h3>
                        <button type="button" onclick="closeItemNoteModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div>
                        <label for="itemNoteInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">@lang('modules.order.orderNote')</label>
                        <textarea id="itemNoteInput" data-gramm="false" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" rows="2"></textarea>
                        <div id="itemNoteError" class="mt-2 text-red-600 text-sm" style="display: none;"></div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-secondary-button type="button" onclick="closeItemNoteModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.cancel')</x-secondary-button>
                        <x-button type="button" onclick="saveItemNote()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">@lang('app.save')</x-button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Modal --}}
        <div id="tableModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-gray-200">@lang('modules.table.availableTables')</h3>
                        <button type="button" onclick="closeTableModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="tableModalContent"></div>
                </div>
            </div>
        </div>

        {{-- Discount Modal --}}
        <div id="discountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-gray-100">@lang('modules.order.addDiscount')</h3>
                        <button type="button" onclick="closeDiscountModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 flex">
                        <input id="discountValue" type="number" step="0.01" class="block w-2/3 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400" placeholder="{{ __('modules.order.enterDiscountValue') }}" min="0" />
                        <select id="discountType" class="block ml-2 w-1/3 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="fixed">@lang('modules.order.fixed')</option>
                            <option value="percent">@lang('modules.order.percent')</option>
                        </select>
                    </div>
                    <div id="discountValueError" class="mt-2 text-red-600 text-sm" style="display: none;"></div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="closeDiscountModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.cancel')</button>
                        <x-button type="button" onclick="saveDiscount()">@lang('app.save')</x-button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loyalty Redemption (AJAX POS) - $posLoyaltyEnabled passed from PosController (tt parity) --}}
        @if($posLoyaltyEnabled ?? false)
        <style>
            /* Ensure loyalty modal sits above other POS UI and has top spacing */
            #loyaltyRedemptionModal {
                z-index: 9999 !important;
                padding-top: 4rem !important;
            }
            @media (min-width: 640px) {
                #loyaltyRedemptionModal {
                    padding-top: 5rem !important;
                }
            }
        </style>
        <div id="loyaltyRedemptionModal" class="fixed inset-0 flex items-start justify-center bg-black/50 backdrop-blur-sm" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/70 dark:border-gray-700 shadow-2xl max-w-md w-full mx-4 mt-8 overflow-hidden">
                <div class="px-6 pt-5 pb-4">
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold dark:text-gray-100">
                                @lang('loyalty::app.redeemLoyaltyPoints')
                            </h3>
                        </div>
                        <button type="button" onclick="closeLoyaltyRedemptionModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        {{-- Always render this block so JS can populate when customer added in-session --}}
                        <div id="loyalty-info-block" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800">
                            <p id="loyalty-customer-row" class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2" style="{{ ($customer ?? null) ? '' : 'display:none' }}">
                                <span id="loyalty-customer-name">{{ optional($customer)->name ?? '' }}</span> {{ __('loyalty::app.hasAvailablePoints') }}:
                                <span id="loyalty-available-points">{{ number_format($loyaltyPointsAvailable ?? 0) }}</span>
                                @lang('loyalty::app.points')
                            </p>
                            <p id="loyalty-no-customer-row" class="text-sm text-blue-900 dark:text-blue-200" style="{{ ($customer ?? null) ? 'display:none' : '' }}">
                                @lang('loyalty::app.noPointsAvailable')
                            </p>
                            <p class="text-xs text-blue-700 dark:text-blue-300" id="loyalty-points-value-row">
                                {{ __('loyalty::app.pointsValue') }}:
                                <span id="loyalty-points-value">0</span>
                            </p>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1" id="loyalty-max-discount-row">
                                {{ __('loyalty::app.maxDiscountToday') }}:
                                <span id="loyalty-max-discount">{{ $loyaltyDiscountAmount ?? 0 }}</span>
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    @lang('loyalty::app.pointsToRedeem')
                                </label>
                                <input
                                    id="loyaltyPointsInput"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="@lang('loyalty::app.enterPoints')" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="loyalty-min-max-row" style="display:none;">
                                    <span id="loyalty-min-wrapper">
                                        {{ __('Minimum') }}:
                                        <span id="loyalty-min-points">0</span> @lang('loyalty::app.points')
                                    </span>
                                    <span id="loyalty-max-wrapper" class="ml-1" style="display:none;">
                                        | {{ __('Maximum') }}:
                                        <span id="loyalty-max-points">0</span> @lang('loyalty::app.points')
                                    </span>
                                </p>
                                <p
                                    class="mt-1 text-xs text-blue-600 dark:text-blue-400"
                                    id="loyalty-multiple-row"
                                    data-template="{{ __('Points must be in multiples of :min', ['min' => ':min']) }}"
                                    style="display:none;">
                                </p>
                                <div id="loyaltyError" class="mt-2 text-red-600 text-sm" style="display:none;"></div>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-300">
                                    @lang('loyalty::app.loyaltyDiscount')
                                </span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400" id="loyalty-discount-preview">
                                    {{ $loyaltyDiscountAmount ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-between items-center w-full pt-3 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="skipLoyaltyRedemption()"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            @lang('app.skip')
                        </button>
                        <div class="flex gap-2">
                            <button type="button" id="loyalty-use-max-btn" onclick="applyLoyaltyRedemptionMax()" style="display: none;"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                @lang('loyalty::app.useMax') (<span id="loyalty-use-max-value">0</span>)
                            </button>
                            <button
                                type="button"
                                onclick="applyLoyaltyRedemption()"
                                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors">
                                @lang('loyalty::app.applyDiscount')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Print Options Modal (AJAX/JS-based) --}}
        <div id="printOptionsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2m8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4z"/></svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.order.printOptions')</h3>
                        </div>
                        <button type="button" onclick="closePrintOptionsModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            @lang('modules.order.selectPrintOption')
                        </p>

                        {{-- Print All Option --}}
                        <button type="button" onclick="handlePrintOption('all')" class="w-full flex items-start gap-4 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                            <div class="p-3 bg-gradient-to-br from-indigo-700 to-indigo-500 rounded-lg text-white flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M19 7h1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h11.5M7 14h6m-6 3h6m0-10h.5m-.5 3h.5M7 7h3v3H7z"/></svg>
                            </div>
                            <div class="flex-1 text-left">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    @lang('modules.order.printAll')
                                </h4>
                                @if(isset($orderDetail) && $orderDetail && $orderDetail->relationLoaded('splitOrders'))
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        @lang('modules.order.printAllDesc', ['count' => $orderDetail->splitOrders->where('status', 'paid')->count() + 1])
                                    </p>
                                @endif
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </button>

                        {{-- Summary Only Option --}}
                        <button type="button" onclick="handlePrintOption('summary')" class="w-full flex items-start gap-4 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all group">
                            <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-lg text-white flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2"/></svg>
                            </div>
                            <div class="flex-1 text-left">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">
                                    @lang('modules.order.summaryOnly')
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    @lang('modules.order.summaryOnlyDesc')
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </button>

                        {{-- Individual Only Option (mapped to split receipts) --}}
                        <button type="button" onclick="handlePrintOption('individual')" class="w-full flex items-start gap-4 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all group">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg text-white flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0m6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0M7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0"/></svg>
                            </div>
                            <div class="flex-1 text-left">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                    @lang('modules.order.individualOnly')
                                </h4>
                                @if(isset($orderDetail) && $orderDetail && $orderDetail->relationLoaded('splitOrders'))
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        @lang('modules.order.individualOnlyDesc', ['count' => $orderDetail->splitOrders->where('status', 'paid')->count()])
                                    </p>
                                @endif
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </button>

                        {{-- Single Guest Option (mapped to main order print) --}}
                        <button type="button" onclick="handlePrintOption('single')" class="w-full flex items-start gap-4 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all group">
                            <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg text-white flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0m-4 7a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7"/></svg>
                            </div>
                            <div class="flex-1 text-left">
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                    @lang('modules.order.singleGuest')
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    @lang('modules.order.singleGuestDesc')
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-amber-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="closePrintOptionsModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{ __('app.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error Modal --}}
        <div id="errorModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-red-600">@lang('app.error')</h3>
                        <button type="button" onclick="closeErrorModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="errorModalContent" class="space-y-3"></div>
                    <div class="mt-4 flex justify-end gap-2">
                        <div id="errorModalNewKotButton" style="display: none;">
                            <a href="#" id="errorModalNewKotLink" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">@lang('modules.order.newKot')</a>
                        </div>
                        <button type="button" onclick="closeErrorModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('app.close')</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modifiers Modal --}}
        <div id="modifiersModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center">
                            <svg viewBox="0 0 32 32" width="24" height="24" fill="currentColor" class="inline mr-1.5 h-6 w-6 text-skin-base" xmlns="http://www.w3.org/2000/svg"><path d="M1 14h13V1C6.82 1 1 6.82 1 14m5-7a1 1 0 1 1 2 0 1 1 0 0 1-2 0m1.4 4.25a.751.751 0 0 1-.586-1.219l3.199-4a.751.751 0 0 1 1.172.938l-3.199 4a.75.75 0 0 1-.586.281M11 11a1 1 0 1 1 0-2 1 1 0 0 1 0 2m4.5-6.725v-2.25C22.446 2.29 28 7.989 28 15c-.873 0-1.65.357-2.297.926.026-.306.047-.614.047-.926 0-5.759-4.555-10.461-10.25-10.725m3.097 23.21A13 13 0 0 1 15 28C7.989 28 2.29 22.446 2.025 15.5h2.25C4.539 21.195 9.241 25.75 15 25.75c1.288 0 2.518-.239 3.663-.656z"/><path d="M16.25 17.3v4.2c0 .97.43 1.838 1.107 2.434A9.2 9.2 0 0 1 15 24.25c-4.932 0-8.963-3.882-9.225-8.75H14.5a1 1 0 0 0 1-1V5.775c4.868.262 8.75 4.293 8.75 9.225 0 .178-.017.352-.027.528-.09-.014-.18-.028-.273-.028h-5.9c-.992 0-1.8.808-1.8 1.8m12.538 6.084L28.972 30a.972.972 0 1 1-1.944 0l.184-6.617c-1.132-.312-1.962-1.302-1.962-2.884 0-1.933 1.231-4 2.75-4s2.75 2.067 2.75 4c0 1.583-.83 2.573-1.962 2.885"/><path d="M24.25 17.3v4.2a1.75 1.75 0 0 1-1.75 1.75h-.715l.187 6.75a.972.972 0 1 1-1.944 0l.188-6.75H19.5a1.75 1.75 0 0 1-1.75-1.75v-4.2a.3.3 0 0 1 .3-.3h.9a.3.3 0 0 1 .3.3v4.2c0 .138.112.25.25.25h.75V17.3a.3.3 0 0 1 .3-.3h.9a.3.3 0 0 1 .3.3v4.45h.75a.25.25 0 0 0 .25-.25v-4.2a.3.3 0 0 1 .3-.3h.9a.3.3 0 0 1 .3.3"/></svg>
                            <h3 class="text-lg font-semibold dark:text-white">@lang('modules.modifier.itemModifiers')</h3>
                        </div>
                        <button type="button" onclick="closeModifiersModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div id="modifiersModalContent"></div>
                </div>
            </div>
        </div>

        {{-- Table Change Modal --}}
        <div id="tableChangeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg fill="currentColor" class="w-5 h-5 text-gray-700 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44.999 44.999" xml:space="preserve">
                            <g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="m42.558 23.378 2.406-10.92a1.512 1.512 0 0 0-2.954-.652l-2.145 9.733h-9.647a1.512 1.512 0 0 0 0 3.026h.573l-3.258 7.713a1.51 1.51 0 0 0 1.393 2.102c.59 0 1.15-.348 1.394-.925l2.974-7.038 4.717.001 2.971 7.037a1.512 1.512 0 1 0 2.787-1.177l-3.257-7.713h.573a1.51 1.51 0 0 0 1.473-1.187m-28.35 1.186h.573a1.512 1.512 0 0 0 0-3.026H5.134L2.99 11.806a1.511 1.511 0 1 0-2.954.652l2.406 10.92a1.51 1.51 0 0 0 1.477 1.187h.573L1.234 32.28a1.51 1.51 0 0 0 .805 1.98 1.515 1.515 0 0 0 1.982-.805l2.971-7.037 4.717-.001 2.972 7.038a1.514 1.514 0 0 0 1.982.805 1.51 1.51 0 0 0 .805-1.98z"/><path d="M24.862 31.353h-.852V18.308h8.13a1.513 1.513 0 1 0 0-3.025H12.856a1.514 1.514 0 0 0 0 3.025h8.13v13.045h-.852a1.514 1.514 0 0 0 0 3.027h4.728a1.513 1.513 0 1 0 0-3.027"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.order.changeTable')</h3>
                    </div>
                    <button onclick="closeTableChangeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4" id="setTableContainer">
                    @livewire('pos.set-table')
                </div>
                <div class="flex justify-end gap-2 p-4 border-t dark:border-gray-700">
                    <button onclick="closeTableChangeModal()" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-25 transition">
                        @lang('app.close')
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="{{ asset('vendor/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/froiden-helper/helper.js') }}"></script>
<script>
// Setup CSRF token for all AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// POS State Management
window.posState = {
    orderTypeId: {{ $orderTypeId ?? 'null' }},
    orderTypeSlug: '{{ $orderTypeSlug ?? "" }}',
    orderType: '{{ $orderType ?? "" }}',
    orderNumber: '{{ $orderNumber ?? "" }}',
    formattedOrderNumber: '{{ $formattedOrderNumber ?? "" }}',
    tableId: {{ $tableId ?? 'null' }},
    tableNo: '{{ $tableNo ?? "" }}',
    customerId: {{ $customerId ?? 'null' }},
    customer: @json($customer ?? null),
    orderItemList: @json($orderItemList ?? []),
    orderItemVariation: @json($orderItemVariation ?? []),
    orderItemQty: @json($orderItemQty ?? []),
    orderItemAmount: @json($orderItemAmount ?? []),
    itemModifiersSelected: @json($itemModifiersSelected ?? []),
    orderItemModifiersPrice: @json($orderItemModifiersPrice ?? []),
    itemNotes: @json($itemNotes ?? []),
    modifierOptions: @json($modifierOptions ?? []),
    subTotal: {{ $subTotal ?? 0 }},
    total: {{ $total ?? 0 }},
    discountType: '{{ $discountType ?? "" }}',
    discountValue: {{ $discountValue ?? 0 }},
    discountAmount: {{ $discountAmount ?? 0 }},
    discountedTotal: {{ $discountedTotal ?? 0 }},
    loyaltyPointsRedeemed: {{ optional($orderDetail)->loyalty_points_redeemed ?? 0 }},
    loyaltyDiscountAmount: {{ optional($orderDetail)->loyalty_discount_amount ?? 0 }},
    stampDiscountAmount: {{ optional($orderDetail)->stamp_discount_amount ?? 0 }},
    hasFreeStampItems: {{ (isset($orderDetail) && $orderDetail && $orderDetail->items()->where('is_free_item_from_stamp', true)->exists()) ? 'true' : 'false' }},
    availableLoyaltyPoints: 0,
    pointsToRedeem: 0,
    maxRedeemablePoints: 0,
    minRedeemPoints: 0,
    loyaltyPointsValue: 0,
    maxLoyaltyDiscount: 0,
    deliveryFee: {{ $deliveryFee ?? 0 }},
    tipAmount: {{ $tipAmount ?? 0 }},
    totalTaxAmount: {{ $totalTaxAmount ?? 0 }},
    orderItemTaxDetails: @json($orderItemTaxDetails ?? []),
    // Pickup date/time (for pickup orders) – initialize from PHP so JS validation works even before user changes inputs
    // IMPORTANT: use restaurant()->date_format to match x-datepicker output and backend parsing.
    // Priority: explicit $pickupDate → existing order's pickup_date → "today" in restaurant date format
    pickupDate: @json(
        $pickupDate
        ?? (
            isset($orderDetail, $orderDetail->pickup_date) && $orderDetail->pickup_date
                ? $orderDetail->pickup_date->format(restaurant()->date_format ?? (global_setting()->date_format ?? 'd-m-Y'))
                : null
        )
        ?? now(restaurant()->timezone)->format(restaurant()->date_format ?? (global_setting()->date_format ?? 'd-m-Y'))
    ),
    pickupTime: @json(
        $pickupTime
        ?? (
            isset($orderDetail, $orderDetail->pickup_date) && $orderDetail->pickup_date
                ? $orderDetail->pickup_date->copy()->format('H:i')
                : null
        )
        ?? now(restaurant()->timezone)->format('H:i')
    ),
    noOfPax: {{ $noOfPax ?? 1 }},
    selectWaiter: {{ $selectWaiter ?? user()->id }},
    selectedDeliveryExecutive: {{ $selectDeliveryExecutive ?? 'null' }},
    selectedDeliveryApp: {{ $selectedDeliveryApp ?? 'null' }},
    orderID: {{ $orderID ?? 'null' }},
    orderStatus: '{{ $orderStatus ?? "confirmed" }}',
    deliveryDateTime: '{{ $deliveryDateTime ?? "" }}',
    orderNote: '{{ $orderNote ?? "" }}',
    orderDetail: @json($orderDetail ?? null),
    menuItem: null,
    selectedModifierItem: null,
    showOrderDetail: {{ request()->get('show-order-detail') == 'true' ? 'true' : 'false' }},
    canCreateOrder: {{ user_can('Create Order') ? 'true' : 'false' }},
    canUpdateOrder: {{ user_can('Update Order') ? 'true' : 'false' }},
    // Flag to indicate totals were pre-calculated by PHP (e.g., from merged tables or existing orders)
    totalsPreCalculated: {{ ($subTotal > 0 || $total > 0) ? 'true' : 'false' }},
    isWaiterLocked: {{ $isWaiterLocked ? 'true' : 'false' }},
    loyaltyEnabled: {{ ($posLoyaltyEnabled ?? false) ? 'true' : 'false' }}
};

/**
 * Current order id for POS AJAX (parity with Livewire: orderID || orderDetail.id || server-rendered order).
 * Without this, waiter/table APIs skip when only orderDetail is populated.
 */
window.getCurrentPosOrderId = function() {
    const toPositiveInt = function(v) {
        if (v === null || v === undefined || v === '' || v === 'null') {
            return null;
        }
        const n = parseInt(String(v), 10);
        return (Number.isNaN(n) || n <= 0) ? null : n;
    };
    let id = toPositiveInt(window.posState?.orderID);
    if (id) {
        return id;
    }
    id = toPositiveInt(window.posState?.orderDetail?.id);
    if (id) {
        return id;
    }
    return toPositiveInt(@json(optional($orderDetail)->id ?? null));
};

// Unified free-stamp detector (tt parity + robust note fallback).
window.isFreeStampItemByMeta = function(itemKey, itemMeta, itemNote) {
    const key = (itemKey || '').toString();
    const item = itemMeta || {};
    const note = (itemNote || '').toString();
    const rawFlag = item.is_free_item_from_stamp;
    const byFlag = rawFlag === true || rawFlag === 1 || rawFlag === '1'
        || (typeof rawFlag === 'string' && rawFlag.toLowerCase() === 'true');
    const byKey = key.startsWith('free_stamp_');
    const noteToken = @json(strtolower(__('loyalty::app.freeItemFromStamp')));
    const noteLower = note.toLowerCase();
    const byToken = noteToken && noteLower.includes(noteToken);
    const byGenericFreeStamp = noteLower.includes('free') && (noteLower.includes('stamp') || noteLower.includes('(st'));
    const byGenericFreeItem = noteLower.startsWith('free') || noteLower.includes('free item');
    return byKey || byFlag || byToken || byGenericFreeStamp || byGenericFreeItem;
};

// TT parity: auto-apply stamp preview in cart when customer is selected first.
window.autoApplyStampPreviewForItem = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const item = window.posState?.orderItemList?.[itemKey];
    if (!item || !window.posState?.customerId) {
        return;
    }

    const note = window.posState?.itemNotes?.[itemKey] || '';
    if (window.isFreeStampItemByMeta(itemKey, item, note)) {
        return;
    }

    const qty = Math.max(1, parseInt(window.posState?.orderItemQty?.[itemKey] || 1, 10));
    // Use variation price when present, else base item price; add modifier price so stamp preview matches displayed line total.
    const variation = window.posState?.orderItemVariation?.[itemKey];
    const basePrice = variation
        ? parseFloat(variation.price ?? item.price ?? 0)
        : parseFloat(item.price || 0);
    const modifierPrice = parseFloat(window.posState?.orderItemModifiersPrice?.[itemKey] || 0);
    const unitPrice = basePrice + modifierPrice;

    window.posLoyaltyApi.stampPreview({
        customer_id: window.posState.customerId,
        menu_item_id: item.id,
        quantity: qty,
        unit_price: unitPrice
    }, function(response) {
            if (!response || !response.success) {
                return;
            }

            const ruleId = parseInt(response.rule_id || 0, 10);
            const keyPrefix = ruleId > 0 ? `free_stamp_${ruleId}` : null;
            const props = [
                'orderItemList',
                'orderItemQty',
                'orderItemAmount',
                'orderItemVariation',
                'itemModifiersSelected',
                'orderItemModifiersPrice',
                'itemNotes',
                'orderItemTaxDetails'
            ];
            const removeKeyFromState = function(key) {
                props.forEach(function(prop) {
                    if (window.posState?.[prop] && window.posState[prop][key] !== undefined) {
                        delete window.posState[prop][key];
                    }
                });
                if (window.posState?.freeStampSourceByKey && window.posState.freeStampSourceByKey[key] !== undefined) {
                    delete window.posState.freeStampSourceByKey[key];
                }
            };

            // Only cleanup free-stamp keys and return when we're NOT going to apply a discount.
            // For discount_amount/discount_percent we must fall through to the block below.
            const isDiscountResponse = response.applied
                && (response.reward_type === 'discount_amount' || response.reward_type === 'discount_percent')
                && (typeof response.preview_discount_amount !== 'undefined' && parseFloat(response.preview_discount_amount) > 0);
            if (keyPrefix && !isDiscountResponse && (!response.applied || response.reward_type !== 'free_item' || !response.reward_item)) {
                Object.keys(window.posState?.orderItemList || {}).forEach(function(existingKey) {
                    if (existingKey.indexOf(keyPrefix) === 0) {
                        removeKeyFromState(existingKey);
                    }
                });
                // Clear stamp discount note and amount for this item when stamp no longer applied
                if (window.posState.orderItemList[itemKey] && window.posState.orderItemList[itemKey].stamp_rule_id) {
                    window.posState.orderItemList[itemKey].stamp_rule_id = null;
                    window.posState.orderItemAmount[itemKey] = Math.max(0, unitPrice * qty);
                    var stampLabel = (window.posConfig && window.posConfig.stampDiscountLabel) || 'Stamp Discount';
                    var existingNote = (window.posState.itemNotes && window.posState.itemNotes[itemKey]) || '';
                    if (existingNote && typeof existingNote === 'string' && existingNote.indexOf(stampLabel) !== -1) {
                        var parts = existingNote.split('|').map(function(s) { return s.trim(); }).filter(function(s) { return s.indexOf(stampLabel) !== 0; });
                        window.posState.itemNotes[itemKey] = parts.join(' | ').trim();
                    }
                }

                window.updateOrderItemsContainer?.();
                window.calculateTotal?.();
                return;
            }

            if (response.reward_type === 'free_item' && response.reward_item && keyPrefix) {
                const reward = response.reward_item;
                const appliedQty = Math.max(1, parseInt(response.applied_qty || 1, 10));

                // Single canonical key per rule so only one free-item card ever shows
                const freeKey = keyPrefix;
                Object.keys(window.posState?.orderItemList || {}).forEach(function(existingKey) {
                    if (existingKey !== freeKey && existingKey.indexOf(keyPrefix) === 0) {
                        removeKeyFromState(existingKey);
                    }
                });

                window.posState.orderItemList[freeKey] = {
                    ...reward,
                    is_free_item_from_stamp: true,
                    stamp_rule_id: ruleId
                };
                window.posState.orderItemQty[freeKey] = appliedQty;
                window.posState.orderItemAmount[freeKey] = 0;
                window.posState.itemNotes[freeKey] = response.free_item_note || @json(__('loyalty::app.freeItemFromStamp'));
                window.posState.orderItemVariation[freeKey] = response.reward_variation || null;
                window.posState.itemModifiersSelected[freeKey] = [];
                window.posState.orderItemModifiersPrice[freeKey] = 0;
                if (window.posState.orderItemTaxDetails && window.posState.orderItemTaxDetails[freeKey] !== undefined) {
                    delete window.posState.orderItemTaxDetails[freeKey];
                }
                if (!window.posState.freeStampSourceByKey) {
                    window.posState.freeStampSourceByKey = {};
                }
                window.posState.freeStampSourceByKey[freeKey] = itemKey;

                window.posState.hasFreeStampItems = true;

                window.updateOrderItemsContainer?.();
                window.calculateTotal?.();
                return;
            }

            // Handle percentage / fixed-amount stamp rewards.
            // Be tolerant of different backend reward_type labels by relying primarily on preview_discount_amount.
            if (
                response.applied
                && typeof response.preview_discount_amount !== 'undefined'
                && response.preview_discount_amount !== null
                && parseFloat(response.preview_discount_amount) > 0
                && (response.reward_type !== 'free_item')
            ) {
                const discountAmount = Math.max(0, parseFloat(response.preview_discount_amount || 0));
                const expectedAmount = Math.max(0, unitPrice * qty);
                window.posState.orderItemAmount[itemKey] = Math.max(0, expectedAmount - discountAmount);
                window.posState.orderItemList[itemKey].stamp_rule_id = ruleId || null;
                window.posState.stampDiscountAmount = discountAmount;

                // Set note on item like free stamp items: "Stamp Discount: -$12.00" (saved with order)
                var stampLabel = (window.posConfig && window.posConfig.stampDiscountLabel) || 'Stamp Discount';
                var existingNote = (window.posState.itemNotes && window.posState.itemNotes[itemKey]) || '';
                var stampNotePart = stampLabel + ': -' + (typeof window.formatCurrency === 'function' ? window.formatCurrency(discountAmount) : ('$' + discountAmount.toFixed(2)));
                var otherParts = existingNote && typeof existingNote === 'string'
                    ? existingNote.split('|').map(function(s) { return s.trim(); }).filter(function(s) { return s.indexOf(stampLabel) !== 0; })
                    : [];
                window.posState.itemNotes[itemKey] = (otherParts.join(' | ').trim() ? otherParts.join(' | ').trim() + ' | ' : '') + stampNotePart;

                window.updateOrderItemsContainer?.();
                window.calculateTotal?.();
            }
    });
};

window.refreshAutoStampPreviews = function() {
    if (!window.posState?.customerId) {
        return;
    }
    Object.keys(window.posState?.orderItemList || {}).forEach(function(key) {
        const item = window.posState?.orderItemList?.[key] || {};
        const note = window.posState?.itemNotes?.[key] || '';
        if (!window.isFreeStampItemByMeta(key, item, note)) {
            window.autoApplyStampPreviewForItem(key);
        }
    });
};

window.getNonFreeCartSummary = function() {
    let nonFreeLineCount = 0;
    let nonFreeQtyTotal = 0;

    Object.keys(window.posState?.orderItemList || {}).forEach(function(key) {
        const item = window.posState?.orderItemList?.[key] || {};
        const note = window.posState?.itemNotes?.[key] || '';
        if (window.isFreeStampItemByMeta(key, item, note)) {
            return;
        }

        nonFreeLineCount++;
        const qty = Math.max(0, parseInt(window.posState?.orderItemQty?.[key] || 1, 10) || 0);
        nonFreeQtyTotal += qty;
    });

    return {
        nonFreeLineCount,
        nonFreeQtyTotal
    };
};

// Tax and charge configuration for JavaScript calculations
@php
    // Keep JS tax config in sync with what should be displayed:
    // - existing order: use attached order taxes
    // - fresh order: use branch/restaurant tax list
    $posTaxes = [];

    if (isset($orderDetail) && $orderDetail && $orderDetail->relationLoaded('taxes') && $orderDetail->taxes->count() > 0) {
        $posTaxes = $orderDetail->taxes
            ->filter(fn($ot) => $ot->tax)
            ->map(fn($ot) => [
                'id' => $ot->tax->id,
                'tax_name' => $ot->tax->tax_name,
                'tax_percent' => $ot->tax->tax_percent,
            ])
            ->unique('id')
            ->values()
            ->toArray();
    } else {
        $posTaxes = collect($taxes ?? [])
            ->map(fn($tax) => [
                'id' => $tax->id ?? null,
                'tax_name' => $tax->tax_name ?? '',
                'tax_percent' => $tax->tax_percent ?? 0,
            ])
            ->filter(fn($tax) => !empty($tax['tax_name']))
            ->unique(function ($tax) {
                return $tax['id'] ?: strtolower(trim($tax['tax_name']));
            })
            ->values()
            ->toArray();
    }
@endphp

window.posConfig = {
    taxMode: '{{ $taxMode ?? "order" }}',
    taxInclusive: {{ $restaurant->tax_inclusive ?? 0 ? 'true' : 'false' }},
    includeChargesInTaxBase: {{ $includeChargesInTaxBase ?? true ? 'true' : 'false' }},
    taxes: @json($posTaxes),
    extraCharges: @json($extraCharges ?? []),
    currencyId: {{ $restaurant->currency_id ?? 1 }},
    currencyCode: '{{ $restaurant->currency->currency_code ?? "USD" }}',
    currencySymbol: '{{ $restaurant->currency->currency_symbol ?? "$" }}',
    moveToLabel: '{{ __('modules.order.moveTo') }}',
    stampDiscountLabel: {!! json_encode(__('app.stampDiscount')) !!}
};

// Fresh-order tax reliability: keep a backend-derived tax index by menu item id.
// This avoids item-wise tax loss when DOM data attributes are malformed/partial.
@php
    $menuItemTaxesIndexData = collect($menuItems ?? [])->mapWithKeys(function ($menuItem) {
        $itemTaxes = collect($menuItem->taxes ?? [])
            ->map(function ($tax) {
                return [
                    'id' => $tax->id ?? null,
                    'tax_name' => $tax->tax_name ?? '',
                    'tax_percent' => (float)($tax->tax_percent ?? 0),
                ];
            })
            ->filter(function ($tax) {
                return !empty($tax['tax_name']);
            })
            ->values()
            ->toArray();
        return [$menuItem->id => $itemTaxes];
    })->toArray();
@endphp
window.menuItemTaxesIndex = Object.assign(
    {},
    window.menuItemTaxesIndex || {},
    @json($menuItemTaxesIndexData)
);

// Wait for jQuery to be available
(function() {
    function initPosScripts() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            setTimeout(initPosScripts, 100);
            return;
        }

        // Initialize POS
        $(document).ready(function() {
            // Load order type selection if needed
            if (!window.posState.orderTypeId) {
                $('#orderTypeSelectionModal').show();
            }

            // Initialize totals display if there are items in cart.
            // Merged-table flow may have no orderDetail yet, so allow recalculation in that case.
            const hasCartItems = window.posState.orderItemList && Object.keys(window.posState.orderItemList).length > 0;
            const isLockedFinalStatus = window.posState.orderDetail && ['billed', 'paid', 'payment_due'].includes(window.posState.orderDetail.status);
            const showOrderDetail = window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true');
            const isFreshKotEntry = !showOrderDetail
                && window.posState
                && window.posState.orderDetail
                && window.posState.orderDetail.status === 'kot';

            // Fresh KOT entry (/pos/kot/{id} without order-detail panel) should start from empty cart totals.
            // Prevent stale order-level totals/taxes from being shown when item list is empty.
            if (!hasCartItems && isFreshKotEntry) {
                window.posState.subTotal = 0;
                window.posState.total = 0;
                window.posState.totalTaxAmount = 0;
                window.posState.taxBase = 0;
                window.posState.discountedTotal = 0;
                window.posState.discountAmount = 0;
                window.posState.orderItemTaxDetails = {};
                window.posState.totalsPreCalculated = false;
                window.posState.hasFreeStampItems = false;
                window.posState.loyaltyPointsRedeemed = 0;
                window.posState.loyaltyDiscountAmount = 0;
                window.posState.stampDiscountAmount = 0;
                window.posState.availableLoyaltyPoints = 0;

                // Ensure any loyalty redemption UI (inputs/previews) is also cleared
                if (typeof window.resetLoyaltyRedemption === 'function') {
                    window.resetLoyaltyRedemption();
                }

                if (typeof window.updateTotalsDisplay === 'function') {
                    window.updateTotalsDisplay();
                }
            }

            if (hasCartItems && !isLockedFinalStatus) {

                if (typeof window.updateOrderItemsContainer === 'function') {
                window.updateOrderItemsContainer();
                }

                // Always recalculate totals on load so that:
                // - merged tables get fresh tax/charge computation
                // - any client-side stamp/loyalty changes are reflected
                // The backend still trusts incoming numbers, but the UI
                // should never show a zero-tax state after merges.
                if (typeof window.calculateTotal === 'function') {
                    if (window.posState.totalsPreCalculated) {
                        if (typeof window.updateTotalsDisplay === 'function') {
                            window.updateTotalsDisplay();
                            window.calculateTotal();
                        }
                    } else {
                        window.calculateTotal();
                    }
                }
            }

            // Play beep sound function
            window.playBeep = function() {
                new Audio("{{ asset('sound/sound_beep-29.mp3') }}").play();
            };

            // Print location function
            window.printLocation = function(url) {
                if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                    window.open(url, '_blank');
                    return;
                }

                const isPWA = (window.matchMedia('(display-mode: standalone)').matches) ||
                             (window.navigator.standalone === true) ||
                             (document.referrer.includes('android-app://'));

                if (isPWA) {
                    window.location.href = url;
                } else {
                const $anchor = $('<a>', { href: url, target: '_blank' });
                    $('body').append($anchor);
                    $anchor[0].click();
                    $anchor.remove();
                }
            };

            // Close modals when clicking on backdrop (outside the modal content)
            function bindBackdropClose(modalSelector, closeFn) {
                const $modal = $(modalSelector);
                if (!$modal.length) return;
                $modal.off('click.posBackdrop').on('click.posBackdrop', function(e) {
                    // Only close when the click is on the backdrop itself, not inside modal content
                    if (e.target === this) {
                        if (typeof closeFn === 'function') {
                            closeFn();
                        } else {
                            $modal.hide();
                        }
                    }
                });
            }

            bindBackdropClose('#kotNoteModal', window.closeKotNoteModal);
            bindBackdropClose('#itemNoteModal', window.closeItemNoteModal);
            bindBackdropClose('#discountModal', window.closeDiscountModal);
            bindBackdropClose('#variationModal', window.closeVariationModal);
            bindBackdropClose('#tableChangeModal', window.closeTableChangeModal);
            bindBackdropClose('#loyaltyRedemptionModal', window.closeLoyaltyRedemptionModal);
        });

        window.toggleSingleActionButton = function(button, isLoading) {
            if (!button) return;
            const textEl = button.querySelector('[data-btn-text]');
            const loadingEl = button.querySelector('[data-btn-loading]');
            button.disabled = !!isLoading;
            button.classList.toggle('opacity-50', !!isLoading);
            if (textEl) textEl.classList.toggle('hidden', !!isLoading);
            if (loadingEl) loadingEl.classList.toggle('hidden', !isLoading);
        };
        window.setGlobalOrderActionLock = function(isLocked) {
            if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
            $('.pos-order-action-btn').prop('disabled', !!isLocked).toggleClass('opacity-50', !!isLocked);
        };

        // Helper function to wait for Livewire and dispatch payment modal
        window.showPaymentModalForOrder = function(orderId, triggerButton = null) {
            if (!orderId) {
                console.error('Order ID is required to show payment modal');
                return;
            }
            window.setGlobalOrderActionLock(true);
            window.toggleSingleActionButton(triggerButton, true);

            // Check if Livewire is available
            if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                try {
                    // Dispatch the event to show payment modal
                    Livewire.dispatch('showPaymentModal', { id: orderId });
                    setTimeout(function() {
                        window.setGlobalOrderActionLock(false);
                        window.toggleSingleActionButton(triggerButton, false);
                    }, 350);
                    return true;
                } catch (e) {
                    console.error('Error dispatching payment modal event:', e);
                }
            }

            // If Livewire is not available, wait and retry
            let attempts = 0;
            const maxAttempts = 10;
            const checkInterval = setInterval(function() {
                attempts++;
                if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                    clearInterval(checkInterval);
                    try {
                        Livewire.dispatch('showPaymentModal', { id: orderId });
                        console.log('Payment modal event dispatched for order:', orderId);
                        setTimeout(function() {
                            window.setGlobalOrderActionLock(false);
                            window.toggleSingleActionButton(triggerButton, false);
                        }, 350);
                    } catch (e) {
                        console.error('Error dispatching payment modal event:', e);
                        // Fallback: redirect to payment page
                        window.location.href = "{{ route('orders.show', ['order' => ':id']) }}".replace(':id', orderId) + '?payment=true';
                    }
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    window.setGlobalOrderActionLock(false);
                    window.toggleSingleActionButton(triggerButton, false);
                    // Fallback: redirect to payment page
                    window.location.href = "{{ route('orders.show', ['order' => ':id']) }}".replace(':id', orderId) + '?payment=true';
                }
            }, 200);
        };
    }

    // Start initialization using jQuery
    $(document).ready(function() {
        initPosScripts();
    });
})();

// Order Type Selection
let selectedOrderTypeId = null;
let selectedOrderTypeSlug = null;

window.selectOrderType = function(orderTypeId, slug) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    selectedOrderTypeId = orderTypeId;
    selectedOrderTypeSlug = slug;

    // If it's delivery, show delivery platform selection
    if (slug === 'delivery') {
        // Show delivery platforms section using jQuery
        $('#orderTypesSection').addClass('hidden');
        $('#deliveryPlatformsSection').removeClass('hidden');
        $('#backToOrderTypesBtn').removeClass('hidden');
        $('#modalTitle').text('@lang("modules.order.selectDeliveryPlatform")');
        $('#modalDescription').text('@lang("modules.order.selectDeliveryPlatformDescription")');
    } else {
        // For non-delivery types, proceed directly
        proceedToPOS(orderTypeId, slug, null);
    }
};

window.selectDeliveryPlatform = function(platformId) {
    proceedToPOS(selectedOrderTypeId, selectedOrderTypeSlug, platformId);
};

window.goBackToOrderTypes = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Show order types section using jQuery
    $('#orderTypesSection').removeClass('hidden');
    $('#deliveryPlatformsSection').addClass('hidden');
    $('#backToOrderTypesBtn').addClass('hidden');
    $('#modalTitle').text('@lang("modules.order.selectOrderType")');
    $('#modalDescription').text('@lang("modules.order.selectOrderTypeDescription")');
    selectedOrderTypeId = null;
    selectedOrderTypeSlug = null;
};

function proceedToPOS(orderTypeId, slug, deliveryPlatform) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Check if set as default using jQuery
    const setAsDefault = $('#setAsDefault').length > 0 ? $('#setAsDefault').is(':checked') : false;

    redirectToPOS(orderTypeId, slug, deliveryPlatform);
}

function redirectToPOS(orderTypeId, slug, deliveryPlatform) {
    // Update state

    window.posState.isWaiterLocked = {{ $isWaiterLocked ? 'true' : 'false' }};

    // Reload page with order type to get updated prices
    const url = new URL(window.location.href);
    url.searchParams.set('orderType', slug);
    url.searchParams.set('orderTypeId', orderTypeId);

    // Remove changeOrderType parameter when order type is selected
    url.searchParams.delete('changeOrderType');
    url.searchParams.delete('allowOrderTypeSelection');

    if (deliveryPlatform && deliveryPlatform !== 'default' && deliveryPlatform !== null) {
        url.searchParams.set('deliveryPlatform', deliveryPlatform);
    } else {
        url.searchParams.delete('deliveryPlatform');
    }

    // Reload page to apply Blade conditions with new order type
    window.location.href = url.toString();
}

// Update order type display (not needed since we reload the page, but kept for compatibility)
window.updateOrderTypeDisplay = function() {
    // When order type changes, the page reloads automatically
    // This function is kept for compatibility but does nothing
    // since prices are updated via page reload
};

/**
 * Change order type with confirmation if items exist in cart
 */
window.changeOrderType = function() {
    const hasItems = window.posState.orderItemList && Object.keys(window.posState.orderItemList).length > 0;

    if (hasItems) {
        // Show confirmation dialog if items exist
        Swal.fire({
            title: '@lang('modules.order.changeOrderType')',
            text: '@lang('modules.order.changeOrderTypeConfirmation')',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '@lang('app.yes'), @lang('app.change')',
            cancelButtonText: '@lang('app.cancel')',
            timer: 4000,
            position: 'center'
        }).then((result) => {
            if (result.isConfirmed) {
                resetOrderTypeSelection();
            }
        });
    } else {
        // No items in cart, safe to change directly
        resetOrderTypeSelection();
    }
};

/**
 * Reset order type selection and show OrderTypeSelection component
 */
function resetOrderTypeSelection() {
    // Create a clean URL with only the changeOrderType parameter
    // This ensures orderTypeId is null and the OrderTypeSelection component shows
    const baseUrl = "{{ route('pos.index') }}";
    window.location.href = baseUrl + "?changeOrderType=1";
}

// Modal Functions
window.closeVariationModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#variationModal').hide();
    }
};

window.closeKotNoteModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#kotNoteModal').hide();
    }
};

window.closeItemNoteModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#itemNoteModal').hide();
        $('#itemNoteError').hide();
        // Do not clear pending key here; user might reopen quickly.
    }
};

window.closeTableModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#tableModal').hide();
    }
};

window.closeDiscountModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#discountModal').hide();
    }
};

// Loyalty API: routes and token in one place (flat structure, no nested functions)
window.posLoyaltyApi = {
    summaryUrl: "{{ route('ajax.pos.loyalty.summary') }}",
    redeemUrl: "{{ route('ajax.pos.loyalty.redeem') }}",
    stampPreviewUrl: "{{ route('ajax.pos.loyalty.stamp-auto-preview') }}",
    token: '{{ csrf_token() }}',
    _defaultErrorMsg: {!! json_encode(__('messages.somethingWentWrong')) !!},
    getSummary: function(successCb, errorCb) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
        var api = window.posLoyaltyApi;
        $.easyAjax({
            url: api.summaryUrl,
            type: 'GET',
            data: {
                customer_id: window.posState?.customerId,
                sub_total: window.posState?.subTotal || 0,
                _token: api.token
            },
            success: successCb || function() {},
            error: errorCb || function(xhr) {
                var msg = xhr.responseJSON?.message || api._defaultErrorMsg;
                window.showToast?.('error', msg);
            }
        });
    },
    redeem: function(points, successCb, errorCb) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
        var api = window.posLoyaltyApi;
        $.easyAjax({
            url: api.redeemUrl,
            type: 'POST',
            data: {
                customer_id: window.posState?.customerId,
                sub_total: window.posState?.subTotal || 0,
                points: points,
                _token: api.token
            },
            success: successCb || function() {},
            error: errorCb || function() {}
        });
    },
    stampPreview: function(payload, successCb, errorCb) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
        var api = window.posLoyaltyApi;
        var data = Object.assign({ _token: api.token }, payload);
        $.ajax({
            url: api.stampPreviewUrl,
            type: 'POST',
            data: data,
            success: successCb || function() {},
            error: errorCb || function() {}
        });
    }
};

// Apply loyalty summary response to posState and loyalty modal (top-level, same style as rest of POS)
window.applyLoyaltySummaryToStateAndModal = function(resp) {
    if (!resp || !resp.success || resp.enabled === false) return false;
    var s = window.posState;
    if (!s) return false;
    s.availableLoyaltyPoints = resp.available_points || 0;
    s.valuePerPoint = resp.value_per_point || 0;
    s.minRedeemPoints = resp.min_redeem_points || 0;
    s.maxRedeemablePoints = resp.max_redeemable_points || 0;
    s.maxLoyaltyDiscount = resp.max_loyalty_discount || 0;
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return true;
    $('#loyalty-available-points').text((s.availableLoyaltyPoints || 0).toLocaleString());
    $('#loyalty-max-discount').text((s.maxLoyaltyDiscount || 0).toFixed(2));
    if (s.valuePerPoint > 0 && $('#loyalty-points-value').length) $('#loyalty-points-value').text(s.valuePerPoint.toFixed(2));
    var min = s.minRedeemPoints || 0, max = s.maxRedeemablePoints || 0;
    var $minMaxRow = $('#loyalty-min-max-row'), $minWrapper = $('#loyalty-min-wrapper'), $maxWrapper = $('#loyalty-max-wrapper');
    if (min > 0 || max > 0) {
        if (min > 0) { $('#loyalty-min-points').text(min.toLocaleString()); $minWrapper.show(); } else { $minWrapper.hide(); }
        if (max > 0) { $('#loyalty-max-points').text(max.toLocaleString()); $maxWrapper.show(); } else { $maxWrapper.hide(); }
        $minMaxRow.show();
    } else { $minMaxRow.hide(); }
    var $multipleRow = $('#loyalty-multiple-row');
    if (min > 0 && max > 0) {
        var template = $multipleRow.data('template') || '', text = template.replace(':min', min.toLocaleString());
        $multipleRow.text(text).show();
    } else { $multipleRow.hide().text(''); }
    $('#loyaltyPointsInput').val(s.loyaltyPointsRedeemed || '');
    $('#loyalty-discount-preview').text((s.loyaltyDiscountAmount || 0).toFixed(2));
    $('#loyaltyError').hide().text('');
    if ((s.maxRedeemablePoints || 0) > 0) {
        $('#loyalty-use-max-value').text(s.maxRedeemablePoints.toLocaleString());
        $('#loyalty-use-max-btn').show();
    } else { $('#loyalty-use-max-btn').hide(); }
    var hasPoints = (s.availableLoyaltyPoints || 0) > 0, cust = s.customer;
    if (hasPoints) {
        if (cust && cust.name) $('#loyalty-customer-name').text(cust.name);
        $('#loyalty-customer-row').show(); $('#loyalty-no-customer-row').hide();
    } else { $('#loyalty-no-customer-row').show(); $('#loyalty-customer-row').hide(); }
    $('#loyaltyRedemptionModal').show();
    return true;
};

// Loyalty Redemption (AJAX POS)
window.openLoyaltyRedemptionModal = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }
    if (!window.posState?.loyaltyEnabled) {
        return;
    }
    if (!window.posState.customerId) {
        window.showToast?.('error', {!! json_encode(__('modules.order.addCustomerDetails')) !!});
        return;
    }

    window.posLoyaltyApi.getSummary(function(resp) {
        window.applyLoyaltySummaryToStateAndModal(resp);
    }, function(xhr) {
        var msg = xhr.responseJSON?.message || window.posLoyaltyApi._defaultErrorMsg;
        window.showToast?.('error', msg);
    });
};

window.closeLoyaltyRedemptionModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#loyaltyRedemptionModal').hide();
    }
};

// Skip = close modal without applying (tt parity)
window.skipLoyaltyRedemption = function() {
    window.closeLoyaltyRedemptionModal();
};

// Use Max = apply redemption with max redeemable points (tt parity)
window.applyLoyaltyRedemptionMax = function() {
    var max = window.posState.maxRedeemablePoints || 0;
    if (max > 0) {
        if (typeof jQuery !== 'undefined' && $('#loyaltyPointsInput').length) {
            $('#loyaltyPointsInput').val(max);
        }
        window.applyLoyaltyRedemption();
    }
};

// -------------------------------------------------------------
// Hotel room-service stay selection modal for AJAX POS
// -------------------------------------------------------------
window.openHotelRoomModalAjax = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    let $modal = $('#hotel-room-modal-ajax');
    // Fallback: if modal container is missing (e.g. in kot_items view),
    // create a minimal version on the fly so the UX still works.
    if (!$modal.length) {
        $('body').append(`
            <div id="hotel-room-modal-ajax"
                 class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
                 onclick="if (event.target === this) { window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax(); }">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col"
                     onclick="event.stopPropagation();">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('hotel::modules.roomService.selectRoom') }}
                        </h3>
                        <button type="button"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                onclick="window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input id="hotel-room-search-ajax"
                                   type="text"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                   placeholder="{{ __('hotel::modules.roomService.searchByRoomOrStay') }}">
                        </div>
                    </div>
                    <div class="px-6 py-3 flex-1 overflow-y-auto">
                        <div id="hotel-room-stay-list-ajax" class="grid grid-cols-2 gap-3 text-sm text-gray-800 dark:text-gray-100"></div>
                        <div id="hotel-room-stay-empty-ajax" class="hidden text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                            {{ __('messages.noRecordFound') }}
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button type="button"
                                class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                                onclick="window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax()">
                            {{ __('app.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        `);
        $modal = $('#hotel-room-modal-ajax');
    }

    $modal.removeClass('hidden').addClass('flex');

    const loadStays = function(searchTerm) {
        $.easyAjax({
            url: "{{ route('ajax.pos.hotel.stays') }}",
            type: "GET",
            data: {
                search: searchTerm || '',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                const $list = $('#hotel-room-stay-list-ajax');
                const $empty = $('#hotel-room-stay-empty-ajax');
                $list.empty();

                if (!response || !response.success || !response.stays || response.stays.length === 0) {
                    $empty.removeClass('hidden');
                    return;
                }

                $empty.addClass('hidden');

                const selectedId = parseInt(window.posState?.selectedStayId || 0, 10);

                response.stays.forEach(function(stay) {
                    const guest = stay.guest_name ? `<div class="text-xs text-gray-500 dark:text-gray-500 mt-1 truncate">${stay.guest_name}</div>` : '';
                    const isSelected = selectedId && parseInt(stay.id || 0, 10) === selectedId;
                    const cardClasses = isSelected
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-sm'
                        : 'border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:shadow-sm';
                    const checkedAttr = isSelected ? 'checked' : '';

                    const html = `
                        <label class="block cursor-pointer">
                            <div class="border rounded-lg p-3 transition-all duration-200 bg-white dark:bg-gray-800 ${cardClasses}">
                                <div class="flex items-start gap-2">
                                    <input type="radio"
                                           name="hotel_room_stay_radio"
                                           value="${stay.id}"
                                           class="w-4 h-4 mt-0.5 text-blue-600 bg-white border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-2 dark:bg-gray-800 dark:border-gray-600"
                                           ${checkedAttr}
                                           onclick="window.selectHotelStayAjax && window.selectHotelStayAjax(${stay.id}, '${(stay.room_number || '').toString().replace(/'/g, '\\\'')}', '${(stay.stay_number || '').toString().replace(/'/g, '\\\'')}')"
                                    >
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                            ${stay.room_number || 'N/A'}
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
                                            ${stay.stay_number || ''}
                                        </div>
                                        ${guest}
                                    </div>
                                </div>
                            </div>
                        </label>
                    `;
                    $list.append(html);
                });
            }
        });
    };

    // Attach search handler once
    const $search = $('#hotel-room-search-ajax');
    if ($search.length && !$search.data('hotel-search-bound')) {
        let timer = null;
        $search.on('input', function() {
            clearTimeout(timer);
            const term = $(this).val();
            timer = setTimeout(function() {
                loadStays(term);
            }, 300);
        });
        $search.data('hotel-search-bound', '1');
    }

    loadStays('');
};

window.closeHotelRoomModalAjax = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }
    $('#hotel-room-modal-ajax').addClass('hidden').removeClass('flex');
};

window.selectHotelStayAjax = function(id, roomNumber, stayNumber) {
    if (!window.posState) {
        window.posState = {};
    }
    window.posState.selectedStayId = id;
    window.posState.billTo = window.posState.billTo || 'POST_TO_ROOM';

    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#ajax-room-stay-summary-room').text(roomNumber || 'N/A');
        if (stayNumber) {
            $('#ajax-room-stay-summary-stay').text(stayNumber).removeClass('hidden');
        } else {
            $('#ajax-room-stay-summary-stay').addClass('hidden').text('');
        }
    }

    window.closeHotelRoomModalAjax();
};

// Update room-service bill_to from AJAX POS select
window.setRoomServiceBillTo = function(value) {
    if (!window.posState) {
        window.posState = {};
    }
    window.posState.billTo = value || 'POST_TO_ROOM';
};

window.applyLoyaltyRedemption = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
    var points = parseInt($('#loyaltyPointsInput').val() || '0', 10);
    $('#loyaltyError').hide().text('');

    window.posLoyaltyApi.redeem(points, function(resp) {
        if (!resp || !resp.success) return;
        var data = resp.data || {};
        window.posState.loyaltyPointsRedeemed = data.points_redeemed || 0;
        window.posState.loyaltyDiscountAmount = data.discount_amount || 0;
        window.posState.discountType = '';
        window.posState.discountValue = 0;
        window.posState.discountAmount = 0;
        if (typeof window.calculateTotal === 'function') {
            window.calculateTotal();
        }
        window.closeLoyaltyRedemptionModal();
        window.showToast?.('success', {!! json_encode(__('loyalty::app.loyaltyPointsRedeemedSuccessfully')) !!});
    }, function(xhr) {
        var msg = xhr.responseJSON?.message || {!! json_encode(__('messages.somethingWentWrong')) !!};
        $('#loyaltyError').show().text(msg);
    });
};

window.resetLoyaltyRedemption = function() {
    window.posState.loyaltyPointsRedeemed = 0;
    window.posState.loyaltyDiscountAmount = 0;
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#loyaltyPointsInput').val('');
        $('#loyalty-discount-preview').text('0');
        $('#loyaltyError').hide().text('');
    }
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
};

// Print options helpers (AJAX POS)
window.openPrintOptionsModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#printOptionsModal').show();
    }
};

window.closePrintOptionsModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#printOptionsModal').hide();
    }
};

window.handlePrintOption = function(mode) {
    const orderId = window.posState ? (window.posState.orderID || (window.posState.orderDetail?.id || null)) : null;
    if (!orderId) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }

    let url = null;
    switch (mode) {
        case 'all':
            url = "{{ route('orders.print-split', ['orderId' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderId);
            break;
        case 'summary':
        case 'individual':
            url = "{{ route('orders.print-split-receipts', ['orderId' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderId);
            break;
        case 'single':
            url = "{{ route('orders.print', ['id' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderId);
            break;
    }

    if (!url) {
        return;
    }

    // Use existing printLocation helper if available for PWA handling
    if (typeof window.printLocation === 'function') {
        window.printLocation(url);
    } else {
        window.open(url, '_blank');
    }

    window.closePrintOptionsModal();
};

/**
 * Run browser events queued by PosAjaxController (Livewire v3 has no PHP Livewire::dispatch).
 * Uses window.Livewire.dispatch for saveOrderImageFromPrint / saveKotImageFromPrint listeners.
 */
window.runPosAjaxLivewireDispatches = function(dispatches) {
    if (!dispatches || !dispatches.length) {
        return;
    }
    dispatches.forEach(function(item) {
        if (!item || !item.name) {
            return;
        }
        if (item.name === 'print_location' && item.params && item.params.length) {
            var u = item.params[0];
            if (typeof window.printLocation === 'function') {
                window.printLocation(u);
            } else {
                window.open(u, '_blank');
            }
            return;
        }
        if (typeof window.Livewire !== 'undefined' && typeof window.Livewire.dispatch === 'function') {
            var params = item.params;
            if (params === undefined || params === null) {
                window.Livewire.dispatch(item.name);
            } else {
                window.Livewire.dispatch(item.name, params);
            }
        }
    });
};

/** AJAX print order — same server logic as PosAjaxController::ajaxPrintOrder / Livewire printOrder */
window.handleAjaxPrintOrderResponse = function(res) {
    if (!res || !res.success) {
        const msg = res && res.message ? res.message : {!! json_encode(__('messages.printerNotConnected')) !!};
        if (typeof window.showToast === 'function') {
            window.showToast('error', msg);
        } else {
            alert(msg);
        }
        return;
    }
    if (res.mode === 'print_options') {
        if (typeof window.openPrintOptionsModal === 'function') {
            window.openPrintOptionsModal();
        }
        return;
    }
    if (res.message && typeof window.showToast === 'function') {
        window.showToast('success', res.message);
    }
    if (res.dispatches && res.dispatches.length && typeof window.runPosAjaxLivewireDispatches === 'function') {
        window.runPosAjaxLivewireDispatches(res.dispatches);
    }
    if (res.mode === 'url' && res.url) {
        if (typeof window.printLocation === 'function') {
            window.printLocation(res.url);
        } else {
            window.open(res.url, '_blank');
        }
    }
};

window.ajaxPrintOrderById = function(orderId) {
    if (!orderId) {
        return;
    }
    $.ajax({
        url: '/ajax/pos/orders/' + orderId + '/print',
        type: 'POST',
        dataType: 'json',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (typeof window.handleAjaxPrintOrderResponse === 'function') {
                window.handleAjaxPrintOrderResponse(res);
            }
        },
        error: function(xhr) {
            let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : null;
            if (!msg && xhr.status === 419) {
                msg = 'Session expired. Please refresh the page.';
            }
            if (!msg) {
                msg = (xhr.statusText && xhr.statusText !== 'error') ? xhr.statusText : {!! json_encode(__('messages.printerNotConnected')) !!};
            }
            if (typeof window.showToast === 'function') {
                window.showToast('error', msg);
            } else {
                alert(msg);
            }
        }
    });
};

/**
 * AJAX print KOT for an order — same as Livewire Pos::printKot($order, $kotContext, $kotIds).
 * One request: kitchen ON → filtered KOT rows, each split by item kot_place; kitchen OFF → default station per kot_id.
 *
 * @param {number} orderId
 * @param {number[]} kotIds Empty array = all KOTs on order when kitchen module is on (matches Livewire empty $kotIds).
 * @param {object} [options] { onComplete: function () }
 */
window.ajaxPrintKotForOrder = function(orderId, kotIds, options) {
    options = options || {};
    var oid = parseInt(orderId, 10);
    if (!oid) {
        if (typeof options.onComplete === 'function') {
            options.onComplete();
        }
        return;
    }
    var ids = (kotIds || []).map(function(id) { return parseInt(id, 10); }).filter(function(id) { return id > 0; });
    var data = {
        _token: '{{ csrf_token() }}',
        kot_ids: ids
    };
    var url = "{{ route('ajax.pos.print-kot-order', ['orderId' => '__OID__']) }}".replace('__OID__', String(oid));
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(res) {
            if (typeof window.handleAjaxPrintKotResponse === 'function') {
                window.handleAjaxPrintKotResponse(res, {
                    afterScheduled: options.onComplete
                });
            } else if (typeof options.onComplete === 'function') {
                options.onComplete();
            }
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : null;
            if (!msg && xhr.status === 419) {
                msg = 'Session expired. Please refresh the page.';
            }
            if (!msg) {
                msg = (xhr.statusText && xhr.statusText !== 'error') ? xhr.statusText : {!! json_encode(__('messages.printerNotConnected')) !!};
            }
            if (typeof window.showToast === 'function') {
                window.showToast('error', msg);
            } else {
                alert(msg);
            }
            if (typeof options.onComplete === 'function') {
                options.onComplete();
            }
        }
    });
};

/**
 * AJAX print KOT — same server logic as PosAjaxController::ajaxPrintKot / Livewire printKot
 * @param {object} [hooks] Optional { afterScheduled: function () } — called after browser print tabs
 *   have been scheduled (respects staggered window.open). Use this to navigate away without
 *   aborting the print request or blocking popups.
 */
window.handleAjaxPrintKotResponse = function(res, hooks) {
    const runAfterScheduled = function() {
        if (hooks && typeof hooks.afterScheduled === 'function') {
            hooks.afterScheduled();
        }
    };
    if (!res || !res.success) {
        const msg = res && res.message ? res.message : {!! json_encode(__('messages.printerNotConnected')) !!};
        if (typeof window.showToast === 'function') {
            window.showToast('error', msg);
        } else {
            alert(msg);
        }
        runAfterScheduled();
        return;
    }
    if (res.dispatches && res.dispatches.length && typeof window.runPosAjaxLivewireDispatches === 'function') {
        window.runPosAjaxLivewireDispatches(res.dispatches);
    }
    const urls = (res.urls && res.urls.length) ? res.urls : (res.url ? [res.url] : []);
    const staggerMs = 350;
    urls.forEach(function(url, i) {
        setTimeout(function() {
            if (typeof window.printLocation === 'function') {
                window.printLocation(url);
            } else {
                window.open(url, '_blank');
            }
        }, i * staggerMs);
    });
    if (res.warnings && res.warnings.length && typeof window.showToast === 'function') {
        window.showToast('warning', res.warnings.join(' '));
    } else if (res.message && typeof window.showToast === 'function') {
        window.showToast('success', res.message);
    }
    // Defer follow-up (e.g. redirect) until after print URLs run so the tab opens before unload.
    var waitMs = 0;
    if (urls.length > 1) {
        waitMs = (urls.length - 1) * staggerMs + 150;
    } else if (urls.length === 1) {
        waitMs = 150;
    } else {
        waitMs = 50;
    }
    setTimeout(runAfterScheduled, waitMs);
};

/**
 * @param {number} kotId
 * @param {object} [options] Optional { onComplete: function () } — called after print handling finishes
 *   (including delayed scheduling for browser popup print). Safe for redirect / resetPosState.
 */
window.ajaxPrintKotById = function(kotId, options) {
    options = options || {};
    if (!kotId) {
        if (typeof options.onComplete === 'function') {
            options.onComplete();
        }
        return;
    }
    $.ajax({
        url: '/ajax/pos/kot/' + kotId + '/print',
        type: 'POST',
        dataType: 'json',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (typeof window.handleAjaxPrintKotResponse === 'function') {
                window.handleAjaxPrintKotResponse(res, {
                    afterScheduled: options.onComplete
                });
            } else if (typeof options.onComplete === 'function') {
                options.onComplete();
            }
        },
        error: function(xhr) {
            let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : null;
            if (!msg && xhr.status === 419) {
                msg = 'Session expired. Please refresh the page.';
            }
            if (!msg) {
                msg = (xhr.statusText && xhr.statusText !== 'error') ? xhr.statusText : {!! json_encode(__('messages.printerNotConnected')) !!};
            }
            if (typeof window.showToast === 'function') {
                window.showToast('error', msg);
            } else {
                alert(msg);
            }
            if (typeof options.onComplete === 'function') {
                options.onComplete();
            }
        }
    });
};

/**
 * Print several KOTs — prefers one Livewire-equivalent call ajaxPrintKotForOrder(orderId, kotIds).
 * Falls back to per-KOT requests if order id is missing.
 * @param {number[]} kotIds
 * @param {object} [options] { onComplete: function (), orderId: number }
 */
window.ajaxPrintKotIdsSequential = function(kotIds, options) {
    options = options || {};
    var ids = (kotIds || []).map(function(id) { return parseInt(id, 10); }).filter(function(id) { return id > 0; });
    if (ids.length === 0) {
        if (typeof options.onComplete === 'function') {
            options.onComplete();
        }
        return;
    }
    var oid = options.orderId || (window.posState && window.posState.orderID);
    oid = oid ? parseInt(oid, 10) : 0;
    if (oid && typeof window.ajaxPrintKotForOrder === 'function') {
        window.ajaxPrintKotForOrder(oid, ids, { onComplete: options.onComplete });
        return;
    }
    var rest = ids.slice(1);
    window.ajaxPrintKotById(ids[0], {
        onComplete: function() {
            if (rest.length) {
                window.ajaxPrintKotIdsSequential(rest, options);
            } else if (typeof options.onComplete === 'function') {
                options.onComplete();
            }
        }
    });
};

window.closeErrorModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#errorModal').hide();
    }
};

window.closeModifiersModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#modifiersModal').hide();
    }
};

// KOT-only click guard to block rapid duplicate clicks without changing saveOrder flow.
window.saveKotActionOnce = function() {
    // Strict guard: once a KOT submit starts, block subsequent KOT clicks
    // until current flow ends (success navigation or explicit unlock on error).
    if (window.__posKotSubmissionLocked) {
        return;
    }

    const now = Date.now();
    const cooldownMs = 900;
    const lastClickAt = window.__lastKotActionClickAt || 0;

    if ((now - lastClickAt) < cooldownMs) {
        return;
    }

    window.__lastKotActionClickAt = now;
    window.__posKotSubmissionLocked = true;
    window.saveOrder.apply(window, arguments);
};

// Save Order Function
window.saveOrder = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Get all action arguments
    const actions = Array.from(arguments).filter(a => a !== null && a !== undefined);
    const action = actions[0] || 'draft';
    const secondAction = actions[1] || null;
    const thirdAction = actions[2] || null;
    const fourthAction = actions[3] || null;
    const isKotAction = action === 'kot';
    let keepActionLockedAfterComplete = false;

    // Always recompute totals from current cart state immediately before building payload.
    // This prevents stale financial fields from being submitted after rapid cart edits.
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }

    // Validate that there are items in the cart
    if (!window.posState.orderItemList || Object.keys(window.posState.orderItemList).length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '@lang("messages.noItemsFound")',
                text: '@lang("messages.orderItemRequired")',
                confirmButtonText: '@lang("app.ok")',
                confirmButtonColor: '#3085d6'
            });
        } else {
            alert('@lang("messages.orderItemRequired")');
        }
        if (isKotAction) {
            window.__posKotSubmissionLocked = false;
        }
        return;
    }

    // Frontend validation: for room service, require a selected stay
    const orderTypeSlug = (window.posState.orderTypeSlug || '').toString();
    if (orderTypeSlug === 'room_service') {
        const stayId = parseInt(window.posState?.selectedStayId || 0, 10);
        if (!stayId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: @json(__('app.error')),
                    text: @json(__('hotel::modules.roomService.selectStayRequired')),
                });
            } else {
                alert(@json(__('hotel::modules.roomService.selectStayRequired')));
            }
            if (isKotAction) {
                window.__posKotSubmissionLocked = false;
            }
            return;
        }
    }

    // Frontend validation: for delivery with internal (default) app, require delivery executive
    if (orderTypeSlug === 'delivery') {
        const selectedDeliveryApp = (window.posState.selectedDeliveryApp ?? 'default') === null
            ? 'default'
            : (window.posState.selectedDeliveryApp || 'default');
        const deliveryExecId = parseInt(window.posState.selectedDeliveryExecutive || 0, 10);

        if (selectedDeliveryApp === 'default' && !deliveryExecId && action !== 'cancel') {
            const message = @json(__('validation.required', ['attribute' => __('modules.delivery.deliveryExecutive')]));
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: @json(__('app.error')),
                    text: message,
                });
            } else {
                alert(message);
            }
            if (isKotAction) {
                window.__posKotSubmissionLocked = false;
            }
            return;
        }
    }

    // Frontend validation: for pickup, only ensure a pickup date & time are chosen.
    // "Future time" and same‑day rules are enforced centrally on the server.
    if (orderTypeSlug === 'pickup' && action !== 'cancel') {
        const pickupDate = (window.posState.pickupDate || '').toString().trim();
        const pickupTime = (window.posState.pickupTime || '').toString().trim();

        if (!pickupDate || !pickupTime) {
            const message = @json(__('validation.required', ['attribute' => __('modules.order.pickUpDateTime')]));
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: @json(__('app.error')),
                    text: message,
                });
            } else {
                alert(message);
            }
            if (isKotAction) {
                window.__posKotSubmissionLocked = false;
            }
            return;
        }
    }

    // Prevent duplicate submissions from rapid double-clicks.
    if (window.__posOrderActionInProgress) {
        return;
    }

    // Determine button IDs based on action
    let buttonId = '';
    let buttonTextId = '';
    let buttonLoadingId = '';

    const actionKey = actions.join('_');

    switch(actionKey) {
        case 'draft':
            buttonId = 'saveDraftBtn';
            buttonTextId = 'saveDraftBtnText';
            buttonLoadingId = 'saveDraftBtnLoading';
            break;
        case 'kot':
            buttonId = 'saveKotBtn';
            buttonTextId = 'saveKotBtnText';
            buttonLoadingId = 'saveKotBtnLoading';
            break;
        case 'kot_print':
            buttonId = 'saveKotPrintBtn';
            buttonTextId = 'saveKotPrintBtnText';
            buttonLoadingId = 'saveKotPrintBtnLoading';
            break;
        case 'kot_bill_payment_print':
            buttonId = 'saveKotBillPaymentBtn';
            buttonTextId = 'saveKotBillPaymentBtnText';
            buttonLoadingId = 'saveKotBillPaymentBtnLoading';
            break;
        case 'bill':
            buttonId = 'saveBillBtn';
            buttonTextId = 'saveBillBtnText';
            buttonLoadingId = 'saveBillBtnLoading';
            break;
        case 'bill_payment':
            buttonId = 'saveBillPaymentBtn';
            buttonTextId = 'saveBillPaymentBtnText';
            buttonLoadingId = 'saveBillPaymentBtnLoading';
            break;
        case 'bill_print':
            buttonId = 'saveBillPrintBtn';
            buttonTextId = 'saveBillPrintBtnText';
            buttonLoadingId = 'saveBillPrintBtnLoading';
            break;
    }

    window.__setPosOrderActionButtonsDisabled = function(disabled) {
        if (typeof window.setGlobalOrderActionLock === 'function') {
            window.setGlobalOrderActionLock(!!disabled);
            return;
        }
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') return;
        $('.pos-order-action-btn').prop('disabled', !!disabled).toggleClass('opacity-50', !!disabled);
    };

    window.__togglePosButtonLoading = function(id, textId, loadingId, isLoading) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined' || !id) return;
        const $btn = $('#' + id);
        if (!$btn.length) return;

        $btn.prop('disabled', !!isLoading).toggleClass('opacity-50', !!isLoading);

        if (textId) {
            const $text = $('#' + textId);
            if ($text.length) $text.toggleClass('hidden', !!isLoading);
        }
        if (loadingId) {
            const $loading = $('#' + loadingId);
            if ($loading.length) $loading.toggleClass('hidden', !isLoading);
        }
    };

    // Show loading state
    if (buttonId) {
        window.__posOrderActionInProgress = true;
        window.__setPosOrderActionButtonsDisabled(true);
        window.__togglePosButtonLoading(buttonId, buttonTextId, buttonLoadingId, true);
    }

    // Prepare order items data
    // API expects: id, variant_id, quantity, price, amount, note, modifier_ids
    const orderItems = [];
    for (const key in window.posState.orderItemList) {
        const item = window.posState.orderItemList[key];
        const qty = window.posState.orderItemQty[key] || 1;
        const variation = window.posState.orderItemVariation[key];
        const modifiers = window.posState.itemModifiersSelected[key] || [];
        const note = window.posState.itemNotes[key] || null;
        const isFreeStampItem = window.isFreeStampItemByMeta(key, item, note);

        // Existing-order KOT generation should include only fresh cart lines.
        // Keys like "kot_<kotId>_<itemId>" represent already-issued KOT items.
        if (action === 'kot' && window.posState.orderID && /^kot_\d+_\d+$/.test(key)) {
            continue;
        }

        // Ensure item and item.id exists
        if (!item) {
            console.error('Item is null or undefined for key:', key);
            continue;
        }

        if (!item.id && item.id !== 0) {
            console.error('Invalid item data - missing id:', item, 'key:', key);
            continue;
        }

        // Ensure id is a number
        const menuItemId = parseInt(item.id);
        if (isNaN(menuItemId) || menuItemId <= 0) {
            console.error('Invalid menu_item_id:', item.id, 'for item:', item);
            continue;
        }

        const basePrice = variation ? (variation.price || item.price) : item.price;
        const modifierPrice = window.posState.orderItemModifiersPrice[key] || 0;
        const itemPrice = basePrice + modifierPrice;
        // IMPORTANT: Prefer stored per-key amount (may include stamp discount / free stamp item = 0)
        const amount = (window.posState.orderItemAmount && typeof window.posState.orderItemAmount[key] !== 'undefined')
            ? window.posState.orderItemAmount[key]
            : (itemPrice * qty);

        // Get tax details for this item if available
        const itemTaxDetails = window.posState.orderItemTaxDetails && window.posState.orderItemTaxDetails[key]
            ? window.posState.orderItemTaxDetails[key]
            : null;

        const orderItem = {
            key: key,
            id: menuItemId,  // API expects 'id' not 'menu_item_id'
            variant_id: variation ? parseInt(variation.id) : 0,  // API expects 'variant_id' not 'menu_item_variation_id'
            quantity: parseInt(qty) || 1,
            price: parseFloat(itemPrice) || 0,
            amount: parseFloat(amount) || 0,
            note: note || null,
            modifier_ids: Array.isArray(modifiers) ? modifiers.map(m => parseInt(m)).filter(m => !isNaN(m)) : [],  // API expects 'modifier_ids' not 'modifier_option_ids'
            tax_amount: itemTaxDetails ? (itemTaxDetails.tax_amount || 0) : 0,
            tax_percentage: itemTaxDetails ? (itemTaxDetails.tax_percent || 0) : 0,
            tax_breakup: itemTaxDetails ? (itemTaxDetails.tax_breakup || null) : null,
            is_free_item_from_stamp: isFreeStampItem,
            stamp_rule_id: item.stamp_rule_id || null
        };

        orderItems.push(orderItem);
    }

    // Validate that we have items
    if (orderItems.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: '@lang("modules.order.noItemsInCart")',
                text: '@lang("messages.orderItemRequired")',
                confirmButtonText: '@lang("app.ok")',
                confirmButtonColor: '#3085d6'
            });
        } else {
            alert('@lang("messages.orderItemRequired")');
        }
        // Hide loading state
        if (buttonId) {
            window.__posOrderActionInProgress = false;
            window.__setPosOrderActionButtonsDisabled(false);
            window.__togglePosButtonLoading(buttonId, buttonTextId, buttonLoadingId, false);
        }
        if (isKotAction) {
            window.__posKotSubmissionLocked = false;
        }
        return;
    }

    // Debug: Log order items before sending
    console.log('Sending order items:', orderItems);

    // Prepare extra charges data
    const extraCharges = [];
    if (window.posConfig && window.posConfig.extraCharges && window.posConfig.extraCharges.length > 0) {
        window.posConfig.extraCharges.forEach(function(charge) {
            extraCharges.push({
                id: charge.id,
                charge_id: charge.id,
                name: charge.name,
                amount: charge.amount,
                type: charge.type,
                is_enabled: charge.is_enabled
            });
        });
    }

    // Prepare taxes data
    const taxes = [];
    if (window.posConfig && window.posConfig.taxes && window.posConfig.taxes.length > 0) {
        window.posConfig.taxes.forEach(function(tax) {
            taxes.push({
                id: tax.id,
                tax_id: tax.id,
                tax_name: tax.tax_name,
                tax_percent: tax.tax_percent
            });
        });
    }

    // Prepare order data
    const orderData = {
        order_id: window.posState.orderID || null,
        order_type: window.posState.orderType || window.posState.orderTypeSlug,
        order_type_id: window.posState.orderTypeId,
        order_type_display: window.posState.orderType,
        table_id: window.posState.tableId,
        customer_id: window.posState.customerId,
        customer: window.posState.customer ? {
            name: window.posState.customer.name,
            phone: window.posState.customer.phone,
            email: window.posState.customer.email
        } : null,
        items: orderItems,
        pax: window.posState.noOfPax || 1,
        waiter_id: window.posState.selectWaiter,
        delivery_executive_id: window.posState.selectedDeliveryExecutive || null,
        delivery_app_id: window.posState.selectedDeliveryApp || null,
        discount_type: window.posState.discountType,
        discount_value: window.posState.discountValue || 0,
        discount_amount: window.posState.discountAmount || 0,
        loyalty_points_redeemed: parseInt(window.posState.loyaltyPointsRedeemed || 0),
        loyalty_discount_amount: parseFloat(window.posState.loyaltyDiscountAmount || 0),
        delivery_fee: window.posState.deliveryFee || 0,
        tip_amount: window.posState.tipAmount || 0,
        order_note: window.posState.orderNote || null,
        sub_total: window.posState.subTotal || 0,
        total: window.posState.total || 0,
        discounted_total: window.posState.discountedTotal || 0,
        total_tax_amount: window.posState.totalTaxAmount || 0,
        tax_base: window.posState.taxBase || 0,
        tax_mode: window.posConfig?.taxMode || '{{ $taxMode ?? "order" }}',
        order_status: window.posState.orderStatus || 'confirmed',
        // For pickup, send combined "<date> <time>" string so the
        // backend can validate and normalize it using the same rules
        // as the Livewire POS component. Fallback to legacy
        // deliveryDateTime when date/time inputs are not available.
        pickup_date: (window.posState.pickupDate && window.posState.pickupTime)
            ? (window.posState.pickupDate + ' ' + window.posState.pickupTime)
            : (window.posState.deliveryDateTime || null),
        pickup_date_only: window.posState.pickupDate || null,
        pickup_time_only: window.posState.pickupTime || null,
        extra_charges: extraCharges,
        taxes: taxes,
        order_item_tax_details: window.posState.orderItemTaxDetails || {},
        actions: actions,
        orders_to_delete_after_merge: @json(session('pos_merged_orders_to_delete', [])),
        _token: '{{ csrf_token() }}'
    };

    // Attach Hotel room-service context (parity with Livewire Pos.php)
    const roomServiceOrderTypeSlug = (window.posState.orderTypeSlug || '').toString();
    if (roomServiceOrderTypeSlug === 'room_service' && window.posState.selectedStayId) {
        orderData.context_type = 'HOTEL_ROOM';
        orderData.context_id = window.posState.selectedStayId;
        orderData.bill_to = window.posState.billTo || 'POST_TO_ROOM';
    }

    function ajaxPrintOrderWithSettings(orderId) {
        if (typeof window.ajaxPrintOrderById === 'function') {
            window.ajaxPrintOrderById(orderId);
        }
    }

    // Call API
    $.easyAjax({
        url: "{{ route('ajax.pos.save-order') }}",
        type: "POST",
        data: orderData,
        success: function(response) {
            if (response.success) {
                // For KOT actions, keep buttons locked to avoid duplicate submits
                // between response and navigation/print completion.
                if (isKotAction) {
                    keepActionLockedAfterComplete = true;
                }
                const orderId = response.order?.id || response.order_id;
                const kotIdsForPrint = (function() {
                    if (Array.isArray(response.kot_ids) && response.kot_ids.length) {
                        return response.kot_ids.map(function(id) { return parseInt(id, 10); }).filter(function(id) { return id > 0; });
                    }
                    if (response.kot && response.kot.id) {
                        var single = parseInt(response.kot.id, 10);
                        return single > 0 ? [single] : [];
                    }
                    return [];
                })();

                // Clear merged orders session data after successful save
                @if(session()->has('pos_merged_orders_to_delete'))
                $.ajax({
                    url: '/ajax/pos/clear-merge-session',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                });
                @endif

                // Handle different actions based on what was clicked
                // Check for payment action (can be secondAction or thirdAction)
                if (secondAction === 'payment' || thirdAction === 'payment') {
                    // Show payment modal using Livewire event
                    if (orderId) {
                        // Use the helper function to show payment modal
                        if (typeof window.showPaymentModalForOrder === 'function') {
                            window.showPaymentModalForOrder(orderId);
                        } else {
                            // Fallback: redirect to payment page
                            window.location.href = "{{ route('orders.show', ['order' => ':id']) }}".replace(':id', orderId) + '?payment=true';
                        }
                    }

                    // Handle print if it's also in the actions (kot,bill,payment,print)
                    if (fourthAction === 'print' || (action === 'kot' && secondAction === 'bill' && thirdAction === 'payment')) {
                        // Print KOT(s) then bill — Livewire-equivalent: printKot($order, null, $kotIds) via ajaxPrintKotForOrder
                        if (orderId) {
                            if (kotIdsForPrint.length && typeof window.ajaxPrintKotForOrder === 'function') {
                                window.ajaxPrintKotForOrder(orderId, kotIdsForPrint, {
                                    onComplete: function() { ajaxPrintOrderWithSettings(orderId); }
                                });
                            } else {
                                if (response.kot && response.kot.id && typeof window.ajaxPrintKotById === 'function') {
                                    window.ajaxPrintKotById(response.kot.id);
                                }
                                ajaxPrintOrderWithSettings(orderId);
                            }
                        }
                    }

                    // Clear cart after showing payment modal
                    window.resetPosState();
                // KOT + print is handled in the `action === 'kot'` branch so we can finish the print
                // AJAX (and open browser tabs) before redirecting — otherwise navigation aborts the request
                // and shows "Printer Not Connected".
                } else if ((secondAction === 'print' || fourthAction === 'print') && !(action === 'kot' && secondAction === 'print')) {
                    // Print-only flows (match Livewire Pos.php):
                    // - bill + print => order bill only
                    // - kot+bill+payment+print is handled above (payment branch)
                    if (orderId) {
                        const isBillPrintOnly = action === 'bill' && secondAction === 'print';

                        if (isBillPrintOnly) {
                            ajaxPrintOrderWithSettings(orderId);
                        } else if (fourthAction === 'print') {
                            // Defensive: should normally be handled in payment branch
                            if (kotIdsForPrint.length && typeof window.ajaxPrintKotForOrder === 'function') {
                                window.ajaxPrintKotForOrder(orderId, kotIdsForPrint, {
                                    onComplete: function() { ajaxPrintOrderWithSettings(orderId); }
                                });
                            } else {
                                if (response.kot && response.kot.id && typeof window.ajaxPrintKotById === 'function') {
                                    window.ajaxPrintKotById(response.kot.id);
                                }
                                ajaxPrintOrderWithSettings(orderId);
                            }
                        }
                    }

                    window.resetPosState();

                    // Show success message
                    if (response.message) {
                        // Use toast notification if available, otherwise alert
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', response.message);
                        } else {
                            alert(response.message);
                        }
                    }

                    // If action is 'bill', show order detail like simple bill action
                    if (action === 'bill') {

                        // Show order detail modal if available
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('showOrderDetail', { id: orderId, fromPos: true });
                        } else {
                            // Fallback: redirect to order detail
                            window.location.href = "{{ route('orders.show', ['order' => ':id']) }}".replace(':id', orderId);
                        }

                        // Refresh POS
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('refreshPos');
                        }
                    } else {
                        // For KOT action, redirect to KOT detail view with show-order-detail=true
                        if (orderId && (kotIdsForPrint.length || response.kot?.id || window.currentKotId)) {
                            const kotDetailUrlTemplate = "{{ route('pos.kot', ['id' => ':id']) }}?show-order-detail=true";
                            const kotDetailUrl = kotDetailUrlTemplate.replace(':id', orderId);
                            window.location.href = kotDetailUrl;
                        }
                    }
                } else if (action === 'kot') {
                    // Check if this is a new order creation (fresh KOT)
                    const isNewOrder = !window.posState.orderID;
                    const wantsKotPrint = secondAction === 'print' && orderId && kotIdsForPrint.length > 0;

                    const finishKotSaveFlow = function() {
                        // For fresh KOT creation, show toast with view order button
                        if (isNewOrder && orderId) {
                            if (typeof Swal !== 'undefined') {
                                window.resetPosState();
                                const kotDetailUrl = "{{ route('pos.kot', ['id' => ':id']) }}?show-order-detail=true".replace(':id', orderId);
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    title: response.message || '@lang("modules.order.kotCreated")',
                                    showConfirmButton: false,
                                    timer: 4000,
                                    timerProgressBar: true,
                                    footer: '<a href="' + kotDetailUrl + '" style="color: #3085d6; text-decoration: underline;">@lang("modules.order.viewOrder")</a>',
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer);
                                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                                    }
                                }).then((result) => {
                                    if (result.dismiss === Swal.DismissReason.timer) {
                                        window.location.href = "{{ route('pos.index') }}";
                                    }
                                });
                            } else {
                                if (response.message) alert(response.message);
                                setTimeout(() => window.location.href = "{{ route('pos.index') }}", 400);
                            }
                            return;
                        }

                        // Show success message for existing order updates
                        if (response.message) {
                            if (typeof window.showToast === 'function') {
                                window.showToast('success', response.message);
                            } else {
                                alert(response.message);
                            }
                        }

                        // After saving KOT (existing order), redirect to KOT detail view
                        if (orderId) {
                            const kotDetailUrl = "{{ route('pos.kot', ['id' => ':id']) }}?show-order-detail=true".replace(':id', orderId);
                            window.location.href = (response.order && response.order.status === 'kot')
                                ? kotDetailUrl
                                : "{{ route('pos.index') }}";
                        }
                    };

                    // Defer finish until print AJAX + browser tabs complete (see handleAjaxPrintKotResponse)
                    if (wantsKotPrint) {
                        if (orderId && typeof window.ajaxPrintKotForOrder === 'function') {
                            window.ajaxPrintKotForOrder(orderId, kotIdsForPrint, { onComplete: finishKotSaveFlow });
                            return;
                        }
                        if (typeof window.ajaxPrintKotIdsSequential === 'function') {
                            window.ajaxPrintKotIdsSequential(kotIdsForPrint, { onComplete: finishKotSaveFlow, orderId: orderId });
                            return;
                        }
                        if (typeof window.ajaxPrintKotById === 'function' && kotIdsForPrint[0]) {
                            window.ajaxPrintKotById(kotIdsForPrint[0], { onComplete: finishKotSaveFlow });
                            return;
                        }
                    }

                    finishKotSaveFlow();
                } else if (action === 'draft') {
                    // Check if this is a new order creation (fresh draft)
                    const isNewOrder = !window.posState.orderID;

                    // Show success message
                    if (response.message) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', response.message);
                        } else {
                            alert(response.message);
                        }
                    }

                    // For fresh draft creation, reload page to reset state
                    if (isNewOrder && orderId) {
                        window.resetPosState();
                        setTimeout(() => window.location.href = "{{ route('pos.index') }}", 500);
                        return;
                    }

                    // For existing draft updates, keep items visible but reset menu selection
                    if (typeof window.updateMenuSelection === 'function') {
                        window.updateMenuSelection(null);
                    }
                    if (window.menuFilters) {
                        window.menuFilters.menuId = null;
                        window.menuFilters.categoryId = null;
                        window.menuFilters.search = '';
                    }
                    $('#menu-filter').val('');
                    $('#category-filter').val('');
                    $('#products-search').val('');
                    if (typeof window.updateCategoryCounts === 'function') {
                        window.updateCategoryCounts();
                    }
                } else if (action === 'bill') {
                    // For bill without payment/print, show order detail
                    if (orderId) {
                        // Show order detail modal if available
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('showOrderDetail', { id: orderId, fromPos: true });
                        } else {
                            // Fallback: redirect to order detail
                            window.location.href = "{{ route('orders.show', ['order' => ':id']) }}".replace(':id', orderId);
                        }
                    }

                    // Reset POS
                    window.resetPosState();

                    // Refresh POS
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('refreshPos');
                    }

                    // Show success message
                    if (response.message) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                } else {
                    // Default: show success and reset
                    if (response.message) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', response.message);
                        } else {
                            alert(response.message);
                        }
                    }

                    window.resetPosState();
                }
            } else {
                // Show error message
                const errorMsg = response.message || 'Error saving order';
                if (typeof window.showToast === 'function') {
                    window.showToast('error', errorMsg);
                } else {
                    alert(errorMsg);
                }
                if (isKotAction) {
                    window.__posKotSubmissionLocked = false;
                }
            }

            keepActionLockedAfterComplete = false;
            window.__posOrderActionInProgress = false;
            window.__setPosOrderActionButtonsDisabled(false);
            window.__togglePosButtonLoading(buttonId, buttonTextId, buttonLoadingId, false);
        },
        error: function(xhr) {
            let errorMessage = 'Error saving order';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    const error = JSON.parse(xhr.responseText);
                    if (error.message) {
                        errorMessage = error.message;
                    }
                } catch (e) {
                    // Use default error message
                }
            }

            // Show error using toast if available
            if (typeof window.showToast === 'function') {
                window.showToast('error', errorMessage);
            } else {
                alert(errorMessage);
            }

            if (isKotAction) {
                window.__posKotSubmissionLocked = false;
            }
        },
        complete: function() {
            // Hide loading state
            if (buttonId) {
                if (keepActionLockedAfterComplete && isKotAction) {
                    // Keep KOT locked after success; redirect/next page resets naturally.
                    window.__posOrderActionInProgress = true;
                    window.__setPosOrderActionButtonsDisabled(true);
                } else {
                    window.__posOrderActionInProgress = false;
                    window.__setPosOrderActionButtonsDisabled(false);
                    window.__togglePosButtonLoading(buttonId, buttonTextId, buttonLoadingId, false);
                }
            }
        }
    });
};

// Update Functions
window.updateNoOfPax = function(value) {
    window.posState.noOfPax = parseInt(value) || 1;
};

window.updateSelectWaiter = function(value) {
    window.posState.selectWaiter = parseInt(value) || null;
};

/**
 * Update waiter selection and persist to backend via API
 */
window.updateWaiterSelection = function(waiterId) {
    const orderId = typeof window.getCurrentPosOrderId === 'function'
        ? window.getCurrentPosOrderId()
        : (window.posState.orderID || window.posState.orderDetail?.id || null);

    // Update local state
    window.posState.selectWaiter = parseInt(waiterId) || null;

    // If no order exists yet, waiter will be set on order creation
    if (!orderId) {
        console.log('No order ID found, waiter will be set on order creation');
        return;
    }

    // Check if jQuery is available
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Update via API
    $.easyAjax({
        url: `/ajax/pos/orders/${orderId}/update-waiter`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            waiter_id: waiterId
        },
        success: function(response) {
            if (response.success) {
                if (!window.posState.orderID && orderId) {
                    window.posState.orderID = orderId;
                }
                showToast('success', response.message || '@lang("messages.waiterUpdated")');
            }
        },
        error: function(xhr) {
            console.error('Error updating waiter:', xhr);
            showToast('error', xhr.responseJSON?.message || 'Failed to update waiter');

            // Revert select to previous value on error
            const order = window.posState.orderDetail;
            if (order && order.waiter_id) {
                $('#waiter-select').val(order.waiter_id);
                window.posState.selectWaiter = order.waiter_id;
            }
        }
    });
};

/**
 * Delivery executive: keep posState in sync; persist immediately when an order already exists (Livewire Pos::saveDeliveryExecutive parity).
 */
window.updateDeliveryExecutiveSelection = function(deliveryExecutiveId) {
    const orderId = typeof window.getCurrentPosOrderId === 'function'
        ? window.getCurrentPosOrderId()
        : (window.posState.orderID || window.posState.orderDetail?.id || null);

    const parsed = deliveryExecutiveId === '' || deliveryExecutiveId === null || deliveryExecutiveId === undefined
        ? null
        : (parseInt(String(deliveryExecutiveId), 10) || null);
    window.posState.selectedDeliveryExecutive = parsed;

    if (!orderId) {
        return;
    }

    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    $.easyAjax({
        url: "{{ route('ajax.pos.update-delivery-executive', ['orderId' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderId),
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            delivery_executive_id: parsed
        },
        success: function(response) {
            if (response.success) {
                if (!window.posState.orderID && orderId) {
                    window.posState.orderID = orderId;
                }
                if (window.posState.orderDetail) {
                    window.posState.orderDetail.delivery_executive_id = response.delivery_executive_id ?? null;
                }
                if (typeof showToast === 'function') {
                    showToast('success', response.message || @json(__('messages.deliveryExecutiveAssigned')));
                }
            }
        },
        error: function(xhr) {
            console.error('Error updating delivery executive:', xhr);
            if (typeof showToast === 'function') {
                showToast('error', xhr.responseJSON?.message || 'Failed to update delivery executive');
            }
            const order = window.posState.orderDetail;
            const prev = order && order.delivery_executive_id != null ? String(order.delivery_executive_id) : '';
            $('#delivery-executive-select, #selectDeliveryExecutiveInput').val(prev);
            window.posState.selectedDeliveryExecutive = order && order.delivery_executive_id
                ? parseInt(String(order.delivery_executive_id), 10)
                : null;
        }
    });
};

window.updateSelectDeliveryExecutive = function(value) {
    window.updateDeliveryExecutiveSelection(value);
};

window.updateDeliveryFee = function(value) {
    window.posState.deliveryFee = parseFloat(value) || 0;
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
};

// Modal Functions
window.showOrderTypeModal = function() {
    // This should open the order type selection modal
    // For now, redirect to order type selection or show modal
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        // Check if modal exists, if not redirect
        if ($('#orderTypeSelectionModal').length) {
            $('#orderTypeSelectionModal').show();
        } else {
            // Redirect to POS index to select order type
            window.location.href = "{{ route('pos.index') }}";
        }
    }
};

window.showAddCustomerModal = function(customerId = null) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const dispatchModal = function() {
        if (typeof Livewire === 'undefined' || !Livewire.dispatch) {
            return false;
        }

        const orderID = window.posState.orderID || {{ optional($orderDetail)->id ?? 'null' }};
        Livewire.dispatch('showAddCustomerModal', {
            id: orderID,
            customerId: customerId,
            fromPos: true
        });
        return true;
    };

    if (dispatchModal()) {
        return;
    }

    // Livewire can load asynchronously; retry briefly before failing silently.
    let attempts = 0;
    const timer = setInterval(function() {
        attempts++;
        if (dispatchModal() || attempts >= 10) {
            clearInterval(timer);
        }
    }, 200);
};

// Listen for customer updates from Livewire (with resilient binding)
if (!window.__posCustomerListenersBound) {
    window.__posCustomerListenersBound = false;
}

window.bindPosCustomerListeners = function() {
    if (window.__posCustomerListenersBound) {
        return true;
    }
    if (typeof Livewire === 'undefined' || !Livewire.on) {
        return false;
    }

    const handleCustomerUpdate = (event) => {
        // Support both direct payload and wrapped payload shapes
        const payload = (event && event.customer) ? event : (event && event[0] ? event[0] : null);
        const customer = payload?.customer || null;

        if (customer && customer.id) {
            window.posState.customerId = customer.id;
            window.posState.customer = customer;
            updateCustomerDisplay(customer);
            window.refreshAutoStampPreviews?.();

            // Auto-open loyalty modal when customer is selected and cart already has items
            try {
                const cartSummary = window.getNonFreeCartSummary?.() || {};
                const hasExactlyOneNonFreeQty = (cartSummary.nonFreeQtyTotal || 0) === 1;
                if (
                    hasExactlyOneNonFreeQty &&
                    window.posState.loyaltyEnabled &&
                    (window.posState.loyaltyPointsRedeemed || 0) === 0 &&
                    (window.posState.subTotal || 0) > 0 &&
                    typeof window.openLoyaltyRedemptionModal === 'function'
                ) {
                    window.openLoyaltyRedemptionModal();
                }
            } catch (e) {
                console.warn('Auto loyalty modal on customer select failed:', e);
            }
        }
    };

    Livewire.on('customerSelected', handleCustomerUpdate);
    Livewire.on('customerAdded', handleCustomerUpdate);
    window.__posCustomerListenersBound = true;
    return true;
};

(function ensurePosCustomerListeners() {
    if (window.bindPosCustomerListeners()) {
        return;
    }
    let tries = 0;
    const timer = setInterval(function() {
        tries++;
        if (window.bindPosCustomerListeners() || tries >= 20) {
            clearInterval(timer);
        }
    }, 150);
})();

document.addEventListener('livewire:navigated', function() {
    window.bindPosCustomerListeners?.();
});

/**
 * Update customer display in all views (kot_items, order_items, order_detail)
 */
function updateCustomerDisplay(customer) {

    // Update all customer display containers
    const customerContainers = document.querySelectorAll('.customer-display-container');

    if (customerContainers.length === 0) {
        console.warn('No customer display containers found on page');
        return;
    }

    customerContainers.forEach((container, index) => {
        const customerInfoSection = container.querySelector('#customer-info-section');
        const addCustomerSection = container.querySelector('#add-customer-section');
        const customerNameElement = container.querySelector('#customer-name');

        if (!customerInfoSection || !addCustomerSection) {
            console.warn(`Customer display sections not found in container ${index}`, container);
            return;
        }

        if (customer && customer.id) {
            // Update customer name
            if (customerNameElement) {
                customerNameElement.textContent = customer.name;
            }

            // Update edit button onclick if it exists
            const editButton = customerInfoSection.querySelector('button[onclick*="showAddCustomerModal"]');
            if (editButton) {
                editButton.setAttribute('onclick', `showAddCustomerModal(${customer.id})`);
            }

            // Show customer info, hide add customer section
            customerInfoSection.style.display = 'flex';
            addCustomerSection.style.display = 'none';

            console.log(`Updated customer display in container ${index}: ${customer.name}`);
        } else {
            // Hide customer info, show add customer section
            customerInfoSection.style.display = 'none';
            addCustomerSection.style.display = 'block';

            console.log(`Cleared customer display in container ${index}`);
        }
    });

    // Show success message
    if (customer && customer.id) {
        showToast('success', 'Customer updated successfully');
    }
}

/**
 * Update table display in all views (kot_items, order_items, order_detail)
 */
function updateTableDisplay(table) {
    console.log('Updating table display:', table);

    // Update all table display containers
    const tableContainers = document.querySelectorAll('.table-display-container');

    if (tableContainers.length === 0) {
        console.warn('No table display containers found on page');
        return;
    }

    tableContainers.forEach((container, index) => {
        const tableInfoSection = container.querySelector('#table-info-section');
        const setTableSection = container.querySelector('#set-table-section');
        const tableCodeElement = container.querySelector('#table-code');

        if (!tableInfoSection || !setTableSection) {
            console.warn(`Table display sections not found in container ${index}`, container);
            return;
        }

        if (table && table.id && table.table_code) {
            // Update table code
            if (tableCodeElement) {
                tableCodeElement.textContent = table.table_code;
            }

            // Show table info, hide set table button
            tableInfoSection.style.display = 'flex';
            setTableSection.style.display = 'none';

            console.log(`Updated table display in container ${index}: ${table.table_code}`);
        } else {
            // Hide table info, show set table button
            tableInfoSection.style.display = 'none';
            setTableSection.style.display = 'block';

            console.log(`Cleared table display in container ${index}`);
        }
    });

    // Show success message
    if (table && table.id) {
        showToast('success', 'Table updated successfully');
    }
}


window.showKotNoteModal = function(itemKey = null, currentNote = '') {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        if (itemKey) {
            // Item-specific note
            window.posState.pendingNoteItemKey = itemKey;
            $('#itemNoteInput').val(currentNote);
            $('#itemNoteModal').show();
        } else {
            // Order note
            $('#orderNote').val(window.posState.orderNote || '');
            $('#kotNoteModal').show();
        }
    }
};

window.showAddDiscountModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        // Block manual discount when loyalty discount is active (mirror SaaS / Livewire behavior)
        if ((window.posState.loyaltyDiscountAmount || 0) > 0) {
            window.showToast?.('error', '{{ __("Loyalty discount already applied.") }}');
            return;
        }

        // Reset discount form
        $('#discountType').val(window.posState.discountType || 'fixed');
        $('#discountValue').val(window.posState.discountValue || '');
        $('#discountModal').show();
    }
};

window.removeCurrentDiscount = function() {
    // When viewing order detail (billed/payment_due), clear discount via API and update UI without reload
    const orderId = (window.posState && (window.posState.orderID || (window.posState.orderDetail && window.posState.orderDetail.id))) || null;
    const showOrderDetail = window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true');
    if (orderId && showOrderDetail) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            return;
        }
        const updateDiscountUrl = "{{ route('ajax.pos.update-order-discount', ['orderId' => 0]) }}".replace(/\/orders\/0\//, '/orders/' + orderId + '/');
        $.easyAjax({
            url: updateDiscountUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                discount_type: '',
                discount_value: 0
            },
            success: function(response) {
                if (response.success) {
                    if (typeof showToast === 'function') showToast('success', response.message);
                    // Ensure bill payload cannot carry stale discount values.
                    if (window.posState) {
                        window.posState.discountType = null;
                        window.posState.discountValue = null;
                        window.posState.discountAmount = 0;
                    }
                    if (response.order && typeof window.updateOrderDetailTotalsFromResponse === 'function') {
                        window.updateOrderDetailTotalsFromResponse(response.order, null);
                    }
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.somethingWentWrong") }}';
                if (typeof showToast === 'function') showToast('error', msg); else alert(msg);
            }
        });
        return;
    }

    window.posState.discountType = null;
    window.posState.discountValue = null;
    window.posState.discountAmount = 0;
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
};

/**
 * Update order detail panel totals from discount API response (subtotal, discount, tax, total) without reload.
 */
window.updateOrderDetailTotalsFromResponse = function(order, discountInfo) {
    if (!order) return;
    var subEl = document.getElementById('order-detail-subtotal') || document.getElementById('subtotal-display');
    var discountRow = document.getElementById('discount-row');
    var discountTypeDisplay = document.getElementById('discount-type-display');
    var discountAmountEl = document.getElementById('discount-display');
    var taxEl = document.getElementById('order-detail-total-tax') || document.getElementById('total-tax-display');
    var totalEl = document.getElementById('order-detail-total') || document.getElementById('total-display');

    if (subEl && order.sub_total_formatted !== undefined) subEl.textContent = order.sub_total_formatted;

    var resolvedDiscountType = null;
    var resolvedDiscountValue = null;
    if (discountInfo && discountInfo.type) {
        resolvedDiscountType = discountInfo.type;
        resolvedDiscountValue = discountInfo.value;
    } else if (order.discount_type) {
        resolvedDiscountType = order.discount_type;
        resolvedDiscountValue = order.discount_value;
    }

    if (discountRow) {
        var hasDiscount = parseFloat(order.discount_amount || 0) > 0;
        discountRow.style.display = hasDiscount ? 'flex' : 'none';

        if (discountTypeDisplay && resolvedDiscountType && hasDiscount) {
            var typeLabel = '';
            if (resolvedDiscountType === 'percent') typeLabel = '(' + (resolvedDiscountValue || '') + '%)';
            discountTypeDisplay.textContent = typeLabel;
        } else if (discountTypeDisplay) {
            discountTypeDisplay.textContent = '';
        }

        if (discountAmountEl) discountAmountEl.textContent = hasDiscount ? '-' + (order.discount_amount_formatted || '0') : '';
    }

    if (taxEl && order.total_tax_amount_formatted !== undefined) taxEl.textContent = order.total_tax_amount_formatted;
    if (totalEl && order.total_formatted !== undefined) totalEl.textContent = order.total_formatted;

    // Update per-tax rows (order-level tax mode) when tax base changes
    var taxBaseNumber = parseFloat(order.tax_base || 0);
    if (!isNaN(taxBaseNumber)) {
        document.querySelectorAll('#order-items-container [data-tax-percent]').forEach(function(taxRow) {
            var taxPercent = parseFloat(taxRow.getAttribute('data-tax-percent') || 0);
            if (isNaN(taxPercent)) return;
            var amountEl = taxRow.querySelector('.tax-amount-display');
            if (!amountEl || typeof window.formatCurrency !== 'function') return;
            var taxAmount = (taxPercent / 100) * taxBaseNumber;
            amountEl.textContent = window.formatCurrency(taxAmount);
        });
    }

    if (window.posState) {
        window.posState.subTotal = parseFloat(order.sub_total) || 0;
        window.posState.discountAmount = parseFloat(order.discount_amount) || 0;
        window.posState.totalTaxAmount = parseFloat(order.total_tax_amount) || 0;
        window.posState.total = parseFloat(order.total) || 0;
        if (order.tax_base !== undefined) {
            window.posState.taxBase = parseFloat(order.tax_base) || 0;
        }
        if (resolvedDiscountType) {
            window.posState.discountType = resolvedDiscountType;
            window.posState.discountValue = resolvedDiscountValue;
        } else {
            window.posState.discountType = null;
            window.posState.discountValue = null;
        }
    }
};

window.removeExtraCharge = function(chargeId, orderType) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Check if user has permission
    @if (!user_can('Update Order'))
        alert('@lang("messages.permissionDenied")');
        return;
    @endif

    const doRemove = function() {
        const orderId = (window.posState && (window.posState.orderID || (window.posState.orderDetail && window.posState.orderDetail.id))) || {{ optional($orderDetail)->id ?? 'null' }};

        // If there's an existing order (order detail view), update via API and reload to show new totals
        if (orderId && window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true')) {
            const removeChargeUrl = "{{ route('ajax.pos.remove-extra-charge', ['orderId' => 0, 'chargeId' => 0]) }}".replace(/\/orders\/0\/remove-charge\/0/, '/orders/' + orderId + '/remove-charge/' + chargeId);
            $.easyAjax({
                url: removeChargeUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_type: orderType
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof showToast === 'function') {
                            showToast('success', response.message || '{{ __("messages.extraChargeRemoved") }}');
                        }
                        // Keep billing payload in sync: removed charge must not be posted again.
                        if (window.posConfig && Array.isArray(window.posConfig.extraCharges)) {
                            window.posConfig.extraCharges = window.posConfig.extraCharges.filter(function(charge) {
                                return String(charge.id) !== String(chargeId);
                            });
                        }
                        // Remove charge row - try both selectors for compatibility
                        var row = document.querySelector('[data-order-detail-charge-id="' + chargeId + '"]');
                        if (!row) {
                            row = document.querySelector('[data-charge-id="' + chargeId + '"]');
                        }
                        if (row) row.remove();

                        if (response.order && typeof window.updateOrderDetailTotalsFromResponse === 'function') {
                            window.updateOrderDetailTotalsFromResponse(response.order, null);
                        }
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.somethingWentWrong") }}';
                    if (typeof showToast === 'function') {
                        showToast('error', error);
                    } else {
                        alert(error);
                    }
                }
            });
            return;
        }

        // For new orders, remove from posConfig.extraCharges array
        if (window.posConfig && window.posConfig.extraCharges) {
            const chargeIndex = window.posConfig.extraCharges.findIndex(c => c.id == chargeId);

            if (chargeIndex > -1) {
                const removedCharge = window.posConfig.extraCharges[chargeIndex];

                // Remove from array
                window.posConfig.extraCharges.splice(chargeIndex, 1);

                console.log('Removed extra charge:', removedCharge.charge_name);

                // Remove the DOM element immediately for better UX
                $(`#extra-charges-container [data-charge-id="${chargeId}"]`).fadeOut(200, function() {
                    $(this).remove();

                    // Hide container if no more charges
                    if (window.posConfig.extraCharges.length === 0) {
                        $('#extra-charges-container').hide();
                    }
                });

                // Recalculate totals
                if (typeof window.calculateTotal === 'function') {
                    window.calculateTotal();
                }

                // Show success message
                if (typeof window.showToast === 'function') {
                    window.showToast('success', '@lang("messages.extraChargeRemoved")');
                }
            } else {
                console.warn('Extra charge not found:', chargeId);
            }
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '@lang("messages.removeExtraCharge")?',
            text: '@lang("messages.removeExtraChargeMessage")',
            showCancelButton: true,
            confirmButtonText: '@lang("modules.order.remove")',
        }).then((result) => {
            if (result.isConfirmed) {
                doRemove();
            }
        });
    } else {
        // Fallback to native confirm
        if (confirm('@lang("messages.removeExtraCharge")')) {
            doRemove();
        }
    }
};

// Reset POS State Function
window.resetPosState = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Clear cart
    window.posState.orderItemList = {};
    window.posState.orderItemQty = {};
    window.posState.orderItemAmount = {};
    window.posState.orderItemVariation = {};
    window.posState.itemModifiersSelected = {};
    window.posState.orderItemModifiersPrice = {};
    window.posState.itemNotes = {};
    window.posState.subTotal = 0;
    window.posState.total = 0;
    window.posState.discountAmount = 0;
    window.posState.discountType = null;
    window.posState.discountValue = null;
    window.posState.discountedTotal = 0;
    window.posState.deliveryFee = 0;
    window.posState.tipAmount = 0;
    window.posState.totalTaxAmount = 0;
    window.posState.orderItemTaxDetails = {};
    window.posState.orderNote = null;
    window.posState.orderID = null;
    window.posState.orderDetail = null;
    window.posState.showOrderDetail = false;
    window.posState.orderNumber = '';
    window.posState.formattedOrderNumber = '';
    window.posState.orderStatus = 'confirmed';
    // Reset loyalty points and stamps context for new KOT / fresh cart
    window.posState.loyaltyPointsRedeemed = 0;
    window.posState.loyaltyDiscountAmount = 0;
    window.posState.stampDiscountAmount = 0;
    window.posState.hasFreeStampItems = false;
    window.currentKotId = null;

    // Reset menu filters
    if (window.menuFilters) {
        window.menuFilters.menuId = null;
        window.menuFilters.categoryId = null;
        window.menuFilters.search = '';
        // Keep the original limit value
        if (!window.menuFilters.originalLimit) {
            window.menuFilters.originalLimit = window.menuFilters.limit || 75;
        }
        window.menuFilters.limit = window.menuFilters.originalLimit;
    } else {
        // Initialize menuFilters if it doesn't exist
        window.menuFilters = {
            menuId: null,
            categoryId: null,
            search: '',
            limit: 75
        };
    }

    // Clear menu selection UI
    if (typeof window.updateMenuSelection === 'function') {
        window.updateMenuSelection(null);
    }

    // Clear category selection
    $('#category-filter').val('');

    // Clear search input
    $('#products-search').val('');

    // Force clear the order items container immediately - try multiple selectors
    let $orderItemsContainer = $('#order-items-container .flex.flex-col.rounded.gap-1');

    if ($orderItemsContainer.length === 0) {
        // Try alternative selectors - look inside the scrollable area
        const $scrollArea = $('#order-items-container .flex-1.overflow-y-auto, #order-items-container .overflow-y-auto');
        if ($scrollArea.length > 0) {
            $orderItemsContainer = $scrollArea.find('div.flex.flex-col.rounded.gap-1');
        }
    }

    if ($orderItemsContainer.length === 0) {
        // Try finding directly in order-items-container
        $orderItemsContainer = $('#order-items-container').find('div.flex.flex-col.rounded.gap-1');
    }

    // Clear the container if found
    if ($orderItemsContainer.length > 0) {
        $orderItemsContainer.html(`
            <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                <div class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <div class="text-gray-500 dark:text-gray-400 text-base">No items added</div>
                </div>
            </div>
        `);
    } else {
        // If container not found, try to clear all items with data-item-key attribute
        $('#order-items-container [data-item-key]').remove();
    }

    // Update UI - use setTimeout to ensure state is cleared first
    setTimeout(function() {
        if (typeof window.updateOrderItemsContainer === 'function') {
            window.updateOrderItemsContainer();
        }
        if (typeof window.calculateTotal === 'function') {
            window.calculateTotal();
        }
        if (typeof window.updateTotalsDisplay === 'function') {
            window.updateTotalsDisplay();
        }
    }, 50);

    // Reload menu items via AJAX to show all items
    if (typeof window.loadMenuItems === 'function') {
        window.loadMenuItems();
    }

    // Update category counts
    if (typeof window.updateCategoryCounts === 'function') {
        window.updateCategoryCounts();
    }

    // Dispatch reset event if Livewire is available
    if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('resetPos');
    }
};

// Toast notification helper
window.showToast = function(type, message) {
    // Use SweetAlert if available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'error' ? 'error' : 'success',
            title: type === 'error' ? 'Error' : 'Success',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        // Fallback to alert
        alert(message);
    }
};

// Save Functions
window.saveKotNote = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        const note = $('#orderNote').val();
        window.posState.orderNote = note;
        if (typeof window.closeKotNoteModal === 'function') {
            window.closeKotNoteModal();
        }
    }
};

window.saveItemNote = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const note = ($('#itemNoteInput').val() || '').toString();

    // If opened by a Livewire/Alpine component (e.g. x-pos.item-note), use callback and let Livewire persist it
    if (typeof window.__posItemNoteSave === 'function') {
        try {
            window.__posItemNoteSave(note);
        } finally {
            if (typeof window.__posItemNoteAfterSave === 'function') {
                window.__posItemNoteAfterSave(note);
            }
            window.__posItemNoteSave = null;
            window.__posItemNoteAfterSave = null;
        }

        if (typeof window.closeItemNoteModal === 'function') {
            window.closeItemNoteModal();
        } else {
            $('#itemNoteModal').hide();
        }
        return;
    }

    // Otherwise, fallback to client-side POS state (used by JS cart mode)
    const itemKey = window.posState.pendingNoteItemKey;
    if (!itemKey) {
        $('#itemNoteError').text('Item not found').show();
        return;
    }

    window.posState.itemNotes = window.posState.itemNotes || {};
    window.posState.itemNotes[itemKey] = note;

    if (typeof window.closeItemNoteModal === 'function') {
        window.closeItemNoteModal();
    } else {
        $('#itemNoteModal').hide();
    }

    // Update UI
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
};

/**
 * Open the POS item note modal with custom save handler (used by Livewire/Alpine item note component)
 */
window.openPosItemNoteModal = function(currentNote, onSave, onAfterSave) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }
    window.__posItemNoteSave = typeof onSave === 'function' ? onSave : null;
    window.__posItemNoteAfterSave = typeof onAfterSave === 'function' ? onAfterSave : null;
    $('#itemNoteInput').val((currentNote || '').toString());
    $('#itemNoteError').hide();
    $('#itemNoteModal').show();
};

window.saveDiscount = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const discountValue = parseFloat($('#discountValue').val()) || 0;
    const discountType = $('#discountType').val();

    if (discountValue < 0) {
        $('#discountValueError').text('Discount value must be positive').show();
        return;
    }

    // When viewing order detail (billed/payment_due), persist discount via API and update UI without reload
    const orderId = (window.posState && (window.posState.orderID || (window.posState.orderDetail && window.posState.orderDetail.id))) || null;
    const showOrderDetail = window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true');
    if (orderId && showOrderDetail) {
        const updateDiscountUrl = "{{ route('ajax.pos.update-order-discount', ['orderId' => 0]) }}".replace(/\/orders\/0\//, '/orders/' + orderId + '/');
        $.easyAjax({
            url: updateDiscountUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                discount_type: discountType,
                discount_value: discountValue
            },
            success: function(response) {
                if (response.success) {
                    if (typeof showToast === 'function') showToast('success', response.message);
                    if (typeof window.closeDiscountModal === 'function') window.closeDiscountModal();
                    if (typeof window.updateOrderDetailTotalsFromResponse === 'function') {
                        window.updateOrderDetailTotalsFromResponse(response.order, discountValue > 0 ? { type: discountType, value: discountValue } : null);
                    }
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.somethingWentWrong") }}';
                if (typeof showToast === 'function') showToast('error', msg); else alert(msg);
            }
        });
        return;
    }

    window.posState.discountValue = discountValue;
    window.posState.discountType = discountType;

    // Calculate discount amount
    if (discountType === 'percent') {
        window.posState.discountAmount = (window.posState.subTotal * discountValue) / 100;
    } else {
        window.posState.discountAmount = Math.min(discountValue, window.posState.subTotal);
    }

    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    if (typeof window.closeDiscountModal === 'function') {
        window.closeDiscountModal();
    }
    if (typeof window.updateOrderItems === 'function') {
        window.updateOrderItems();
    }
};

// Load Functions
window.loadExtraCharges = function(orderTypeSlug) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }
    $.easyAjax({
        url: "{{ route('ajax.pos.extra-charges', ['orderType' => ':orderType']) }}".replace(':orderType', orderTypeSlug),
        type: "GET",
        success: function(response) {
            // Store in posConfig to match updateTotalsDisplay expectations
            if (!window.posConfig) {
                window.posConfig = {};
            }
            window.posConfig.extraCharges = response;

            // Recalculate totals which will update the display
            if (typeof window.calculateTotal === 'function') {
                window.calculateTotal();
            }
        }
    });
};

window.loadOrderItems = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    $.easyAjax({
        url: "{{ route('pos.index') }}",
        type: "GET",
        data: {
            orderTypeId: window.posState.orderTypeId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // Update order items container using jQuery
            $('#order-items-container').html($(response).find('#order-items-container').html());
            if (typeof window.setGlobalOrderActionLock === 'function' && (window.__posOrderActionInProgress || window.__posPrintOrderInProgress)) {
                window.setGlobalOrderActionLock(true);
            }
        }
    });
};

window.refreshOrderPanelsFromServer = function(options = {}) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    if (window.__refreshOrderPanelsInProgress) {
        return;
    }

    const requestUrl = options.url || window.location.href;
    window.__refreshOrderPanelsInProgress = true;

    $.ajax({
        url: requestUrl,
        method: 'GET',
        cache: false,
        dataType: 'html',
        success: function(html) {
            const $newPanel = $(html).find('#order-items-container');
            if ($newPanel.length) {
                $('#order-items-container').html($newPanel.html());
                if (typeof window.setGlobalOrderActionLock === 'function' && (window.__posOrderActionInProgress || window.__posPrintOrderInProgress)) {
                    window.setGlobalOrderActionLock(true);
                }
                // Keep order_items totals in sync after partial refresh.
                if (document.getElementById('subtotal-display') && typeof window.calculateTotal === 'function') {
                    window.calculateTotal();
                }
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess();
                }
                return;
            }

            if (typeof options.onError === 'function') {
                options.onError();
            }
        },
        error: function(xhr) {
            console.error('POS panel refresh failed', xhr?.status, xhr?.statusText);
            if (typeof options.onError === 'function') {
                options.onError();
            }
        },
        complete: function() {
            window.__refreshOrderPanelsInProgress = false;
        }
    });
};

window.removeItemKeyFromPosState = function(itemKey) {
    if (!itemKey || !window.posState) {
        return;
    }

    const cleanKey = String(itemKey).replace(/"/g, '');
    const props = [
        'orderItemList',
        'orderItemQty',
        'orderItemAmount',
        'orderItemVariation',
        'itemModifiersSelected',
        'orderItemModifiersPrice',
        'itemNotes',
        'orderItemTaxDetails'
    ];

    props.forEach(function(prop) {
        if (window.posState[prop] && Object.prototype.hasOwnProperty.call(window.posState[prop], cleanKey)) {
            delete window.posState[prop][cleanKey];
        }
    });
};

window.removeOrderItemFromPosStateById = function(itemId) {
    if (!window.posState || !window.posState.orderItemList) {
        return;
    }

    const targetId = String(itemId);
    Object.keys(window.posState.orderItemList).forEach(function(key) {
        if (key === `order_item_${targetId}` || key.endsWith(`_${targetId}`)) {
            window.removeItemKeyFromPosState(key);
        }
    });
};

// Calculate Total - mirrors PHP calculateTotal() logic
window.calculateTotal = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.warn('jQuery not available yet for calculateTotal');
        return;
    }

    const normalizeTaxesList = function(rawTaxes) {
        if (!rawTaxes) {
            return [];
        }
        if (Array.isArray(rawTaxes)) {
            return rawTaxes;
        }
        if (typeof rawTaxes === 'object') {
            return Object.values(rawTaxes).filter(function(tax) {
                return tax && typeof tax === 'object';
            });
        }
        return [];
    };

    const normalizeTaxBreakup = function(rawBreakup) {
        const normalized = {};
        if (!rawBreakup) {
            return normalized;
        }

        // Format A: { "GST": {percent/rate, amount}, ... }
        if (!Array.isArray(rawBreakup) && typeof rawBreakup === 'object') {
            Object.keys(rawBreakup).forEach(function(key) {
                const taxInfo = rawBreakup[key] || {};
                const taxName = taxInfo.tax_name || taxInfo.name || key;
                const taxPercent = Number(taxInfo.percent ?? taxInfo.rate ?? taxInfo.tax_percent ?? 0);
                const taxAmount = Number(taxInfo.amount ?? 0);
                if (taxName) {
                    normalized[taxName] = {
                        percent: taxPercent,
                        amount: taxAmount
                    };
                }
            });
            return normalized;
        }

        // Format B: [{name/tax_name, percent/rate, amount}, ...]
        if (Array.isArray(rawBreakup)) {
            rawBreakup.forEach(function(taxInfo) {
                if (!taxInfo || typeof taxInfo !== 'object') {
                    return;
                }
                const taxName = taxInfo.tax_name || taxInfo.name;
                if (!taxName) {
                    return;
                }
                normalized[taxName] = {
                    percent: Number(taxInfo.percent ?? taxInfo.rate ?? taxInfo.tax_percent ?? 0),
                    amount: Number(taxInfo.amount ?? 0)
                };
            });
        }

        return normalized;
    };

    let total = 0;
    let subTotal = 0;
    let totalTaxAmount = 0;
    let orderItemTaxDetails = {};

    // If cart is empty, reset status
    if (!window.posState.orderItemList || Object.keys(window.posState.orderItemList).length === 0) {
        window.posState.total = 0;
        window.posState.subTotal = 0;
        window.posState.totalTaxAmount = 0;
        window.posState.orderItemTaxDetails = {};
        window.posState.totalsPreCalculated = false;
        if (typeof window.updateTotalsDisplay === 'function') {
            window.updateTotalsDisplay();
        }
        return;
    }

    // Get configuration
    const taxMode = window.posConfig?.taxMode || '{{ $taxMode ?? "order" }}';
    const isInclusive = window.posConfig?.taxInclusive ?? {{ $restaurant->tax_inclusive ?? 0 ? 'true' : 'false' }};
    const taxes = window.posConfig?.taxes || [];

    if (taxMode === 'item' && window.posState.orderItemAmount) {
        // Calculate tax details for each item
        // For client-side added items, apply all taxes from config
        for (const key in window.posState.orderItemAmount) {
            const item = window.posState.orderItemList[key];
            if (!item) continue;

            // Skip free stamp items completely from tax calculations (tt Livewire parity)
            const itemNote = window.posState.itemNotes?.[key] || '';
            if (window.isFreeStampItemByMeta && window.isFreeStampItemByMeta(key, item, itemNote)) {
                continue;
            }

            const qty = window.posState.orderItemQty[key] || 1;
            const basePrice = window.posState.orderItemVariation[key]?.price || item.price || 0;
            const modifierPrice = window.posState.orderItemModifiersPrice[key] || 0;
            const itemPriceWithModifiers = basePrice + modifierPrice;
            const lineAmount = parseFloat(window.posState.orderItemAmount[key] || 0);
            // tt parity: compute taxes on effective discounted per-unit amount.
            const discountedPerUnit = qty > 0 ? (lineAmount / qty) : itemPriceWithModifiers;

            // Item tax mode must use item-specific taxes only.
            // If item has no tax assignment, do not apply any tax.
            const normalizedItemTaxes = normalizeTaxesList(item.taxes);
            const itemTaxes = (taxMode === 'item')
                ? (normalizedItemTaxes.length > 0 ? normalizedItemTaxes : [])
                : taxes;
            let itemTaxAmount = 0;
            let itemTaxPercent = 0;
            let taxBreakup = {};

            if (itemTaxes.length > 0) {
                // Calculate total tax percent first (needed for inclusive tax calculation)
                const totalTaxPercent = itemTaxes.reduce((sum, tax) => sum + parseFloat(tax.tax_percent || 0), 0);

                for (const tax of itemTaxes) {
                    const taxPercent = parseFloat(tax.tax_percent || 0);

                    let taxAmount = 0;
                    if (isInclusive) {
                        // Tax is included in price: tax = (price * rate) / (100 + total_rate)
                        taxAmount = (discountedPerUnit * taxPercent) / (100 + totalTaxPercent);
                    } else {
                        // Tax is added to price: tax = price * rate / 100
                        taxAmount = (discountedPerUnit * taxPercent) / 100;
                    }

                    itemTaxAmount += taxAmount;
                    itemTaxPercent += taxPercent;
                    taxBreakup[tax.tax_name] = {
                        percent: taxPercent,
                        amount: taxAmount
                    };
                }
            }

            // Keep persisted tax details for old/existing orders when item tax relation is unavailable on client.
            if (itemTaxes.length === 0 && window.posState.orderItemTaxDetails && window.posState.orderItemTaxDetails[key]) {
                const persistedTax = window.posState.orderItemTaxDetails[key];
                const persistedBreakup = normalizeTaxBreakup(persistedTax.tax_breakup);

                if (Object.keys(persistedBreakup).length > 0) {
                    let persistedTaxAmount = Number(persistedTax.tax_amount ?? persistedTax.total_tax ?? 0);
                    if (!(persistedTaxAmount > 0)) {
                        Object.keys(persistedBreakup).forEach(function(taxName) {
                            persistedTaxAmount += Number(persistedBreakup[taxName].amount || 0) * qty;
                        });
                    }

                    orderItemTaxDetails[key] = {
                        tax_amount: persistedTaxAmount,
                        tax_percent: Number(persistedTax.tax_percent || 0),
                        tax_breakup: persistedBreakup,
                        base_price: itemPriceWithModifiers,
                        discounted_price: discountedPerUnit,
                        qty: qty
                    };
                    continue;
                }
            }

            orderItemTaxDetails[key] = {
                tax_amount: itemTaxAmount * qty,
                tax_percent: itemTaxPercent,
                tax_breakup: taxBreakup,
                base_price: itemPriceWithModifiers,
                discounted_price: discountedPerUnit,
                qty: qty
            };
        }
    }

    // Calculate totals from item amounts
    for (const key in window.posState.orderItemAmount) {
        const item = window.posState.orderItemList?.[key] || {};
        const itemNote = window.posState.itemNotes?.[key] || '';

        // Skip free stamp items from subtotal/total/tax base (tt Livewire parity)
        if (window.isFreeStampItemByMeta && window.isFreeStampItemByMeta(key, item, itemNote)) {
            continue;
        }

        const value = parseFloat(window.posState.orderItemAmount[key] || 0);
        total += value;

        // For inclusive taxes, subtract tax from subtotal
        if (taxMode === 'item' && orderItemTaxDetails[key]) {
            const taxDetail = orderItemTaxDetails[key];
            if (isInclusive) {
                // For inclusive tax: subtotal = item amount - tax amount
                subTotal += (value - (taxDetail.tax_amount || 0));
            } else {
                // For exclusive tax: subtotal = item amount
                subTotal += value;
            }
        } else {
            // No item taxes or order-level taxes
            subTotal += value;
        }
    }

    window.posState.subTotal = subTotal;
    window.posState.orderItemTaxDetails = orderItemTaxDetails;
    let discountedTotal = total;

    // Apply discounts
    const discountValue = parseFloat(window.posState.discountValue || 0);
    const discountType = window.posState.discountType;

    // Loyalty redemption (when applied, regular discount is disabled)
    const loyaltyPointsRedeemed = parseInt(window.posState.loyaltyPointsRedeemed || 0);
    const loyaltyDiscountAmount = parseFloat(window.posState.loyaltyDiscountAmount || 0);

    if (discountValue > 0 && discountType) {
        if (discountType === 'percent') {
            window.posState.discountAmount = Math.round((subTotal * discountValue) / 100 * 100) / 100;
        } else if (discountType === 'fixed') {
            window.posState.discountAmount = Math.min(discountValue, subTotal);
        }
        total -= window.posState.discountAmount;
    } else {
        window.posState.discountAmount = 0;
    }

    // Apply loyalty discount AFTER regular discount calculation.
    // This mirrors SaaS behavior: if loyalty is redeemed, discount_type/value/amount are cleared.
    if (loyaltyPointsRedeemed > 0 && loyaltyDiscountAmount > 0) {
        window.posState.discountType = '';
        window.posState.discountValue = 0;
        window.posState.discountAmount = 0;
        total -= loyaltyDiscountAmount;
    }

    // Ensure total does not go negative
    total = Math.max(0, total);

    discountedTotal = total;
    window.posState.discountedTotal = discountedTotal;

    // Step 2: Calculate service charges on discountedTotal
    let serviceTotal = 0;
    const extraCharges = window.posConfig?.extraCharges || [];

    // Detect "fresh KOT" context on JS POS:
    // Only skip charges when:
    // - URL path looks like /pos/kot/{id} AND
    // - show-order-detail=true is NOT present in URL AND
    // - showOrderDetail flag in state is NOT true
    const path = (window.location && window.location.pathname) ? window.location.pathname.toString() : '';
    const href = (window.location && window.location.href) ? window.location.href.toString() : '';
    const urlShowOrderDetail = /[?&]show-order-detail=true(?:&|$)/.test(href);
    const showOrderDetailFlag = !!(window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true'));
    const effectiveShowOrderDetail = showOrderDetailFlag || urlShowOrderDetail;
    const isFreshKotContext =
        !effectiveShowOrderDetail &&
        /\/pos\/kot\/\d+/.test(path);

    // IMPORTANT:
    // For fresh/new KOT creation screen we should not apply UI-only extra charges again,
    // otherwise charges are effectively added twice (once on original bill, once on new KOT view).
    const shouldApplyCharges =
        !isFreshKotContext &&
        extraCharges &&
        extraCharges.length > 0 &&
        Object.keys(window.posState.orderItemAmount).length > 0;

    if (shouldApplyCharges) {
        for (const charge of extraCharges) {
            let chargeAmount = 0;
            if (charge.charge_type === 'percent') {
                chargeAmount = (parseFloat(charge.charge_value || 0) / 100) * discountedTotal;
            } else {
                chargeAmount = parseFloat(charge.charge_value || 0);
            }
            total += chargeAmount;
            serviceTotal += chargeAmount;
        }
    }

    // Step 3: Calculate tax_base based on setting
    const includeChargesInTaxBase = window.posConfig?.includeChargesInTaxBase ?? true;
    let taxBase = includeChargesInTaxBase ? discountedTotal + serviceTotal : discountedTotal;
    window.posState.taxBase = taxBase;

    // Step 4: Calculate taxes on tax_base
    totalTaxAmount = 0;

    if (taxMode === 'order') {
        // Order-level taxation
        if (taxes && taxes.length > 0) {
            for (const tax of taxes) {
                const taxPercent = parseFloat(tax.tax_percent || 0);
                const taxAmount = (taxPercent / 100) * taxBase;
                totalTaxAmount += taxAmount;
                total += taxAmount;
            }
        }
    } else if (taxMode === 'item') {
        // Item-level taxation
        let totalInclusiveTax = 0;
        let totalExclusiveTax = 0;

        for (const key in orderItemTaxDetails) {
            const taxDetail = orderItemTaxDetails[key];
            const taxAmount = taxDetail.tax_amount || 0;

            if (isInclusive) {
                totalInclusiveTax += taxAmount;
            } else {
                totalExclusiveTax += taxAmount;
            }
        }

        totalTaxAmount = totalInclusiveTax + totalExclusiveTax;

        // For exclusive taxes, add them to the total
        if (totalExclusiveTax > 0) {
            total += totalExclusiveTax;
        }
    }

    window.posState.totalTaxAmount = totalTaxAmount;

    // Add tip and delivery fees
    const tipAmount = parseFloat(window.posState.tipAmount || 0);
    const deliveryFee = parseFloat(window.posState.deliveryFee || 0);

    if (tipAmount > 0) {
        total += tipAmount;
    }

    if (deliveryFee > 0) {
        total += deliveryFee;
    }

    // Update state
    window.posState.total = Math.round(total * 100) / 100;

    // Mark that totals are now calculated (no longer using pre-calculated PHP values)
    window.posState.totalsPreCalculated = false;

    // Update display
    if (typeof window.updateTotalsDisplay === 'function') {
        window.updateTotalsDisplay();
    }

    updateCustomerDisplayCache();
};

// Debounce timer for customer display updates
let customerDisplayUpdateTimer = null;

// Update customer display cache - mirrors Livewire Pos.php calculateTotal() pattern
// Debounced to reduce server calls while maintaining real-time feel
function updateCustomerDisplayCache() {
    // Clear existing timer
    if (customerDisplayUpdateTimer) {
        clearTimeout(customerDisplayUpdateTimer);
    }

    // Debounce: wait 300ms after last change before updating
    customerDisplayUpdateTimer = setTimeout(function() {
        sendCustomerDisplayUpdate();
    }, 300);
}

function sendCustomerDisplayUpdate() {
    // Prepare items for customer display (matching Livewire getCustomerDisplayItems())
    const displayItems = [];
    for (const key in window.posState.orderItemList) {
        const item = window.posState.orderItemList[key];
        const qty = window.posState.orderItemQty[key] || 1;
        const variation = window.posState.orderItemVariation[key];
        const modifiersSelected = window.posState.itemModifiersSelected[key] || [];
        const modifierPrice = window.posState.orderItemModifiersPrice[key] || 0;
        const basePrice = variation ? (variation.price || item.price) : item.price;
        const totalUnitPrice = basePrice + modifierPrice;

        // Build modifiers array matching Livewire structure (name must be string - translatable can be object)
        const modifiers = [];
        if (modifiersSelected.length > 0 && window.posConfig.modifierOptions) {
            modifiersSelected.forEach(function(modifierId) {
                const modifier = window.posConfig.modifierOptions[modifierId];
                if (modifier) {
                    var mName = modifier.name;
                    if (typeof mName !== 'string' && mName !== null && typeof mName === 'object') {
                        mName = mName.en || mName[Object.keys(mName)[0]] || '';
                    }
                    modifiers.push({
                        name: (typeof mName === 'string' ? mName : '') || 'Modifier',
                        price: parseFloat(modifier.price) || 0
                    });
                }
            });
        }

        displayItems.push({
            name: item.item_name || item.name,
            qty: qty,
            price: basePrice,
            total_unit_price: totalUnitPrice,
            variation: variation ? {
                name: variation.variation || variation.name,
                price: variation.price
            } : null,
            modifiers: modifiers,
            notes: window.posState.itemNotes?.[key] || null
        });
    }

    // Prepare taxes for display (matching Livewire pattern)
    const taxes = window.posConfig?.taxes || [];
    const taxesDisplay = taxes.map(function(tax) {
        const taxPercent = parseFloat(tax.tax_percent || 0);
        const taxAmount = (taxPercent / 100) * (window.posState.discountedTotal || 0);
        return {
            name: tax.tax_name,
            percent: taxPercent,
            amount: taxAmount
        };
    });

    // Prepare extra charges for display (matching Livewire pattern)
    const extraCharges = window.posConfig?.extraCharges || [];
    const chargesDisplay = extraCharges.map(function(charge) {
        let amount = 0;
        if (charge.charge_type === 'percent') {
            amount = (parseFloat(charge.charge_value || 0) / 100) * (window.posState.discountedTotal || 0);
        } else {
            amount = parseFloat(charge.charge_value || 0);
        }
        return {
            name: charge.charge_name || charge.name,
            amount: amount
        };
    });

    // Call API to update customer display cache (matching Livewire pattern)
    // Uses server Cache + Pusher broadcast for multi-device support
    $.ajax({
        url: '{{ route("ajax.pos.update-customer-display") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            items: displayItems,
            order_number: window.posState.orderNumber,
            formatted_order_number: window.posState.formattedOrderNumber,
            sub_total: window.posState.subTotal,
            discount: window.posState.discountAmount || 0,
            total: window.posState.total,
            taxes: taxesDisplay,
            extra_charges: chargesDisplay,
            tip: window.posState.tipAmount || 0,
            delivery_fee: window.posState.deliveryFee || 0,
            order_type: window.posState.orderType,
            status: 'idle'
        },
        success: function(response) {
            // Cache updated successfully - silent
        },
        error: function(xhr, status, error) {
            // Silent fail - don't interrupt POS flow
            console.warn('Failed to update customer display:', error);
        }
    });
}

window.updateTotalsDisplay = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Use posConfig for configuration
    const currencySymbol = window.posConfig?.currencySymbol || '{{ $restaurant->currency->currency_symbol ?? "$" }}';
    const currencyCode = window.posConfig?.currencyCode || '{{ $restaurant->currency->currency_code ?? "USD" }}';
    const taxMode = window.posConfig?.taxMode || '{{ $taxMode ?? "order" }}';
    const isInclusive = window.posConfig?.taxInclusive ?? {{ $restaurant->tax_inclusive ?? 0 ? 'true' : 'false' }};
    const extraCharges = window.posConfig?.extraCharges || [];
    const hasItems = Object.keys(window.posState.orderItemList || {}).length > 0;

    // Update total items count
    const itemCount = Object.keys(window.posState.orderItemList || {}).length;
    $('#total-items-display').text(itemCount);

    const $discountBtn = $('#discount-button-container');
    $discountBtn.toggle(itemCount > 0);

    // Update subtotal
    $('#subtotal-display').text(window.formatCurrency(window.posState.subTotal || 0));

    // Update subtotal stamp badge (tt parity style)
    const stampLabel = @json(__('app.stampDiscount'));
    const freeItemLabel = @json(__('app.freeItem'));
    const $stampBadge = $('#stamp-discount-badge');
    const $stampBadgeText = $('#stamp-discount-badge-text');
    if ($stampBadge.length && $stampBadgeText.length) {
        let hasFreeStampItems = false;
        let calculatedStampDiscount = 0;
        Object.keys(window.posState.orderItemList || {}).forEach(function(key) {
            const item = window.posState.orderItemList?.[key] || {};
            const note = window.posState.itemNotes?.[key] || '';
            const qty = parseInt(window.posState.orderItemQty?.[key] || 1, 10);
            const amount = parseFloat(window.posState.orderItemAmount?.[key] || 0);
            const variation = window.posState.orderItemVariation?.[key];
            const modifierPrice = parseFloat(window.posState.orderItemModifiersPrice?.[key] || 0);
            const basePrice = variation ? (parseFloat(variation.price || item.price || 0)) : parseFloat(item.price || 0);
            const expectedAmount = (basePrice + modifierPrice) * qty;
            const isFreeStampItem = window.isFreeStampItemByMeta(key, item, note);

            if (isFreeStampItem) {
                hasFreeStampItems = true;
                return;
            }

            if (expectedAmount > amount) {
                calculatedStampDiscount += (expectedAmount - amount);
            }
        });

        const orderLevelStampDiscount = parseFloat(window.posState.stampDiscountAmount || 0);
        const stampDiscountAmount = Math.max(orderLevelStampDiscount, calculatedStampDiscount);

        const hasServerFreeStampItems = window.posState.hasFreeStampItems === true || window.posState.hasFreeStampItems === 1 || window.posState.hasFreeStampItems === '1';
        const shouldShowStampBadge = hasFreeStampItems || hasServerFreeStampItems || stampDiscountAmount > 0.01;
        window.posState.hasFreeStampItems = hasFreeStampItems || hasServerFreeStampItems;

        if (shouldShowStampBadge) {
            const badgeText = stampDiscountAmount > 0.01
                ? `${stampLabel} (-${window.formatCurrency(stampDiscountAmount)})`
                : `${stampLabel} (${freeItemLabel})`;
            $stampBadgeText.text(badgeText);
            $stampBadge.removeClass('hidden').show();
        } else {
            $stampBadge.addClass('hidden').hide();
        }
    }

    // Update/show discount row (hide if loyalty discount is active)
    const loyaltyActive = (window.posState.loyaltyDiscountAmount || 0) > 0;
    if (!loyaltyActive && window.posState.discountAmount > 0) {
        let discountTypeText = '';
        if (window.posState.discountType === 'percent') {
            discountTypeText = ` (${window.posState.discountValue}%)`;
        }
        $('#discount-type-display').text(discountTypeText);
        $('#discount-display').text('-' + window.formatCurrency(window.posState.discountAmount));
        $('#discount-row').show();
    } else {
        $('#discount-row').hide();
    }

    // Loyalty discount row (tt parity): show and update when loyalty applied in session
    const $loyaltyRowJs = $('#loyalty-discount-row-js');
    const $loyaltyRowBlade = $('#loyalty-discount-row-blade');
    if ($loyaltyRowJs.length) {
        if (loyaltyActive) {
            const pts = parseInt(window.posState.loyaltyPointsRedeemed || 0, 10);
            const amt = parseFloat(window.posState.loyaltyDiscountAmount || 0);
            const loyaltyDiscountLabel = @json(__('loyalty::app.loyaltyDiscount'));
            const pointsLabel = @json(__('loyalty::app.points'));
            const $loyaltyLabel = $loyaltyRowJs.find('#loyalty-js-label');
            if ($loyaltyLabel.length) {
                $loyaltyLabel.text(`${loyaltyDiscountLabel} (${pts.toLocaleString()} ${pointsLabel})`);
            }
            $loyaltyRowJs.find('#loyalty-js-points').text(pts.toLocaleString());
            $loyaltyRowJs.find('#loyalty-js-amount').text('-' + window.formatCurrency(amt));
            $loyaltyRowJs.show();
            if ($loyaltyRowBlade.length) $loyaltyRowBlade.hide();
        } else {
            $loyaltyRowJs.hide();
            if ($loyaltyRowBlade.length) $loyaltyRowBlade.show();
        }
    }

    // Update delivery fee note
    if (window.posState.deliveryFee == 0) {
        $('#delivery-fee-note').text('(@lang("modules.delivery.freeDelivery"))').show();
    } else {
        $('#delivery-fee-note').hide();
    }

    // Detect fresh KOT context (same rule as calculateTotal)
    const path = (window.location && window.location.pathname) ? window.location.pathname.toString() : '';
    const href = (window.location && window.location.href) ? window.location.href.toString() : '';
    const urlShowOrderDetail = /[?&]show-order-detail=true(?:&|$)/.test(href);
    const showOrderDetailFlag = !!(window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true'));
    const effectiveShowOrderDetail = showOrderDetailFlag || urlShowOrderDetail;
    const isFreshKotContext =
        !effectiveShowOrderDetail &&
        /\/pos\/kot\/\d+/.test(path);

    // Update extra charges (handle both Blade-rendered and dynamically loaded)
    // Skip rendering charges for fresh new KOT screen to avoid double-charging in UI.
    if (!isFreshKotContext && extraCharges && extraCharges.length > 0 && Object.keys(window.posState.orderItemAmount).length > 0) {
        // Check if charges exist in DOM (Blade rendered) or need to be created (dynamically loaded)
        const existingCharges = $('#extra-charges-container [data-charge-id]').length;

        if (existingCharges === 0) {
            // Charges were loaded dynamically - need to build HTML
            let chargesHtml = '';
            extraCharges.forEach(charge => {
                let chargeAmount = 0;
                const chargeValue = parseFloat(charge.charge_value || 0);

                if (charge.charge_type === 'percent') {
                    chargeAmount = (chargeValue / 100) * (window.posState.discountedTotal || 0);
                } else {
                    chargeAmount = chargeValue;
                }

                const percentText = charge.charge_type === 'percent' ? ` (${chargeValue}%)` : '';
                const deleteButton = @json(user_can('Update Order'))
                    ? `<span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                            onclick="removeExtraCharge(${charge.id}, '{{ $orderType ?? '' }}')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1" clip-rule="evenodd"/>
                            </svg>
                        </span>`
                    : '';

                chargesHtml += `
                    <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400"
                         data-charge-id="${charge.id}"
                         data-charge-name="${charge.charge_name}"
                         data-charge-type="${charge.charge_type}"
                         data-charge-value="${chargeValue}">
                        <div class="inline-flex items-center gap-x-1">
                            ${charge.charge_name}${percentText}
                            ${deleteButton}
                        </div>
                        <div class="charge-amount-display">${window.formatCurrency(chargeAmount)}</div>
                    </div>
                `;
            });

            $('#extra-charges-container').html(chargesHtml).show();
        } else {
            // Charges exist - just update amounts
            extraCharges.forEach(charge => {
                let chargeAmount = 0;
                const chargeValue = parseFloat(charge.charge_value || 0);

                if (charge.charge_type === 'percent') {
                    chargeAmount = (chargeValue / 100) * (window.posState.discountedTotal || 0);
                } else {
                    chargeAmount = chargeValue;
                }

                const $chargeRow = $(`#extra-charges-container [data-charge-id="${charge.id}"]`);
                if ($chargeRow.length > 0) {
                    $chargeRow.find('.charge-amount-display').text(window.formatCurrency(chargeAmount));
                }
            });

            $('#extra-charges-container').show();
        }
    } else {
        $('#extra-charges-container').hide();
    }

    const normalizeTaxBreakup = function(rawBreakup) {
        const normalized = {};
        if (!rawBreakup) {
            return normalized;
        }

        if (!Array.isArray(rawBreakup) && typeof rawBreakup === 'object') {
            Object.keys(rawBreakup).forEach(function(key) {
                const taxInfo = rawBreakup[key] || {};
                const taxName = taxInfo.tax_name || taxInfo.name || key;
                if (!taxName) {
                    return;
                }
                normalized[taxName] = {
                    percent: Number(taxInfo.percent ?? taxInfo.rate ?? taxInfo.tax_percent ?? 0),
                    amount: Number(taxInfo.amount ?? 0)
                };
            });
            return normalized;
        }

        if (Array.isArray(rawBreakup)) {
            rawBreakup.forEach(function(taxInfo) {
                if (!taxInfo || typeof taxInfo !== 'object') {
                    return;
                }
                const taxName = taxInfo.tax_name || taxInfo.name;
                if (!taxName) {
                    return;
                }
                normalized[taxName] = {
                    percent: Number(taxInfo.percent ?? taxInfo.rate ?? taxInfo.tax_percent ?? 0),
                    amount: Number(taxInfo.amount ?? 0)
                };
            });
        }

        return normalized;
    };

    // Update taxes based on tax mode
    if (taxMode === 'order') {
        // Order-level taxes - rebuild from config
        const taxes = window.posConfig?.taxes || [];
        let orderTaxesHtml = '';

        if (taxes && taxes.length > 0) {
            for (const tax of taxes) {
                const taxPercent = parseFloat(tax.tax_percent || 0);
                const taxAmount = hasItems ? ((taxPercent / 100) * (window.posState.taxBase || window.posState.discountedTotal || 0)) : 0;

                orderTaxesHtml += `
                    <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400"
                         data-tax-name="${tax.tax_name}"
                         data-tax-percent="${taxPercent}">
                        <div>${tax.tax_name} (${taxPercent}%)</div>
                        <div class="tax-amount-display">${window.formatCurrency(taxAmount)}</div>
                    </div>
                `;
            }

            $('#order-taxes-container').html(orderTaxesHtml).show();
        } else {
            $('#order-taxes-container').hide();
        }
    } else {
        // Item-level taxes - rebuild tax breakdown
        const taxTotals = {};
        const taxes = window.posConfig?.taxes || [];

        // Calculate tax totals from orderItemTaxDetails
        for (const key in window.posState.orderItemTaxDetails) {
            const taxDetail = window.posState.orderItemTaxDetails[key];
            if (taxDetail && taxDetail.tax_breakup) {
                const qty = Number(taxDetail.qty || 1);
                const taxBreakup = normalizeTaxBreakup(taxDetail.tax_breakup);

                for (const taxName in taxBreakup) {
                    const taxInfo = taxBreakup[taxName];
                    const perUnitAmount = Number(taxInfo?.amount || 0);

                    if (!taxTotals[taxName]) {
                        taxTotals[taxName] = {
                            percent: taxInfo.percent,
                            amount: 0
                        };
                    }

                    // tax_breakup amounts are per-unit; scale by item quantity
                    taxTotals[taxName].amount += perUnitAmount * qty;
                }
            }
        }

        // Rebuild item taxes display
        let itemTaxesHtml = '';
        for (const taxName in taxTotals) {
            const taxInfo = taxTotals[taxName];
            itemTaxesHtml += `
                <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                    <div>${taxName} (${taxInfo.percent}%)</div>
                    <div>${window.formatCurrency(taxInfo.amount)}</div>
                </div>
            `;
        }

        const shouldShowTotalTax = Number(window.posState.totalTaxAmount || 0) > 0
            || (hasItems && Object.keys(taxTotals).length > 0)
            || (!hasItems && taxes && taxes.length > 0);

        if (shouldShowTotalTax) {
            const taxInclusiveText = isInclusive
                ? '@lang("modules.settings.taxInclusive")'
                : '@lang("modules.settings.taxExclusive")';

            itemTaxesHtml += `
                <div class="flex justify-between text-gray-500 text-xs dark:text-neutral-400">
                    <div>
                        @lang('modules.order.totalTax')
                        <span class="text-xs text-gray-400">(${taxInclusiveText})</span>
                    </div>
                    <div id="total-tax-display">${window.formatCurrency(window.posState.totalTaxAmount || 0)}</div>
                </div>
            `;
        }

        $('#item-taxes-container').html(itemTaxesHtml);
    }

    // Update total
    $('#total-display').text(window.formatCurrency(window.posState.total || 0));

};

@php
    // Use the same formatter settings as `currency_format()` in `app/Helper/start.php`.
    $formats = currency_format_setting();

    $currency_position = $formats->currency_position ?? 'left';
    $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
    $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
    $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

    // For current restaurant (currencyId = null in PHP helper), symbol comes from restaurant currency.
    $currency_symbol = $restaurant->currency->currency_symbol ?? '';
@endphp

window.formatCurrency = function(amount) {
    const noOfDecimal = parseInt(@json($no_of_decimal), 10) || 0;
    const thousandSeparator = @json($thousand_separator);
    const decimalSeparator = @json($decimal_separator);
    const currencySymbol = @json($currency_symbol);
    const currencyPosition = @json($currency_position);

    const num = Number(amount || 0);
    const sign = num < 0 ? '-' : '';
    const absNum = Math.abs(num);

    // `toFixed` always uses '.' as decimal separator; we replace it below.
    const fixed = absNum.toFixed(noOfDecimal);
    let parts = fixed.split('.');
    let intPart = parts[0] || '0';
    const decPart = parts.length > 1 ? parts[1] : '';

    if (thousandSeparator) {
        // Insert thousand separators from right, like PHP's `number_format()`.
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, function() {
            return thousandSeparator;
        });
    }

    const formattedAmount = noOfDecimal > 0
        ? (intPart + decimalSeparator + decPart)
        : intPart;

    const amountWithSign = sign + formattedAmount;

    switch (currencyPosition) {
        case 'right':
            return amountWithSign + currencySymbol;
        case 'left_with_space':
            return currencySymbol + ' ' + amountWithSign;
        case 'right_with_space':
            return amountWithSign + ' ' + currencySymbol;
        default:
            return currencySymbol + amountWithSign;
    }
};

window.updateOrderItems = function() {
    // Recalculate total and update display
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    // Also update the order items container if needed
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
};

window.getMenuItemTaxesFromInput = function($input) {
    if (!$input || !$input.length) {
        return [];
    }

    const menuItemId = parseInt($input.attr('data-item-id') || $input.data('item-id') || 0, 10);
    const fallbackTaxes = (!isNaN(menuItemId) && menuItemId > 0 && window.menuItemTaxesIndex && Array.isArray(window.menuItemTaxesIndex[menuItemId]))
        ? window.menuItemTaxesIndex[menuItemId]
        : [];

    const rawTaxes = $input.attr('data-item-taxes');
    if (!rawTaxes) {
        return fallbackTaxes.map(function(tax) {
            return {
                id: parseInt(tax.id) || null,
                tax_name: tax.tax_name || '',
                tax_percent: parseFloat(tax.tax_percent || 0)
            };
        });
    }

    const decodeHtmlEntities = function(value) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
    };

    try {
        let parsed = null;
        try {
            parsed = JSON.parse(rawTaxes);
        } catch (e) {
            parsed = JSON.parse(decodeHtmlEntities(rawTaxes));
        }

        if (!Array.isArray(parsed)) {
            return fallbackTaxes;
        }

        const normalized = parsed
            .filter(function(tax) {
                return tax && typeof tax === 'object';
            })
            .map(function(tax) {
                return {
                    id: parseInt(tax.id) || null,
                    tax_name: tax.tax_name || '',
                    tax_percent: parseFloat(tax.tax_percent || 0)
                };
            });
        return normalized.length > 0 ? normalized : fallbackTaxes;
    } catch (e) {
        return fallbackTaxes;
    }
};

// Client-side add item to cart (no server request)
window.addCartItemClientSide = function(menuItemId) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    const orderID = window.posState.orderID;

    if ((orderID && !window.posState.canUpdateOrder) || (!orderID && !window.posState.canCreateOrder)) {
        window.showToast('error', '@lang("messages.permissionDenied")');
        return;
    }


    // Check order limit
    @php
        $orderStats = getRestaurantOrderStats(branch()->id);
    @endphp
    @if (!$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'])
        window.showToast('error', '@lang("messages.orderLimitReached")');
        return;
    @endif

    // Prevent adding items to finalized or KOT orders
    if (orderID && window.posState.orderDetail) {
        const status = window.posState.orderDetail.status;
        if (['billed', 'paid', 'payment_due'].includes(status)) {
            return;
        }
        if (window.posState.showOrderDetail === true && status === 'kot') {
            window.showNewKotRequiredModal();
            return;
        }
    }

    // Get item data from the clicked element
    const $input = $(`input[data-item-id="${menuItemId}"]`);
    if ($input.length === 0) {
        console.error('Menu item not found');
        return;
    }

    const itemData = {
        id: parseInt($input.data('item-id')),
        name: $input.data('item-name'),
        item_name: $input.data('item-name'),
        price: parseFloat($input.data('item-price')),
        image: $input.data('item-image'),
        taxes: window.getMenuItemTaxesFromInput($input),
        variationsCount: parseInt($input.data('variations-count')) || 0,
        modifiersCount: parseInt($input.data('modifiers-count')) || 0
    };

    // Hard fallback for item-wise tax mode: always pick backend-indexed taxes by menu id.
    if (
        (window.posConfig?.taxMode || 'order') === 'item'
        && (!Array.isArray(itemData.taxes) || itemData.taxes.length === 0)
        && window.menuItemTaxesIndex
        && Array.isArray(window.menuItemTaxesIndex[itemData.id])
    ) {
        itemData.taxes = window.menuItemTaxesIndex[itemData.id];
    }

    // Check if item has variations or modifiers - show modals if needed
    if (itemData.variationsCount > 0) {
        // Show variation modal
        if (typeof window.showVariationModal === 'function') {
            // Store the item data for later use
            window.posState.pendingMenuItem = itemData;
            window.showVariationModal(menuItemId);
        }
        return;
    }

    if (itemData.modifiersCount > 0) {
        // Show modifiers modal
        if (typeof window.showModifiersModal === 'function') {
            // Store the item data for later use
            window.posState.pendingMenuItem = itemData;
            window.showModifiersModal(menuItemId);
        }
        return;
    }

    // Initialize state if needed
    if (!window.posState.orderItemList) {
        window.posState.orderItemList = {};
    }
    if (!window.posState.orderItemQty) {
        window.posState.orderItemQty = {};
    }
    if (!window.posState.orderItemAmount) {
        window.posState.orderItemAmount = {};
    }
    if (!window.posState.orderItemVariation) {
        window.posState.orderItemVariation = {};
    }
    if (!window.posState.itemModifiersSelected) {
        window.posState.itemModifiersSelected = {};
    }
    if (!window.posState.orderItemModifiersPrice) {
        window.posState.orderItemModifiersPrice = {};
    }
    if (!window.posState.itemNotes) {
        window.posState.itemNotes = {};
    }

    // Check if item already exists in cart (same item ID, no variation, no modifiers)
    // IMPORTANT: Skip free-stamp items so paid items never merge into a free row.
    let existingKey = null;
    if (window.posState.orderItemList) {
        Object.keys(window.posState.orderItemList).forEach(key => {
            const item = window.posState.orderItemList[key];
            const note = window.posState.itemNotes && window.posState.itemNotes[key] ? window.posState.itemNotes[key] : '';

            // If this row is a free stamp item, never merge into it.
            if (window.isFreeStampItemByMeta && window.isFreeStampItemByMeta(key, item || {}, note)) {
                return;
            }

            const hasVariation = window.posState.orderItemVariation && window.posState.orderItemVariation[key];
            const hasModifiers = window.posState.itemModifiersSelected && window.posState.itemModifiersSelected[key] && window.posState.itemModifiersSelected[key].length > 0;

            // Same item, no variation, no modifiers = same item, increase quantity
            if (item && item.id === itemData.id && !hasVariation && !hasModifiers) {
                existingKey = key;
            }
        });
    }

    let affectedItemKey = null;
    if (existingKey) {
        // Item already exists, increase quantity
        window.posState.orderItemQty[existingKey] = (window.posState.orderItemQty[existingKey] || 0) + 1;
        const basePrice = itemData.price;
        const modifierPrice = (window.posState.orderItemModifiersPrice && window.posState.orderItemModifiersPrice[existingKey]) || 0;
        window.posState.orderItemAmount[existingKey] = window.posState.orderItemQty[existingKey] * (basePrice + modifierPrice);
        window.autoApplyStampPreviewForItem?.(existingKey);
        affectedItemKey = existingKey;
    } else {
        // Add new item
        const itemKey = 'item_' + itemData.id + '_' + Date.now();
        window.posState.orderItemList[itemKey] = itemData;
        window.posState.orderItemQty[itemKey] = 1;
        window.posState.orderItemAmount[itemKey] = itemData.price;
        window.autoApplyStampPreviewForItem?.(itemKey);
        affectedItemKey = itemKey;
    }

    // Update the UI
    window.updateOrderItemsContainer();

    // Recalculate totals
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }

    // Play beep sound
    if (typeof window.playBeep === 'function') {
        window.playBeep();
    }

    // Auto-open loyalty modal when first non-free item added (tt parity)
    try {
        if (
            window.posState?.loyaltyEnabled &&
            window.posState.customerId &&
            (window.posState.loyaltyPointsRedeemed || 0) === 0 &&
            (window.posState.subTotal || 0) > 0 &&
            typeof window.openLoyaltyRedemptionModal === 'function'
        ) {
            const cartSummary = window.getNonFreeCartSummary?.() || {};
            if ((cartSummary.nonFreeQtyTotal || 0) === 1) {
                window.openLoyaltyRedemptionModal();
            }
        }
    } catch (e) {
        console.warn('Auto loyalty modal check failed:', e);
    }
};

// Update order items container HTML directly - matching original design
window.updateOrderItemsContainer = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    // Find ALL item containers that should be populated by JS
    // This includes both kot_items.blade.php and order_items.blade.php containers
    let itemsContainers = $('#order-items-container').find('div.flex.flex-col.rounded.gap-1, div[data-js-populated="1"]');

    // If not found, try the kot_items structure
    if (itemsContainers.length === 0) {
        itemsContainers = $('#order-items-container .flex.flex-col.rounded.gap-1');
    }

    // If still not found, create it
    if (itemsContainers.length === 0) {
        const flexContainer = $('#order-items-container .flex-1.overflow-y-auto');
        if (flexContainer.length > 0) {
            flexContainer.append('<div class="flex flex-col rounded gap-1"></div>');
            itemsContainers = $('#order-items-container .flex.flex-col.rounded.gap-1');
        } else {
            // Fallback: just update the order-items-container directly
            itemsContainers = $('#order-items-container');
        }
    }

    if (!window.posState.orderItemList || Object.keys(window.posState.orderItemList).length === 0) {
        // Empty cart - clear all containers
        itemsContainers.each(function() {
            const $container = $(this);
            // Only show empty state in the main kot_items container (without data-kot-id)
            if (!$container.attr('data-kot-id')) {
                $container.html(`
                    <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div class="text-gray-500 dark:text-gray-400 text-base">No items added</div>
                        </div>
                    </div>
                `);
            } else {
                // For KOT-specific containers, just clear them
                $container.empty();
            }
        });
        return;
    }

    const freeItemLabel = @json(__('app.freeItem'));
    const stampDiscountLabel = @json(__('app.stampDiscount'));

    let detectedFreeStampItems = false;

    // Group items by KOT if we're in order_items view
    const itemsByKot = {};
    Object.keys(window.posState.orderItemList).forEach(key => {
        // Check if this is a KOT item (format: kot_123_456)
        const kotMatch = key.match(/^kot_(\d+)_/);
        if (kotMatch) {
            const kotId = kotMatch[1];
            if (!itemsByKot[kotId]) {
                itemsByKot[kotId] = [];
            }
            itemsByKot[kotId].push(key);
        } else {
            // Non-KOT items go in the main list
            if (!itemsByKot['main']) {
                itemsByKot['main'] = [];
            }
            itemsByKot['main'].push(key);
        }
    });

    // Render items in their respective containers
    itemsContainers.each(function() {
        const $container = $(this);
        const kotId = $container.attr('data-kot-id');

        // Determine which items to show in this container
        let itemKeys;
        if (kotId) {
            // This is a KOT-specific container in order_items.blade.php
            itemKeys = itemsByKot[kotId] || [];
        } else {
            // This is the main container in kot_items.blade.php - show all items
            itemKeys = Object.keys(window.posState.orderItemList);
        }

        if (itemKeys.length === 0) {
            // No items for this container
            if (!kotId) {
                // Show empty state only in main container
                $container.html(`
                    <div class="text-center text-gray-500 dark:text-gray-400 mt-4">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div class="text-gray-500 dark:text-gray-400 text-base">No items added</div>
                        </div>
                    </div>
                `);
            } else {
                $container.empty();
            }
            return;
        }

        // Reorder so free-stamp items appear once, directly after the item that triggered them
        try {
            const sourceMap = window.posState.freeStampSourceByKey || {};
            const keySet = new Set(itemKeys);
            const orderedKeys = [];
            const mappedFreeKeys = Object.keys(sourceMap).filter(fk => keySet.has(fk));
            const usedFree = new Set();

            itemKeys.forEach(function(key) {
                const isFreeStampKey = key.indexOf('free_stamp_') === 0;
                if (!isFreeStampKey) {
                    orderedKeys.push(key);
                }
                mappedFreeKeys.forEach(function(freeKey) {
                    if (!usedFree.has(freeKey) && sourceMap[freeKey] === key) {
                        orderedKeys.push(freeKey);
                        usedFree.add(freeKey);
                    }
                });
            });

            itemKeys.forEach(function(key) {
                if (!orderedKeys.includes(key)) {
                    orderedKeys.push(key);
                }
            });

            itemKeys = orderedKeys;
        } catch (e) {
            // Fallback safely if reordering fails
        }

        // Build HTML for items
        let html = '';
        itemKeys.forEach(key => {
            const item = window.posState.orderItemList[key];
            const qty = window.posState.orderItemQty[key] || 1;
            const amount = window.posState.orderItemAmount[key] || 0;
            const variation = window.posState.orderItemVariation && window.posState.orderItemVariation[key];
            const modifiers = window.posState.itemModifiersSelected && window.posState.itemModifiersSelected[key];
            const modifierPrice = (window.posState.orderItemModifiersPrice && window.posState.orderItemModifiersPrice[key]) || 0;
            const note = (window.posState.itemNotes && window.posState.itemNotes[key]) || '';
            // Calculate display price (base price + modifier price)
            const basePrice = variation ? (variation.price || item.price) : item.price;
            const displayPrice = basePrice + modifierPrice;
            const expectedAmount = displayPrice * qty;
            // Treat as free-stamp item only when meta says so AND the line amount is actually zero (or nearly zero)
            const isFreeStampItem = window.isFreeStampItemByMeta(key, item, note) && (amount <= 0.0001);
            if (isFreeStampItem) {
                detectedFreeStampItems = true;
            }
            const stampDiscountAmount = Math.max(0, expectedAmount - amount);
            const hasStampDiscount = !isFreeStampItem && stampDiscountAmount > 0.01;
            const cardClasses = isFreeStampItem
                ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
                : (hasStampDiscount ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700');

            html += `
            <div class="border ${cardClasses} rounded-md p-2 flex flex-col gap-2" data-item-key="${key}">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-gray-900 dark:text-white text-xs">${item.item_name || item.name || 'Item'}</span>
                                ${isFreeStampItem ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">${freeItemLabel}</span>` : ''}
                                ${hasStampDiscount ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">${stampDiscountLabel} (-${window.formatCurrency(stampDiscountAmount)})</span>` : ''}
                            </div>
                            ${variation ? `<span class="text-gray-500 dark:text-gray-400 text-xs">&bull; ${variation.variation || ''}</span>` : ''}
                            ${modifiers && modifiers.length > 0 ? `
                                <div class="inline-flex flex-wrap gap-2 text-xs text-gray-600 dark:text-white">
                                    ${modifiers.map(function(modIdOrObj) {
                                        var modId = (typeof modIdOrObj === 'object' && modIdOrObj !== null && modIdOrObj.id != null) ? modIdOrObj.id : modIdOrObj;
                                        var modOption = (window.posState.modifierOptions && window.posState.modifierOptions[modId]) || (typeof modIdOrObj === 'object' && modIdOrObj !== null ? modIdOrObj : null);
                                        if (!modOption) return '';
                                        var modName = modOption.name;
                                        if (typeof modName !== 'string' && modName !== null && typeof modName === 'object') {
                                            modName = modName.en || modName[Object.keys(modName)[0]] || 'Modifier';
                                        }
                                        modName = (typeof modName === 'string' ? modName : '') || 'Modifier';
                                        var modPrice = parseFloat(modOption.price) || 0;
                                        return '<div class="inline-flex items-center justify-between text-xs mb-1 py-0.5 px-1 border-l-2 border-blue-500 bg-gray-200 dark:bg-gray-900 rounded-md">' +
                                            '<span class="text-gray-900 dark:text-white">' + modName + '</span>' +
                                            (modPrice > 0 ? '<span class="text-gray-600 dark:text-gray-300 ml-1">' + window.formatCurrency(modPrice) + '</span>' : '') +
                                            '</div>';
                                    }).filter(m => m).join('')}
                                </div>
                            ` : ''}
                        </div>
                        <div class="flex items-center gap-2">
                            ${
                                isFreeStampItem
                                    ? `<div class="flex flex-col items-end">
                                            <div class="text-green-600 dark:text-green-400 text-xs font-bold">${window.formatCurrency(0)}</div>
                                            <div class="text-[10px] text-gray-400 line-through">${window.formatCurrency(expectedAmount)}</div>
                                       </div>`
                                    : hasStampDiscount
                                        ? `<div class="flex flex-col items-end">
                                                <div class="text-blue-600 dark:text-blue-400 text-xs font-bold">${window.formatCurrency(amount)}</div>
                                                <div class="text-[10px] text-gray-400 line-through">${window.formatCurrency(expectedAmount)}</div>
                                           </div>`
                                        : `<div class="text-gray-500 dark:text-gray-400 text-xs">${window.formatCurrency(displayPrice)}</div>
                                           <div class="text-gray-500 dark:text-gray-400 text-xs font-bold">${window.formatCurrency(amount)}</div>`
                            }
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 justify-between">
                    ${
                        isFreeStampItem
                            ? `<div class="text-xs text-gray-500 dark:text-gray-400">@lang('app.qty') ${qty}</div>`
                            : `<div class="relative inline-flex items-center max-w-[7rem]">
                                    <button type="button" onclick="decreaseQty('${key}')"
                                        class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-s-md p-3 h-8 relative">
                                        <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                                        </svg>
                                    </button>
                                    <input type="text" value="${qty}"
                                        onchange="updateQtyInput('${key}', this.value)"
                                        class="min-w-10 bg-white border-x-0 border-gray-300 h-8 text-center text-gray-900 text-sm block w-full py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                        min="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                    <button type="button" onclick="increaseQty('${key}')"
                                        class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-e-md p-3 h-8 relative">
                                        <svg class="w-2 h-2 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                                        </svg>
                                    </button>
                               </div>`
                    }
                    <div class="flex-shrink-0">
                        ${note ? `
                            <button type="button" onclick="showItemNote('${key}')"
                                class="group relative inline-flex items-center gap-1.5 px-2 py-1.5 text-xs text-skin-base hover:text-skin-base/80 bg-skin-base/10 dark:bg-skin-base/20 dark:text-white hover:bg-skin-base/20 dark:hover:bg-skin-base/30 rounded-md transition-all duration-200"
                                title="${note}">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <span class="truncate max-w-[60px] md:max-w-[100px] lg:max-w-[80px] font-medium">${note}</span>
                                <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                </svg>
                            </button>
                        ` : `
                            <button type="button" onclick="showItemNote('${key}')"
                                class="inline-flex items-center gap-1 px-2 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-skin-base dark:hover:text-blue-400 hover:bg-skin-base/10 dark:hover:bg-blue-900/20 rounded-md transition-all duration-200"
                                title="@lang('modules.order.addNote')">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span class="font-medium">@lang('modules.order.addNote')</span>
                            </button>
                        `}
                    </div>
                    <div>
                        <button type="button" onclick="removeCartItem('${key}')"
                            class="rounded text-gray-800 dark:text-gray-400 border dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-900/20 p-2 relative">
                            <svg class="w-4 h-4 text-gray-700 dark:text-gray-200" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        });

        // Update this container's HTML
        $container.html(html);
    });

    // Keep runtime free-item truth in sync for subtotal badge logic.
    window.posState.hasFreeStampItems = detectedFreeStampItems;
};

// Show item note (placeholder - can be implemented later)
window.showItemNote = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const currentNote = (window.posState.itemNotes && window.posState.itemNotes[itemKey]) || '';

    // Use the POS item note modal (same UI used elsewhere)
    window.posState.pendingNoteItemKey = itemKey;
    $('#itemNoteInput').val(currentNote);
    $('#itemNoteError').hide();
    $('#itemNoteModal').show();

    // Auto focus on textarea with a small delay to ensure modal is visible
    setTimeout(() => {
        $('#itemNoteInput').focus();
    }, 200);
};

// Quantity management functions (using jQuery for DOM updates)
window.increaseQty = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    const itemMeta = window.posState.orderItemList?.[itemKey] || {};
    const itemNote = window.posState.itemNotes?.[itemKey] || '';
    const isFreeStampItem = window.isFreeStampItemByMeta(itemKey, itemMeta, itemNote) && ((window.posState.orderItemAmount?.[itemKey] || 0) <= 0.0001);

    if (isFreeStampItem) {
        window.showToast?.('info', @json(__('app.freeItem')));
        return;
    }

    if (!window.posState.orderItemQty[itemKey]) {
        window.posState.orderItemQty[itemKey] = 0;
    }
    const currentQty = window.posState.orderItemQty[itemKey] || 0;
    const currentAmount = parseFloat(window.posState.orderItemAmount[itemKey] || 0);
    window.posState.orderItemQty[itemKey]++;
    const item = window.posState.orderItemList[itemKey];
    const variation = window.posState.orderItemVariation && window.posState.orderItemVariation[itemKey];
    const modifierPrice = (window.posState.orderItemModifiersPrice && window.posState.orderItemModifiersPrice[itemKey]) || 0;
    const basePrice = variation ? (variation.price || item.price) : item.price;
    const unitPrice = currentQty > 0 ? (currentAmount / currentQty) : (basePrice + modifierPrice);
    window.posState.orderItemAmount[itemKey] = window.posState.orderItemQty[itemKey] * unitPrice;

    // Update the input field to show the new quantity
    $(`#qty-${itemKey}`).val(window.posState.orderItemQty[itemKey]);

    // Update UI using jQuery
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    // IMPORTANT: Only auto-preview stamps for NON-free items; free items themselves should not re-trigger stamp preview
    window.autoApplyStampPreviewForItem?.(itemKey);
};

window.decreaseQty = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    const itemMeta = window.posState.orderItemList?.[itemKey] || {};
    const itemNote = window.posState.itemNotes?.[itemKey] || '';
    const isFreeStampItem = window.isFreeStampItemByMeta(itemKey, itemMeta, itemNote) && ((window.posState.orderItemAmount?.[itemKey] || 0) <= 0.0001);

    if (isFreeStampItem) {
        window.showToast?.('info', @json(__('app.freeItem')));
        return;
    }

    if (!window.posState.orderItemQty[itemKey] || window.posState.orderItemQty[itemKey] <= 1) {
        window.deleteCartItemHandler(itemKey);
        return;
    }
    const currentQty = window.posState.orderItemQty[itemKey] || 0;
    const currentAmount = parseFloat(window.posState.orderItemAmount[itemKey] || 0);
    window.posState.orderItemQty[itemKey]--;
    const item = window.posState.orderItemList[itemKey];
    const variation = window.posState.orderItemVariation && window.posState.orderItemVariation[itemKey];
    const modifierPrice = (window.posState.orderItemModifiersPrice && window.posState.orderItemModifiersPrice[itemKey]) || 0;
    const basePrice = variation ? (variation.price || item.price) : item.price;
    const unitPrice = currentQty > 0 ? (currentAmount / currentQty) : (basePrice + modifierPrice);
    window.posState.orderItemAmount[itemKey] = window.posState.orderItemQty[itemKey] * unitPrice;

    // Update UI using jQuery
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    window.autoApplyStampPreviewForItem?.(itemKey);
};

window.updateQtyInput = function(itemKey, newQty) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    const itemMeta = window.posState.orderItemList?.[itemKey] || {};
    const itemNote = window.posState.itemNotes?.[itemKey] || '';
    const isFreeStampItem = window.isFreeStampItemByMeta(itemKey, itemMeta, itemNote);

    if (isFreeStampItem) {
        window.showToast?.('info', @json(__('app.freeItem')));
        return;
    }

    const qty = parseInt(newQty) || 1;
    if (qty < 1) {
        window.deleteCartItemHandler(itemKey);
        return;
    }
    const previousQty = parseInt(window.posState.orderItemQty[itemKey] || 0);
    const previousAmount = parseFloat(window.posState.orderItemAmount[itemKey] || 0);
    window.posState.orderItemQty[itemKey] = qty;
    const item = window.posState.orderItemList[itemKey];
    const variation = window.posState.orderItemVariation && window.posState.orderItemVariation[itemKey];
    const modifierPrice = (window.posState.orderItemModifiersPrice && window.posState.orderItemModifiersPrice[itemKey]) || 0;
    const basePrice = variation ? (variation.price || item.price) : item.price;
    const unitPrice = previousQty > 0 ? (previousAmount / previousQty) : (basePrice + modifierPrice);
    window.posState.orderItemAmount[itemKey] = qty * unitPrice;

    // Update the input field to show the correct value
    $(`#qty-${itemKey}`).val(qty);

    // Update UI using jQuery
    if (typeof window.updateOrderItemsContainer === 'function') {
        window.updateOrderItemsContainer();
    }
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    window.autoApplyStampPreviewForItem?.(itemKey);
};

/**
 * Handle delete cart item - routes to appropriate delete function based on item type
 */
window.deleteCartItemHandler = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Remove quotes from itemKey if present
    itemKey = itemKey.replace(/"/g, '');

    // Parse the item key
    const parts = itemKey.split('_');

    // Get order ID from posState
    const orderId = window.posState?.orderID || window.posState?.orderDetail?.id || {{ optional($orderDetail)->id ?? 'null' }};

    // Check if it's a draft order item (format: order_item_123)
    if (parts.length >= 3 && parts[0] === 'order' && parts[1] === 'item') {
        const orderItemId = parts[2];
        if (orderId) {
            window.deleteOrderItem(orderItemId, itemKey);
        } else {
            console.error('Order ID not found for order item');
        }
        return;
    }

    // Check if it's a KOT item (format: kot_123_456)
    if (parts.length >= 3 && parts[0] === 'kot') {
        const kotId = parts[1];
        const kotItemId = parts[2];

        if (!orderId) {
            console.error('Order ID not found for KOT item');
            return;
        }

        const doDelete = function() {
            $.easyAjax({
                url: '/ajax/pos/delete-cart-item',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    item_key: itemKey,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('success', response.message);
                        }

                        // If order was deleted, redirect
                        if (response.order_deleted && response.redirect) {
                            setTimeout(() => window.location.href = response.redirect, 500);
                        } else {
                            window.removeItemKeyFromPosState(itemKey);
                            var showOrderDetail = window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true');
                            if (showOrderDetail && response.order && typeof window.updateOrderDetailTotalsFromResponse === 'function') {
                                window.updateOrderDetailTotalsFromResponse(response.order, null);
                            }
                            if (typeof window.calculateTotal === 'function') {
                                window.calculateTotal();
                            }
                            if (typeof window.updateOrderItemsContainer === 'function') {
                                window.updateOrderItemsContainer();
                            }
                        }
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || @json(__('messages.somethingWentWrong'));
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        };

        // Show confirmation dialog
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: @json(__('modules.order.deleteOrderItem') . '?'),
                text: @json(__('modules.order.deleteOrderItemMessage')),
                showCancelButton: true,
                confirmButtonText: '@lang("app.delete")',
            }).then((result) => {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            if (confirm('@lang("messages.confirmDeleteItem")')) {
                doDelete();
            }
        }
        return;
    }

    // For new items not yet saved (no prefix), just remove from client-side state
    window.removeCartItem(itemKey);
};

// /**
//  * Delete KOT item from order via API
//  */
// window.deleteKotItem = function(kotItemId, orderId) {
//     if (!orderId) {
//         orderId = window.posState?.orderID || window.posState?.orderDetail?.id || {{ optional($orderDetail)->id ?? 'null' }};
//     }

//     if (!orderId) {
//         showToast('error', 'Order ID not found');
//         return;
//     }

//     $.easyAjax({
//         url: `/ajax/pos/orders/${orderId}/items/${kotItemId}`,
//         type: 'DELETE',
//         success: function(response) {
//             if (response.success) {
//                 showToast('success', response.message || 'Item deleted successfully');

//                 // If redirect URL provided (last item deleted), redirect to POS index
//                 if (response.redirect) {
//                     setTimeout(() => window.location.href = response.redirect, 500);
//                 } else {
//                     // Otherwise just reload to show updated order
//                     setTimeout(() => location.reload(), 500);
//                 }
//             }
//         },
//         error: function(xhr) {
//             const error = xhr.responseJSON?.message || 'Failed to delete item';
//             showToast('error', error);
//         }
//     });
// };

window.removeCartItem = function(itemKey) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    if (window.posState.orderItemList?.[itemKey]) {
        // Define properties to delete for cleaner code
        const propertiesToDelete = [
            'orderItemList',
            'orderItemQty',
            'orderItemAmount',
            'orderItemVariation',
            'itemModifiersSelected',
            'orderItemModifiersPrice',
            'itemNotes',
            'orderItemTaxDetails'
        ];

        // Delete all related properties for the primary item
        propertiesToDelete.forEach(prop => {
            if (window.posState[prop]?.[itemKey]) {
                delete window.posState[prop][itemKey];
            }
        });

        // Maintain stamp-source mapping if present
        if (window.posState.freeStampSourceByKey) {
            delete window.posState.freeStampSourceByKey[itemKey];
        }

        // When deleting a main (paid/discounted) item, only remove the free stamp items
        // that were created from this specific line, instead of wiping all free stamp items.
        if (itemKey.indexOf('free_stamp_') !== 0) {
            const list = window.posState.orderItemList || {};
            const sourceMap = window.posState.freeStampSourceByKey || {};

            Object.keys(list).forEach(function(key) {
                // Only consider free-stamp rows whose recorded source is this itemKey
                if (key.indexOf('free_stamp_') === 0 && sourceMap[key] === itemKey) {
                    propertiesToDelete.forEach(prop => {
                        if (window.posState[prop] && window.posState[prop][key] !== undefined) {
                            delete window.posState[prop][key];
                        }
                    });

                    if (window.posState.freeStampSourceByKey) {
                        delete window.posState.freeStampSourceByKey[key];
                    }
                }
            });
        } else if (window.posState.freeStampSourceByKey) {
            // If deleting a free-stamp-only line, ensure its mapping entry is cleared
            delete window.posState.freeStampSourceByKey[itemKey];
        }

        // Update UI using jQuery
        window.updateOrderItemsContainer?.();
        window.calculateTotal?.();
    }
};

// Add item to cart function (kept for backward compatibility)
window.addItemToCart = function(menuItem) {
    if (!menuItem) {
        console.error('Menu item is required');
        return;
    }


    if (!menuItem.item_name && menuItem.name) {
        menuItem.item_name = menuItem.name;
    }
    if (!menuItem.name && menuItem.item_name) {
        menuItem.name = menuItem.item_name;
    }

    // Generate a unique key for this cart item
    const itemKey = 'item_' + menuItem.id + '_' + Date.now();

    // Add to cart state
    if (!window.posState.orderItemList) {
        window.posState.orderItemList = {};
    }
    if (!window.posState.orderItemQty) {
        window.posState.orderItemQty = {};
    }
    if (!window.posState.orderItemAmount) {
        window.posState.orderItemAmount = {};
    }

    window.posState.orderItemList[itemKey] = menuItem;
    window.posState.orderItemQty[itemKey] = 1;

    // Calculate item amount (base price + modifiers if any)
    let itemPrice = menuItem.price || 0;
    if (menuItem.modifiers && menuItem.modifiers.length > 0) {
        menuItem.modifiers.forEach(modifier => {
            if (modifier.selected) {
                itemPrice += modifier.price || 0;
            }
        });
    }

    window.posState.orderItemAmount[itemKey] = itemPrice;

    // Update the UI
    window.updateOrderItemsContainer();

    // Recalculate totals
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }
    window.autoApplyStampPreviewForItem?.(itemKey);

    // Auto-open loyalty redemption modal when first non-free item is added
    try {
        if (
            window.posState?.loyaltyEnabled &&
            window.posState.customerId &&
            (window.posState.loyaltyPointsRedeemed || 0) === 0 &&
            nonFreeQtyBefore === 0 &&
            nonFreeQtyAfter > 0 &&
            typeof window.openLoyaltyRedemptionModal === 'function'
        ) {
            window.openLoyaltyRedemptionModal();
        }
    } catch (e) {
        console.warn('Auto loyalty modal check failed:', e);
    }
};

// Add cart item to POS (called from menu.blade.php)
window.addCartItemToPos = function(menuItemId, variationsCount, modifierGroupsCount) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    $.easyAjax({
        url: "{{ route('ajax.pos.add-cart-item') }}",
        type: "POST",
        data: {
            menu_item_id: menuItemId,
            order_type_id: window.posState.orderTypeId,
            delivery_app_id: window.posState.selectedDeliveryApp,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                        if (response.has_variations) {
                            // Show variation modal
                            if (typeof window.showVariationModal === 'function') {
                                window.showVariationModal(menuItemId);
                            }
                        } else if (response.has_modifiers) {
                            // Show modifiers modal
                            if (typeof window.showModifiersModal === 'function') {
                                window.showModifiersModal(menuItemId);
                            }
                        } else {
                            // Add directly to cart
                            if (typeof window.addItemToCart === 'function') {
                                window.addItemToCart(response.menu_item);
                            }
                        }
            }
        },
        error: function() {
            alert('Error adding item to cart');
        }
    });
};

/**
 * Show modal when user tries to add item in order detail view
 * Requires creating a new KOT first
 * Includes permission and order limit validation
 */
window.showNewKotRequiredModal = function() {

    const hasOrderId = window.posState.orderID && window.posState.orderID !== null;
    const orderStatus = window.posState.orderDetail.status || '';

    // Check if order status is 'kot' (cannot add items to KOT status orders)
    if (hasOrderId && orderStatus === 'kot') {
        const newKotUrl = window.location.href.replace(/[?&]show-order-detail=true/g, '');

        const modalHtml = `
            <div id="newKotRequiredModal" class="fixed inset-0 z-[9999]" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="window.closeNewKotRequiredModal()"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-2xl max-w-md w-full border border-gray-200 dark:border-slate-700 transform transition-all duration-300 scale-100">
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-2">@lang('app.error')</h3>
                                    <p class="text-sm text-gray-600 dark:text-slate-300 leading-relaxed">@lang('messages.errorWantToCreateNewKot')</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-900/50 px-6 py-4 flex gap-3 justify-end border-t border-gray-200 dark:border-slate-700">
                            <button type="button" onclick="window.closeNewKotRequiredModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 rounded-md transition-colors duration-200 border border-gray-300 dark:border-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                @lang('app.cancel')
                            </button>
                            <button type="button" onclick="window.navigateToNewKot('${newKotUrl}')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-md transition-colors duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                @lang('modules.order.newKot')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if present and append new one
        $('#newKotRequiredModal').remove();
        $('body').append(modalHtml);
    }
};

/**
 * Close the new KOT required modal
 */
window.closeNewKotRequiredModal = function() {
    $('#newKotRequiredModal').remove();
};

/**
 * Navigate to new KOT page (remove show-order-detail parameter)
 */
window.navigateToNewKot = function(url) {
    window.location.href = url;
};

// Show variation modal
window.showVariationModal = function(menuItemId) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }
    // Store menu item ID for later use
    window.posState.pendingMenuItemId = menuItemId;

    // Load variations and show modal
    if (typeof $.easyAjax === 'function') {
        $.easyAjax({
            url: "{{ route('ajax.pos.menu-item-variations', ['id' => ':id']) }}".replace(':id', menuItemId),
            type: "GET",
            data: {
                order_type_id: window.posState.orderTypeId,
                delivery_app_id: window.posState.selectedDeliveryApp
            },
            success: function(response) {
                if (response.success) {
                    // Populate and show variation modal
                    $('#variationModalContent').html(response.html);
                    $('#variationModal').show();
                }
            }
        });
    }
};

// Handle variation selection
window.selectVariation = function(variationId, menuItemId) {
    // Get variation data from API
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    $.easyAjax({
        url: "{{ route('ajax.pos.menu-item-variations', ['id' => ':id']) }}".replace(':id', menuItemId),
        type: "GET",
        data: {
            order_type_id: window.posState.orderTypeId,
            delivery_app_id: window.posState.selectedDeliveryApp
        },
        success: function(response) {
            if (response.success && response.variations) {
                const variation = response.variations.find(v => v.id === variationId);
                if (variation) {
                    // Check if item has modifiers
                    const menuItem = window.posState.pendingMenuItem || {};
                    const modifiersCount = menuItem.modifiersCount || 0;

                    if (modifiersCount > 0) {
                        // Show modifiers modal with variation
                        window.posState.pendingVariation = {
                            id: variationId,
                            variation: variation.variation,
                            price: parseFloat(variation.price) || 0
                        };
                        window.closeVariationModal();
                        window.showModifiersModal(menuItemId, variationId);
                    } else {
                        // Add to cart directly with variation
                        window.addItemWithVariation(menuItemId, variation);
                        window.closeVariationModal();
                    }
                }
            }
        }
    });
};

// Show modifiers modal
window.showModifiersModal = function(menuItemId, variationId) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }
    // Store menu item ID and variation ID for later use
    window.posState.pendingMenuItemId = menuItemId;
    window.posState.pendingVariationId = variationId || null;

    // Load modifiers and show modal
    if (typeof $.easyAjax === 'function') {
        $.easyAjax({
            url: "{{ route('ajax.pos.menu-item-modifiers', ['id' => ':id']) }}".replace(':id', menuItemId),
            type: "GET",
            data: {
                order_type_id: window.posState.orderTypeId,
                delivery_app_id: window.posState.selectedDeliveryApp,
                variation_id: variationId || null
            },
            success: function(response) {
                if (response.success) {
                    // Store modifier options data for later use
                    if (response.modifier_options) {
                        window.posState.modifierOptions = window.posState.modifierOptions || {};
                        Object.keys(response.modifier_options).forEach(optionId => {
                            window.posState.modifierOptions[optionId] = response.modifier_options[optionId];
                        });
                    }
                    // Populate and show modifiers modal
                    $('#modifiersModalContent').html(response.html);
                    $('#modifiersModal').show();
                }
            }
        });
    }
};

// Handle single selection for radio buttons
window.handleSingleSelection = function(groupId, optionId) {
    // Uncheck other options in the same group
    $(`input[name="modifier_group_${groupId}"]`).not(`[value="${optionId}"]`).prop('checked', false);
};

// Save modifiers and add to cart
window.saveModifiers = function(menuItemId, variationId) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    // Get all selected modifier options
    const selectedModifiers = [];
    let modifierPrice = 0;
    const modifierOptionsData = {};

    $('.modifier-option-checkbox:checked').each(function() {
        const optionId = parseInt($(this).val());
        const groupId = parseInt($(this).data('modifier-group-id'));
        const price = parseFloat($(this).data('modifier-price')) || 0;
        const name = $(this).data('modifier-name') || '';

        selectedModifiers.push(optionId);
        modifierPrice += price;

        // Store modifier option details
        modifierOptionsData[optionId] = {
            id: optionId,
            name: name,
            price: price,
            groupId: groupId
        };
    });

    // Validate required modifiers (check for required groups)
    let hasError = false;
    $('#modifiersModalContent [data-modifier-group-id]').each(function() {
        const groupId = parseInt($(this).data('modifier-group-id'));
        const isRequired = $(this).data('is-required') === '1' || $(this).data('is-required') === 1;

        if (isRequired) {
            const hasSelection = $(`.modifier-option-checkbox[data-modifier-group-id="${groupId}"]:checked`).length > 0;
            if (!hasSelection) {
                $(`#required-error-${groupId}`).removeClass('hidden');
                hasError = true;
            } else {
                $(`#required-error-${groupId}`).addClass('hidden');
            }
        }
    });

    if (hasError) {
        return;
    }

    // Get menu item data
    const $input = $(`input[data-item-id="${menuItemId}"]`);
    if ($input.length === 0) {
        console.error('Menu item not found');
        return;
    }

    const itemData = {
        id: parseInt($input.data('item-id')),
        name: $input.data('item-name'),
        item_name: $input.data('item-name'), // Add item_name for consistency with backend data
        price: parseFloat($input.data('item-price')),
        image: $input.data('item-image'),
        taxes: window.getMenuItemTaxesFromInput($input)
    };

    // Add to cart with variation and modifiers
    window.addItemWithVariationAndModifiers(itemData, variationId, selectedModifiers, modifierPrice, modifierOptionsData);

    // Close modal
    window.closeModifiersModal();
};

// Add item to cart with variation
window.addItemWithVariation = function(menuItemId, variation) {
    const $input = $(`input[data-item-id="${menuItemId}"]`);
    if ($input.length === 0) {
        console.error('Menu item not found');
        return;
    }

    const itemData = {
        id: parseInt($input.data('item-id')),
        name: $input.data('item-name'),
        item_name: $input.data('item-name'),
        price: parseFloat($input.data('item-price')),
        image: $input.data('item-image'),
        taxes: window.getMenuItemTaxesFromInput($input)
    };

    // Initialize state if needed
    if (!window.posState.orderItemList) {
        window.posState.orderItemList = {};
    }
    if (!window.posState.orderItemQty) {
        window.posState.orderItemQty = {};
    }
    if (!window.posState.orderItemAmount) {
        window.posState.orderItemAmount = {};
    }
    if (!window.posState.orderItemVariation) {
        window.posState.orderItemVariation = {};
    }

    // Generate unique key with variation
    const itemKey = 'item_' + itemData.id + '_' + variation.id + '_' + Date.now();

    // Add to cart
    window.posState.orderItemList[itemKey] = itemData;
    window.posState.orderItemQty[itemKey] = 1;
    window.posState.orderItemVariation[itemKey] = {
        id: variation.id,
        variation: variation.variation,
        price: parseFloat(variation.price) || 0
    };
    window.posState.orderItemAmount[itemKey] = parseFloat(variation.price) || 0;

    // Re-run stamp preview for this item (variation price) so discount applies if eligible
    if (window.posState.customerId && typeof window.autoApplyStampPreviewForItem === 'function') {
        window.autoApplyStampPreviewForItem(itemKey);
    }

    // Update UI
    window.updateOrderItemsContainer();

    // Recalculate totals
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }

    // Play beep sound
    if (typeof window.playBeep === 'function') {
        window.playBeep();
    }
};

// Add item to cart with variation and modifiers
window.addItemWithVariationAndModifiers = function(itemData, variationId, modifierIds, modifierPrice, modifierOptionsData) {
    // Normalize itemData to ensure it has both item_name and name fields
    if (!itemData.item_name && itemData.name) {
        itemData.item_name = itemData.name;
    }
    if (!itemData.name && itemData.item_name) {
        itemData.name = itemData.item_name;
    }

    // Initialize state if needed
    if (!window.posState.orderItemList) {
        window.posState.orderItemList = {};
    }
    if (!window.posState.orderItemQty) {
        window.posState.orderItemQty = {};
    }
    if (!window.posState.orderItemAmount) {
        window.posState.orderItemAmount = {};
    }
    if (!window.posState.orderItemVariation) {
        window.posState.orderItemVariation = {};
    }
    if (!window.posState.itemModifiersSelected) {
        window.posState.itemModifiersSelected = {};
    }
    if (!window.posState.orderItemModifiersPrice) {
        window.posState.orderItemModifiersPrice = {};
    }
    if (!window.posState.modifierOptions) {
        window.posState.modifierOptions = {};
    }

    // Generate unique key
    const sortNumber = modifierIds.sort().join('');
    let itemKey;
    if (variationId) {
        itemKey = 'item_' + itemData.id + '_' + variationId + '_' + sortNumber + '_' + Date.now();
    } else {
        itemKey = 'item_' + itemData.id + '_' + sortNumber + '_' + Date.now();
    }

    // Get variation data if exists
    let variation = null;
    if (variationId && window.posState.pendingVariation) {
        variation = window.posState.pendingVariation;
    }

    // Calculate base price
    const basePrice = variation ? variation.price : itemData.price;
    const totalPrice = basePrice + modifierPrice;

    // Add to cart
    window.posState.orderItemList[itemKey] = itemData;
    window.posState.orderItemQty[itemKey] = 1;
    window.posState.orderItemAmount[itemKey] = totalPrice;

    if (variation) {
        window.posState.orderItemVariation[itemKey] = {
            id: variation.id,
            variation: variation.variation,
            price: variation.price
        };
    }

    if (modifierIds.length > 0) {
        window.posState.itemModifiersSelected[itemKey] = modifierIds;
        window.posState.orderItemModifiersPrice[itemKey] = modifierPrice;

        // Store modifier options for display (passed from saveModifiers)
        window.posState.modifierOptions = window.posState.modifierOptions || {};
        if (modifierOptionsData && typeof modifierOptionsData === 'object') {
            Object.keys(modifierOptionsData).forEach(modId => {
                window.posState.modifierOptions[modId] = modifierOptionsData[modId];
            });
        }
    }

    // Re-run stamp preview for this item (with or without modifiers) so discount uses correct unit price
    if (window.posState.customerId && typeof window.autoApplyStampPreviewForItem === 'function') {
        window.autoApplyStampPreviewForItem(itemKey);
    }

    // Update UI
    window.updateOrderItemsContainer();

    // Recalculate totals
    if (typeof window.calculateTotal === 'function') {
        window.calculateTotal();
    }

    // Play beep sound
    if (typeof window.playBeep === 'function') {
        window.playBeep();
    }

    // Clear pending data
    window.posState.pendingMenuItem = null;
    window.posState.pendingVariation = null;
    window.posState.pendingVariationId = null;
};

// Modal functions for kot_items
window.showReservationModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#reservationModal').show();
    }
};

window.closeReservationModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#reservationModal').hide();
    }
};

window.confirmSameCustomer = function() {
    window.posState.isSameCustomer = true;
    if (typeof window.closeReservationModal === 'function') {
        window.closeReservationModal();
    }
    // Continue with order save
};

window.confirmDifferentCustomer = function() {
    window.posState.isSameCustomer = false;
    if (typeof window.closeReservationModal === 'function') {
        window.closeReservationModal();
    }
    // Continue with order save
};

window.showTableChangeConfirmationModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#tableChangeModal').show();

        // Refresh the SetTable component data
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('refreshSetTableComponent');
        }
    }
};

window.closeTableChangeModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#tableChangeModal').hide();
    }
};

window.closeTableChangeConfirmationModal = function() {
    window.closeTableChangeModal();
};

// cancelTableChange / confirmTableChange are defined below with full behavior (avoid duplicate stubs).

// Listen for Livewire order type selection event
if (typeof Livewire !== 'undefined') {
    // Listen for setOrderTypeChoice event
    Livewire.on('setOrderTypeChoice', (event) => {
        const orderTypeId = event.orderTypeId || event[0]?.orderTypeId;
        const orderTypeSlug = event.orderTypeSlug || event[0]?.orderTypeSlug;
        const deliveryPlatform = event.deliveryPlatform || event[0]?.deliveryPlatform || null;

        if (orderTypeId && orderTypeSlug) {
            redirectToPOS(orderTypeId, orderTypeSlug, deliveryPlatform);
        }
    });

    // Wait for Livewire to initialize
    document.addEventListener('livewire:initialized', () => {
        // Listen for the setTable event from Livewire SetTable component
        Livewire.on('setTable', (event) => {
            const table = event.table;

            if (!table || !table.id) {
                console.error('Invalid table data received');
                return;
            }

            // Save table via AJAX using existing pattern
            saveTableSelectionViaAPI(table);
        });

        // Listen for table updates from Livewire (when table is changed/updated)
        Livewire.on('tableUpdated', (event) => {
            if (event && event.table) {
                window.posState.tableId = event.table.id;
                window.posState.tableNo = event.table.table_code;

                // Update the UI without full reload
                if (typeof updateTableDisplay === 'function') {
                    updateTableDisplay(event.table);
                }
            }
        });

        // Also listen for tableSelected browser event
        Livewire.on('tableSelected', (event) => {
            const tableId = event.tableId;
            const tableCode = event.tableCode;

            console.log('Table selected:', tableId, tableCode);
        });

        // Close order detail modal
         const component = Livewire.all().find(c => c.name === 'order.order-detail');
        if (!component) return;

        let wasOpen = component.$wire.showOrderDetail;
        component.$wire.$watch('showOrderDetail', (isOpen) => {
            if (wasOpen && !isOpen) window.location.reload();
            wasOpen = isOpen;
        });
    });
}

/**
 * Save table selection via AJAX API call
 */
function saveTableSelectionViaAPI(table) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    // Get current order ID (same resolution as Livewire / submit-order helpers)
    const orderId = typeof window.getCurrentPosOrderId === 'function'
        ? window.getCurrentPosOrderId()
        : (window.posState?.orderID || window.posState?.orderDetail?.id || null);

    // Show loading state
    const $modal = $('#tableChangeModal');
    $modal.css({'pointer-events': 'none', 'opacity': '0.6'});

    // Make AJAX call to save the table
    $.easyAjax({
        url: '/ajax/pos/set-table',
        type: 'POST',
        data: {
            table_id: table.id,
            order_id: orderId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Update posState
                window.posState.tableId = table.id;
                window.posState.tableNo = table.table_code;
                if (orderId && !window.posState.orderID) {
                    window.posState.orderID = orderId;
                }

                // Update the table display in the UI using centralized function
                if (typeof updateTableDisplay === 'function') {
                    updateTableDisplay(table);
                }

                // Close the modal
                window.closeTableChangeModal();

                // Show success message
                window.showToast('success', response.message || @json(__('messages.tableLocked', ['table' => ''])));

                // Refresh the SetTable component to update lock status
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('refreshSetTableComponent');
                }
            }
        },
        error: function(xhr) {
            let errorMessage = @json(__('messages.tableLockFailed'));

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            window.showToast('error', errorMessage);
        },
        complete: function() {
            // Hide loading state
            $modal.css({'pointer-events': '', 'opacity': ''});
        }
    });
}



/**
 * Open merge table modal and load tables with unpaid orders
 */
window.showMergeTableModal = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    // Show modal with loading state
    $('#mergeTableModal').show();
    $('#mergeTableModalContent').html(`
        <div class="text-center py-8">
            <svg class="animate-spin mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">@lang('app.loading')</p>
        </div>
    `);

    // Load tables with unpaid orders
    $.easyAjax({
        url: "{{ route('ajax.pos.tables-with-unpaid-orders') }}",
        type: "GET",
        success: function(response) {
            if (response.success) {
                const tables = response.tables || [];
                const currentTableId = window.posState.tableId ? parseInt(window.posState.tableId) : null;
                let html = '';

                if (!tables.length) {
                    html = `
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                @lang('modules.order.noTablesWithUnpaidOrders')
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @lang('modules.order.noTablesWithUnpaidOrdersDescription')
                            </p>
                        </div>
                    `;
                } else {
                    html = `
                        <div class="space-y-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                @lang('modules.order.mergeTableDescription')
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div class="grid grid-cols-1 gap-3">
                    `;

                    tables.forEach(table => {
                        const isCurrentTable = currentTableId && currentTableId === parseInt(table.id);

                        // Get order info from unpaidOrders array
                        let orderStatus = '';
                        let orderInfo = '';
                        let itemCount = 0;

                        if (table.unpaid_orders && table.unpaid_orders.length > 0) {
                            const latestOrder = table.unpaid_orders[0];
                            orderStatus = latestOrder.status || 'draft';

                            // Count items
                            if (latestOrder.items && latestOrder.items.length > 0) {
                                itemCount = latestOrder.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                            } else if (latestOrder.kot && latestOrder.kot.length > 0) {
                                latestOrder.kot.forEach(kot => {
                                    if (kot.items && kot.items.length > 0) {
                                        itemCount += kot.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                                    }
                                });
                            }

                            orderInfo = `${itemCount} @lang('modules.menu.items')`;
                        }

                        const statusClass = isCurrentTable ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '';
                        const statusBadgeClass = orderStatus === 'kot' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                 orderStatus === 'billed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                                 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';

                        html += `
                            <label class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer ${statusClass} ${isCurrentTable ? 'opacity-60 cursor-not-allowed' : ''}" id="table-checkbox-${table.id}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1">
                                        <input type="checkbox"
                                            value="${table.id}"
                                            onchange="toggleTableSelection(${table.id})"
                                            ${isCurrentTable ? 'disabled' : ''}
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <svg fill="currentColor" class="w-6 h-6 text-gray-700 dark:text-gray-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44.999 44.999" xml:space="preserve">
                                            <path d="m42.558 23.378 2.406-10.92a1.512 1.512 0 0 0-2.954-.652l-2.145 9.733h-9.647a1.512 1.512 0 0 0 0 3.026h.573l-3.258 7.713a1.51 1.51 0 0 0 1.393 2.102c.59 0 1.15-.348 1.394-.925l2.974-7.038 4.717.001 2.971 7.037a1.512 1.512 0 1 0 2.787-1.177l-3.257-7.713h.573a1.51 1.51 0 0 0 1.473-1.187m-28.35 1.186h.573a1.512 1.512 0 0 0 0-3.026H5.134L2.99 11.806a1.511 1.511 0 1 0-2.954.652l2.406 10.92a1.51 1.51 0 0 0 1.477 1.187h.573L1.234 32.28a1.51 1.51 0 0 0 .805 1.98 1.515 1.515 0 0 0 1.982-.805l2.971-7.037 4.717-.001 2.972 7.038a1.514 1.514 0 0 0 1.982.805 1.51 1.51 0 0 0 .805-1.98z"/>
                                            <path d="M24.862 31.353h-.852V18.308h8.13a1.513 1.513 0 1 0 0-3.025H12.856a1.514 1.514 0 0 0 0 3.025h8.13v13.045h-.852a1.514 1.514 0 0 0 0 3.027h4.728a1.513 1.513 0 1 0 0-3.027"/>
                                        </svg>
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-900 dark:text-white select-none">
                                                ${table.table_code}
                                                ${isCurrentTable ? '<span class="text-xs text-blue-600 dark:text-blue-400 ml-2">(@lang("modules.order.currentTable"))</span>' : ''}
                                            </div>
                                            ${orderInfo ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${orderInfo}</div>` : ''}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        ${orderStatus ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusBadgeClass}">${orderStatus.charAt(0).toUpperCase() + orderStatus.slice(1)}</span>` : ''}
                                    </div>
                                </div>
                            </label>
                        `;
                    });

                    html += `
                                </div>
                            </div>
                            <div id="selectedTablesCount" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800" style="display: none;">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    <strong id="selectedCount">0</strong> @lang('modules.order.tablesSelectedForMerge')
                                </p>
                            </div>
                        </div>
                    `;
                }

                $('#mergeTableModalContent').html(html);
                // Reset button state
                $('#mergeTablesButton').prop('disabled', true);
            }
        },
        error: function(xhr) {
            const errorMessage = xhr.responseJSON?.message || @json(__('messages.somethingWentWrong'));
            $('#mergeTableModalContent').html(`
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">${errorMessage}</p>
                </div>
            `);
        }
    });
};

/**
 * Close merge table modal
 */
window.closeMergeTableModal = function() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#mergeTableModal').hide();
        // Clear selections
        $('#mergeTableModalContent input[type="checkbox"]').prop('checked', false);
        $('#mergeTablesButton').prop('disabled', true);
    }
};

/**
 * Toggle table selection and update UI
 */
window.toggleTableSelection = function(tableId) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    // Get selected count
    const selectedCount = $('#mergeTableModalContent input[type="checkbox"]:checked').length;

    // Enable/disable merge button
    $('#mergeTablesButton').prop('disabled', selectedCount === 0);

    // Update selected tables count display
    if (selectedCount > 0) {
        $('#selectedCount').text(selectedCount);
        $('#selectedTablesCount').show();
    } else {
        $('#selectedTablesCount').hide();
    }

    // Update checkbox label styling
    const $checkbox = $(`input[value="${tableId}"]`);
    const $label = $checkbox.closest('label');

    if ($checkbox.is(':checked')) {
        $label.addClass('bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700');
    } else {
        $label.removeClass('bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700');
    }
};

/**
 * Merge selected tables into current order
 */
window.mergeSelectedTables = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    const selectedTableIds = [];
    $('#mergeTableModalContent input[type="checkbox"]:checked').each(function() {
        selectedTableIds.push(parseInt($(this).val()));
    });

    if (selectedTableIds.length === 0) {
        window.showToast('error', @json(__('modules.order.selectAtLeastOneTable')));
        return;
    }

    // Show loading state on button
    const $button = $('#mergeTablesButton');
    $button.prop('disabled', true);
    $('#mergeButtonText').hide();
    $('#mergeButtonLoading').removeClass('hidden');

    $.easyAjax({
        url: "{{ route('ajax.pos.merge-tables') }}",
        type: "POST",
        data: {
            table_ids: selectedTableIds,
            current_table_id: window.posState.tableId || null,
            order_type_id: window.posState.orderTypeId || null,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                window.closeMergeTableModal();
                window.showToast('success', response.message || '@lang("modules.order.tablesmergedSuccessfully")');
                const shouldReload = response?.data?.reload_required === true;
                if (shouldReload) {
                    // Merge payload is stored in session and consumed on next page load.
                    // Reload is required so merged items/totals are hydrated into POS state.
                    setTimeout(function() {
                        window.location.reload();
                    }, 250);
                    return;
                }

            }
        },
        error: function(xhr) {
            const errorMessage = xhr.responseJSON?.message || @json(__('messages.somethingWentWrong'));
            window.showToast('error', errorMessage);

            // Reset button state
            $button.prop('disabled', false);
            $('#mergeButtonText').show();
            $('#mergeButtonLoading').addClass('hidden');
        }
    });
};

// Ensure table modal always loads data when opened from POS
window.showTableModal = function() {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        return;
    }
    $('#tableModal').show();
    // Load available tables content
    if (typeof loadAvailableTables === 'function') {
        loadAvailableTables();
    }
};

// All functions are already globally accessible via window.*
// All DOM manipulation uses jQuery
// All AJAX requests use $.easyAjax
// Native JavaScript is only used for: URL manipulation, Array/Object methods, window.location (all standard APIs)

// Additional functions for KOT items (for new_order_items.blade.php)
window.updatePickupDate = function(value) {
    if (typeof window.posState !== 'undefined') {
        window.posState.pickupDate = value;
    }
};

window.updatePickupTime = function(value) {
    if (typeof window.posState !== 'undefined') {
        window.posState.pickupTime = value;
    }
};

window.confirmCancelOrder = function() {
    const orderID = window.posState ? window.posState.orderID : null;
    if (!orderID) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }

    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery not loaded');
        return;
    }

    // You can show a modal to get cancel reason here
    // For now, just confirm and cancel
    if (confirm('@lang("modules.order.cancelOrderMessage")')) {
        $.easyAjax({
            url: "{{ route('ajax.pos.cancel-order', ['id' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderID),
            type: "POST",
            data: {
                cancel_reason_text: 'Cancelled from POS',
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = "{{ route('pos.index') }}";
                }
            }
        });
    }
};

window.confirmDeleteOrder = function() {
    const orderID = window.posState ? window.posState.orderID : null;
    if (!orderID) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }

    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery not loaded');
        return;
    }

    if (confirm(@json(__('modules.order.deleteOrderMessage')))) {
        $.easyAjax({
            url: "{{ route('ajax.pos.delete-order', ['id' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderID),
            type: "DELETE",
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = "{{ route('pos.index') }}";
                }
            }
        });
    }
};

function updateOrderStatusUI(newStatus) {
    const stepsContainer = document.getElementById('order-status-steps');
    if (!stepsContainer) return;

    const steps = Array.from(stepsContainer.querySelectorAll('.order-status-step'));
    if (!steps.length) return;

    // Compute statuses based on order type
    const orderType = window.posState ? window.posState.orderType : 'dine_in';
    let statuses;
    switch (orderType) {
        case 'delivery':
            statuses = ['placed', 'confirmed', 'preparing', 'food_ready', 'picked_up', 'out_for_delivery', 'reached_destination', 'delivered'];
            break;
        case 'pickup':
            statuses = ['placed', 'confirmed', 'preparing', 'ready_for_pickup', 'delivered'];
            break;
        default:
            statuses = ['placed', 'confirmed', 'preparing', 'food_ready', 'served'];
    }

    const currentIndex = statuses.indexOf(newStatus);
    if (currentIndex === -1) return;

    // Update step icons (match Blade classes for completed/current/next states)
    steps.forEach((step, index) => {
        const icon = step.querySelector('.order-status-icon');
        if (!icon) return;

        icon.classList.remove(
            'bg-skin-base',
            'text-white',
            'ring-2',
            'ring-skin-base',
            'ring-offset-1',
            'sm:ring-offset-2',
            'dark:ring-offset-gray-800',
            'bg-gray-100',
            'dark:bg-gray-700',
            'text-gray-500',
            'dark:text-gray-400',
            'ring-gray-300',
            'dark:ring-gray-600',
            'text-gray-400',
            'dark:text-gray-500'
        );

        if (index <= currentIndex) {
            // Completed/current steps
            icon.classList.add(
                'bg-skin-base',
                'text-white',
                'ring-2',
                'ring-skin-base',
                'ring-offset-1',
                'sm:ring-offset-2',
                'dark:ring-offset-gray-800'
            );
        } else if (index === currentIndex + 1) {
            // Next step
            icon.classList.add(
                'bg-gray-100',
                'dark:bg-gray-700',
                'text-gray-500',
                'dark:text-gray-400',
                'ring-2',
                'ring-gray-300',
                'dark:ring-gray-600',
                'ring-offset-1',
                'sm:ring-offset-2',
                'dark:ring-offset-gray-800'
            );
        } else {
            // Future steps
            icon.classList.add(
                'bg-gray-100',
                'dark:bg-gray-700',
                'text-gray-400',
                'dark:text-gray-500'
            );
        }
    });

    // Update progress bar width (uses same formula as Blade)
    const progressContainer = stepsContainer.previousElementSibling;
    if (progressContainer) {
        const progressBar = progressContainer.querySelector('.h-full.bg-skin-base');
        if (progressBar && statuses.length > 1) {
            const width = currentIndex > 0 ? (currentIndex / (statuses.length - 1)) * 100 : 0;
            progressBar.style.width = `${width}%`;
        }
    }

    // Update badge
    const badge = document.getElementById('order-status-badge');
    if (badge) {
        const labelEl = steps[currentIndex]?.querySelector('.order-status-label');
        if (labelEl) {
            badge.textContent = labelEl.textContent.trim();
        }
        badge.setAttribute('data-status', newStatus);
    }

    // Update cancel button
    const cancelBtn = document.getElementById('order-status-cancel-btn');
    if (cancelBtn) {
        cancelBtn.classList.toggle('hidden', newStatus !== 'placed');
    }

    // Update next button
    const nextBtn = document.getElementById('order-status-next-btn');
    const nextLabel = document.getElementById('order-status-next-label');
    if (nextBtn && nextLabel) {
        if (currentIndex < statuses.length - 1) {
            const nextStatus = statuses[currentIndex + 1];
            const nextLabelEl = steps[currentIndex + 1]?.querySelector('.order-status-label');
            const nextLabelText = nextLabelEl ? nextLabelEl.textContent.trim() : '';

            nextBtn.classList.remove('hidden');
            nextBtn.setAttribute('onclick', `updateOrderStatus('${nextStatus}')`);

            const moveToLabel = window.posConfig?.moveToLabel || 'Move to';
            nextLabel.textContent = `${moveToLabel} ${nextLabelText}`;
        } else {
            nextBtn.classList.add('hidden');
        }
    }
}

window.updateOrderStatus = function(newStatus) {
    const orderID = window.posState ? window.posState.orderID : null;
    if (!orderID) {
        console.error('Order ID not found');
        alert(@json(__('modules.order.orderNotFound')));
        return;
    }

    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery not loaded');
        return;
    }

    $.easyAjax({
        url: "{{ route('ajax.pos.update-order-status', ['id' => '__ORDER_ID__']) }}".replace('__ORDER_ID__', orderID),
        type: "POST",
        data: {
            status: newStatus,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Update local state
                if (window.posState) {
                    window.posState.orderStatus = newStatus;
                    // Keep orderDetail in sync so later actions (bill/kot) post the latest status.
                    if (window.posState.orderDetail) {
                        if (typeof window.posState.orderDetail === 'object' && window.posState.orderDetail !== null) {
                            if (window.posState.orderDetail.order_status && typeof window.posState.orderDetail.order_status === 'object') {
                                window.posState.orderDetail.order_status.value = newStatus;
                            } else {
                                window.posState.orderDetail.order_status = { value: newStatus };
                            }
                        }
                    }
                }

                // Show success message
                if (typeof Livewire !== 'undefined' && Livewire.dispatch) {
                    Livewire.dispatch('alert', {
                        type: 'success',
                        message: response.message,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    showToast('success', response.message);
                }

                // Update UI without page reload
                updateOrderStatusUI(newStatus);
            }
        },
        error: function(xhr) {
            const errorMessage = xhr.responseJSON?.message || @json(__('messages.somethingWentWrong'));
            alert(errorMessage);
        }
    });
};

/**
 * Delete order item via AJAX
 */
window.deleteOrderItem = function(itemId, itemKey = null) {
    if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        alert(@json(__('messages.somethingWentWrong')));
        return;
    }

    const orderId = window.posState ? window.posState.orderID : null;

    if (!orderId) {
        alert(@json(__('modules.order.orderNotFound')));
        return;
    }

    const doDelete = function() {
        $.easyAjax({
            url: `/ajax/pos/orders/${orderId}/items/${itemId}`,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    if (typeof Livewire !== 'undefined' && Livewire.dispatch) {
                        Livewire.dispatch('alert', {
                            type: 'success',
                            message: response.message,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        showToast('success', response.message);
                    }

                    // If order was deleted, redirect
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }
                    // Order detail view: update UI without reload
                    var showOrderDetail = window.posState && (window.posState.showOrderDetail === true || window.posState.showOrderDetail === 'true');
                    if (showOrderDetail && response.order) {
                        var row = document.querySelector('tr[data-order-item-id="' + itemId + '"]');
                        if (row) row.remove();
                        var countEl = document.getElementById('order-detail-items-count');
                        if (countEl && response.order.items_count !== undefined) countEl.textContent = response.order.items_count;
                        if (typeof window.updateOrderDetailTotalsFromResponse === 'function') {
                            window.updateOrderDetailTotalsFromResponse(response.order, null);
                        }
                    } else {
                        if (itemKey) {
                            window.removeItemKeyFromPosState(itemKey);
                        } else {
                            window.removeOrderItemFromPosStateById(itemId);
                        }

                        if (typeof window.calculateTotal === 'function') {
                            window.calculateTotal();
                        }
                        if (typeof window.updateOrderItemsContainer === 'function') {
                            window.updateOrderItemsContainer();
                        }
                    }
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || @json(__('messages.somethingWentWrong'));
                alert(errorMessage);
            }
        });
    };

    if (typeof Swal !== 'undefined') {

        Swal.fire({
            title: @json(__('modules.order.deleteOrderItem') . '?'),
            text: @json(__('modules.order.deleteOrderItemMessage')),
            showCancelButton: true,
            confirmButtonText: @json(__('app.delete')),
        }).then((result) => {
            if (result.isConfirmed) {
                doDelete();
            }
        });
    } else {
        // Fallback to native confirm
        if (confirm('@lang("messages.confirmDeleteItem")')) {
            doDelete();
        }
    }
};

// ============================
// Order Detail Functions
// ============================

/**
 * Open table change confirmation modal
 */
window.openTableChangeConfirmation = function() {
    $('#tableModal').show();
    // Load available tables
    loadAvailableTables();
};

/**
 * Load available tables for selection
 */
function loadAvailableTables() {
    $.easyAjax({
        url: '/ajax/pos/tables',
        type: 'GET',
        success: function(response) {
            renderTablesModal(response.tables || response);
        }
    });
}

/**
 * Render tables in modal
 */
function renderTablesModal(tables) {
    let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">';

    tables.forEach(table => {
        const isOccupied = table.status === 'occupied';
        const isCurrentTable = window.posState.tableId === table.id;

        html += `
            <button type="button"
                onclick="selectTable(${table.id}, '${table.table_code}')"
                class="p-4 border-2 rounded-lg transition-all ${
                    isCurrentTable ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' :
                    isOccupied ? 'border-red-300 bg-red-50 dark:bg-red-900/20 cursor-not-allowed opacity-60' :
                    'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10'
                }"
                ${isOccupied && !isCurrentTable ? 'disabled' : ''}>
                <div class="text-center">
                    <div class="font-semibold text-lg">${table.table_code}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ${isCurrentTable ? 'Current' : isOccupied ? 'Occupied' : 'Available'}
                    </div>
                </div>
            </button>
        `;
    });

    html += '</div>';
    html += `<div class="mt-4 flex justify-end">
        <button type="button" onclick="closeTableModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
            ${window.trans?.app?.cancel || 'Cancel'}
        </button>
    </div>`;

    $('#tableModalContent').html(html);
}

/**
 * Select a table
 */
window.selectTable = function(tableId, tableCode) {
    // Check if this is a different table
    if (window.posState.tableId && window.posState.tableId !== tableId) {
        // Show confirmation modal
        window.posState.pendingTable = { id: tableId, code: tableCode };
        $('#pendingTableNo').text(tableCode);
        $('#currentTableNo').text(window.posState.tableNo || '--');
        closeTableModal();
        $('#showTableChangeConfirmationModal').show();
    } else {
        // Set the table directly
        setTableForOrder(tableId, tableCode);
        closeTableModal();
    }
};

/**
 * Set table for order
 */
function setTableForOrder(tableId, tableCode) {
    const orderId = typeof window.getCurrentPosOrderId === 'function'
        ? window.getCurrentPosOrderId()
        : (window.posState.orderID || window.posState.orderDetail?.id || null);

    if (!orderId) {
        // For new orders, just update the state
        window.posState.tableId = tableId;
        window.posState.tableNo = tableCode;
        closeTableModal();
        return;
    }

    // For existing orders, update via API
    $.easyAjax({
        url: '/ajax/pos/set-table',
        type: 'POST',
        data: {
            order_id: orderId,
            table_id: tableId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                if (!window.posState.orderID) {
                    window.posState.orderID = orderId;
                }
                window.posState.tableId = tableId;
                window.posState.tableNo = tableCode;
                if (typeof updateTableDisplay === 'function') {
                    updateTableDisplay({ id: tableId, table_code: tableCode });
                }
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to update table';
            showToast('error', error);
        }
    });
}

/**
 * Cancel table change (pending confirmation + table picker modal)
 */
window.cancelTableChange = function() {
    window.posState.pendingTable = null;
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $('#showTableChangeConfirmationModal').hide();
    }
    if (typeof window.closeTableChangeModal === 'function') {
        window.closeTableChangeModal();
    }
};

/**
 * Confirm table change (apply pending table or just close picker)
 */
window.confirmTableChange = function() {
    if (window.posState.pendingTable) {
        setTableForOrder(window.posState.pendingTable.id, window.posState.pendingTable.code);
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            $('#showTableChangeConfirmationModal').hide();
        }
        window.posState.pendingTable = null;
    } else if (typeof window.closeTableChangeModal === 'function') {
        window.closeTableChangeModal();
    }
};

/**
 * Show cancel order modal
 */
window.showCancelOrderModal = function() {
    $('#confirmDeleteModal').show();
};

/**
 * Close cancel order modal
 */
window.closeCancelOrderModal = function() {
    $('#confirmDeleteModal').hide();
    $('#cancelReason').val('');
    $('#cancelReasonText').val('');
    $('#cancelReasonError').hide();
};

/**
 * Cancel order
 */
window.cancelOrder = function() {
    const orderId = window.posState.orderID;
    const cancelReason = $('#cancelReason').val();
    const cancelReasonText = $('#cancelReasonText').val();

    if (!orderId) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }

    if (!cancelReason && !cancelReasonText) {
        $('#cancelReasonError').text(@json(__('modules.settings.selectCancelReason'))).show();
        return;
    }

    $.easyAjax({
        url: `/ajax/pos/orders/${orderId}/cancel`,
        type: 'POST',
        data: {
            cancel_reason_id: cancelReason,
            cancel_reason_text: cancelReasonText
        },
        success: function(response) {
            if (response.success) {
                closeCancelOrderModal();
                showToast('success', response.message || 'Order cancelled successfully');
                if (window.posState) {
                    window.posState.status = 'canceled';
                    window.posState.orderStatus = 'cancelled';
                }
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to cancel order';
            $('#cancelReasonError').text(error).show();
        }
    });
};


/**
 * Show payment modal/page
 */
window.showPayment = function(orderId) {
    window.location.href = `/orders/payment/${orderId}`;
};

/**
 * Print order via AJAX (PosAjaxController::ajaxPrintOrder) — directPrint vs URL same as Livewire Pos.
 */
window.printOrder = function(orderId, triggerButton = null) {
    if (window.__posPrintOrderInProgress) {
        return;
    }
    const id = orderId || (window.posState && window.posState.orderID) || null;
    if (!id) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }
    window.__posPrintOrderInProgress = true;
    if (typeof window.setGlobalOrderActionLock === 'function') {
        window.setGlobalOrderActionLock(true);
    }
    if (typeof window.toggleSingleActionButton === 'function') {
        window.toggleSingleActionButton(triggerButton, true);
    }
    const releasePrintButton = function() {
        window.__posPrintOrderInProgress = false;
        if (typeof window.setGlobalOrderActionLock === 'function') {
            window.setGlobalOrderActionLock(false);
        }
        if (typeof window.toggleSingleActionButton === 'function') {
            window.toggleSingleActionButton(triggerButton, false);
        }
    };
    if (typeof window.ajaxPrintOrderById === 'function') {
        window.ajaxPrintOrderById(id);
        setTimeout(releasePrintButton, 900);
        return;
    }
    const printUrl = '/orders/print/' + id;
    if (typeof window.printLocation === 'function') {
        window.printLocation(printUrl);
    } else {
        window.open(printUrl, '_blank');
    }
    setTimeout(releasePrintButton, 900);
};

/**
 * Print KOT via AJAX (PosAjaxController::ajaxPrintKot). Pass kotId or uses currentKotId / last KOT on order.
 */
window.printKot = function(kotId) {
    let id = kotId;
    if (!id && typeof window.currentKotId !== 'undefined' && window.currentKotId) {
        id = window.currentKotId;
    }
    if (!id && window.posState && window.posState.orderDetail) {
        const od = window.posState.orderDetail;
        const list = od.kot || od.kots;
        if (Array.isArray(list) && list.length) {
            id = list[list.length - 1].id;
        }
    }
    if (!id) {
        alert('@lang("modules.order.orderNotFound")');
        return;
    }
    const orderIdForPrint = window.posState && window.posState.orderID ? parseInt(window.posState.orderID, 10) : 0;
    if (orderIdForPrint && typeof window.ajaxPrintKotForOrder === 'function') {
        window.ajaxPrintKotForOrder(orderIdForPrint, [id]);
        return;
    }
    if (typeof window.ajaxPrintKotById === 'function') {
        window.ajaxPrintKotById(id);
        return;
    }
    const orderId = window.posState.orderID || {{ optional($orderDetail)->id ?? 'null' }};
    const printUrl = '/pos/kot/' + (orderId || '');
    if (typeof window.printLocation === 'function') {
        window.printLocation(printUrl);
    } else {
        window.open(printUrl, '_blank');
    }
};

/**
 * Create new KOT for existing order
 */
window.newKot = function() {
    const orderId = window.posState.orderID;
    if (!orderId) {
        console.error('Order ID not found');
        return;
    }
    window.location.href = `/pos/kot/${orderId}`;
};

// Initialize order detail data if viewing order detail page
@if(isset($orderDetail) && $orderDetail)
    // Override posState with order detail data using JSON parse to avoid XrayWrapper issues
    try {
        window.posState.orderID = {{ $orderDetail->id }};
        window.posState.orderDetail = JSON.parse({!! json_encode(json_encode($orderDetail)) !!});
        window.posState.orderStatus = '{{ $orderDetail->order_status->value }}';
    } catch (e) {
        console.error('Error initializing order detail:', e);
    }
@endif

</script>
@endpush
