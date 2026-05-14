<div>
    <!-- Header Section -->
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.itemReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @lang('modules.report.itemReportMessage')
                <strong>
                    ({{ $startDate === $endDate
                        ? __('modules.report.salesDataFor') . " $startDate, " . __('modules.report.timePeriod') . " " . \Carbon\Carbon::createFromFormat('H:i', $startTime)->format(timeFormat()) . " - " . \Carbon\Carbon::createFromFormat('H:i', $endTime)->format(timeFormat())
                        : __('modules.report.salesDataFrom') . " $startDate " . __('app.to') . " $endDate, " . __('modules.report.timePeriodEachDay') . " " . \Carbon\Carbon::createFromFormat('H:i', $startTime)->format(timeFormat()) . " - " . \Carbon\Carbon::createFromFormat('H:i', $endTime)->format(timeFormat()) }})
                </strong>
            </p>
        </div>

        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <!-- Sum Of Total Revenue -->
            <div class="p-4 bg-skin-base/10 rounded-xl shadow-sm dark:bg-skin-base/10 border border-skin-base/30 dark:border-skin-base/40">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-skin-base dark:text-skin-base">@lang('modules.report.sumOfTotalRevenue')</h3>
                <div class="p-2 bg-skin-base/10 rounded-lg dark:bg-skin-base/10">
                <svg class="w-4 h-4 text-skin-base dark:text-skin-base" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 13.75c0 .97.75 1.75 1.67 1.75h1.88c.8 0 1.45-.68 1.45-1.53 0-.91-.4-1.24-.99-1.45l-3.01-1.05c-.59-.21-.99-.53-.99-1.45 0-.84.65-1.53 1.45-1.53h1.88c.92 0 1.67.78 1.67 1.75M12 7.5v9"/><path d="M22 12c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2m10 4V2h-4m-1 5 5-5"/></g></svg>
                </div>
            </div>
            <p class="text-3xl break-words font-bold text-skin-base dark:text-skin-base">
                {{ currency_format($this->totalRevenue, restaurant()->currency_id) }}
            </p>
            </div>

            <!-- Total Quantity Sold Card -->
            <div class="p-4 bg-emerald-50 rounded-xl shadow-sm dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-emerald-600 dark:text-emerald-400">@lang('modules.report.totalQuantitySold')</h3>
                <div class="p-2 bg-emerald-100 rounded-lg dark:bg-emerald-900/50">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="icon glyph"><path d="M18.22 17H9.8a2 2 0 0 1-2-1.55L5.2 4H3a1 1 0 0 1 0-2h2.2a2 2 0 0 1 2 1.55L9.8 15h8.42L20 7.76a1 1 0 0 1 2 .48l-1.81 7.25A2 2 0 0 1 18.22 17m-1.72 2a1.5 1.5 0 1 0 1.5 1.5 1.5 1.5 0 0 0-1.5-1.5m-5 0a1.5 1.5 0 1 0 1.5 1.5 1.5 1.5 0 0 0-1.5-1.5m3.21-9.29 4-4a1 1 0 1 0-1.42-1.42L14 7.59l-1.29-1.3a1 1 0 0 0-1.42 1.42l2 2a1 1 0 0 0 1.42 0" fill="currentColor"/></svg>
                </div>
            </div>
            <p class="text-3xl break-words font-bold text-gray-800 dark:text-gray-100">
                {{ $this->totalQuantitySold }}
            </p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="flex flex-wrap justify-between items-center gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="flex flex-row items-center gap-4 w-full min-w-0 sm:flex-1">
                <div class="relative flex-1 min-w-0">
                    <x-input id="menu_name" class="block  pr-10" type="text"
                        placeholder="{{ __('placeholders.searchMenuItems') }}" wire:model.live.debounce.500ms="searchTerm" />
                    @if($searchTerm)
                        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3" wire:click="$set('searchTerm', '')">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                <div class="shrink-0">
                    <a href="javascript:;" wire:click='exportReport'
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                        @lang('app.export')
                    </a>
                </div>
            </div>
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="sm:pr-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <select id="dateRangeType" class="px-4 py-2 text-lg font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600" wire:model.defer="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                        </select>

                        <select
                            wire:model.live="selectedHandler"
                            wire:change="filterHandler"
                            class=" px-4 py-2 text-lg font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600">
                            <option value="">@lang('modules.report.allHandlers')</option>
                            @foreach($handlers ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>

                        <select
                            wire:model.live="selectedWaiter"
                            wire:change="filterWaiter"
                            class=" px-4 py-2 text-lg font-medium text-gray-900 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600">
                            <option value="">@lang('modules.report.allWaiters')</option>
                            @foreach($waiters ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>

                        <div class="lg:flex items-center gap-1">
                            <div class="w-full max-w-[15rem]" wire:key="start-date-{{ $startDate }}">
                                <x-datepicker wire:model.change='startDate' placeholder="@lang('app.selectStartDate')" />
                            </div>
                            <span class="mx-1 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                            <div class="w-full max-w-[15rem]" wire:key="end-date-{{ $endDate }}">
                                <x-datepicker wire:model.live='endDate' placeholder="@lang('app.selectEndDate')" />
                            </div>
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


        </div>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 p-4 rounded-lg">
        <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('item_name')" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors {{ $sortBy === 'item_name' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.menu.itemName')
                            <svg width="24" height="24" class="w-4 h-4" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <path @class(['opacity-100' => $sortBy === 'item_name' && $sortDirection === 'asc', 'opacity-30' => !($sortBy === 'item_name' && $sortDirection === 'asc')]) fill="currentColor" d="M11 7h-6l3-4z"></path>
                                <path @class(['opacity-100' => $sortBy === 'item_name' && $sortDirection === 'desc', 'opacity-30' => !($sortBy === 'item_name' && $sortDirection === 'desc')]) fill="currentColor" d="M5 9h6l-3 4z"></path>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-left rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('category_name')" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors {{ $sortBy === 'category_name' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.menu.categoryName')
                            <svg width="24" height="24" class="w-4 h-4" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <path @class(['opacity-100' => $sortBy === 'category_name' && $sortDirection === 'asc', 'opacity-30' => !($sortBy === 'category_name' && $sortDirection === 'asc')]) fill="currentColor" d="M11 7h-6l3-4z"></path>
                                <path @class(['opacity-100' => $sortBy === 'category_name' && $sortDirection === 'desc', 'opacity-30' => !($sortBy === 'category_name' && $sortDirection === 'desc')]) fill="currentColor" d="M5 9h6l-3 4z"></path>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('quantity_sold')" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors mx-auto {{ $sortBy === 'quantity_sold' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.report.quantitySold')
                            <svg width="24" height="24" class="w-4 h-4" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <path @class(['opacity-100' => $sortBy === 'quantity_sold' && $sortDirection === 'asc', 'opacity-30' => !($sortBy === 'quantity_sold' && $sortDirection === 'asc')]) fill="currentColor" d="M11 7h-6l3-4z"></path>
                                <path @class(['opacity-100' => $sortBy === 'quantity_sold' && $sortDirection === 'desc', 'opacity-30' => !($sortBy === 'quantity_sold' && $sortDirection === 'desc')]) fill="currentColor" d="M5 9h6l-3 4z"></path>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('price')" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors mx-auto {{ $sortBy === 'price' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.report.sellingPrice')
                            <svg width="24" height="24" class="w-4 h-4" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <path @class(['opacity-100' => $sortBy === 'price' && $sortDirection === 'asc', 'opacity-30' => !($sortBy === 'price' && $sortDirection === 'asc')]) fill="currentColor" d="M11 7h-6l3-4z"></path>
                                <path @class(['opacity-100' => $sortBy === 'price' && $sortDirection === 'desc', 'opacity-30' => !($sortBy === 'price' && $sortDirection === 'desc')]) fill="currentColor" d="M5 9h6l-3 4z"></path>
                            </svg>
                        </button>
                    </th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider ltr:text-end rtl:text-right text-gray-600 uppercase dark:text-gray-300">
                        <button wire:click="sortByToggle('total_revenue')" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors ltr:ml-auto rtl:mr-auto {{ $sortBy === 'total_revenue' ? 'font-bold text-gray-900 dark:text-white' : '' }}">
                            @lang('modules.report.totalRevenue')
                            <svg width="24" height="24" class="w-4 h-4" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                <path @class(['opacity-100' => $sortBy === 'total_revenue' && $sortDirection === 'asc', 'opacity-30' => !($sortBy === 'total_revenue' && $sortDirection === 'asc')]) fill="currentColor" d="M11 7h-6l3-4z"></path>
                                <path @class(['opacity-100' => $sortBy === 'total_revenue' && $sortDirection === 'desc', 'opacity-30' => !($sortBy === 'total_revenue' && $sortDirection === 'desc')]) fill="currentColor" d="M5 9h6l-3 4z"></path>
                            </svg>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse ($this->menuItems as $item)
                    @if($item->variations_count > 0)
                        <!-- For items with variations, show each variation as a separate row -->
                        @foreach($item->variations as $variation)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item->item_name }} <span class="text-gray-500 dark:text-gray-400">({{ $variation->variation }})</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $item->category->category_name ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white text-center">
                                        {{ $variation->quantity_sold }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white text-center">
                                        {{ currency_format($variation->price, restaurant()->currency_id) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white text-right">
                                        {{ currency_format($variation->total_revenue, restaurant()->currency_id) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <!-- For items without variations, show a single row -->
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $item->item_name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $item->category->category_name ?? '' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white text-center">
                                    {{ $item->quantity_sold }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white text-center">
                                    {{ currency_format($item->price, restaurant()->currency_id) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white text-right">
                                    {{ currency_format($item->total_revenue, restaurant()->currency_id) }}
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                            @lang('messages.noItemAdded')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
