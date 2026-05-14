<div>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">@lang('modules.menu.exportMenuItems')</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">@lang('modules.menu.exportMenuItemsHelp')</p>
        </div>

        <!-- Quick Export Section -->
        <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-lightning inline mr-2" viewBox="0 0 16 16">
                            <path d="M5.52.359A.5.5 0 0 1 6 0h4a.5.5 0 0 1 .474.658L8.694 6H12.5a.5.5 0 0 1 .395.807l-7 9a.5.5 0 0 1-.873-.454L6.823 9.5H3.5a.5.5 0 0 1-.48-.641l2.5-8.5zM6.374 1 4.168 8.5H7.5a.5.5 0 0 1 .478.647L6.78 13.04 11.478 7H8a.5.5 0 0 1-.474-.658L9.306 1H6.374z"/>
                        </svg>
                        @lang('modules.menu.quickExport')
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">@lang('modules.menu.quickExportDescription')</p>
                </div>
            </div>

            <x-button wire:click="quickExport" target="quickExport" type="button">
                <svg wire:loading.remove wire:target="quickExport" class="inline w-5 h-5 current-color" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4"/></svg>
                <span wire:loading.remove wire:target="quickExport">@lang('modules.menu.exportAllCSV')</span>
                <span wire:loading wire:target="quickExport">@lang('app.exporting')...</span>
            </x-button>
        </div>

        <!-- Advanced Export Section -->
        <div class="mb-6">
            <button
                type="button" wire:click="toggleAdvancedExport"
                class="flex items-center justify-between w-full p-3 text-left bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
            >
                <div class="flex items-center gap-x-2.5">
                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2zM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2m1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0"/></svg>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('modules.menu.advancedExport')</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">@lang('modules.menu.advancedExportDescription')</p>
                    </div>
                </div>
                <svg class="w-5 h-5 transition-transform text-gray-800 dark:text-white {{ $showAdvancedExport ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
            </button>
        </div>

        <!-- Advanced Export Form -->
        @if ($showAdvancedExport)
            <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 space-y-6">
                <!-- Format Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.exportFormat')</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="format" value="csv" class="form-radio text-blue-600 dark:text-blue-500">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">CSV</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="format" value="xlsx" class="form-radio text-blue-600 dark:text-blue-500">
                            <span class="ml-2 text-gray-700 dark:text-gray-300">Excel (XLSX)</span>
                        </label>
                    </div>
                </div>

                <!-- Filters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Category Filter -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.itemCategory')</label>
                        <select wire:model.live="category_id" id="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">@lang('app.all')</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Menu Filter (Subcategory) -->
                    <div>
                        <label for="menu_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.menuName')</label>
                        <select wire:model.live="menu_id" id="menu_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">@lang('app.all')</option>
                            @foreach($menus as $menu)
                                <option value="{{ $menu->id }}">{{ $menu->menu_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.status')</label>
                        <select wire:model.live="status" id="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">@lang('app.all')</option>
                            @foreach($this->statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.itemType')</label>
                        <select wire:model.live="type" id="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">@lang('app.all')</option>
                            @foreach($this->types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('app.startDate')</label>
                        <input type="date" wire:model.live="start_date" id="start_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('app.endDate')</label>
                        <input type="date" wire:model.live="end_date" id="end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                </div>

                <!-- Record Count Display -->
                @if($recordCount > 0)
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle inline mr-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                            </svg>
                            <strong>{{ $recordCount }}</strong> {{ Str::plural('record', $recordCount) }} will be exported.
                            @if($recordCount > 5000)
                                <span class="block mt-1">@lang('messages.largeExportQueued')</span>
                            @endif
                        </p>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <x-secondary-button wire:click="resetFilters" type="button">
                        @lang('app.reset')
                    </x-secondary-button>

                    <x-button wire:click="advancedExport" target="advancedExport" type="button">
                        <svg wire:loading.remove wire:target="advancedExport" class="inline w-5 h-5 current-color" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4"/></svg>
                        <span wire:loading.remove wire:target="advancedExport">@lang('app.export')</span>
                        <span wire:loading wire:target="advancedExport">@lang('app.exporting')...</span>
                    </x-button>
                </div>
            </div>
        @endif
    </div>
</div>
