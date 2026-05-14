<div>
    <div class="flex flex-col">
        <!-- Item Header -->
        <div class="flex gap-4 mb-4">
            @if (restaurant() && !restaurant()->hide_menu_item_image_on_pos)
                <img class="w-16 h-16 rounded-md object-cover" src="{{ $menuItem->item_photo_url }}" alt="{{ $menuItem->item_name }}">
            @endif
            <div class="text-sm font-normal text-gray-500 dark:text-gray-400 space-y-1">
                <div class="text-base font-semibold text-gray-900 dark:text-white inline-flex items-center">
                    <img src="{{ asset('img/'.$menuItem->type.'.svg') }}" class="h-4 mr-2"
                        title="@lang('modules.menu.' . $menuItem->type)" alt="" />
                    {{ $menuItem->item_name }}
                    @if ($variationId)
                        @php
                            $variation = \App\Models\MenuItemVariation::find($variationId);
                        @endphp
                        @if ($variation)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ms-1">({{ $variation->variation }})</span>
                        @endif
                    @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $menuItem->description }}</div>
                <div class="text-sm text-gray-800 dark:text-gray-400 mt-1 sm:mt-0 font-medium">
                    {{ $menuItem->price ? currency_format($menuItem->price, $menuItem->branch->restaurant->currency_id) : __('--') }}
                </div>
            </div>
        </div>

        <!-- Modifiers List -->
        @foreach ($modifierGroups as $modifier)
            @php
                $isRequired = $modifier->itemModifiers->isNotEmpty() 
                    ? ($modifier->itemModifiers->first()->is_required ?? false) 
                    : false;
                $allowMultiple = $modifier->itemModifiers->isNotEmpty() 
                    ? ($modifier->itemModifiers->first()->allow_multiple_selection ?? false) 
                    : false;
            @endphp
            <div x-data="{ open: true }" class="flex flex-col items-start mb-4 border p-2 rounded-md bg-white dark:bg-gray-800 dark:border-gray-700" data-modifier-group-id="{{ $modifier->id }}" data-is-required="{{ $isRequired ? '1' : '0' }}">
                <!-- Modifier Header (Clickable) -->
                <div class="flex justify-between items-center w-full cursor-pointer select-none p-3 rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                     @click="open = !open">
                    <div class="flex flex-col justify-between w-full">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $modifier->name }}
                            @if ($isRequired)
                                <span class="text-red-500 text-xs">*</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 sm:mt-0">
                            {{ $modifier->description }}
                        </div>
                    </div>
                    <!-- Expand/Collapse Icon -->
                    <svg x-bind:class="{ 'rotate-180': open }" class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-300 transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <!-- Options Table (Collapsible) -->
                <div x-show="open" x-collapse class="overflow-x-auto w-full transition-all duration-300 ease-in-out">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600 mt-2">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.modifier.optionName')
                                </th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.setPrice')
                                </th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                                    @lang('app.select')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($modifier->options as $option)
                                <tr class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="$event.target.tagName !== 'INPUT' && $el.querySelector('input[data-modifier-option-id=\'{{ $option->id }}\']')?.click()">
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $option->name }}
                                    </td>
                                    <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $option->price ? currency_format($option->price, $menuItem->branch->restaurant->currency_id) : __('--') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right">
                                        @if ($option->is_available)
                                            <input type="checkbox" 
                                                name="modifier_group_{{ $modifier->id }}" 
                                                value="{{ $option->id }}"
                                                data-modifier-group-id="{{ $modifier->id }}"
                                                data-modifier-option-id="{{ $option->id }}"
                                                data-modifier-price="{{ $option->price ?? 0 }}"
                                                data-modifier-name="{{ $option->name }}"
                                                class="modifier-option-checkbox w-4 h-4 rounded border-gray-300 focus:ring-skin-base text-skin-base"
                                                @click.stop
                                                @if (!$allowMultiple) onclick="handleSingleSelection({{ $modifier->id }}, {{ $option->id }})" @endif />
                                        @else
                                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                @lang('modules.menu.notAvailable')
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div id="required-error-{{ $modifier->id }}" class="mt-2 text-red-600 text-sm hidden">
                        @lang('validation.requiredModifierGroup', ['name' => $modifier->name])
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Save Button -->
    <div class="mt-4 text-right">
        <x-button type="button" onclick="saveModifiers({{ $menuItem->id }}, {{ $variationId ?? 'null' }})" 
            class="ml-3">
            @lang('app.save')
        </x-button>
        <x-secondary-button type="button" onclick="closeModifiersModal()" 
            class="ml-3">
            @lang('app.cancel')
        </x-secondary-button>
    </div>
</div>

