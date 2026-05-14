<div class="w-full h-full">
    @php
        $orderStats = getRestaurantOrderStats(branch()->id);
        $orderLimitReached = !$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'];
    @endphp
    <div x-data="{
        showMenu: false,
        filterView: getCookie('posFilterView') ?? 'select',
        toggleMenu() {
            this.showMenu = !this.showMenu;
            if (this.showMenu) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        setFilterView(view) {
            this.filterView = view;
            setCookie('posFilterView', view, 30);
        }
    }">
        <!-- Mobile Toggle Button -->
        <button
            @click="toggleMenu()"
            class="fixed bottom-6 right-6 z-50 md:hidden bg-skin-base text-white rounded-full shadow-lg p-4 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-skin-base transition"
            aria-label="Toggle Menu"
            type="button"
        >
            <svg x-show="!showMenu" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg x-show="showMenu" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>

            <span class="lg:hidden ml-1">@lang('menu.menu')</span>
        </button>

        <!-- Menu Panel -->
        <div :class="{'hidden': !showMenu, ' inset-0 z-40 flex': showMenu}" class="md:flex flex-col bg-gray-50 lg:h-full w-full ps-1  dark:bg-gray-900 transition-transform duration-300 md:static md:inset-auto md:z-auto md:translate-x-0 overflow-y-auto md:overflow-visible md:max-h-none" style="backdrop-filter: blur(2px);" x-cloak>
            {{-- Search + Filters --}}
            <div class="bg-white/70 dark:bg-gray-800/70 rounded-xl border border-gray-100 dark:border-gray-700 p-3 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                    <div class="flex-1 order-2 lg:order-1">
                        <form action="#" method="GET" onsubmit="event.preventDefault(); return false;">
                            <label for="products-search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                    </svg>
                                </div>
                                <x-input id="products-search" class="block w-full pl-10 pr-10 py-2 border-gray-200 rounded-lg text-sm focus:ring-skin-base focus:border-skin-base" type="text"
                                    placeholder="{{ __('placeholders.searchMenuItems') }}"
                                    value="{{ $search }}" />
                                    <x-button
                                        id="products-search-clear"
                                        type="button"
                                        onclick="clearSearch()"
                                        class="absolute  {{ empty($search) ? 'hidden' : '' }}"
                                        aria-label="{{ __('app.reset') }}"
                                    >
                                        <svg class="w-5 h-5" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 20 4 4m16 0L4 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </x-button>
                            </div>
                        </form>
                    </div>

                    <div class="flex items-center justify-between gap-2 order-1 lg:order-2">

                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-md p-1">
                                <button type="button"
                                    @click="setFilterView('select')"
                                    :aria-pressed="filterView === 'select'"
                                    :class="filterView === 'select' ? 'bg-skin-base text-white shadow-sm' : 'text-gray-700 dark:text-gray-200'"
                                    class="px-2 py-1 rounded-md transition"
                                    aria-label="@lang('app.dropdown')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                <button type="button"
                                    @click="setFilterView('grid')"
                                    :aria-pressed="filterView === 'grid'"
                                    :class="filterView === 'grid' ? 'bg-skin-base text-white shadow-sm' : 'text-gray-700 dark:text-gray-200'"
                                    class="px-2 py-1 rounded-md transition"
                                    aria-label="@lang('app.grid')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <x-primary-link href="{{ route('pos.index') }}"
                            class="inline-flex items-center px-3 py-2 gap-1 text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z" />
                                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466" />
                            </svg>
                            @lang('app.reset')
                        </x-primary-link>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                        <div class="relative space-y-2">
                            <template x-if="filterView === 'select'">
                                <div class="relative">
                                    <label for="menu-filter" class="sr-only">@lang('modules.menu.menus')</label>
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                                    </div>
                                    <select id="menu-filter" onchange="filterByMenu(this.value)" class="block w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 focus:ring-skin-base focus:border-skin-base">
                                        <option value="">{{ __('app.filterByMenu') }}</option>
                                        @foreach ($menuList as $menu)
                                            <option value="{{ $menu->id }}" {{ $menuId == $menu->id ? 'selected' : '' }}>
                                                {{ $menu->getTranslation('menu_name', session('locale', app()->getLocale())) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>

                            <template x-if="filterView === 'grid'">
                                <div class="flex gap-2 items-center flex-wrap">
                                    <button type="button"
                                        onclick="filterByMenu(null)"
                                        @class([
                                            'px-3 py-1.5 text-xs rounded-lg border transition text-left',
                                            'border-skin-base bg-skin-base text-white shadow-sm' => $menuId === null,
                                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base' => $menuId !== null,
                                        ])>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium">Show All</span>
                                        </div>
                                    </button>
                                    @foreach ($menuList as $menu)
                                        @php
                                            $isActiveMenu = (string) $menuId === (string) $menu->id;
                                        @endphp
                                        <button type="button"
                                            onclick="filterByMenu({{ $menu->id }})"
                                            @class([
                                                'px-3 py-1.5 text-xs rounded-lg border transition text-left',
                                                'border-skin-base bg-skin-base text-white shadow-sm' => $isActiveMenu,
                                                'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base' => ! $isActiveMenu,
                                            ])>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-medium">{{ $menu->getTranslation('menu_name', session('locale', app()->getLocale())) }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </template>
                        </div>

                        <div class="relative space-y-2">
                            <template x-if="filterView === 'select'">
                                <div class="relative">
                                    <label for="category-filter" class="sr-only">@lang('modules.menu.categories')</label>
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" stroke-linejoin="round"/></svg>
                                    </div>
                                    <select id="category-filter" onchange="filterByCategory(this.value)" class="block w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 focus:ring-skin-base focus:border-skin-base">
                                        <option value="">{{ __('app.filterByCategory') }}</option>
                                        @foreach ($categoryList as $category)
                                            <option value="{{ $category->id }}" {{ $filterCategories == $category->id ? 'selected' : '' }}>
                                                {{ $category->category_name }} ({{ $category->items_count }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>

                            <template x-if="filterView === 'grid'">
                                <div class="flex gap-2 items-center flex-wrap">
                                    <button type="button"
                                        onclick="filterByCategory(null)"
                                        @class([
                                            'px-3 py-1.5 text-xs rounded-lg border transition text-left',
                                            'border-skin-base bg-skin-base text-white shadow-sm' => $filterCategories === null,
                                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base' => $filterCategories !== null,
                                        ])>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium">Show All</span>
                                        </div>
                                    </button>
                                    @foreach ($categoryList as $category)
                                        @php
                                            $isActiveCategory = (string) $filterCategories === (string) $category->id;
                                        @endphp
                                        <button type="button"
                                            onclick="filterByCategory({{ $category->id }})"
                                            @class([
                                                ' px-3 py-1.5 text-xs rounded-lg border transition text-left',
                                                'border-skin-base bg-skin-base text-white shadow-sm' => $isActiveCategory,
                                                'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base' => ! $isActiveCategory,
                                            ])>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="font-medium">{{ $category->category_name }}</span>
                                                <span class="text-[11px] text-gray-500 dark:text-gray-300">({{ $category->items_count }})</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </template>
                        </div>
                </div>
            </div>

            {{-- Menu Items Grid --}}
            <div
                class="mt-4 overflow-y-auto
                    [&::-webkit-scrollbar]:w-2
                    [&::-webkit-scrollbar-track]:bg-gray-300
                    [&::-webkit-scrollbar-thumb]:bg-gray-400
                    hover:[&::-webkit-scrollbar-thumb]:bg-gray-500
                    dark:[&::-webkit-scrollbar-track]:bg-gray-700
                    dark:[&::-webkit-scrollbar-thumb]:bg-gray-500
                    dark:hover:[&::-webkit-scrollbar-thumb]:bg-gray-400"
                x-data="{
                    loadedCount: {{ $menuItemsLoaded }},
                    totalCount: {{ $totalMenuItemsCount }},

                    get allItemsLoaded() {
                        return this.loadedCount >= this.totalCount;
                    },

                    scrollHandler(scrollEl = $el) {
                        if (this.allItemsLoaded) {
                            return;
                        }
                        if (!scrollEl) {
                            return;
                        }

                        if (scrollEl.scrollHeight - scrollEl.scrollTop <= scrollEl.clientHeight + 250) {
                            loadMoreMenuItems();
                        }
                    }
                }"
                >
                <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 max-h-[calc(100vh-12rem)] overflow-y-auto"
                    @scroll.throttle.100ms="scrollHandler($event.target)">
                    @forelse ($menuItems as $item)
                        <li class="menu-card bg-white rounded-xl border border-gray-100 overflow-hidden cursor-pointer group dark:border-gray-700">
                            <input type="checkbox" id="item-{{ $item->id }}" value="{{ $item->id }}"
                                onclick="addCartItemClientSide({{ $item->id }})"
                                data-item-id="{{ $item->id }}"
                                data-item-name="{{ $item->item_name }}"
                                data-item-price="{{ $item->price }}"
                                data-item-image="{{ $item->item_photo_url }}"
                                data-item-taxes="{{ e(json_encode(($item->taxes ?? collect())->map(fn($tax) => ['id' => $tax->id, 'tax_name' => $tax->tax_name, 'tax_percent' => $tax->tax_percent])->values()->toArray())) }}"
                                data-variations-count="{{ $item->variations_count }}"
                                data-modifiers-count="{{ $item->modifier_groups_count }}"
                                data-item-in-stock="{{ $item->in_stock ? '1' : '0' }}"
                                {{ ($orderLimitReached || !$item->in_stock) ? 'disabled' : '' }}
                                class="hidden peer">
                            <label for="item-{{ $item->id }}"
                                @class([
                                    "block w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 relative outline-none",
                                    "cursor-pointer hover:shadow-md dark:hover:bg-gray-700/30 active:scale-95 focus-visible:scale-95" => !$orderLimitReached && $item->in_stock,
                                    "cursor-not-allowed opacity-60" => $orderLimitReached || !$item->in_stock,
                                    "bg-gray-100 dark:bg-gray-800" => !$item->in_stock,
                                    "bg-white dark:bg-gray-900" => $item->in_stock && !$orderLimitReached,
                                    "bg-gray-200 dark:bg-gray-800" => $orderLimitReached,
                                ])
                                tabindex="{{ $orderLimitReached ? '-1' : '0' }}"
                        >

                                    {{-- Loading Overlay --}}
                                    <div id="loading-{{ $item->id }}" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-lg z-10 items-center justify-center hidden">
                                        <svg class="animate-spin h-6 w-6 text-skin-base" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>

                                    {{-- Image Section --}}
                                    @if (!$restaurant->hide_menu_item_image_on_pos)
                                    <div class="relative  hidden md:block select-none">
                                        <img class="w-full h-28 object-cover rounded-t-lg select-none" draggable="false"
                                            src="{{ $item->item_photo_url }}"
                                            alt="{{ $item->item_name }}" />
                                        <span class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm">
                                            <img src="{{ asset('img/' . $item->type . '.svg') }}"
                                                class="h-4 w-4" title="@lang('modules.menu.' . $item->type)"
                                                alt="" />
                                        </span>
                                    </div>
                                    @endif

                                    {{-- Content Section --}}
                                    <div class="p-2">
                                        <h5 class="text-xs font-medium text-gray-900 dark:text-white select-none">
                                            {{ $item->item_name }}
                                        </h5>
                                        @if ($orderLimitReached)
                                            <div class="text-red-500 text-xs">@lang('messages.orderLimitReached')</div>
                                        @elseif (!$item->in_stock)
                                            <div class="text-red-500">Out of stock</div>
                                        @else

                                        <div class="mt-1 flex items-center justify-between gap-2 select-none">
                                            @if ($item->variations_count == 0)
                                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ currency_format($item->price, $restaurant->currency_id) }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                    </svg>
                                                    @lang('modules.menu.showVariations')
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </label>
                        </li>
                    @empty
                        <li class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                                <p>@lang('messages.noItemAdded')</p>
                            </div>
                        </li>
                    @endforelse


                </ul>

                <div class="flex items-center justify-center py-6 px-4">
                    <div id="menu-loading-indicator" class="flex items-center justify-center gap-3 text-gray-600 dark:text-gray-400 hidden">
                        <svg class="inline animate-spin h-6 w-6 text-skin-base " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12zm2 5.291A7.96 7.96 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938z"/></svg>
                        <span class="text-sm font-medium">@lang('messages.loadingData')</span>
                    </div>
                    <div id="menu-all-loaded" class="flex items-center gap-x-1 text-gray-500 dark:text-gray-400 {{ $menuItemsLoaded >= $totalMenuItemsCount ? '' : 'hidden' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/></svg>
                        <span class="text-sm font-medium">@lang('messages.allItemsLoaded')</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Cookie functions (don't need jQuery)
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/`;
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }

    // Define functions immediately (they will check for jQuery when called)
    // Initialize menu filters from URL parameters or defaults
    const urlParams = new URLSearchParams(window.location.search);
    window.menuFilters = {
        menuId: urlParams.get('menuId') || null,
        categoryId: urlParams.get('filterCategories') || null,
        search: urlParams.get('search') || '',
        limit: parseInt(urlParams.get('limit')) || {{ $menuItemsLoaded }}
    };

    window.updateSearchClearButtonVisibility = function() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            return;
        }

        const $clearBtn = $('#products-search-clear');
        if (!$clearBtn.length) {
            return;
        }

        const searchValue = ($('#products-search').val() || '').toString().trim();
        $clearBtn.toggleClass('hidden', searchValue.length === 0);
    };

    // Wait for jQuery before initializing event listeners
    (function() {
        function initMenuScripts() {
            if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                setTimeout(initMenuScripts, 100);
                return;
            }

            // Search functionality
            let searchTimeout;
            window.updateSearchClearButtonVisibility();
            $('#products-search').on('input', function() {
                window.updateSearchClearButtonVisibility();
                clearTimeout(searchTimeout);
                const search = $(this).val();
                searchTimeout = setTimeout(function() {
                    if (typeof window.filterMenuItems === 'function') {
                        window.filterMenuItems();
                    }
                }, 500);
            });

            // If there are filters, load menu items via AJAX
            if (window.menuFilters.menuId || window.menuFilters.categoryId || window.menuFilters.search) {
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    if (typeof window.loadMenuItems === 'function') {
                        window.loadMenuItems();
                    }
                    if (typeof window.updateCategoryCounts === 'function') {
                        window.updateCategoryCounts();
                    }
                }, 100);
            } else {
                // Update category counts on initial load
                if (typeof window.updateCategoryCounts === 'function') {
                    setTimeout(function() {
                        window.updateCategoryCounts();
                    }, 200);
                }
            }
        }

        // Start initialization when jQuery is ready
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            $(document).ready(function() {
                initMenuScripts();
            });
        } else {
            // Wait for jQuery to load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    initMenuScripts();
                });
            } else {
                initMenuScripts();
            }
        }
    })();

    window.clearSearch = function() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        $('#products-search').val('');
        window.menuFilters.search = '';
        window.updateSearchClearButtonVisibility();
        window.loadMenuItems();
    };

    // Keep existing filters initialized from URL/query if present.
    window.menuFilters = window.menuFilters || {
        menuId: null,
        categoryId: null,
        search: '',
        limit: {{ $menuItemsLoaded }}
    };

    window.filterByMenu = function(menuId) {
        // Ensure menuFilters is initialized
        if (!window.menuFilters) {
            window.menuFilters = {
                menuId: null,
                categoryId: null,
                search: '',
                limit: {{ $menuItemsLoaded }}
            };
        }

        window.menuFilters.menuId = menuId || null;
        window.menuFilters.categoryId = null; // Reset category when menu changes

        // Wait for jQuery if not loaded, then call loadMenuItems and updateCategories
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            const checkJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
                    window.updateMenuSelection(menuId);
                    if (typeof window.loadMenuItems === 'function') {
                        window.loadMenuItems();
                    }
                    if (typeof window.updateCategoryCounts === 'function') {
                        window.updateCategoryCounts();
                    }
                }
            }, 100);
            // Stop checking after 5 seconds
            setTimeout(function() {
                clearInterval(checkJQuery);
            }, 5000);
            return;
        }

        // Update menu selection UI
        window.updateMenuSelection(menuId);

        if (typeof window.loadMenuItems === 'function') {
            window.loadMenuItems();
        }
        if (typeof window.updateCategoryCounts === 'function') {
            window.updateCategoryCounts();
        }
    };

    // Update menu selection UI (dropdown and grid buttons)
    window.updateMenuSelection = function(menuId) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            return;
        }

        // Normalize menuId to string for comparison
        const selectedMenuId = menuId ? String(menuId) : null;

        // Update select dropdown
        const $select = $('#menu-filter');
        if ($select.length) {
            $select.val(selectedMenuId || '');
        }

        // Update grid view buttons
        const $menuButtons = $('button[onclick*="filterByMenu"]');
        $menuButtons.each(function() {
            const $button = $(this);
            const onclickAttr = $button.attr('onclick') || '';

            // Check if this is the "Show All" button (filterByMenu(null))
            if (onclickAttr.includes('filterByMenu(null)')) {
                if (!selectedMenuId) {
                    // Show All is selected - add active classes
                    $button.removeClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                    $button.addClass('border-skin-base bg-skin-base text-white shadow-sm');
                } else {
                    // Show All is not selected - remove active classes
                    $button.removeClass('border-skin-base bg-skin-base text-white shadow-sm');
                    $button.addClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                }
            } else {
                // Extract menu ID from onclick attribute
                const match = onclickAttr.match(/filterByMenu\((\d+)\)/);
                if (match) {
                    const buttonMenuId = String(match[1]);
                    if (selectedMenuId && buttonMenuId === selectedMenuId) {
                        // This menu is selected - add active classes
                        $button.removeClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                        $button.addClass('border-skin-base bg-skin-base text-white shadow-sm');
                    } else {
                        // This menu is not selected - remove active classes
                        $button.removeClass('border-skin-base bg-skin-base text-white shadow-sm');
                        $button.addClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                    }
                }
            }
        });
    };

    // Update category selection UI (dropdown and grid buttons)
    window.updateCategorySelection = function(categoryId) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            return;
        }

        // Normalize categoryId to string for comparison
        const selectedCategoryId = categoryId ? String(categoryId) : null;

        // Update select dropdown
        const $select = $('#category-filter');
        if ($select.length) {
            $select.val(selectedCategoryId || '');
        }

        // Update grid view buttons
        const $categoryButtons = $('button[onclick*="filterByCategory"]');
        $categoryButtons.each(function() {
            const $button = $(this);
            const onclickAttr = $button.attr('onclick') || '';

            // Check if this is the "Show All" button (filterByCategory(null))
            if (onclickAttr.includes('filterByCategory(null)')) {
                if (!selectedCategoryId) {
                    // Show All is selected - add active classes
                    $button.removeClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                    $button.addClass('border-skin-base bg-skin-base text-white shadow-sm');
                } else {
                    // Show All is not selected - remove active classes
                    $button.removeClass('border-skin-base bg-skin-base text-white shadow-sm');
                    $button.addClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                }
            } else {
                // Extract category ID from onclick attribute
                const match = onclickAttr.match(/filterByCategory\((\d+)\)/);
                if (match) {
                    const buttonCategoryId = String(match[1]);
                    if (selectedCategoryId && buttonCategoryId === selectedCategoryId) {
                        // This category is selected - add active classes
                        $button.removeClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                        $button.addClass('border-skin-base bg-skin-base text-white shadow-sm');
                    } else {
                        // This category is not selected - remove active classes
                        $button.removeClass('border-skin-base bg-skin-base text-white shadow-sm');
                        $button.addClass('border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base');
                    }
                }
            }
        });
    };

    window.filterByCategory = function(categoryId) {
        // Ensure menuFilters is initialized
        if (!window.menuFilters) {
            window.menuFilters = {
                menuId: null,
                categoryId: null,
                search: '',
                limit: {{ $menuItemsLoaded }}
            };
        }

        window.menuFilters.categoryId = categoryId || null;

        // Wait for jQuery if not loaded, then call loadMenuItems
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            const checkJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
                    window.updateCategorySelection(categoryId);
                    if (typeof window.loadMenuItems === 'function') {
                        window.loadMenuItems();
                    }
                }
            }, 100);
            // Stop checking after 5 seconds
            setTimeout(function() {
                clearInterval(checkJQuery);
            }, 5000);
            return;
        }

        // Update category selection UI
        window.updateCategorySelection(categoryId);

        if (typeof window.loadMenuItems === 'function') {
            window.loadMenuItems();
        }
    };

    window.filterMenuItems = function() {
        // Ensure menuFilters is initialized
        if (!window.menuFilters) {
            window.menuFilters = {
                menuId: null,
                categoryId: null,
                search: '',
                limit: {{ $menuItemsLoaded }}
            };
        }

        // Wait for jQuery if not loaded
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            const checkJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
                    const search = $('#products-search').val() || '';
                    window.menuFilters.search = search;
                    if (typeof window.loadMenuItems === 'function') {
                        window.loadMenuItems();
                    }
                    if (typeof window.updateCategoryCounts === 'function') {
                        window.updateCategoryCounts();
                    }
                }
            }, 100);
            // Stop checking after 5 seconds
            setTimeout(function() {
                clearInterval(checkJQuery);
            }, 5000);
            return;
        }

        const search = $('#products-search').val() || '';
        window.menuFilters.search = search;
        if (typeof window.loadMenuItems === 'function') {
            window.loadMenuItems();
        }
        if (typeof window.updateCategoryCounts === 'function') {
            window.updateCategoryCounts();
        }
    };

    // Load menu items via AJAX
    window.loadMenuItems = function() {
        // Ensure menuFilters is initialized
        if (!window.menuFilters) {
            window.menuFilters = {
                menuId: null,
                categoryId: null,
                search: '',
                limit: {{ $menuItemsLoaded }}
            };
        }

        // Wait for jQuery if not loaded
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            const checkJQuery = setInterval(function() {
                if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
                    window.loadMenuItems();
                }
            }, 100);
            // Stop checking after 5 seconds
            setTimeout(function() {
                clearInterval(checkJQuery);
            }, 5000);
            return;
        }

        // Show loading indicator
        const $menuContainer = $('ul.grid');
        if ($menuContainer.length) {
            $menuContainer.html('<li class="col-span-full text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white"></div></li>');
        }

        // Get current order type from state
        const orderTypeId = window.posState ? window.posState.orderTypeId : null;
        const deliveryAppId = window.posState ? window.posState.selectedDeliveryApp : null;

        $.easyAjax({
            url: "{{ route('ajax.pos.items') }}",
            type: "GET",
            data: {
                menu_id: window.menuFilters.menuId,
                category_id: window.menuFilters.categoryId,
                search: window.menuFilters.search,
                limit: window.menuFilters.limit,
                order_type_id: orderTypeId,
                delivery_app_id: deliveryAppId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.items) {
                    if (typeof window.renderMenuItems === 'function') {
                        window.renderMenuItems(response.items, response.total_count, response.loaded_count);
                    }
                } else {
                    console.error('Failed to load menu items');
                    if ($menuContainer.length) {
                        $menuContainer.html('<li class="col-span-full text-center py-8 text-red-500">Error loading menu items</li>');
                    }
                }
            },
            error: function(xhr) {
                console.error('Error loading menu items:', xhr);
                if ($menuContainer.length) {
                    $menuContainer.html('<li class="col-span-full text-center py-8 text-red-500">Error loading menu items</li>');
                }
            }
        });
    };

    // Render menu items in the grid
    window.renderMenuItems = function(items, totalCount, loadedCount) {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        const $menuContainer = $('ul.grid');
        if (!$menuContainer.length) {
            console.error('Menu container not found');
            return;
        }

        if (!items || items.length === 0) {
            $menuContainer.html(`
                <li class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                    <div>No items found</div>
                </li>
            `);
            return;
        }

        const hideImage = {{ $restaurant->hide_menu_item_image_on_pos ? 'true' : 'false' }};
        const currencyId = {{ $restaurant->currency_id ?? 'null' }};
        const orderLimitReached = false; // You can add this check if needed

        let html = '';
        items.forEach(function(item) {
            const itemPrice = parseFloat(item.price) || 0;
            const variationsCount = item.variations_count || 0;
            const modifiersCount = item.modifier_groups_count || 0;
            const itemImage = item.item_photo_url || '';
            const itemName = item.item_name || 'Item';
            const itemType = item.type || 'veg';
            const isInStock = item.in_stock !== false;
            const itemTaxes = Array.isArray(item.taxes)
                ? item.taxes.map(function(tax) {
                    return {
                        id: parseInt(tax.id) || null,
                        tax_name: tax.tax_name || '',
                        tax_percent: parseFloat(tax.tax_percent || 0)
                    };
                })
                : [];
            if (!window.menuItemTaxesIndex) {
                window.menuItemTaxesIndex = {};
            }
            window.menuItemTaxesIndex[item.id] = itemTaxes;
            const itemTaxesJson = JSON.stringify(itemTaxes).replace(/"/g, '&quot;');

            // Format price using the same format as Blade
            let formattedPrice = '';
            if (typeof window.formatCurrency === 'function') {
                formattedPrice = window.formatCurrency(itemPrice);
            } else {
                // Fallback formatting
                formattedPrice = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                }).format(itemPrice);
            }

            const labelClasses = [
                "block lg:w-32 w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 relative outline-none",
                (!orderLimitReached && isInStock) ? "cursor-pointer hover:shadow-md dark:hover:bg-gray-700/30 active:scale-95 focus-visible:scale-95 bg-white dark:bg-gray-900" : "cursor-not-allowed opacity-60",
                !isInStock ? "bg-gray-100 dark:bg-gray-800" : "",
                orderLimitReached ? "bg-gray-200 dark:bg-gray-800" : ""
            ].filter(c => c).join(' ');

            html += `
                <li class="group relative flex items-center justify-center">
                    <input type="checkbox" id="item-${item.id}" value="${item.id}"
                        onclick="addCartItemClientSide(${item.id})"
                        data-item-id="${item.id}"
                        data-item-name="${itemName}"
                        data-item-price="${itemPrice}"
                        data-item-image="${itemImage}"
                        data-item-taxes="${itemTaxesJson}"
                        data-variations-count="${variationsCount}"
                        data-modifiers-count="${modifiersCount}"
                        ${orderLimitReached ? 'disabled' : ''}
                        class="hidden peer">
                    <label for="item-${item.id}" class="${labelClasses}" tabindex="${orderLimitReached ? '-1' : '0'}">
                        ${!hideImage && itemImage ? `
                        <div class="relative aspect-square hidden md:block">
                            <img class="w-full lg:w-32 lg:h-32 object-cover select-none rounded-t-lg"
                                src="${itemImage}"
                                alt="${itemName}" />
                            <span class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm">
                                <img src="{{ asset('img/${itemType}.svg') }}"
                                    class="h-4 w-4" title="${itemType}"
                                    alt="" />
                            </span>
                        </div>
                        ` : ''}
                        <div class="p-2">
                            <h5 class="text-xs font-medium text-gray-900 dark:text-white min-h-[1.5rem]">
                                ${itemName}
                            </h5>
                            ${orderLimitReached ? '<div class="text-red-500 text-xs">Order limit reached</div>' : !isInStock ? '<div class="text-red-500">Out of stock</div>' : `
                            <div class="mt-1 flex items-center justify-between gap-2">
                                ${variationsCount == 0 ? `
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    ${formattedPrice}
                                </span>
                                ` : `
                                <span class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                    Show Variations
                                </span>
                                `}
                            </div>
                            `}
                        </div>
                    </label>
                </li>
            `;
        });

        $menuContainer.html(html);

        // Update loaded count if needed
        if (loadedCount < totalCount) {
            // Show load more button or handle infinite scroll
        }
    };

    // Client-side addCartItem (no server request)
    window.addCartItem = function(menuItemId) {
        if (typeof window.addCartItemClientSide === 'function') {
            window.addCartItemClientSide(menuItemId);
        }
    };

    window.loadMoreMenuItems = function() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        // Show loading indicator
        $('#menu-loading-indicator').removeClass('hidden');

        // Increase limit and load more items
        window.menuFilters.limit = (window.menuFilters.limit || {{ $menuItemsLoaded }}) + {{ $menuItemsLoaded }};

        // Get current order type from state
        const orderTypeId = window.posState ? window.posState.orderTypeId : null;
        const deliveryAppId = window.posState ? window.posState.selectedDeliveryApp : null;

        $.easyAjax({
            url: "{{ route('ajax.pos.items') }}",
            type: "GET",
            data: {
                menu_id: window.menuFilters.menuId,
                category_id: window.menuFilters.categoryId,
                search: window.menuFilters.search,
                limit: window.menuFilters.limit,
                order_type_id: orderTypeId,
                delivery_app_id: deliveryAppId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success && response.items) {
                    window.renderMenuItems(response.items, response.total_count, response.loaded_count);
                }
                $('#menu-loading-indicator').addClass('hidden');
            },
            error: function() {
                $('#menu-loading-indicator').addClass('hidden');
            }
        });
    };

    // Update category counts based on current filters
    window.updateCategoryCounts = function() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        // Ensure menuFilters is initialized
        if (!window.menuFilters) {
            window.menuFilters = {
                menuId: null,
                categoryId: null,
                search: '',
                limit: {{ $menuItemsLoaded }}
            };
        }

        $.easyAjax({
            url: "{{ route('ajax.pos.categories') }}",
            type: "GET",
            data: {
                menu_id: window.menuFilters.menuId,
                search: window.menuFilters.search,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response && Array.isArray(response)) {
                    // Update select dropdown
                    const $select = $('#category-filter');
                    if ($select.length) {
                        const currentValue = $select.val();
                        let html = '<option value="">{{ __('app.filterByCategory') }}</option>';
                        response.forEach(function(category) {
                            const selected = currentValue == category.id ? 'selected' : '';
                            html += `<option value="${category.id}" ${selected}>${category.category_name} (${category.count})</option>`;
                        });
                        $select.html(html);
                    }

                    // Update grid view buttons - find the category grid container
                    // The grid is inside a template, so we need to find it by looking for buttons with filterByCategory
                    const $firstCategoryButton = $('button[onclick*="filterByCategory"]').first();
                    let $categoryGrid = null;
                    if ($firstCategoryButton.length) {
                        $categoryGrid = $firstCategoryButton.closest('.grid');
                    }

                    if ($categoryGrid && $categoryGrid.length) {
                        let html = '';

                        // Add "All Categories" button
                        const allSelected = !window.menuFilters.categoryId;
                        html += `
                            <button type="button" onclick="filterByCategory(null)"
                                class="w-full px-3 py-3 text-xs rounded-lg border transition text-left ${allSelected ? 'border-skin-base bg-skin-base text-white shadow-sm' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base'}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium">Show All</span>
                                </div>
                            </button>
                        `;

                        // Add category buttons
                        response.forEach(function(category) {
                            const isSelected = window.menuFilters.categoryId == category.id;
                            html += `
                                <button type="button" onclick="filterByCategory(${category.id})"
                                    class="w-full px-3 py-3 text-xs rounded-lg border transition text-left ${isSelected ? 'border-skin-base bg-skin-base text-white shadow-sm' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:border-skin-base hover:text-skin-base'}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-medium">${category.category_name}</span>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-300">(${category.count})</span>
                                    </div>
                                </button>
                            `;
                        });

                        $categoryGrid.html(html);
                    }
                }
            },
            error: function(xhr) {
                console.error('Error loading category counts:', xhr);
            }
        });
    };

    // Reset menu filters
    window.resetMenuFilters = function() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            console.error('jQuery is not loaded');
            return;
        }

        // Reset filter state
        window.menuFilters = {
            menuId: null,
            categoryId: null,
            search: '',
            limit: {{ $menuItemsLoaded }}
        };

        // Clear form inputs
        $('#products-search').val('');
        $('#menu-filter').val('');
        $('#category-filter').val('');

        // Update menu selection UI
        if (typeof window.updateMenuSelection === 'function') {
            window.updateMenuSelection(null);
        }

        // Reload menu items and update category counts
        window.loadMenuItems();
        window.updateCategoryCounts();
    };
</script>
