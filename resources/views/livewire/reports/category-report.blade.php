<div>
    <!-- Header Section -->
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.categoryReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.report.categoryReportMessage')
                <strong>
                    ({{ $startDate === $endDate
                        ? __('modules.report.salesDataFor') . " $startDate, " . __('modules.report.timePeriod') . " " . \Carbon\Carbon::createFromFormat('H:i', $startTime)->format(timeFormat()) . " - " . \Carbon\Carbon::createFromFormat('H:i', $endTime)->format(timeFormat())
                        : __('modules.report.salesDataFrom') . " $startDate " . __('app.to') . " $endDate, " . __('modules.report.timePeriodEachDay') . " " . \Carbon\Carbon::createFromFormat('H:i', $startTime)->format(timeFormat()) . " - " . \Carbon\Carbon::createFromFormat('H:i', $endTime)->format(timeFormat()) }})
                </strong>
            </p>
        </div>

        <!-- Filter Section -->
        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="sm:pr-3" action="#" method="GET">

                    <div class="lg:flex gap-2 items-center">
                        <x-select id="dateRangeType" class="block w-fit" wire:model.defer="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                        </x-select>

                        <div class="flex items-center w-full gap-2">
                            <x-datepicker wire:model.change='startDate' placeholder="@lang('app.selectStartDate')" />
                            <span class="mx-2 text-gray-500 dark:text-gray-100 whitespace-nowrap">@lang('app.to')</span>
                            <x-datepicker wire:model.live='endDate' placeholder="@lang('app.selectEndDate')" />
                        </div>
                        <div class="lg:flex items-center gap-2 ms-2">
                            <div class="w-full max-w-[15rem]">
                                <label for="start-time" class="sr-only">@lang('modules.reservation.timeStart'):</label>
                                <x-time-picker wire:model.live.debounce.500ms="startTime" value="{{ $startTime }}" />
                            </div>
                            <span class="mx-2 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                            <div class="w-full max-w-[15rem]">
                                <label for="end-time" class="sr-only">@lang('modules.reservation.timeEnd'):</label>
                                <x-time-picker wire:model.live.debounce.500ms="endTime" value="{{ $endTime }}" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <a href="javascript:;" wire:click='exportReport'
                class="inline-flex items-center  w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                @lang('app.export')
            </a>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-4">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="p-4 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.menu.itemCategory')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.report.quantitySold')
                    </th>
                    <th class="p-4 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        @lang('modules.order.amount')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse ($menuItems as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-4 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                        {{ $item->category_name }}
                    </td>
                    <td class="p-4 text-sm text-center text-gray-900 dark:text-white">
                        {{ $item->orders->sum('quantity') }}
                    </td>
                    <td class="p-4 text-sm text-center text-gray-900 dark:text-white">
                        {{ currency_format($item->orders->sum(function($order) { return $order->quantity * $order->price; }), restaurant()->currency_id) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4 text-sm text-center text-gray-500 dark:text-gray-400">
                        @lang('messages.noItemAdded')
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
