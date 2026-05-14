<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-2 text-xl font-semibold dark:text-white">@lang('modules.settings.restaurantOpenCloseSettings')</h3>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.restaurantOpenCloseSettingsInfo')</p>

        @if(!$openCloseColumnsAvailable)
            <div class="mb-4 p-3 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                @lang('messages.restaurantOpenCloseSchemaMissing')
            </div>
        @endif

        <form wire:submit="submitForm" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label @class([
                    'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                    'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $openCloseAuto,
                    'border-gray-200 dark:border-gray-700' => !$openCloseAuto
                ])>
                    <div class="flex items-center justify-between mb-2">
                        <span @class([
                            'font-medium',
                            'text-skin-base' => $openCloseAuto,
                            'text-gray-900 dark:text-white' => !$openCloseAuto
                        ])>
                            @lang('modules.settings.openCloseAutoMode')
                        </span>
                        <x-checkbox wire:model.live="openCloseAuto" />
                    </div>
                </label>

                <label @class([
                    'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                    'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $openCloseManual,
                    'border-gray-200 dark:border-gray-700' => !$openCloseManual
                ])>
                    <div class="flex items-center justify-between mb-2">
                        <span @class([
                            'font-medium',
                            'text-skin-base' => $openCloseManual,
                            'text-gray-900 dark:text-white' => !$openCloseManual
                        ])>
                            @lang('modules.settings.openCloseManualMode')
                        </span>
                        <x-checkbox wire:model.live="openCloseManual" />
                    </div>
                </label>
            </div>

            @if($openCloseManual)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label @class([
                        'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                        'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $manualUseToggle,
                        'border-gray-200 dark:border-gray-700' => !$manualUseToggle
                    ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'font-medium',
                                'text-skin-base' => $manualUseToggle,
                                'text-gray-900 dark:text-white' => !$manualUseToggle
                            ])>
                                @lang('modules.settings.manualControlByToggle')
                            </span>
                            <x-checkbox wire:model.live="manualUseToggle" />
                        </div>
                    </label>

                    <label @class([
                        'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                        'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $manualUseTime,
                        'border-gray-200 dark:border-gray-700' => !$manualUseTime
                    ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'font-medium',
                                'text-skin-base' => $manualUseTime,
                                'text-gray-900 dark:text-white' => !$manualUseTime
                            ])>
                                @lang('modules.settings.manualControlByTime')
                            </span>
                            <x-checkbox wire:model.live="manualUseTime" />
                        </div>
                    </label>
                </div>

                @if($manualUseToggle)
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        @lang('modules.settings.manualControlByToggleInfo')
                    </p>
                @endif

                @if($manualUseTime)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-label for="manualOpenTime" value="{{ __('modules.settings.manualOpenTime') }}" />
                            <input
                                id="manualOpenTime"
                                type="time"
                                step="60"
                                class="block mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-skin-base focus:ring-skin-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                wire:model.live="manualOpenTime"
                                onclick="this.showPicker && this.showPicker()"
                                onfocus="this.showPicker && this.showPicker()"
                            />
                            <x-input-error for="manualOpenTime" class="mt-2" />
                        </div>
                        <div>
                            <x-label for="manualCloseTime" value="{{ __('modules.settings.manualCloseTime') }}" />
                            <input
                                id="manualCloseTime"
                                type="time"
                                step="60"
                                class="block mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-skin-base focus:ring-skin-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                wire:model.live="manualCloseTime"
                                onclick="this.showPicker && this.showPicker()"
                                onfocus="this.showPicker && this.showPicker()"
                            />
                            <x-input-error for="manualCloseTime" class="mt-2" />
                        </div>
                    </div>
                @endif
            @endif

            <label @class([
                'relative flex flex-col p-3 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
                'border-skin-base bg-skin-base/10 dark:bg-skin-base/10' => $isTemporarilyClosed,
                'border-gray-200 dark:border-gray-700' => !$isTemporarilyClosed
            ])>
                <div class="flex items-center justify-between mb-2">
                    <span @class([
                        'font-medium',
                        'text-skin-base' => $isTemporarilyClosed,
                        'text-gray-900 dark:text-white' => !$isTemporarilyClosed
                    ])>
                        @lang('modules.settings.temporarilyCloseRestaurant')
                    </span>
                    <x-checkbox wire:model.live="isTemporarilyClosed" />
                </div>
            </label>

            <div>
                <x-button>@lang('app.save')</x-button>
            </div>
        </form>
    </div>
</div>
