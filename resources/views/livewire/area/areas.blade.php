<div x-data="{ addAreaOpen: false, openAddArea() { this.addAreaOpen = true }, closeAddArea() { this.addAreaOpen = false } }">
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('modules.table.allAreas')</h1>
            </div>

            @if(user_can('Create Area'))
            <div class="items-center justify-end block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700">
                <x-button type="button" @click="openAddArea()">@lang('modules.table.addArea')</x-button>
            </div>
            @endif

        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.table.areaName')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.table.noOfTables')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='menu-item-list-{{ microtime() }}'>

                            @forelse ($areas as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='menu-item-{{ $item->id . microtime() }}' wire:loading.class.delay='opacity-10'>

                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->area_name }}
                                </td>

                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">{{
                                    $item->tables_count }}</td>

                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Area'))
                                    <x-secondary-button-table wire:click='showEditArea({{ $item->id }})'
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

                                    @if(user_can('Delete Area'))
                                    <x-danger-button-table  wire:click="showDeleteArea({{ $item->id }})">
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
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="3">
                                    <svg class="mx-auto h-8 w-8" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 7h12v2H6zm0 4h12v2H6zm12 4H6v2h12z" fill="currentColor"/></svg>
                                    <p class="mt-2 text-base font-medium">@lang('messages.noAreaAdded')</p>
                                </td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    {{-- Add Area: JS-only modal (no Livewire round-trip on open/close) --}}
    <div
        x-cloak
        class="jetstream-modal fixed inset-0 overflow-y-auto overflow-x-hidden px-4 py-6 sm:px-0 z-40"
        style="display: none;"
        x-show="addAreaOpen"
        x-on:keydown.escape.window="closeAddArea()"
        @close-add-area-modal.window="closeAddArea()"
    >
        <div
            x-show="addAreaOpen"
            class="fixed inset-0 transform transition-all"
            x-on:click="closeAddArea()"
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
            x-show="addAreaOpen"
            class="mb-6 bg-white dark:bg-gray-800 overflow-y-auto overflow-x-hidden shadow-xl transform transition-all fixed top-0 left-0 right-0 w-screen max-w-full sm:left-auto sm:right-0 sm:w-full h-screen sm:max-w-md flex flex-col"
            x-trap.noscroll="addAreaOpen"
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
                    {{ __("modules.table.addArea") }}
                </div>
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <livewire:forms.add-area />
                </div>
            </div>
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <button type="button" @click="closeAddArea()"
                    class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

    @if ($activeArea)
    <x-right-modal wire:model.live="showEditAreaModal">
        <x-slot name="title">
            {{ __("modules.table.editArea") }}
        </x-slot>

        <x-slot name="content">
            @livewire('forms.editArea', ['activeArea' => $activeArea], key(str()->random(50)))
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditAreaModal', false)" wire:loading.attr="disabled">
                {{ __('app.close') }}
            </x-secondary-button>
        </x-slot>
    </x-right-modal>


    <x-confirmation-modal wire:model.defer="confirmDeleteAreaModal">
        <x-slot name="title">
            @lang('modules.table.deleteArea')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.table.deleteAreaMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteAreaModal')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click='deleteArea({{ $activeArea->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif

</div>
