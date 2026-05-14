<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.deliveryExecutiveCodMonitoring')</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.codMonitoringDescription')</p>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-gray-300">@lang('modules.delivery.dueToCollect')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['due_collection_total'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['due_collection_orders'], ['count' => $totals['due_collection_orders']]) }}</div>
                </div>
                <div class="rounded-lg border border-blue-300 bg-blue-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-blue-700 dark:text-gray-300">@lang('modules.delivery.readyForSettlement')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['ready_settlement_total'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['ready_settlement_orders'], ['count' => $totals['ready_settlement_orders']]) }}</div>
                </div>
                <div class="rounded-lg border border-violet-300 bg-violet-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-violet-700 dark:text-gray-300">@lang('modules.delivery.submittedForApproval')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['submitted_settlement_total'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['submitted_settlement_orders'], ['count' => $totals['submitted_settlement_orders']]) }}</div>
                </div>
                <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-gray-300">@lang('modules.delivery.totalSettled')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['settled_total'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['settled_orders'], ['count' => $totals['settled_orders']]) }}</div>
                </div>
                <div class="rounded-lg border border-cyan-300 bg-cyan-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-cyan-700 dark:text-gray-300">@lang('modules.delivery.collectedToday')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['collected_today_total'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['collected_today_orders'], ['count' => $totals['collected_today_orders']]) }}</div>
                </div>
                <div class="rounded-lg border border-rose-300 bg-rose-50 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="text-xs uppercase tracking-wide text-rose-700 dark:text-gray-300">@lang('modules.delivery.totalCodOrders')</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($totals['total_cod_amount'], restaurant()->currency_id) }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans_choice('modules.delivery.ordersCountLabel', $totals['total_cod_orders'], ['count' => $totals['total_cod_orders']]) }}</div>
                </div>
            </div>

            <div class="items-center justify-between block sm:flex">
                <div class="flex flex-col gap-3 mb-4 sm:mb-0 sm:flex-row sm:items-center">
                    <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                        <x-input id="cash-monitor-search" class="block mt-1 w-full" type="text" placeholder="{{ __('modules.delivery.searchCodCollections') }}" wire:model.live.debounce.500ms="search" />
                    </div>
                    @if ($activeTab !== 'summary')
                        <div class="w-48">
                            <x-select wire:model.live="status" class="mt-1 block w-full">
                                <option value="">@lang('modules.delivery.allStatuses')</option>
                                <option value="pending_collection">@lang('modules.delivery.pendingCollection')</option>
                                <option value="collected">@lang('modules.delivery.collected')</option>
                                <option value="submitted">@lang('modules.delivery.submitted')</option>
                                <option value="settled">@lang('modules.delivery.settled')</option>
                            </x-select>
                        </div>
                    @endif
                    <a href="{{ route('reports.cod') }}" class="inline-flex items-center justify-center rounded-lg bg-skin-base px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                        @lang('menu.codReport')
                    </a>
                </div>

            </div>
        </div>
    </div>

    @if ($tableMissing)
        <div class="m-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700/40 dark:bg-amber-900/10 dark:text-amber-200">
            @lang('modules.delivery.codMonitoringMigrationMessage')
        </div>
    @else
        <div class="p-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex flex-wrap gap-6">
                    <button type="button" wire:click="switchTab('settlements')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'settlements',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'settlements',
                    ])>@lang('modules.delivery.settlementHistory')</button>
                    <button type="button" wire:click="switchTab('summary')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'summary',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'summary',
                    ])>@lang('modules.delivery.executivePendingSummary')</button>
                    <button type="button" wire:click="switchTab('orders')" @class([
                        'border-b-2 px-1 py-3 text-sm font-medium',
                        'border-skin-base text-skin-base' => $activeTab === 'orders',
                        'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'orders',
                    ])>@lang('modules.delivery.orderWiseCodList')</button>
                </nav>

                @if (in_array('Export Report', restaurant_modules()))
                    <a href="javascript:;" wire:click="exportReport"
                        class="mb-3 inline-flex items-center px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                        @lang('app.export')
                    </a>
                @endif
            </div>

            @if ($activeTab === 'orders')
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.orderWiseCodList')</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.order.orderNumber')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.customer.customer')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.dueAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.collectedAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.status')</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.dateTime')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($collections as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->order?->show_formatted_order_number ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->order?->customer?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $item->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format($item->expected_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format((float) ($item->collected_amount ?? 0), restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                {{
                                                    match ($item->status) {
                                                        'pending_collection' => __('modules.delivery.pendingCollection'),
                                                        'collected' => __('modules.delivery.collected'),
                                                        'submitted' => __('modules.delivery.submitted'),
                                                        'settled' => __('modules.delivery.settled'),
                                                        default => ucwords(str_replace('_', ' ', $item->status)),
                                                    }
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                            @include('common.date-time-display', ['date' => $item->recorded_at ?? $item->updated_at])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noCodCollectionsFound')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($collections->hasPages())
                        <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                            {{ $collections->links() }}
                        </div>
                    @endif
                </div>
            @elseif ($activeTab === 'settlements')
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.settlementHistory')</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.settlementNumber')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('menu.deliveryExecutive')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.orderCount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.submittedAmount')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('modules.delivery.submittedAt')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.note')</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.status')</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">@lang('app.action')</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($settlements as $settlement)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $settlement->settlement_number ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $settlement->deliveryExecutive?->name ?? '--' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $settlement->items->count() }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ currency_format($settlement->submitted_amount, restaurant()->currency_id) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            @include('common.date-time-display', ['date' => $settlement->submitted_at])
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $settlement->notes ?: '--' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                {{
                                                    match ($settlement->status) {
                                                        'submitted' => __('modules.delivery.submitted'),
                                                        'approved' => __('modules.delivery.settled'),
                                                        'rejected' => __('modules.delivery.rejected'),
                                                        default => ucwords(str_replace('_', ' ', $settlement->status)),
                                                    }
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            @if ($settlement->status === 'submitted')
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" wire:click="approveSettlement({{ $settlement->id }})" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                                                        @lang('modules.delivery.approveSettlement')
                                                    </button>
                                                    <button type="button" wire:click="rejectSettlement({{ $settlement->id }})" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                                        @lang('modules.delivery.rejectSettlement')
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $settlement->approved_at?->timezone(timezone())->format(dateFormat() . ' ' . timeFormat()) ?? '--' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noSettlementHistoryFound')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.executivePendingSummary')</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($summary as $executive)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $executive->deliveryExecutive?->name ?? '--' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ ($executive->deliveryExecutive?->phone_code ? '+'. $executive->deliveryExecutive?->phone_code . ' ' : '') . ($executive->deliveryExecutive?->phone ?? '--') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ currency_format((float) ($executive->cod_pending_amount ?? 0), restaurant()->currency_id) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('modules.delivery.pendingOrdersCount', (int) ($executive->cod_pending_orders ?? 0), ['count' => (int) ($executive->cod_pending_orders ?? 0)]) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noCodSummaryFound')</div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
