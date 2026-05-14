<div>
    <div class="p-4 bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('menu.codReport')</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang('modules.report.codReportMessage')</p>
        </div>

        @if ($tableMissing)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700/40 dark:bg-amber-900/10 dark:text-amber-200">
                @lang('modules.delivery.codMonitoringMigrationMessage')
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="p-4 rounded-xl shadow-sm border border-blue-100 bg-blue-50 dark:bg-blue-900/10 dark:border-blue-800">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">@lang('modules.delivery.totalCodOrders')</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $summaryCards['total_orders'] }}</div>
                </div>
                <div class="p-4 rounded-xl shadow-sm border border-amber-100 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-800">
                    <div class="text-sm font-medium text-amber-600 dark:text-amber-400">@lang('modules.report.expectedCodAmount')</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ currency_format($summaryCards['expected_amount'], restaurant()->currency_id) }}</div>
                </div>
                <div class="p-4 rounded-xl shadow-sm border border-cyan-100 bg-cyan-50 dark:bg-cyan-900/10 dark:border-cyan-800">
                    <div class="text-sm font-medium text-cyan-600 dark:text-cyan-400">@lang('modules.report.collectedCodAmount')</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ currency_format($summaryCards['collected_amount'], restaurant()->currency_id) }}</div>
                </div>
                <div class="p-4 rounded-xl shadow-sm border border-emerald-100 bg-emerald-50 dark:bg-emerald-900/10 dark:border-emerald-800">
                    <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400">@lang('modules.report.settledCodAmount')</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800 dark:text-gray-100">{{ currency_format($summaryCards['settled_amount'], restaurant()->currency_id) }}</div>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                <x-select wire:model.live="dateRangeType" class="block w-fit">
                    <option value="today">@lang('app.today')</option>
                    <option value="currentWeek">@lang('app.currentWeek')</option>
                    <option value="lastWeek">@lang('app.lastWeek')</option>
                    <option value="last7Days">@lang('app.last7Days')</option>
                    <option value="currentMonth">@lang('app.currentMonth')</option>
                    <option value="lastMonth">@lang('app.lastMonth')</option>
                    <option value="currentYear">@lang('app.currentYear')</option>
                    <option value="lastYear">@lang('app.lastYear')</option>
                </x-select>

                <div id="date-range-picker" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:w-44">
                        <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                            </svg>
                        </div>
                        <x-datepicker wire:model.change='startDate' placeholder="@lang('app.selectStartDate')" class="pl-10" />
                    </div>
                    <span class="self-center hidden text-gray-500 sm:block dark:text-gray-100">@lang('app.to')</span>
                    <div class="relative w-full sm:w-44">
                        <div class="absolute inset-y-0 flex items-center pointer-events-none start-0 ps-3">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                            </svg>
                        </div>
                        <x-datepicker wire:model.live='endDate' placeholder="@lang('app.selectEndDate')" class="pl-10" />
                    </div>
                </div>

                <x-input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    class="w-full sm:w-64"
                    placeholder="{{ __('modules.report.searchCodReport') }}"
                />

                <x-select wire:model.live="deliveryExecutiveId" class="block w-fit min-w-[200px]">
                        <option value="">@lang('modules.report.allExecutives')</option>
                        @foreach ($executives as $executive)
                            <option value="{{ $executive->id }}">{{ $executive->name }}</option>
                        @endforeach
                </x-select>

                <x-select wire:model.live="status" class="block w-fit min-w-[180px]">
                        <option value="">@lang('modules.delivery.allStatuses')</option>
                        <option value="pending_collection">@lang('modules.delivery.pendingCollection')</option>
                        <option value="collected">@lang('modules.delivery.collected')</option>
                        <option value="submitted">@lang('modules.delivery.submitted')</option>
                        <option value="settled">@lang('modules.delivery.settled')</option>
                        <option value="rejected">@lang('modules.delivery.rejected')</option>
                </x-select>

                @if (in_array('Export Report', restaurant_modules()))
                    <a href="javascript:;" wire:click="exportReport"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                        @lang('app.export')
                    </a>
                @endif
            </div>

            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex flex-wrap gap-6">
                    <button type="button" wire:click="switchTab('order-status')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'order-status',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'order-status',
                    ])>@lang('modules.report.orderPaymentStatusReport')</button>
                    <button type="button" wire:click="switchTab('collections')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'collections',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'collections',
                    ])>@lang('modules.report.codCollectionReport')</button>
                    <button type="button" wire:click="switchTab('pending')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'pending',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'pending',
                    ])>@lang('modules.report.executivePendingCashReport')</button>
                    <button type="button" wire:click="switchTab('settlements')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'settlements',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'settlements',
                    ])>@lang('modules.report.settlementHistoryReport')</button>
                </nav>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="overflow-x-auto">
                    @if ($activeTab === 'order-status')
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.orderNumber')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.orderDate')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.customer.customer')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.expectedCodAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.collectedCodAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.status')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.settlementStatus')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($tabData as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->order?->show_formatted_order_number ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">@include('common.date-time-display', ['date' => $item->order?->date_time])</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->order?->customer?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->expected_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) ($item->collected_amount ?? 0), restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ __('modules.delivery.' . $item->status) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{
                                                match ($item->status) {
                                                    'settled' => __('modules.delivery.settled'),
                                                    'submitted' => __('modules.delivery.submitted'),
                                                    'collected' => __('modules.delivery.readyForSettlement'),
                                                    default => __('modules.delivery.pendingCollection'),
                                                }
                                            }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('app.noDataFound')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($activeTab === 'collections')
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.ordersCount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.expectedCodAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.collectedCodAmount')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($tabData as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ (int) $item->total_orders }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->expected_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->collected_amount, restaurant()->currency_id) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('app.noDataFound')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($activeTab === 'pending')
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.dueToCollect')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.readyForSettlement')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.submittedForApproval')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.report.ordersCount')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($tabData as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->due_to_collect, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->ready_for_settlement, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->submitted_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ (int) $item->pending_orders }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('app.noDataFound')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.settlementNumber')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.orderCount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.submittedAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.note')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.status')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($tabData as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->settlement_number ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->items->count() }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) $item->submitted_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->notes ?: '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{
                                                match ($item->status) {
                                                    'submitted' => __('modules.delivery.submitted'),
                                                    'approved' => __('modules.delivery.settled'),
                                                    'rejected' => __('modules.delivery.rejected'),
                                                    default => ucwords(str_replace('_', ' ', $item->status)),
                                                }
                                            }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('app.noDataFound')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>

                @if ($tabData instanceof \Illuminate\Contracts\Pagination\Paginator && $tabData->hasPages())
                    <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                        {{ $tabData->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
