<div>
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.name')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.email')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.customer.phone')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.order.totalOrder')
                                </th>
                                @if(function_exists('module_enabled') && module_enabled('Loyalty') && function_exists('restaurant_modules') && in_array('Loyalty', restaurant_modules()) && $loyaltyColumnName)
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    {{ $loyaltyColumnName }}
                                </th>
                                @endif
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='customer-list-{{ microtime() }}'>
                            @forelse ($customers as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='customer-{{ $item->id . rand(1111, 9999) . microtime() }}' wire:loading.class.delay='opacity-10'>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->name }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->email ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->phone ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    <span
                                    @if(user_can('Show Order'))
                                     wire:click='showCustomerOrders({{ $item->id }})'
                                    @endif

                                     @class(['text-xs font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 border border-gray-400 cursor-pointer'])>
                                        {{ $item->orders_count }} @lang('menu.orders')
                                    </span>
                                 </td>
                                @if(function_exists('module_enabled') && module_enabled('Loyalty') && function_exists('restaurant_modules') && in_array('Loyalty', restaurant_modules()) && $loyaltyColumnName)
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @php
                                        $restaurantId = restaurant()->id ?? null;
                                        $displayValue = 0;
                                        $displayLabel = '';
                                        $displayText = '';

                                        if ($restaurantId) {
                                            // Points only
                                            if ($enablePoints && !$enableStamps) {
                                                $displayValue = $item->loyalty_points ?? 0;
                                                $displayLabel = __('loyalty::app.points');
                                                $displayText = $displayValue > 0 ? $displayValue . ' ' . $displayLabel : '0 ' . $displayLabel;
                                            }
                                            // Stamps only
                                            elseif ($enableStamps && !$enablePoints) {
                                                $totalStamps = 0;
                                                try {
                                                    if (module_enabled('Loyalty')) {
                                                        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                                                        $customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $item->id);
                                                        if (!empty($customerStamps)) {
                                                            foreach ($customerStamps as $stampData) {
                                                                $availableStamps = $stampData['available_stamps'] ?? 0;
                                                                $totalStamps += $availableStamps;
                                                            }
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    // Silently fail
                                                }
                                                $displayLabel = __('loyalty::app.stamps');
                                                $displayText = $totalStamps > 0 ? $totalStamps . ' ' . $displayLabel : '0 ' . $displayLabel;
                                            }
                                            // Both points and stamps
                                            elseif ($enablePoints && $enableStamps) {
                                                $points = $item->loyalty_points ?? 0;
                                                $totalStamps = 0;
                                                try {
                                                    if (module_enabled('Loyalty')) {
                                                        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                                                        $customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $item->id);
                                                        if (!empty($customerStamps)) {
                                                            foreach ($customerStamps as $stampData) {
                                                                $availableStamps = $stampData['available_stamps'] ?? 0;
                                                                $totalStamps += $availableStamps;
                                                            }
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    // Silently fail
                                                }

                                                // Show both if available, or whichever is available
                                                if ($points > 0 && $totalStamps > 0) {
                                                    $displayText = $points . ' ' . __('loyalty::app.points') . ' / ' . $totalStamps . ' ' . __('loyalty::app.stamps');
                                                } elseif ($points > 0) {
                                                    $displayText = $points . ' ' . __('loyalty::app.points');
                                                } elseif ($totalStamps > 0) {
                                                    $displayText = $totalStamps . ' ' . __('loyalty::app.stamps');
                                                } else {
                                                    $displayText = '0 ' . __('loyalty::app.points') . ' / 0 ' . __('loyalty::app.stamps');
                                                }
                                            }
                                        }
                                    @endphp
                                    <span
                                    wire:click='showLoyaltyAccount({{ $item->id }})'
                                    @class(['text-xs font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 border border-blue-400 cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-800'])>
                                        {{ $displayText }}
                                    </span>
                                </td>
                                @endif
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right rtl:space-x-reverse">
                                    @if(user_can('Update Customer'))
                                    <x-secondary-button-table wire:click='showEditCustomer({{ $item->id }})' wire:key='customer-edit-{{ $item->id . microtime() }}'
                                        wire:key='editmenu-item-button-{{ $item->id }}'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z">
                                            </path>
                                            <path fill-rule="evenodd"
                                                d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        @lang('app.update')
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Customer'))
                                    <x-danger-button-table  wire:click="showDeleteCustomer({{ $item->id }})"  wire:key='customer-del-{{ $item->id . microtime() }}'>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </x-danger-button-table>
                                    @endif

                                </td>
                            </tr>
                            @empty
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 space-x-6 text-gray-500" colspan="{{ (function_exists('module_enabled') && module_enabled('Loyalty') && function_exists('restaurant_modules') && in_array('Loyalty', restaurant_modules()) && $loyaltyColumnName) ? '6' : '5' }}">
                                    @lang('messages.noCustomerFound')
                                </td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div wire:key='customer-table-paginate-{{ microtime() }}'
        class="sticky bottom-0 right-0 items-center w-full py-2.5 px-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0 w-full">
            {{ $customers->links() }}
        </div>
    </div>

    <x-right-modal wire:model.live="showEditCustomerModal">
        <x-slot name="title">
            {{ __("modules.customer.editCustomer") }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
            @livewire('forms.editCustomer', ['customer' => $customer], key('edit-customer-form-'.$customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditCustomerModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showCustomerOrderModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __("menu.orders") }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('customer.customerOrders', ['customer' => $customer], key('customer-orders-form-'.$customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCustomerOrderModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if(function_exists('module_enabled') && module_enabled('Loyalty') && function_exists('restaurant_modules') && in_array('Loyalty', restaurant_modules()))
    <x-right-modal wire:model.live="showLoyaltyAccountModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __('loyalty::app.loyaltyAccount') }} - {{ $customer->name ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($customer)
                @livewire('loyalty::customer.loyalty-account', ['customer' => $customer], key('loyalty-account-'.$customer->id))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showLoyaltyAccountModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>
    @endif

    <x-confirmation-modal wire:model.defer="confirmDeleteCustomerModal">
        <x-slot name="title">
            @lang('modules.customer.deleteCustomer')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.customer.deleteCustomerMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteCustomerModal')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            @if ($customer)
            <x-danger-button class="ml-3" wire:click='deleteCustomer({{ $customer->id }}, true)' wire:loading.attr="disabled">
                @lang('modules.customer.deleteWithOrder')
            </x-danger-button>

            <x-danger-button class="ml-3" wire:click='deleteCustomer({{ $customer->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
            @endif
         </x-slot>
    </x-confirmation-modal>


</div>
