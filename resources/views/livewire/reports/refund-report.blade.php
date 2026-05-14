<div>
    <!-- Header Section -->
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('modules.refund.refundReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.refund.refundReportMessage')
                @php
                    $formattedStartTime = \Carbon\Carbon::parse($startTime)->format('h:i A');
                    $formattedEndTime = \Carbon\Carbon::parse($endTime)->format('h:i A');
                @endphp
                <strong>
                    ({{ $startDate === $endDate
                        ? __('modules.report.salesDataFor') . " $startDate, " . __('modules.report.timePeriod') . " $formattedStartTime - $formattedEndTime"
                        : __('modules.report.salesDataFrom') . " $startDate " . __('app.to') . " $endDate, " . __('modules.report.timePeriodEachDay') . " $formattedStartTime - $formattedEndTime" }})
                </strong>
            </p>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <!-- Total Refunds -->
            <div class="p-4 bg-skin-base/10 rounded-xl shadow-sm dark:bg-skin-base/10 border border-skin-base/30 dark:border-skin-base/40">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-skin-base dark:text-skin-base">@lang('modules.refund.totalRefunds')</h3>
                    <div class="p-2 bg-skin-base/10 rounded-lg dark:bg-skin-base/10">
                        <svg class="w-4 h-4 text-skin-base dark:text-skin-base" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-skin-base dark:text-skin-base">
                    {{ $this->totalRefunds }}
                </p>
            </div>

            <!-- Total Refund Amount -->
            <div class="p-4 bg-red-50 rounded-xl shadow-sm dark:bg-red-900/10 border border-red-100 dark:border-red-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-red-600 dark:text-red-400">@lang('modules.refund.totalRefundAmount')</h3>
                    <div class="p-2 bg-red-100 rounded-lg dark:bg-red-900/50">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">
                    {{ currency_format($this->totalRefundAmount, restaurant()->currency_id) }}
                </p>
            </div>

            <!-- Total Original Amount -->
            <div class="p-4 bg-blue-50 rounded-xl shadow-sm dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-600 dark:text-blue-400">@lang('modules.refund.totalOriginalAmount')</h3>
                    <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900/50">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.5 13.75c0 .97.75 1.75 1.67 1.75h1.88c.8 0 1.45-.68 1.45-1.53 0-.91-.4-1.24-.99-1.45l-3.01-1.05c-.59-.21-.99-.53-.99-1.45 0-.84.65-1.53 1.45-1.53h1.88c.92 0 1.67.78 1.67 1.75M12 7.5v9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 12c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2m10 4V2h-4m-1 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">
                    {{ currency_format($this->totalOriginalAmount, restaurant()->currency_id) }}
                </p>
            </div>

            <!-- Commission Adjustment -->
            <div class="p-4 bg-orange-50 rounded-xl shadow-sm dark:bg-orange-900/10 border border-orange-100 dark:border-orange-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-orange-600 dark:text-orange-400">@lang('modules.refund.commissionAdjustment')</h3>
                    <div class="p-2 bg-orange-100 rounded-lg dark:bg-orange-900/50">
                        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">
                    {{ currency_format($this->totalCommissionAdjustment, restaurant()->currency_id) }}
                </p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="sm:pr-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <x-select id="dateRangeType" class="block w-full sm:w-fit mb-2 lg:mb-0" wire:model.defer="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                        </x-select>

                        <div id="date-range-picker" date-rangepicker class="flex items-center w-full">
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                                </div>
                                <input id="datepicker-range-start" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500 dark:text-gray-100">@lang('app.to')</span>
                            <div class="relative">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20zM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2"/></svg>
                                </div>
                                <input id="datepicker-range-end" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
                            </div>
                        </div>

                        <div class="lg:flex items-center justify-between gap-x-2 ms-2">
                            <div class="w-full max-w-[7rem]">
                                <label for="start-time" class="sr-only">@lang('modules.reservation.timeStart'):</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 end-0 top-0 pe-3.5 ps-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                    </div>
                                    <x-input id="start-time" type="time" wire:model.live.debounce.500ms="startTime" />
                                </div>
                            </div>
                            <span class="mx-2 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                            <div class="w-full max-w-[7rem]">
                                <label for="end-time" class="sr-only">@lang('modules.reservation.timeEnd'):</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 end-0 top-0 pe-3.5 ps-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                    </div>
                                    <x-input id="end-time" type="time" wire:model.live.debounce.500ms="endTime" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 lg:items-center w-full lg:w-auto">
                <div class="relative w-full sm:w-auto">
                    <x-input id="search" class="block w-full pr-10" type="text"
                        placeholder="{{ __('placeholders.searchRefunds') }}" wire:model.live.debounce.500ms="searchTerm" />
                    @if($searchTerm)
                        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3" wire:click="$set('searchTerm', '')">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                <x-select id="refundTypeFilter" class="block w-full sm:w-auto" wire:model.live="refundTypeFilter">
                    <option value="">@lang('app.all') @lang('modules.refund.refundTypes')</option>
                    <option value="full">@lang('modules.refund.fullRefund')</option>
                    <option value="partial">@lang('modules.refund.partialRefund')</option>
                    <option value="waste">@lang('modules.refund.wasteRefund')</option>
                </x-select>

                <a href="javascript:;" wire:click='exportReport'
                    class="inline-flex items-center w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                    @lang('app.export')
                </a>
            </div>
        </div>
    </div>

    <!-- Refunds Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-4 rounded-lg">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('processed_at')" class="hover:text-gray-900 dark:hover:text-white transition-colors {{ $sortBy === 'processed_at' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('app.date')
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.order.orderNumber')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('refund_type')" class="hover:text-gray-900 dark:hover:text-white transition-colors {{ $sortBy === 'refund_type' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.refund.refundType')
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.settings.refundReason')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('processed_by')" class="hover:text-gray-900 dark:hover:text-white transition-colors {{ $sortBy === 'processed_by' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.refund.processedBy')
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-right rtl:text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.refund.originalPrice')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-right rtl:text-left text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('amount')" class="hover:text-gray-900 dark:hover:text-white transition-colors ltr:ml-auto rtl:mr-auto {{ $sortBy === 'amount' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.refund.refundedAmount')
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-right rtl:text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.refund.resalePrice')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.order.deliveryApp')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-right rtl:text-left text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.refund.commissionAdjustment')
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.refund.inventoryChange')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse ($this->refunds as $refund)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $refund->processed_at ? \Carbon\Carbon::parse($refund->processed_at)->setTimezone(timezone())->format('M d, Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $refund->payment && $refund->payment->order ? ($refund->payment->order->show_formatted_order_number ?? $refund->payment->order->order_number) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $typeLabels = [
                                    'full' => __('modules.refund.fullRefund'),
                                    'partial' => __('modules.refund.partialRefund'),
                                    'waste' => __('modules.refund.wasteRefund')
                                ];
                                $typeColors = [
                                    'full' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'waste' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$refund->refund_type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $typeLabels[$refund->refund_type] ?? $refund->refund_type }}
                            </span>
                            @if($refund->refund_type === 'partial' && $refund->partial_refund_type)
                                @php
                                    $partialTypeLabels = [
                                        'fixed' => __('modules.refund.fixedAmount'),
                                        'custom' => __('modules.refund.customAmount')
                                    ];
                                @endphp
                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $partialTypeLabels[$refund->partial_refund_type] ?? $refund->partial_refund_type }})
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $refund->refundReason->reason ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $refund->processedBy->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white text-right">
                            {{ currency_format($refund->payment->amount ?? 0, restaurant()->currency_id) }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-red-600 dark:text-red-400 text-right">
                            {{ currency_format($refund->amount, restaurant()->currency_id) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">
                            {{ __('app.na') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            @if($refund->deliveryPlatform)
                                {{ $refund->deliveryPlatform->name }}
                            @elseif($refund->payment && $refund->payment->order && $refund->payment->order->deliveryPlatform)
                                {{ $refund->payment->order->deliveryPlatform->name }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                            @if($refund->commission_adjustment && $refund->commission_adjustment > 0)
                                <span class="text-orange-600 dark:text-orange-400">
                                    {{ currency_format($refund->commission_adjustment, restaurant()->currency_id) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if($refund->refund_type === 'waste')
                                <span class="text-red-600 dark:text-red-400">@lang('modules.refund.writeOff')</span>
                            @else
                                {{ __('app.na') }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                            @lang('messages.noRecordFound')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @script
    <script>
        const datepickerEl1 = document.getElementById('datepicker-range-start');

        datepickerEl1.addEventListener('changeDate', (event) => {
            $wire.dispatch('setStartDate', { start: datepickerEl1.value });
        });

        const datepickerEl2 = document.getElementById('datepicker-range-end');

        datepickerEl2.addEventListener('changeDate', (event) => {
            $wire.dispatch('setEndDate', { end: datepickerEl2.value });
        });
    </script>
    @endscript
</div>
