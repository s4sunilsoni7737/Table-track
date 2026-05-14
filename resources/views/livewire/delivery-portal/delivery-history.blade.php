<div class="px-4">
    <h2 class="text-2xl font-extrabold dark:text-white">@lang('modules.delivery.deliveryHistory')</h2>

    <ul role="list" class="space-y-2 mt-4">
        @forelse ($orders as $order)
            @php
                $status = $order->order_status->value;
                $statusClasses = match ($status) {
                    'picked_up' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700',
                    'out_for_delivery' => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
                    'reached_destination' => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-700',
                    'delivered' => 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
                    default => 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
                };
            @endphp
            <li class="rounded-lg border bg-white p-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <a href="{{ route('delivery.orders.show', ['uuid' => $order->uuid, 'from' => 'history']) }}" class="block font-medium text-skin-base truncate dark:text-white">
                    <div class="flex w-full items-center justify-between">
                        <div>
                            {{ $order->show_formatted_order_number }}
                            <div class="flex items-center flex-1 text-xs text-gray-500 mt-1">
                                {{ $order->items->count() }} @lang('modules.menu.item')
                                <span class="mx-1">|</span>
                                {{ $order->date_time->timezone($restaurant->timezone)->format(dateFormat() . ' ' . timeFormat()) }}
                            </div>
                        </div>
                        <div class="inline-flex flex-col text-right text-base font-semibold text-gray-900 dark:text-white">
                            <div>{{ currency_format($order->total, $restaurant->currency_id) }}</div>
                            <div class="mt-1 inline-flex items-center justify-end">
                                <span @class([
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                                    $statusClasses,
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">@lang('modules.delivery.noDeliveryHistoryYet')</h3>
            </li>
        @endforelse
    </ul>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
