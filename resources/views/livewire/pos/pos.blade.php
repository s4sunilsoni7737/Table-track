<div class="relative">
    {{-- Include MultiPOS registration and status handling --}}
    @if(module_enabled('MultiPOS') && in_array('MultiPOS', restaurant_modules()))
        @include('multipos::partials.pos-registration', [
            'hasPosMachine' => $hasPosMachine,
            'machineStatus' => $machineStatus,
            'posMachine' => $posMachine,
            'limitReached' => $limitReached,
            'limitMessage' => $limitMessage,
            'shouldBlockPos' => $shouldBlockPos
        ])
    @endif

    {{-- Only render POS content if not blocked by registration/pending/declined --}}
    @if(!$shouldBlockPos)
        @if($showRestaurantClosedBanner)
            <div class="px-4 pt-2">
                <div class="w-full p-3 text-sm font-medium text-center text-red-700 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                    {{ $restaurantClosedMessage }}
                </div>
            </div>
        @endif

        @if(!$orderTypeId)
        @livewire('forms.OrderTypeSelection')
        @endif

        <div class="flex-grow lg:flex h-auto">
            @include('pos.menu')
            @if (!$orderDetail || ($orderDetail && $orderDetail->status == 'draft'))
                @include('pos.kot_items')
            @elseif($orderDetail->status == 'kot')
                @include('pos.order_items')
            @elseif($orderDetail->status == 'billed' || $orderDetail->status == 'paid')
                @include('pos.order_detail')
            @endif
        </div>

        <x-dialog-modal wire:model.live="showVariationModal" maxWidth="xl">
            <x-slot name="title">
                @lang('modules.menu.itemVariations')
            </x-slot>

            <x-slot name="content">
                @if ($menuItem)
                @livewire('pos.itemVariations', [
                    'menuItemId' => $menuItem->id,
                    'orderTypeId' => $orderTypeId,
                    'deliveryAppId' => $this->normalizedDeliveryAppId
                ], key(str()->random(50)))
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-button-cancel wire:click="$toggle('showVariationModal')" wire:loading.attr="disabled" />
            </x-slot>
        </x-dialog-modal>

        <x-dialog-modal wire:model.live="showKotNote" maxWidth="xl">
            <x-slot name="title">
                @lang('modules.order.addNote')
            </x-slot>

            <x-slot name="content">
                <div>
                    <x-label for="orderNote" :value="__('modules.order.orderNote')" />
                    <x-textarea data-gramm="false"  class="block mt-1 w-full"  wire:model='orderNote' rows='2' />
                    <x-input-error for="orderNote" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-button wire:click="$toggle('showKotNote')" wire:loading.attr="disabled">@lang('app.save')</x-button>
            </x-slot>
        </x-dialog-modal>

        <x-dialog-modal wire:model.live="showTableModal" maxWidth="2xl">
            <x-slot name="title">
                @lang('modules.table.availableTables')
            </x-slot>

            <x-slot name="content">
                @livewire('pos.setTable')
            </x-slot>

            <x-slot name="footer">
                <x-button-cancel wire:click="$toggle('showTableModal')" wire:loading.attr="disabled" />
            </x-slot>
        </x-dialog-modal>

        @if(module_enabled('Hotel')  && in_array('Hotel', restaurant_modules()))
            @include('hotel::pos.show-stay')
        @endif

        <x-dialog-modal wire:model.live="showDiscountModal" maxWidth="xl">
            <x-slot name="title">
                @lang('modules.order.addDiscount')
            </x-slot>

            <x-slot name="content">
                <div class="mt-4 flex">
                    <!-- Discount Value -->
                    <x-input id="discountValue" class="block w-2/3 text-md" type="number" step="0.001" wire:model.defer="discountValue"
                        placeholder="{{ __('modules.order.enterDiscountValue') }}" min="0" />
                    <!-- Discount Type -->
                    <x-select id="discountType" class="block ml-2 w-1/3 rounded-md border-gray-300" wire:model.defer="discountType">
                        <option value="fixed">@lang('modules.order.fixed')</option>
                        <option value="percent">@lang('modules.order.percent')</option>
                    </x-select>
                </div>
            <x-input-error for="discountValue" class="mt-2" />
            </x-slot>

            <x-slot name="footer">
                <x-button-cancel wire:click="$set('showDiscountModal', false)">@lang('app.cancel')</x-button-cancel>
                <x-button class="ml-3" wire:click="addDiscounts" wire:loading.attr="disabled">@lang('app.save')</x-button>
            </x-slot>
        </x-dialog-modal>


        @if ($errors->count())
            <x-dialog-modal wire:model='showErrorModal' maxWidth="xl">
                <x-slot name="title">
                    @lang('app.error')
                </x-slot>

                <x-slot name="content">
                    <div class="space-y-3">
                        @foreach ($errors->all() as $error)
                            <div class="text-red-700 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
                                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                </svg>
                                {{ $error }}
                            </div>
                        @endforeach
                    </div>

                </x-slot>

                <x-slot name="footer">
                    @if ($showNewKotButton)
                        <x-button class="me-2">
                            <a href="{{ route('pos.kot', ['id' => $orderDetail->id]) }}">
                                @lang('modules.order.newKot')
                            </a>
                        </x-button>
                    @endif
                    <x-button-cancel wire:click="closeErrorModal" wire:loading.attr="disabled" />
                </x-slot>
            </x-dialog-modal>
        @endif

        <x-dialog-modal wire:model.live="showModifiersModal" maxWidth="xl">
            <x-slot name="title">
                @lang('modules.modifier.itemModifiers')
            </x-slot>

            <x-slot name="content">
                @if ($selectedModifierItem)
                    @livewire('pos.itemModifiers', [
                        'menuItemId' => $selectedModifierItem,
                        'orderTypeId' => $orderTypeId,
                        'deliveryAppId' => $selectedDeliveryApp
                    ], key(str()->random(50)))
                @endif
            </x-slot>
        </x-dialog-modal>

        @script
        <script>
            $wire.on('play_beep', () => {
                new Audio("{{ asset('sound/sound_beep-29.mp3')}}").play();
            });

            $wire.on('print_location', (url) => {
                // Detect if running in PWA standalone mode
                const isPWA = (window.matchMedia('(display-mode: standalone)').matches) ||
                             (window.navigator.standalone === true) ||
                             (document.referrer.includes('android-app://'));

                if (isPWA) {
                    // In PWA mode, open in same tab to prevent app closing
                    window.location.href = url;
                } else {
                    // In browser mode, open in new tab
                    const anchor = document.createElement('a');
                    anchor.href = url;
                    anchor.target = '_blank';
                    anchor.click();
                }
            });

            // Handle deletion of merged orders after save
            // Use a small delay to ensure it happens after Livewire response is complete
            $wire.on('deleteMergedOrdersAfterSave', (orderIds) => {
                // Delay deletion to ensure it happens after all redirects/modals are processed
                setTimeout(() => {
                    $wire.call('handleDeleteMergedOrdersAfterSave', orderIds);
                }, 100);
            });

        </script>

    @endscript
    @endif

    <!-- Loyalty Points Redemption Modal -->
    @if(function_exists('module_enabled') && module_enabled('Loyalty') && function_exists('restaurant_modules') && in_array('Loyalty', restaurant_modules()))
    <style>
        /* Ensure modal appears above top bar and has space from top */
        div[wire\:model*="showLoyaltyRedemptionModal"].jetstream-modal,
        div[x-data*="showLoyaltyRedemptionModal"].jetstream-modal {
            z-index: 9999 !important;
            padding-top: 4rem !important;
        }
        @media (min-width: 640px) {
            div[wire\:model*="showLoyaltyRedemptionModal"].jetstream-modal,
            div[x-data*="showLoyaltyRedemptionModal"].jetstream-modal {
                padding-top: 5rem !important;
            }
        }
    </style>
    <x-dialog-modal wire:model.live="showLoyaltyRedemptionModal" maxWidth="md">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('loyalty::app.redeemLoyaltyPoints') }}
            </div>
        </x-slot>

        <x-slot name="content">
            @if($customer && $availableLoyaltyPoints > 0)
                <div class="space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
                            {{ $customer->name }} {{ __('loyalty::app.hasAvailablePoints') }}: {{ number_format($availableLoyaltyPoints) }} @lang('loyalty::app.points')
                        </p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            {{ __('loyalty::app.pointsValue') }}: {{ currency_format($loyaltyPointsValue, restaurant()->currency_id) }}
                        </p>
                        @if($maxLoyaltyDiscount > 0)
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                {{ __('loyalty::app.maxDiscountToday') }}: {{ currency_format($maxLoyaltyDiscount, restaurant()->currency_id) }}
                            </p>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.pointsToRedeem') }}
                            </label>
                            <x-input type="number"
                                     wire:model="pointsToRedeem"
                                     min="{{ $minRedeemPoints }}"
                                     max="{{ $maxRedeemablePoints }}"
                                     step="{{ $minRedeemPoints }}"
                                     class="block w-full"
                                     placeholder="{{ __('loyalty::app.enterPoints') }}" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @if($minRedeemPoints > 0)
                                    {{ __('Minimum') }}: {{ number_format($minRedeemPoints) }} @lang('loyalty::app.points')
                                    @if($maxRedeemablePoints > 0)
                                        | {{ __('Maximum') }}: {{ number_format($maxRedeemablePoints) }} @lang('loyalty::app.points')
                                    @endif
                                @else
                                    {{ __('loyalty::app.maxPoints') }}: {{ number_format($availableLoyaltyPoints) }}
                                @endif
                            </p>
                            @if($minRedeemPoints > 0 && $maxRedeemablePoints > 0)
                                <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                    {{ __('Points must be in multiples of :min', ['min' => number_format($minRedeemPoints)]) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.noPointsAvailable') }}</p>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-between w-full">
                <x-button-cancel wire:click="skipLoyaltyRedemption" wire:loading.attr="disabled">
                    {{ __('app.skip') }}
                </x-button-cancel>
                <div class="flex gap-2">
                    @if($maxRedeemablePoints > 0)
                        <x-secondary-button wire:click="redeemLoyaltyPoints({{ $maxRedeemablePoints }})" wire:loading.attr="disabled">
                            {{ __('loyalty::app.useMax') }} ({{ number_format($maxRedeemablePoints) }})
                        </x-secondary-button>
                    @endif
                    <x-button wire:click="redeemLoyaltyPoints()" wire:loading.attr="disabled">
                        {{ __('loyalty::app.applyDiscount') }}
                    </x-button>
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>
    @endif
</div>
