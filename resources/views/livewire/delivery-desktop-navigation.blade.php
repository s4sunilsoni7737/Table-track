<header class="hidden lg:block z-50 relative">
    <nav class="bg-white border-gray-200 px-4 py-2.5 dark:bg-gray-800 sticky top-4 rounded-md mt-2">
        <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
            <div class="flex gap-8 items-center">
                <a href="{{ route('delivery.assigned-orders') }}" class="inline-flex items-center app-logo">
                    <img src="{{ $restaurant->logoUrl }}" class="ltr:mr-3 rtl:ml-3 h-6 sm:h-9" alt="App Logo" />
                    @if ($restaurant->show_logo_text)
                        <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">{{ $restaurant->name }}</span>
                    @endif
                </a>

                <ul class="flex flex-col mt-4 font-medium lg:flex-row lg:space-x-8 lg:mt-0 rtl:space-x-reverse">
                    <li>
                        <a href="{{ route('delivery.dashboard') }}" @class([
                            'block py-2 pr-4 pl-3 rounded lg:bg-transparent lg:p-0',
                            'text-gray-700 dark:text-white' => $activeTab !== 'dashboard',
                            'dark:text-skin-base text-skin-base' => $activeTab === 'dashboard',
                        ])>@lang('menu.deliveryDashboard')</a>
                    </li>
                    <li>
                        <a href="{{ route('delivery.assigned-orders') }}" @class([
                            'block py-2 pr-4 pl-3 rounded lg:bg-transparent lg:p-0',
                            'text-gray-700 dark:text-white' => $activeTab !== 'assigned',
                            'dark:text-skin-base text-skin-base' => $activeTab === 'assigned',
                        ])>@lang('menu.assignedOrders')</a>
                    </li>
                    <li>
                        <a href="{{ route('delivery.history') }}" @class([
                            'block py-2 pr-4 pl-3 rounded lg:bg-transparent lg:p-0',
                            'text-gray-700 dark:text-white' => $activeTab !== 'history',
                            'dark:text-skin-base text-skin-base' => $activeTab === 'history',
                        ])>@lang('menu.deliveryHistory')</a>
                    </li>
                    <li>
                        <a href="{{ route('delivery.cod-settlement') }}" @class([
                            'block py-2 pr-4 pl-3 rounded lg:bg-transparent lg:p-0',
                            'text-gray-700 dark:text-white' => $activeTab !== 'cod-settlement',
                            'dark:text-skin-base text-skin-base' => $activeTab === 'cod-settlement',
                        ])>@lang('menu.deliveryCodSettlement')</a>
                    </li>
                </ul>
            </div>

            <div class="flex items-center lg:order-2 gap-3">
                <button id="theme-toggle" data-tooltip-target="tooltip-toggle" type="button"
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm p-2.5">
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0M17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1M5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414M4 11a1 1 0 100-2H3a1 1 0 000 2h1"
                            fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <div class="relative" x-data="{ accountOpen: false }" @click.outside="accountOpen = false">
                    <button type="button" @click="accountOpen = !accountOpen" class="flex items-center py-2 px-3 text-gray-900 dark:text-white">
                        @lang('menu.myAccount')
                        <svg class="w-2.5 h-2.5 ms-2.5" fill="none" viewBox="0 0 10 6"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/></svg>
                    </button>
                    <div x-show="accountOpen" x-cloak x-transition class="absolute right-0 mt-1 z-10 bg-white rounded-lg shadow w-44 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                            <li><a href="{{ route('delivery.profile') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">@lang('menu.profile')</a></li>
                            <li><a href="{{ route('delivery.logout') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">@lang('app.logout')</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
