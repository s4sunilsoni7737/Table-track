<div class="mx-2 mb-4 space-y-6 md:mx-0 px-4">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">@lang('modules.delivery.orderDetails')</h2>
        <a href="{{ $this->goBackUrl }}" class="text-sm text-gray-500 hover:text-skin-base">&larr; @lang('app.goBack')</a>
    </div>

    <div class="p-4 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-800">
        <div class="flex flex-col justify-between gap-3 mb-3 md:flex-row">
            <div class="flex flex-col gap-1.5">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $order->show_formatted_order_number }}</h3>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    {{ $order->date_time->timezone($restaurant->timezone)->format($dateFormat . ' ' . $timeFormat) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300">@lang('app.status'): {{ $order->order_status->translatedLabel() }}</div>
            </div>
            <div class="text-lg font-bold text-gray-900 md:text-xl dark:text-white">
                {{ currency_format($order->total, $restaurant->currency_id) }}
            </div>
        </div>

        <div @class([
            'grid grid-cols-1 gap-4 mt-4',
            'md:grid-cols-2' => !$this->isHistoryContext,
        ])>
            <div class="p-3 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">@lang('modules.restaurant.restaurantDetails')</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $restaurant->name ?? '--' }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    @lang('modules.restaurant.phone'):
                    {{ $restaurant->phone_code ? '+' . $restaurant->phone_code . ' ' : '' }}{{ $restaurant->phone_number ?? $restaurant->phone_number ?? '--' }}
                </p>
                
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    @lang('modules.settings.branchName'): {{ $order->branch?->name ?? '--' }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $order->branch?->address ?? '--' }}</p>
                <a href="{{ $this->branchNavigationUrl }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm text-skin-base">@lang('modules.delivery.openNavigation')</a>
            </div>
            @if (!$this->isHistoryContext)
                <div class="p-3 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">@lang('modules.customer.customerDetails')</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $order->customer?->name ?? '--' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('modules.restaurant.phone'):
                        {{ $order->customer?->phone_code ? '+' . $order->customer?->phone_code . ' ' : '' }}{{ $order->customer?->phone_number ?? $order->customer?->phone ?? '--' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                        {{ $order->customer?->latestDeliveryAddress?->address ?? $order->delivery_address ?? $order->customer?->delivery_address ?? '--' }}
                    </p>
                    <a href="{{ $this->navigationUrl }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm text-skin-base">@lang('modules.delivery.openNavigation')</a>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="p-3 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">@lang('modules.delivery.items')</h4>
                <ul class="space-y-2">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>{{ $item->menuItem?->item_name ?? __('modules.menu.item') }} x {{ $item->quantity }}</span>
                            <span>{{ currency_format($item->amount, $restaurant->currency_id) }}</span>
                        </li>
                    @endforeach
                </ul>

                @php
                    $displayLoyaltyPointsRedeemed = (float) ($order->loyalty_points_redeemed ?? 0);
                    $displayLoyaltyDiscountAmount = (float) ($order->loyalty_discount_amount ?? 0);
                    $displayStampDiscountAmount = (float) ($order->stamp_discount_amount ?? 0);
                    $hasFreeStampItems = $order->items()->where('is_free_item_from_stamp', true)->exists();

                    $chargeBase = (float) ($order->sub_total ?? 0)
                        - (float) ($order->discount_amount ?? 0)
                        - $displayLoyaltyDiscountAmount
                        - $displayStampDiscountAmount;

                    $chargeBase = max($chargeBase, 0);

                    $taxBase = $order->tax_base ?? ($chargeBase + $order->charges->sum(fn($item) => $item->charge?->getAmount($chargeBase) ?? 0));
                @endphp

                <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-600">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>@lang('modules.order.subTotal')</span>
                            <span>{{ currency_format($order->sub_total, $restaurant->currency_id) }}</span>
                        </div>

                        @if ($order->discount_amount && $order->discount_amount > 0)
                             <div class="flex items
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>@lang('modules.order.discount')</span>
                                <span>-{{ currency_format($order->discount_amount, $restaurant->currency_id) }}</span>
                            </div>
                        @endif

                        @if(module_enabled('Loyalty') && $displayLoyaltyPointsRedeemed > 0 && $displayLoyaltyDiscountAmount > 0)
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>
                                    @lang('app.loyaltyDiscount')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ number_format($displayLoyaltyPointsRedeemed, 0) }} @lang('app.points'))
                                    </span>
                                </span>
                                <span>-{{ currency_format($displayLoyaltyDiscountAmount, $restaurant->currency_id) }}</span>
                            </div>
                        @endif

                        @if(module_enabled('Loyalty') && ($displayStampDiscountAmount > 0 || $hasFreeStampItems))
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>
                                    @lang('app.stampDiscount')
                                    @if($hasFreeStampItems)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(@lang('app.freeItem'))</span>
                                    @endif
                                </span>
                                <span>
                                    @if($displayStampDiscountAmount > 0)
                                        -{{ currency_format($displayStampDiscountAmount, $restaurant->currency_id) }}
                                    @else
                                        --
                                    @endif
                                </span>
                            </div>
                        @endif

                        @foreach ($order->charges as $item)
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>
                                    {{ $item->charge->charge_name }}
                                    @if ($item->charge->charge_type === 'percent')
                                        ({{ $item->charge->charge_value }}%)
                                    @endif
                                </span>
                                <span>
                                    {{ currency_format($item->charge->getAmount($chargeBase), $restaurant->currency_id) }}
                                </span>
                            </div>
                        @endforeach

                        @foreach ($order->taxes as $item)
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>{{ $item->tax->tax_name }} ({{ $item->tax->tax_percent }}%)</span>
                                <span>{{ currency_format(($item->tax->tax_percent / 100) * $taxBase, $restaurant->currency_id) }}</span>
                            </div>
                        @endforeach

                        @if($order->tip_amount > 0)
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>@lang('modules.order.tip')</span>
                                <span>{{ currency_format($order->tip_amount, $restaurant->currency_id) }}</span>
                            </div>
                        @endif

                        @if ($order->order_type === 'delivery' && !is_null($order->delivery_fee))
                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span>@lang('modules.delivery.deliveryFee')</span>
                                <span>
                                    @if($order->delivery_fee > 0)
                                        {{ currency_format($order->delivery_fee, $restaurant->currency_id) }}
                                    @else
                                        @lang('modules.delivery.freeDelivery')
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-3 text-sm font-semibold text-gray-900 dark:border-gray-600 dark:text-white">
                    <span>@lang('modules.order.total')</span>
                    <span>{{ currency_format($order->total, $restaurant->currency_id) }}</span>
                </div>
            </div>

            <div class="p-3 border rounded-md bg-white dark:bg-gray-700 dark:border-gray-600">
                @php
                    $currentStatus = $order->order_status->value;
                    $allowedStatuses = match ($currentStatus) {
                        'confirmed', 'preparing', 'food_ready', 'ready_for_pickup' => ['picked_up'],
                        'picked_up' => ['out_for_delivery', 'reached_destination'],
                        'out_for_delivery' => ['reached_destination', 'delivered'],
                        'reached_destination' => ['delivered'],
                        default => [],
                    };

                    $statusButtons = [
                        [
                            'value' => 'picked_up',
                            'label' => __('modules.delivery.pickedUp'),
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13h14M12 5v14"/>',
                        ],
                        [
                            'value' => 'out_for_delivery',
                            'label' => __('modules.delivery.outForDelivery'),
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12h3l2-3h5l2 3h6M7 15a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
                        ],
                        [
                            'value' => 'reached_destination',
                            'label' => __('modules.delivery.reachedDestination'),
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/><circle cx="12" cy="11" r="2"/>',
                        ],
                        [
                            'value' => 'delivered',
                            'label' => __('app.delivered'),
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12l4 4L19 6"/>',
                        ],
                    ];
                @endphp

                <div class="bg-gradient-to-br from-white to-gray-50 dark:border-gray-600 dark:from-gray-700 dark:to-gray-800">
                    @if ($order->orderCashCollection && $order->orderCashCollection->expected_amount > 0)
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-600/40 dark:bg-amber-900/10">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.cashOnDelivery')</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">@lang('modules.delivery.collectDueAmountMessage')</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">@lang('modules.delivery.dueAmount')</div>
                                    <div class="text-lg font-bold text-amber-700 dark:text-amber-300">
                                        {{ currency_format($order->orderCashCollection->expected_amount, $restaurant->currency_id) }}
                                    </div>
                                </div>
                            </div>

                            @if ($order->orderCashCollection->status === 'collected')
                                <div class="mt-3 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-300">
                                    @lang('modules.delivery.dueAmountCollected')
                                </div>
                            @else
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    @lang('modules.delivery.collectAmountFromCustomer', ['amount' => currency_format($order->orderCashCollection->expected_amount, $restaurant->currency_id)])
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.updateDeliveryStatus')</h4>
                        <span class="rounded-full bg-skin-base/10 px-2.5 py-1 text-xs font-semibold text-skin-base dark:bg-skin-base/20">
                            {{ $order->order_status->translatedLabel() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($statusButtons as $status)
                            @php
                                $isActive = $currentStatus === $status['value'];
                                $isEnabled = $isActive || in_array($status['value'], $allowedStatuses, true);
                            @endphp
                            <button
                                type="button"
                                @if ($isEnabled && !$isActive) wire:click="updateDeliveryStatus('{{ $status['value'] }}')" @endif
                                @disabled(!$isEnabled)
                                @class([
                                    'group flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-left text-sm transition-all duration-200',
                                    'border-skin-base bg-skin-base text-white shadow-sm shadow-skin-base/30' => $isActive,
                                    'border-gray-200 bg-white text-gray-700 hover:-translate-y-0.5 hover:border-skin-base/40 hover:shadow-sm dark:border-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:hover:border-skin-base/50' => !$isActive && $isEnabled,
                                    'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 opacity-70 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500' => !$isEnabled,
                                ])
                            >
                                <span @class([
                                    'inline-flex h-8 w-8 items-center justify-center rounded-full',
                                    'bg-white/20 text-white' => $isActive,
                                    'bg-skin-base/10 text-skin-base dark:bg-skin-base/20' => !$isActive && $isEnabled,
                                    'bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => !$isEnabled,
                                ])>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        {!! $status['icon'] !!}
                                    </svg>
                                </span>
                                <span class="font-medium">{{ $status['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($showDeliveryConfirmationModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto bg-gray-900/60">
            <div class="flex min-h-screen items-center justify-center px-4 py-6">
                <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.confirmDueAmount')</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        @lang('modules.delivery.confirmDueAmountQuestion', ['amount' => currency_format($order->orderCashCollection?->expected_amount ?? 0, $restaurant->currency_id)])
                    </p>

                    <div class="mt-5 flex justify-end gap-2">
                        <button wire:click="closeDeliveryConfirmationModal" type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            @lang('modules.delivery.notYet')
                        </button>
                        <button wire:click="confirmCollectedAndDeliver" type="button" class="rounded-md bg-skin-base px-3 py-2 text-sm font-medium text-white hover:opacity-90">
                            @lang('app.yes')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
