<div x-data="{
    addCategoryOpen: false,
    editCategoryOpen: false,
    openAddCategory() { this.addCategoryOpen = true },
    closeAddCategory() { this.addCategoryOpen = false },
    openEditCategory() { this.editCategoryOpen = true },
    closeEditCategory() { this.editCategoryOpen = false }
}">
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">@lang('menu.itemCategories')</h1>
            </div>
            <div class="items-center justify-between block sm:flex ">
                <div class="flex items-center mb-4 sm:mb-0">
                    <form class="sm:pr-3" action="#" method="GET">
                        <label for="products-search" class="sr-only">Search</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="menu_name" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.searchItemCategory') }}" wire:model.live.debounce.500ms="search" />

                        </div>
                    </form>
                </div>

                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-secondary-link href="{{ route('menu-items.entities.sort') }}">
                        @lang('modules.menu.sortMenuItems')
                    </x-secondary-link>

                    @if(user_can('Create Item Category'))
                    <x-button type="button" @click="openAddCategory()">@lang('modules.menu.addItemCategory')</x-button>
                    @endif
                </div>
            </div>
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
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.itemCategory')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('modules.menu.allMenuItems')
                                </th>
                                <th scope="col"
                                    class="py-2.5 px-4 text-xs font-medium ltr:text-left rtl:text-right text-gray-500 uppercase dark:text-gray-400">
                                    @lang('app.action')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='menu-item-list-{{ microtime() }}'>

                            @forelse ($categories as $item)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='menu-item-{{ $item->id . microtime() }}' wire:loading.class.delay='opacity-10'>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->category_name }}
                                </td>
                                <td class="py-2.5 px-4 text-xs text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $item->items_count }} @lang('modules.menu.item')
                                </td>

                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Item Category'))
                                    <x-secondary-button-table x-on:click="openEditCategory(); $wire.showEditCategory({{ $item->id }})"
                                        wire:key='edit-cat-button-{{ $item->id }}'>
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

                                    @if(user_can('Delete Item Category'))
                                    <x-danger-button-table wire:click="showDeleteCategory({{ $item->id }})">
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
                                <td class="py-2.5 px-4 space-x-6 dark:text-gray-400" colspan="5">
                                    @lang('messages.noItemCategoryAdded')
                                </td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <div wire:key='menu-item-category-paginate-{{ microtime() }}'
        class="sticky bottom-0 right-0 items-center w-full p-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-4 sm:mb-0 w-full">
            {{ $categories->links() }}
        </div>
    </div>


    <!-- Edit Item Category Modal (client-side open/close) -->
    <div
        x-cloak
        x-show="editCategoryOpen"
        x-on:keydown.escape.window="closeEditCategory()"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
            <div
                x-show="editCategoryOpen"
                x-transition.opacity
                class="fixed inset-0 bg-gray-900/50"
                x-on:click="closeEditCategory()"
                aria-hidden="true"
            ></div>

            <div
                x-show="editCategoryOpen"
                x-transition
                class="relative inline-block w-full max-w-xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800"
            >
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __("modules.menu.itemCategory") }}
                </h3>

                <div class="mt-4">
                    @if ($itemCategory)
                    @livewire('forms.editItemCategory', ['itemCategory' => $itemCategory], key(str()->random(50)))
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="closeEditCategory()" wire:loading.attr="disabled">
                        {{ __('app.close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Item Category: JS-only modal (no Livewire round-trip on open/close) --}}
    <div
        x-cloak
        class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
        style="display: none;"
        x-show="addCategoryOpen"
        x-on:keydown.escape.window="closeAddCategory()"
        @close-add-category-modal.window="closeAddCategory()"
    >
        <div
            x-show="addCategoryOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 transform transition-all"
            x-on:click="closeAddCategory()"
        >
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
        </div>

        <div
            x-show="addCategoryOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-xl sm:mx-auto overflow-y-auto mt-16 sm:mt-20"
            x-trap.noscroll="addCategoryOpen"
            x-on:click.stop
        >
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    @lang('modules.menu.addItemCategory')
                </div>
            </div>
            <div class="px-6 pb-4 text-sm text-gray-600 dark:text-gray-400">
                @livewire('forms.addItemCategory')
            </div>
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
                <button type="button" @click="closeAddCategory()"
                    class="inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                    {{ __('app.close') }}
                </button>
            </div>
        </div>
    </div>

    <x-confirmation-modal wire:model.defer="confirmDeleteCategory">
        <x-slot name="title">
            @lang('modules.menu.deleteItemCategory')?
        </x-slot>

        <x-slot name="content">
            @lang('modules.menu.deleteCategoryMessage')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteCategory')" wire:loading.attr="disabled">
                {{ __('app.cancel') }}
            </x-secondary-button>

            @if ($itemCategory)
            <x-danger-button class="ml-3" wire:click='deleteItemCategory({{ $itemCategory->id }})' wire:loading.attr="disabled">
                {{ __('app.delete') }}
            </x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>

</div>
