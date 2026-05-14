<div x-data="{ assignTableModalOpen: false }">
    <div class="p-4 block sm:flex items-center justify-between ">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('modules.menu.allMenus')</h1>
            </div>
            <div class="items-center justify-between block sm:flex">
                <div class="flex items-center mb-4 sm:mb-0">
                    <form class="sm:pr-3" action="#" method="GET">
                        <label for="products-search" class="sr-only">Search</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.searchMenus') }}" wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-secondary-link href="javascript:;" x-on:click.prevent="assignTableModalOpen = true">
                        @lang('modules.menu.assignTableToMenu')
                    </x-secondary-link>
                <x-secondary-link href="{{ route('menu-items.entities.sort') }}">
                    @lang('modules.menu.sortMenuItems')
                </x-secondary-link>

                @if(user_can('Create Menu'))
                    <x-button type='button' data-drawer-target="drawer-create-product-default"
                    data-drawer-show="drawer-create-product-default"
                    aria-controls="drawer-create-product-default"
                    data-drawer-placement="right" id="createProductButton">@lang('modules.menu.addMenu')</x-button>
                @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col my-4 px-4">
        <!-- Card Section -->
        <div class="flex items-center gap-3 mb-6 overflow-x-auto pb-1">
            @forelse ($menus as $item)
            <!-- Card -->
            <div @class([
                'rounded-xl px-4 py-3 flex items-center gap-3 min-w-[220px] cursor-pointer shrink-0',
                'bg-skin-base dark:bg-skin-base text-white' => ($menuId == $item->id),
                'bg-white text-gray-800 dark:text-gray-200 group-hover:text-skin-base dark:bg-gray-700' => ($menuId != $item->id),
            ]) wire:key='menu-{{ $item->id . microtime() }}' wire:click='showMenuItems({{ $item->id }})'>
                <div @class([
                    'w-9 h-9 rounded-lg  flex items-center justify-center shrink-0',
                    'bg-gray-50 dark:bg-gray-800 ' => ($menuId != $item->id),
                    'bg-white/20 ' => ($menuId == $item->id),
                ])>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                  <p @class(['text-sm leading-tight', 
                  'font-semibold' => ($menuId == $item->id),
                  'font-medium' => ($menuId != $item->id),
                  ])>{{ $item->menu_name }}</p>
                  <p @class(["text-[11px] text-gray-400 mt-0.5 font-medium", 'text-white/70' => ($menuId == $item->id), 'text-gray-400' => ($menuId != $item->id)])>{{ $item->items_count }} @lang('modules.menu.item')</p>
                </div>
            </div>

         
            <!-- End Card -->
            @empty
                <span class="dark:text-gray-400">@lang('messages.noMenuAdded')</span>
            @endforelse

        </div>
        <!-- End Card Section -->


        @if ($menuItems)
        <div class="w-full">
            <div class="my-4 flex items-center gap-4">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ $activeMenu->menu_name }}</h1>

                @if(user_can('Update Menu'))
                <x-secondary-button-table wire:click='showEditMenu({{ $activeMenu->id }})'
                    wire:key='editmenu-button-{{ $activeMenu->id }}'>
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

                @if(user_can('Delete Menu'))
                <x-danger-button-table wire:click="$toggle('confirmDeleteMenuModal')">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </x-danger-button-table>
                @endif

            </div>
        </div>

            @if(user_can('Show Menu Item'))
            <livewire:menu.menu-items :menuID='$menuId' key='menu-item-{{ microtime() }}' />
            @endif
        @endif
    </div>


    @if ($activeMenu)
    <x-right-modal wire:model.live="showEditMenuModal">
        <x-slot name="title">
            {{ __("modules.menu.editMenuItem") }}
        </x-slot>

        <x-slot name="content">
            @if ($activeMenu)
            @livewire('forms.editMenu', ['activeMenu' => $activeMenu], key(str()->random(50)))
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditMenuModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model.defer="confirmDeleteMenuModal">
        <x-slot name="title">
            @lang('modules.menu.deleteMenu')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.menu.deleteMenuMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteMenuModal')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click='deleteMenu({{ $activeMenu->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif


    <!-- Assign Table to Menu Modal (client-side open/close) -->
    <div
        x-cloak
        x-show="assignTableModalOpen"
        x-on:keydown.escape.window="assignTableModalOpen = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
            <div
                x-show="assignTableModalOpen"
                x-transition.opacity
                class="fixed inset-0 bg-gray-900/50"
                x-on:click="assignTableModalOpen = false"
                aria-hidden="true"
            ></div>

            <div
                x-show="assignTableModalOpen"
                x-transition
                class="relative inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800"
            >
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    @lang('modules.menu.assignTableToMenu')
                </h3>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-label for="selectedTableId" :value="__('modules.table.table')" />
                        <x-select id="selectedTableId" class="mt-1 block w-full" wire:model.live="selectedTableId">
                            <option value="">{{ __('app.select') }}</option>
                            @foreach ($allTables as $table)
                            <option value="{{ $table->id }}">{{ $table->table_code }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error for="selectedTableId" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="selectedMenuIds" :value="__('modules.menu.menuName')" />
                        <div
                            class="overflow-x-auto w-full transition-all duration-300 ease-in-out mt-2 border border-gray-300 dark:border-gray-600 rounded-md max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                            {{ __('modules.menu.menuName') }}
                                        </th>
                                        <th
                                            class="py-2.5 px-4 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">
                                            {{ __('app.select') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    @forelse($allMenus as $menu)
                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key="menu-row-{{ $menu->id }}">
                                            <td class="py-2.5 px-4 text-sm text-gray-900 dark:text-white">
                                                {{ $menu->menu_name }}
                                            </td>
                                            <td class="py-2.5 px-4 text-right">
                                                <x-checkbox id="menu{{ $menu->id }}" name="selectedMenuIds[]"
                                                    wire:model.live="selectedMenuIds"
                                                    wire:key="checkbox-{{ $menu->id }}-{{ $menuCheckboxKey }}"
                                                    :value="(int) $menu->id" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-2.5 px-4 text-sm text-gray-500 text-center">
                                                {{ __('messages.noMenuAdded') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-input-error for="selectedMenuIds" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="is_active">
                            <div class="flex items-center cursor-pointer">
                                <x-checkbox name="is_active" id="is_active" wire:model.defer="is_active" />
                                <div class="ms-2">
                                    {{ __('app.active') }}
                                </div>
                            </div>
                        </x-label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="assignTableModalOpen = false" wire:loading.attr="disabled">
                        {{ __('app.close') }}
                    </x-secondary-button>
                    <x-button wire:click="saveTableMenuAssignment" wire:loading.attr="disabled" class="ml-3">
                        {{ __('app.save') }}
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</div>



