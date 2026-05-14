<div>
    <form wire:submit="submitForm">
        @csrf

        @if($payment)
        <div class="space-y-4">
            <!-- Payment Information -->
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">@lang('modules.order.paymentInformation')</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.orderNumber'):</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $payment->order->show_formatted_order_number ?? $payment->order->order_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.amount'):</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ currency_format($payment->amount, restaurant()->currency_id) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.paymentMethod'):</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $payment->payment_method }}</span>
                    </div>
                    @if($payment->transaction_id)
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">@lang('modules.order.transactionId'):</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $payment->transaction_id }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Refund Reason -->
            <div>
                <x-label for="refundReasonId" value="{{ __('modules.settings.refundReason') }}" />
                <select id="refundReasonId" wire:model.live="refundReasonId"
                    class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-skin-base focus:ring-skin-base rounded-md shadow-sm">
                    <option value="">@lang('app.select') @lang('modules.settings.refundReason')</option>
                    @foreach($refundReasons as $reason)
                        <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                    @endforeach
                </select>
                <x-input-error for="refundReasonId" class="mt-2" />
            </div>

            <!-- Refund Type -->
            <div>
                <x-label value="{{ __('modules.refund.refundType') }}" class="mb-2" />
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="radio" wire:model.live="refundType" value="full"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.fullRefund')</span>
                    </label>

                    <label class="flex items-center">
                        <input type="radio" wire:model.live="refundType" value="partial"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.partialRefund')</span>
                    </label>

                    <label class="flex items-center">
                        <input type="radio" wire:model.live="refundType" value="waste"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.wasteRefund')</span>
                    </label>
                </div>
                <x-input-error for="refundType" class="mt-2" />
            </div>

            <!-- Partial Refund Type (only show when partial is selected) -->
            @if($refundType === 'partial')
            <div>
                <x-label value="{{ __('modules.refund.partialRefundType') }}" class="mb-2" />
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="radio" wire:model.live="partialRefundType" value="half"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.halfPrice')</span>
                    </label>

                    <label class="flex items-center">
                        <input type="radio" wire:model.live="partialRefundType" value="fixed"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.fixedAmount')</span>
                    </label>

                    <label class="flex items-center">
                        <input type="radio" wire:model.live="partialRefundType" value="custom"
                            class="w-4 h-4 bg-gray-100 border-gray-300 text-skin-base focus:ring-skin-base dark:focus:ring-skin-base dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">@lang('modules.refund.customAmount')</span>
                    </label>
                </div>
                <x-input-error for="partialRefundType" class="mt-2" />
            </div>
            @endif

            <!-- Refund Amount -->
            <div>
                <x-label for="amount" value="{{ __('modules.refund.refundAmount') }}" />
                <x-input id="amount" class="block w-full mt-1" type="number" step="0.01" min="0"
                    :max="$payment->amount" wire:model.live="amount"
                    :disabled="$refundType === 'waste' || $refundType === 'full' || ($refundType === 'partial' && $partialRefundType === 'half')" />
                <x-input-error for="amount" class="mt-2" />
                @if($refundType === 'partial')
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('modules.refund.maxRefundAmount'): {{ currency_format($payment->amount, restaurant()->currency_id) }}
                    </p>
                    @if($partialRefundType === 'half')
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                            @lang('modules.refund.halfAmountNote'): {{ currency_format($payment->amount / 2, restaurant()->currency_id) }}
                        </p>
                    @endif
                @endif
                @if($refundType === 'waste')
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('modules.refund.wasteRefundNote')
                    </p>
                    <p class="mt-1 text-xs text-orange-600 dark:text-orange-400">
                        @lang('modules.refund.wasteAmountDisplayNote'): {{ currency_format($payment->amount, restaurant()->currency_id) }}
                    </p>
                @endif
                @if($refundType === 'full')
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('modules.refund.fullRefundNote')
                    </p>
                @endif
            </div>

            <!-- Notes -->
            <div>
                <x-label for="notes" value="{{ __('modules.refund.notes') }}" />
                <textarea id="notes" wire:model.defer="notes" rows="3"
                    class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-skin-base focus:ring-skin-base rounded-md shadow-sm"></textarea>
                <x-input-error for="notes" class="mt-2" />
            </div>

        </div>
        @endif

        <div class="flex w-full pb-4 mt-6 space-x-4 rtl:space-x-reverse">
            <x-button wire:loading.attr="disabled">@lang('modules.refund.processRefund')</x-button>
            <x-button-cancel wire:click="$dispatch('closeRefundModal')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>

