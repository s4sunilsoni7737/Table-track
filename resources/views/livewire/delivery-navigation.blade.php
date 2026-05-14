<header class="lg:hidden">
    <nav class="bg-white border-gray-200 px-4 py-2.5 dark:bg-gray-800">
        <div class="flex flex-wrap justify-between items-center mx-auto">
            <a href="{{ route('delivery.assigned-orders') }}" class="flex items-center app-logo">
                <img src="{{ $restaurant->logoUrl }}" class="ltr:mr-3 rtl:ml-3 h-6 sm:h-9" alt="App Logo" />
                @if ($restaurant->show_logo_text)
                    <span class="self-center text-xl font-semibold whitespace-nowrap dark:text-white">{{ $restaurant->name }}</span>
                @endif
            </a>

            <div class="flex items-center gap-2">
                <button id="theme-toggle-mobile" data-tooltip-target="tooltip-toggle-mobile" type="button"
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                    <svg id="theme-toggle-dark-icon-mobile" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon-mobile" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0M17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1M5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414M4 11a1 1 0 100-2H3a1 1 0 000 2h1"
                            fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <button data-collapse-toggle="mobile-menu-2" type="button" class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="mobile-menu-2" aria-expanded="false">
                    <span class="sr-only">@lang('menu.openMainMenu')</span>
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                </button>
            </div>

            <div class="hidden justify-between items-center w-full bg-gray-50 mt-4 rounded-md dark:bg-gray-800" id="mobile-menu-2">
                <ul class="flex flex-col font-medium w-full">
                    <li><a href="{{ route('delivery.dashboard') }}" class="block py-2 px-3 {{ $activeTab === 'dashboard' ? 'text-skin-base dark:text-skin-base' : 'text-gray-700 dark:text-gray-300' }}">@lang('menu.deliveryDashboard')</a></li>
                    <li><a href="{{ route('delivery.assigned-orders') }}" class="block py-2 px-3 {{ $activeTab === 'assigned' ? 'text-skin-base dark:text-skin-base' : 'text-gray-700 dark:text-gray-300' }}">@lang('menu.assignedOrders')</a></li>
                    <li><a href="{{ route('delivery.history') }}" class="block py-2 px-3 {{ $activeTab === 'history' ? 'text-skin-base dark:text-skin-base' : 'text-gray-700 dark:text-gray-300' }}">@lang('menu.deliveryHistory')</a></li>
                    <li><a href="{{ route('delivery.cod-settlement') }}" class="block py-2 px-3 {{ $activeTab === 'cod-settlement' ? 'text-skin-base dark:text-skin-base' : 'text-gray-700 dark:text-gray-300' }}">@lang('menu.deliveryCodSettlement')</a></li>
                    <li><a href="{{ route('delivery.profile') }}" class="block py-2 px-3 text-gray-700 dark:text-gray-300">@lang('menu.profile')</a></li>
                    <li><a href="{{ route('delivery.logout') }}" class="block py-2 px-3 text-gray-700 dark:text-gray-300">@lang('app.logout')</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
