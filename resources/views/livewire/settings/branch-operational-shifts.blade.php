<div>
    <div
        class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 space-y-4 md:space-y-0">
            <div>
                <h3 class="text-xl font-semibold dark:text-white">@lang('modules.settings.operationalShifts')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @lang('modules.settings.operationalShiftsDescription')
                </p>
            </div>
        </div>

        @if(count($branches) > 1)
        <div class="mb-6">
            <x-label for="selectedBranchId" value="{{ __('modules.settings.selectBranch') }}" class="mb-2" />
            <x-select id="selectedBranchId" class="block w-full" wire:model.live="selectedBranchId">
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </x-select>
        </div>
        @endif

        <!-- Shifts List -->
        <div class="mb-6">
            <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <h4 class="text-lg font-medium dark:text-white">
                    @lang('modules.settings.shiftsForBranch', ['branch' => $branchName])
                </h4>
                <div class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:gap-3">
                    <!-- Business Day Information Alert (Inline) - Only show if today is selected -->
                    @if(count($shifts) > 0 && $businessDayInfo)
                        <div class="relative inline-block" x-data="{ showTooltip: false }">
                            @if($businessDayInfo['extends_to_next_day'])
                            <div
                                class="px-3 py-2.5 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-800 cursor-help whitespace-nowrap"
                                @mouseenter="showTooltip = true"
                                @mouseleave="showTooltip = false"
                            >
                            @else
                            <div
                                class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-900/20 dark:border-gray-800 cursor-help whitespace-nowrap"
                                @mouseenter="showTooltip = true"
                                @mouseleave="showTooltip = false"
                            >
                            @endif
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-xs font-medium text-blue-900 dark:text-blue-200">
                                        @lang('modules.settings.businessDayInfo')
                                    </span>
                                </div>
                                <!-- Hover Tooltip -->
                                <div
                                    x-show="showTooltip"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute left-0 top-full mt-2 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-50 pointer-events-none"
                                    style="display: none; width: 320px; max-width: 90vw; box-sizing: border-box; overflow: hidden;"
                                    x-cloak
                                >
                                    <div style="word-wrap: break-word; overflow-wrap: break-word; width: 100%;">
                                        <p class="font-semibold mb-2 text-white" style="word-wrap: break-word; overflow-wrap: break-word; width: 100%;">@lang('modules.settings.businessDayInfo')</p>
                                        <p class="mb-2 leading-relaxed text-white" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; width: 100%;">
                                            @if($businessDayInfo['extends_to_next_day'])
                                            @lang('modules.settings.businessDayResetsAt', ['time' => $businessDayInfo['start']])
                                            @lang('app.to') {{ $businessDayInfo['end'] }}
                                            (@lang('app.on') {{ \Carbon\Carbon::parse($businessDayInfo['end_date'])->translatedFormat(restaurant()->date_format ?? 'd-m-Y') }})
                                            @else
                                            @lang('modules.settings.businessDayResetsAt', ['time' => $businessDayInfo['start']])
                                            @if($businessDayInfo['start'] != $businessDayInfo['end'])
                                                @lang('app.to') {{ $businessDayInfo['end'] }}
                                            @endif
                                            @endif
                                        </p>
                                        <p class="text-gray-300 leading-relaxed mt-2 text-sm" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; width: 100%;">
                                            @lang('modules.settings.businessDayExtendsInfo')
                                        </p>
                                    </div>
                                    @if($businessDayInfo['extends_to_next_day'])
                                    <div class="absolute -top-2 left-4 w-0 h-0 border-l-8 border-r-8 border-b-8 border-transparent border-b-gray-900"></div>
                                    @else
                                    <div class="absolute -top-2 left-4 w-0 h-0 border-l-8 border-r-8 border-b-8 border-transparent border-b-gray-900"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    <x-button type="button" wire:click="openAddModal" class="inline-flex w-full items-center justify-center whitespace-nowrap sm:w-auto sm:min-w-[140px]">
                        <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>@lang('modules.settings.addShift')</span>
                    </x-button>
                </div>
            </div>

            @if(count($shifts) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.settings.shiftName')
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.settings.startTime')
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                @lang('modules.settings.endTime')
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                @lang('app.status')
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                @lang('app.action')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @foreach($shifts as $shift)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $shift['shift_name'] ?: __('modules.settings.shift') . ' ' . ($loop->index + 1) }}
                                @php
                                    $days = is_string($shift['day_of_week']) ? json_decode($shift['day_of_week'], true) : ($shift['day_of_week'] ?? []);
                                    if (!is_array($days)) $days = [$days];
                                    $allWeekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    $showDays = in_array('All', $days) ? $allWeekDays : array_filter($days, function($day) { return $day !== 'All'; });
                                @endphp
                                @if(!empty($showDays))
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        @foreach($showDays as $day)
                                            <span class="inline-block mr-1">@lang('app.' . $day)</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $shift['start_time_display'] ?? $shift['start_time_local'] ?? $shift['start_time'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $shift['end_time_display'] ?? $shift['end_time_local'] ?? $shift['end_time'] }}
                                @if($shift['is_overnight_local'] ?? false)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">(@lang('modules.settings.nextDay'))</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($shift['is_active'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        @lang('app.active')
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        @lang('app.inactive')
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right space-x-2">
                                <x-secondary-button-table type="button" wire:click="openEditModal({{ $shift['id'] }})" title="{{ __('app.update') }}">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/>
                                    </svg>
                                </x-secondary-button-table>
                                <x-danger-button-table type="button" wire:click="deleteShift({{ $shift['id'] }})" wire:confirm="{{ __('messages.confirmDelete') }}" title="{{ __('app.delete') }}">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </x-danger-button-table>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('modules.settings.noShiftsConfigured')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.noShiftsConfiguredDescription')</p>
                <div class="mt-6">
                    <x-button type="button" wire:click="openAddModal" class="inline-flex items-center whitespace-nowrap min-w-[160px]">
                        <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>@lang('modules.settings.addFirstShift')</span>
                    </x-button>
                </div>
            </div>
            @endif
        </div>

        <!-- Help Text -->
        <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600">
            <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">@lang('modules.settings.howItWorks')</h5>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1 list-disc list-inside">
                <li>@lang('modules.settings.shiftHelp1')</li>
                <li>@lang('modules.settings.shiftHelp2')</li>
                <li>@lang('modules.settings.shiftHelp3')</li>
                <li>@lang('modules.settings.shiftHelp4')</li>
            </ul>
        </div>
    </div>

    <!-- Add/Edit Shift Modal -->
    <x-modal wire:model.defer="showShiftModal" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    @if($editingShiftId)
                        @lang('modules.settings.editShift')
                    @else
                        @lang('modules.settings.addShift')
                    @endif
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveShift" class="space-y-4">
                <!-- Active Toggle -->
                <div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <x-checkbox id="isActive" wire:model.defer="isActive" />
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">@lang('app.active')</span>
                    </label>
                </div>

                <!-- Shift Name -->
                <div>
                    <x-label for="shiftName" value="{{ __('modules.settings.shiftName') }}" />
                    <x-input id="shiftName" class="block mt-1 w-full" type="text" wire:model.defer="shiftName" :placeholder="__('modules.settings.shiftNamePlaceholder')" />
                    <x-input-error for="shiftName" class="mt-2" />
                </div>

                <!-- Start Time and End Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="startTime" value="{{ __('modules.settings.startTime') }}" />
                        <div>
                            <x-time-picker wire:model.live="startTime" value="{{ $startTime }}" :restaurant="$branch->restaurant" />
                        </div>
                        <x-input-error for="startTime" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="endTime" value="{{ __('modules.settings.endTime') }}" />
                        <div>
                            <x-time-picker wire:model.live="endTime" value="{{ $endTime }}" :restaurant="$branch->restaurant" />
                        </div>
                        <x-input-error for="endTime" class="mt-2" />
                    </div>
                </div>

                @php($isOvernight = $endTime < $startTime)
                @if($isOvernight)
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-800">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>@lang('modules.settings.overnightShift')</strong><br>
                        @lang('modules.settings.overnightShiftDescription')
                    </p>
                </div>
                @endif

                <!-- Day of Week (Checkboxes in one line) -->
                <div>
                    <x-label value="{{ __('modules.settings.dayOfWeek') }}" class="mb-2" />
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Monday">
                            <x-checkbox wire:model.defer="selectedDays" value="Monday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Monday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Tuesday">
                            <x-checkbox wire:model.defer="selectedDays" value="Tuesday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Tuesday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Wednesday">
                            <x-checkbox wire:model.defer="selectedDays" value="Wednesday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Wednesday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Thursday">
                            <x-checkbox wire:model.defer="selectedDays" value="Thursday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Thursday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Friday">
                            <x-checkbox wire:model.defer="selectedDays" value="Friday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Friday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Saturday">
                            <x-checkbox wire:model.defer="selectedDays" value="Saturday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Saturday')</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer" wire:key="day-checkbox-Sunday">
                            <x-checkbox wire:model.defer="selectedDays" value="Sunday" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">@lang('app.Sunday')</span>
                        </label>
                    </div>
                    <x-input-error for="selectedDays" class="mt-2" />
                </div>

                <!-- Sort Order (Hidden - auto-generated) -->
                <input type="hidden" wire:model.defer="sortOrder" />

                <!-- Footer Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                    <x-secondary-button type="button" wire:click="closeModal" wire:loading.attr="disabled">
                        {{ __('app.cancel') }}
                    </x-secondary-button>
                    <x-button type="submit" wire:loading.attr="disabled">
                        {{ __('app.save') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
