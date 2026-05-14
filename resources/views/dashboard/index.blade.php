@extends('layouts.app')

@section('content')

<div class="p-4 block  dark:bg-gray-800 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
        <h1 class="text-base font-semibold text-gray-900 dark:text-white leading-tight">@lang('menu.dashboard')</h1>

        <div class="text-xs text-gray-400 mt-0.5 inline-flex items-center gap-1 font-medium dark:text-white flex-wrap sm:flex-nowrap">
            @php
                $currentDateTime = now()->timezone(timezone());
            @endphp
            {{ $currentDateTime->translatedFormat('l') }}, {{ $currentDateTime->format(dateFormat() . ', ' . timeFormat()) }}
        </div>
    </div>

</div>

@if (!empty(global_setting()->vapid_public_key) && !empty(global_setting()->vapid_private_key))
    <div id="notification-alert" class="px-4" x-cloak>
        <x-alert type="warning" icon="info-circle">
            <div class="flex items-center gap-2">
                @lang('messages.pushNotificationMessage')
                <x-button id="subscribe-button">@lang('messages.enableNotifications')</x-button>
            </div>
        </x-alert>
    </div>
@endif

<x-banner />
@php
    $restaurantModules = restaurant_modules();
    $restaurantId = user()->restaurant_id;

    // Get stats for all three modules
    $orderStats = branch() ? getRestaurantOrderStats(branch()->id) : null;
    $staffStats = getRestaurantStaffStats($restaurantId);
    $menuItemStats = getRestaurantMenuItemStats($restaurantId);

    // Check which limits are exceeded
    $orderLimitExceeded = in_array('Order', $restaurantModules) && $orderStats && !$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'];
    $staffLimitExceeded = in_array('Staff', $restaurantModules) && $staffStats && !$staffStats['unlimited'] && $staffStats['current_count'] >= $staffStats['staff_limit'];
    $menuItemLimitExceeded = in_array('Menu Item', $restaurantModules) && $menuItemStats && !$menuItemStats['unlimited'] && $menuItemStats['current_count'] >= $menuItemStats['menu_items_limit'];

    $anyLimitExceeded = $orderLimitExceeded || $staffLimitExceeded || $menuItemLimitExceeded;
@endphp

@if($anyLimitExceeded)
<div class="px-4 py-4 space-y-3">
    @if($orderLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.order.orderLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.order.orderLimitExceededMessage', [
                        'current' => number_format($orderStats['current_count']),
                        'limit' => number_format($orderStats['order_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($staffLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.staff.staffLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.staff.staffLimitExceededMessage', [
                        'current' => number_format($staffStats['current_count']),
                        'limit' => number_format($staffStats['staff_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($menuItemLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.menu.menuItemLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.menu.menuItemLimitExceededMessage', [
                        'current' => number_format($menuItemStats['current_count']),
                        'limit' => number_format($menuItemStats['menu_items_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<div class="grid">

    <div class="p-4">

        <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            @if (user_can('Show Order'))
            @livewire('dashboard.todayOrderCount')
            @endif

            @if (user_can('Show Reports'))
            @livewire('dashboard.todayEarnings')
            @endif

            @if (user_can('Show Customer'))
            @livewire('dashboard.todayCustomerCount')
            @endif

            @if (user_can('Show Reports'))
            @livewire('dashboard.averageDailyEarning')
            @endif

        </div>
    </div>

    <div class="grid w-full grid-cols-1 lg:grid-cols-[1fr_300px] gap-4 p-4">
        @if (user_can('Show Reports'))
        <div class="grid w-full col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 p-5 dark:border-gray-700">
            @livewire('dashboard.weeklySalesChart')
        </div>
        @endif

        @if (user_can('Show Order'))
        @livewire('dashboard.todayOrderList')
        @endif

    </div>

    @if (user_can('Show Reports'))
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4">
        @livewire('dashboard.todayPaymentMethodEarnings')

        @livewire('dashboard.today-menu-item-earnings')

        @livewire('dashboard.today-table-earnings')
    </div>
    @endif

</div>

@endsection
