<div x-data="{ addStaffOpen: false, openAddStaff() { this.addStaffOpen = true }, closeAddStaff() { this.addStaffOpen = false } }">
    <div>

        <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.staff')</h1>
                </div>
                <div class="items-center justify-between block sm:flex ">
                    <div class="lg:flex items-center mb-4 sm:mb-0">
                        <form class="sm:pr-3" action="#" method="GET">
                            <label for="products-search" class="sr-only">Search</label>
                            <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                                <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.searchStaffmember') }}" wire:model.live.debounce.500ms="search"  />
                            </div>
                        </form>
                    </div>

                    <div class="lg:inline-flex items-center gap-4">
                        <a wire:click="exportStaffList"
                         class="inline-flex items-center cursor-pointer justify-center w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                            @lang('app.export')
                        </a>

                        @php $stats = getRestaurantStaffStats(user()->restaurant_id); @endphp

                        @if(user_can('Create Staff Member') && ($stats['unlimited'] || $stats['current_count'] < $stats['staff_limit']))
                            <x-button type="button" @click="openAddStaff()">@lang('modules.staff.addStaff')</x-button>
                        @endif

                    </div>

                </div>


            </div>

        </div>

        <livewire:staff.staff-table :search='$search' key='staff-table-{{ microtime() }}' />


    </div>


    {{-- Add Staff: JS-only modal (no Livewire round-trip on open/close) --}}
    <div
        x-cloak
        class="jetstream-modal fixed inset-0 overflow-y-auto overflow-x-hidden px-4 py-6 sm:px-0 z-40"
        style="display: none;"
        x-show="addStaffOpen"
        x-on:keydown.escape.window="closeAddStaff()"
        @close-add-staff-modal.window="closeAddStaff()"
    >
        <div
            x-show="addStaffOpen"
            class="fixed inset-0 transform transition-all"
            x-on:click="closeAddStaff()"
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
            x-show="addStaffOpen"
            class="mb-6 bg-white dark:bg-gray-800 overflow-y-auto overflow-x-hidden shadow-xl transform transition-all fixed top-0 left-0 right-0 w-screen max-w-full sm:left-auto sm:right-0 sm:w-full h-screen sm:max-w-md flex flex-col"
            x-trap.noscroll="addStaffOpen"
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
                    {{ __("modules.staff.addStaff") }}
                </div>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <livewire:forms.add-staff :key="'add-staff-' . now()->timestamp" />
                </div>
            </div>
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <button type="button" @click="closeAddStaff()"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
