<div class="px-4">
    <div class="mb-4">
        <h2 class="text-2xl font-extrabold dark:text-white">@lang('menu.deliveryCodSettlement')</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.codSettlementDescription')</p>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.submitSettlement')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.submitSettlementDescription')</p>
            </div>

            @if ($pendingSettlementCollections->count())
                <div class="space-y-3">
                    @foreach ($pendingSettlementCollections as $collection)
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <input type="checkbox" value="{{ $collection->id }}" wire:model.live="selectedSettlementCollections" class="mt-1 rounded border-gray-300 text-skin-base focus:ring-skin-base">
                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $collection->order?->show_formatted_order_number ?? '--' }}</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ currency_format((float) ($collection->collected_amount ?? 0), restaurant()->currency_id) }}</div>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $collection->order?->customer?->name ?? '--' }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4">
                    <x-label for="settlementAmount" value="{{ __('modules.delivery.submittedAmount') }}" />
                    <x-input id="settlementAmount" type="number" step="0.01" min="0.01" wire:model.live="settlementAmount" class="mt-1 block w-full" />
                    <x-input-error for="settlementAmount" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-label for="settlementNotes" value="{{ __('app.note') }}" />
                    <textarea id="settlementNotes" wire:model="settlementNotes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                    <x-input-error for="settlementNotes" class="mt-2" />
                    <x-input-error for="selectedSettlementCollections" class="mt-2" />
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        @lang('modules.delivery.selectedSettlementTotal'):
                        <span class="ml-1 text-lg font-extrabold text-gray-900 dark:text-white">
                            {{ currency_format($this->selectedSettlementTotal, restaurant()->currency_id) }}
                        </span>
                    </div>
                    <x-button type="button" wire:click="submitSettlement">@lang('modules.delivery.submitSettlement')</x-button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noPendingSettlementCollections')</p>
            @endif
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.delivery.settlementHistory')</h3>
            </div>

            @if ($settlementHistory->count())
                <div class="space-y-3">
                    @foreach ($settlementHistory as $settlement)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $settlement->settlement_number ?? '--' }}</div>
                                @php
                                    $badgeClasses = match ($settlement->status) {
                                        'submitted' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
                                        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
                                    };

                                    $badgeLabel = match ($settlement->status) {
                                        'submitted' => __('modules.delivery.submitted'),
                                        'approved' => __('modules.delivery.settled'),
                                        'rejected' => __('modules.delivery.rejected'),
                                        default => ucwords(str_replace('_', ' ', $settlement->status)),
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClasses }}">
                                    {{ $badgeLabel }}
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ currency_format((float) $settlement->submitted_amount, restaurant()->currency_id) }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $settlement->submitted_at?->timezone(timezone())->format(dateFormat() . ' ' . timeFormat()) ?? '--' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.delivery.noSettlementHistoryFound')</p>
            @endif
        </div>
    </div>
</div>
