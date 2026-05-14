<div x-data="{ addExecutiveOpen: false, openAddExecutive() { this.addExecutiveOpen = true }, closeAddExecutive() { this.addExecutiveOpen = false } }">
    <div>

        <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.deliveryExecutive')</h1>
                </div>
                <div class="items-center justify-between block sm:flex ">
                    <div class="lg:flex items-center mb-4 sm:mb-0">
                        <form class="sm:pr-3" action="#" method="GET">
                            <label for="products-search" class="sr-only">@lang('app.search')</label>
                            <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                                <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.searchCustomers') }}" wire:model.live.debounce.500ms="search"  />
                            </div>
                        </form>
                    </div>

                    <div class="lg:inline-flex items-center gap-4">
                        @if(user_can('Create Staff Member'))
                        <x-button type="button" @click="openAddExecutive()">@lang('modules.staff.addExecutive')</x-button>
                        @endif
                        
                        @php 
                            $executiveSiteUrl = route('delivery.login');

                            if (function_exists('module_enabled') && module_enabled('Subdomain') && restaurant()?->sub_domain) {
                                $restaurantFromDomain = function_exists('getRestaurantBySubDomain')
                                    ? getRestaurantBySubDomain()
                                    : null;

                                if (is_null($restaurantFromDomain)) {
                                    $executiveSiteUrl = request()->getScheme() . '://' . restaurant()->sub_domain . '/delivery/login';
                                }
                            }
                        @endphp

                        <a wire:click="exportDeliveryExecutiveList"
                         class="inline-flex items-center justify-center cursor-pointer w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                            @lang('app.export')
                        </a>

                        <a href="{{ $executiveSiteUrl }}" target="_blank"
                            class="inline-flex items-center justify-center cursor-pointer w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            <svg class="w-4 h-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h6M5 5a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-6" />
                            </svg>
                            @lang('menu.openExecutiveSite')
                        </a>

                    </div>

                </div>
            </div>

        </div>

        <livewire:deliveryExecutive.delivery-executive-table :search='$search' key='executive-table-{{ microtime() }}' />
    </div>


    {{-- Add Executive: JS-only modal (no Livewire round-trip on open/close) --}}
    <div
        x-cloak
        class="jetstream-modal fixed inset-0 overflow-y-auto overflow-x-hidden px-4 py-6 sm:px-0 z-40"
        style="display: none;"
        x-show="addExecutiveOpen"
        x-on:keydown.escape.window="closeAddExecutive()"
        @close-add-executive-modal.window="closeAddExecutive()"
    >
        <div
            x-show="addExecutiveOpen"
            class="fixed inset-0 transform transition-all"
            x-on:click="closeAddExecutive()"
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
            x-show="addExecutiveOpen"
            class="mb-6 bg-white dark:bg-gray-800 overflow-y-auto overflow-x-hidden shadow-xl transform transition-all fixed top-0 left-0 right-0 w-screen max-w-full sm:left-auto sm:right-0 sm:w-full h-screen sm:max-w-md flex flex-col"
            x-trap.noscroll="addExecutiveOpen"
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
                    {{ __("modules.staff.addExecutive") }}
                </div>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    @livewire('forms.addExecutive')
                </div>
            </div>
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <button type="button" @click="closeAddExecutive()"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
