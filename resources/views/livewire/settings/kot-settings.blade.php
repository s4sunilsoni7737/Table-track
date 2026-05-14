<div class="grid grid-cols-1 gap-6 mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">

    <div >
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('modules.settings.kotSettings')</h3>

        <form wire:submit="submitForm" class="grid gap-6 grid-cols-1 md:grid-cols-2">
            <div class="grid gap-6 border border-gray-200 dark:border-gray-700 p-4 rounded-lg">
                <div>
                    <div class="relative flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="enableItemLevelStatus" wire:model.defer="enableItemLevelStatus"
                                class="w-5 h-5 border-gray-300 rounded text-primary-600 focus:ring-primary-500">
                        </div>
                        <div class="ml-4">
                            <label for="enableItemLevelStatus" class="text-base font-medium text-gray-900 dark:text-white">
                                @lang('modules.settings.enableItemLevelStatus')
                            </label>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @lang('modules.settings.enableItemLevelStatusDescription')
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <h4 class="mb-4 text-lg font-semibold dark:text-white">@lang('modules.settings.defaultKotStatus')</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- POS Tab --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700 mb-4">
                                <ul class="flex flex-wrap -mb-px">
                                    <li class="w-full">
                                        <span class="inline-block p-2 border-b-2 border-skin-base text-skin-base dark:text-skin-base dark:border-skin-base font-semibold">
                                            @lang('modules.menu.pos')
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class="grid gap-4">
                                <div class="relative flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 cursor-pointer"
                                    wire:click="setPosStatus('pending')">
                                    <div class="flex items-center h-5">
                                        <input id="statusPending" type="checkbox"
                                            @checked($defaultKotStatus === 'pending')
                                            class="w-5 h-5 border-gray-300 rounded text-primary-600 focus:ring-primary-500 pointer-events-none">
                                    </div>
                                    <div class="ml-4">
                                        <label for="statusPending" class="text-base font-medium text-gray-900 dark:text-white cursor-pointer">@lang('modules.settings.kotStatusesPending')</label>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.kotStatusesPendingDescription')</p>
                                    </div>
                                </div>

                                <div class="relative flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 cursor-pointer"
                                    wire:click="setPosStatus('cooking')">
                                    <div class="flex items-center h-5">
                                        <input id="statusCooking" type="checkbox"
                                            @checked($defaultKotStatus === 'cooking')
                                            class="w-5 h-5 border-gray-300 rounded text-primary-600 focus:ring-primary-500 pointer-events-none">
                                    </div>
                                    <div class="ml-4">
                                        <label for="statusCooking" class="text-base font-medium text-gray-900 dark:text-white cursor-pointer">@lang('modules.settings.kotStatusesCooking')</label>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.kotStatusesCookingDescription')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Customer Tab --}}
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700 mb-4">
                                <ul class="flex flex-wrap -mb-px">
                                    <li class="w-full">
                                        <span class="inline-block p-2 border-b-2 border-skin-base text-skin-base dark:text-skin-base dark:border-skin-base font-semibold">
                                            @lang('modules.customer.customer')
                                        </span>
                                    </li>
                                </ul>
                            </div>
                            <div class="grid gap-4">
                                <div class="relative flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 cursor-pointer"
                                    wire:click="setCustomerStatus('pending')">
                                    <div class="flex items-center h-5">
                                        <input id="customerStatusPending" type="checkbox"
                                            @checked($defaultCustomerKotStatus === 'pending')
                                            class="w-5 h-5 border-gray-300 rounded text-primary-600 focus:ring-primary-500 pointer-events-none">
                                    </div>
                                    <div class="ml-4">
                                        <label for="customerStatusPending" class="text-base font-medium text-gray-900 dark:text-white cursor-pointer">@lang('modules.settings.kotStatusesPending')</label>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.kotStatusesPendingDescription')</p>
                                    </div>
                                </div>

                                <div class="relative flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 cursor-pointer"
                                    wire:click="setCustomerStatus('cooking')">
                                    <div class="flex items-center h-5">
                                        <input id="customerStatusCooking" type="checkbox"
                                            @checked($defaultCustomerKotStatus === 'cooking')
                                            class="w-5 h-5 border-gray-300 rounded text-primary-600 focus:ring-primary-500 pointer-events-none">
                                    </div>
                                    <div class="ml-4">
                                        <label for="customerStatusCooking" class="text-base font-medium text-gray-900 dark:text-white cursor-pointer">@lang('modules.settings.kotStatusesCooking')</label>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.kotStatusesCookingDescription')</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <div class="col-span-1 md:col-span-2">
                <x-button>@lang('app.save')</x-button>
            </div>
        </form>
    </div>

</div>
