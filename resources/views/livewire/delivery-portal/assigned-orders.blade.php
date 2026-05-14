<div class="px-4">
    <h2 class="text-2xl font-extrabold dark:text-white">@lang('modules.delivery.assignedOrders')</h2>

    <ul role="list" class="space-y-2 mt-4">
        @forelse ($orders as $order)
            <li class="rounded-lg border bg-white p-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <a href="{{ route('delivery.orders.show', ['uuid' => $order->uuid, 'from' => 'assigned']) }}" class="block font-medium text-skin-base truncate dark:text-white">
                    <div class="flex w-full items-center justify-between">
                        <div>
                            {{ $order->show_formatted_order_number }}
                            <div class="flex items-center flex-1 text-xs text-gray-500 mt-1">
                                {{ $order->items->count() }} @lang('modules.menu.item')
                                <span class="mx-1">|</span>
                                {{ $order->date_time->timezone($restaurant->timezone)->format(dateFormat() . ' ' . timeFormat()) }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $order->customer?->name ?? '--' }} | {{ $order->customer?->phone ?? '--' }}
                            </div>
                            @if ($order->orderCashCollection && $order->orderCashCollection->expected_amount > 0)
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full bg-amber-100 px-2 py-1 font-medium text-amber-700">
                                        COD {{ currency_format($order->orderCashCollection->expected_amount, $restaurant->currency_id) }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                        {{ str_replace('_', ' ', $order->orderCashCollection->status) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="inline-flex flex-col text-right text-base font-semibold text-gray-900 dark:text-white">
                            <div>{{ currency_format($order->total, $restaurant->currency_id) }}</div>
                            <div class="mt-4 inline-flex items-center justify-end">
                                <span @class([
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                                    $order->order_status->badgeClasses(),
                                ])>
                                    {{ $order->order_status->translatedLabel() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
        @empty
            <li class="p-8 border rounded-md text-center bg-white dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">@lang('modules.delivery.noAssignedOrders')</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noAssignedOrdersDescription')</p>
            </li>
        @endforelse
    </ul>
</div>
