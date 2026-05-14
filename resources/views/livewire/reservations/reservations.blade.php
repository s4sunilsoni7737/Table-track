<div x-data="{ addReservationOpen: false, openAddReservation() { this.addReservationOpen = true }, closeAddReservation() { this.addReservationOpen = false } }">
    @assets
    <script src="{{ asset('vendor/pikaday.js') }}" defer></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/pikaday.css') }}">
    @endassets

    <div class="p-4 bg-white block  dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-4">
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.reservations')</h1>
        </div>

        @if(!$isRestaurantOpenForReservations)
            <div class="w-full p-3 mb-4 text-sm font-medium text-center text-red-700 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                {{ $restaurantClosedMessage }}
            </div>
        @endif

        <div class="items-center justify-between block sm:flex bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="sm:pr-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <x-select id="dateRangeType" class="block w-fit" wire:model.defer="dateRangeType"
                            wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="nextWeek">@lang('app.nextWeek')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                        </x-select>

                        <div class="flex items-center mt-2 md:mt-0 w-full">
                            <x-datepicker wire:model.change="startDate" id="datepicker-range-start" placeholder="@lang('app.selectStartDate')" />
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <x-datepicker wire:model.live="endDate" id="datepicker-range-end" placeholder="@lang('app.selectEndDate')" />
                        </div>
                    </div>
                </form>

                <x-input class="block w-full md:w-1/3 mt-2 md:mt-0" type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('placeholders.searchCustomers') }}" />
            </div>

            @if(user_can('Create Reservation') && in_array('Table Reservation', restaurant_modules()) && restaurant()->enable_admin_reservation)
                @if($isRestaurantOpenForReservations)
                    <x-button type="button" @click="openAddReservation()">
                        @lang('modules.reservation.newReservation')</x-button>
                @endif
            @endif
        </div>

        <div class="flex flex-col my-4">

            <!-- Card Section -->
            <div class="space-y-4">
                <div class="grid sm:grid-cols-3 gap-3 sm:gap-4">
                    @forelse ($reservations as $item)
                    @livewire('reservations.reservation-card', ['reservation' => $item], key('reservation-' . $item->id
                    . microtime()))
                    @empty
                    <div class="text-center col-span-full">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2zm-3-2v4M8 2v4m-5 4h18m-7 4h-4v4h4v-4z"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">@lang('messages.noReservationsFound')</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
            <!-- End Card Section -->

        </div>

    </div>

    {{-- New Reservation: JS-only modal (no Livewire round-trip on open/close) --}}
    <div
        x-cloak
        class="jetstream-modal fixed inset-0 overflow-y-auto overflow-x-hidden px-4 py-6 sm:px-0 z-40"
        style="display: none;"
        x-show="addReservationOpen"
        x-on:keydown.escape.window="closeAddReservation()"
        @close-add-reservation-modal.window="closeAddReservation()">
        
        <div
            x-show="addReservationOpen"
            class="fixed inset-0 transform transition-all"
            x-on:click="closeAddReservation()"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
        </div>

        <div
            x-show="addReservationOpen"
            class="mb-6 bg-white dark:bg-gray-800 overflow-y-auto overflow-x-hidden shadow-xl transform transition-all fixed top-0 left-0 right-0 w-screen max-w-full sm:left-auto sm:right-0 sm:w-full h-screen sm:max-w-2xl flex flex-col"
            x-trap.noscroll="addReservationOpen"
            x-on:click.stop
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="px-6 py-4 flex-1">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __("modules.reservation.newReservation") }}
                </div>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <livewire:forms.new-reservation />
                </div>
            </div>
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <button type="button" @click="closeAddReservation()"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>



</div>
