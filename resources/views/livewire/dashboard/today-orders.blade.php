<div class="relative">
<a href="{{ route('orders.index') }}" wire:navigate wire:key="today-orders-link"
    class="{{ request()->routeIs('pos.*') ? 'inline-flex' : 'hidden lg:inline-flex' }} items-center px-2 py-1 group text-sm font-medium text-center text-gray-600 bg-white border-skin-base border rounded-md focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-gray-800 dark:text-gray-300"
    data-tooltip-target="today-orders-tooltip-toggle"
    >
    <svg class="w-5 h-5 fill-gray-600 group-hover:fill-gray-800 dark:fill-gray-400 dark:group-hover:fill-gray-100" height="24" width="24" stroke-width="2.5" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="stroke-width: 1px;"><path d="M30 31.75H14c-.965 0-1.75-.785-1.75-1.75v-2c0-.965.785-1.75 1.75-1.75h16c.965 0 1.75.785 1.75 1.75v2c0 .965-.785 1.75-1.75 1.75m-16-4a.25.25 0 0 0-.25.25v2c0 .138.112.25.25.25h16a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25z" stroke="currentColor" stroke-width="1"/><path d="M29 27.75H15a.75.75 0 0 1-.75-.75v-1c0-4.273 3.477-7.75 7.75-7.75s7.75 3.477 7.75 7.75v1a.75.75 0 0 1-.75.75m-13.25-1.5h12.5V26c0-3.446-2.804-6.25-6.25-6.25s-6.25 2.804-6.25 6.25z"/><path d="M22 19.75a.75.75 0 0 1-.75-.75v-1.617a.75.75 0 0 1 1.5 0V19a.75.75 0 0 1-.75.75m-12 10H2c-.965 0-1.75-.785-1.75-1.75V7c0-.965.785-1.75 1.75-1.75h15c.965 0 1.75.785 1.75 1.75v9.84a.75.75 0 0 1-1.5 0V7a.253.253 0 0 0-.25-.25H2a.253.253 0 0 0-.25.25v21c0 .136.114.25.25.25h8a.75.75 0 0 1 0 1.5" stroke="currentColor" stroke-width="1"/><path d="M1 23.75A.75.75 0 0 1 .25 23V6a.75.75 0 0 1 .513-.711l13.68-4.56A1.75 1.75 0 0 1 16.75 2.39V6a.75.75 0 0 1-1.5 0V2.39a.25.25 0 0 0-.33-.24L1.75 6.54V23a.75.75 0 0 1-.75.75"/><path d="M14 17.75H5a.75.75 0 0 1-.75-.75v-7A.75.75 0 0 1 5 9.25h9a.75.75 0 0 1 .75.75v7a.75.75 0 0 1-.75.75m-8.25-1.5h7.5v-5.5h-7.5zm6.25 5.5H5a.75.75 0 0 1 0-1.5h7a.75.75 0 0 1 0 1.5m-1 4H5a.75.75 0 0 1 0-1.5h6a.75.75 0 0 1 0 1.5" stroke="currentColor" stroke-width="0.5"/></svg>

    <span @if(!pusherSettings()->is_enabled_pusher_broadcast) wire:poll.15s.keep-alive="refreshOrders" wire:key="today-orders-count" @endif
        class="inline-flex items-center justify-center px-2 py-0.5 ms-2 text-xs font-semibold text-white bg-skin-base rounded-md">
        {{ $count }}
    </span>

</a>
<div id="today-orders-tooltip-toggle" role="tooltip"
    class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
    @lang('modules.order.todayOrder')
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
</div>
@push('scripts')

    @if(pusherSettings()->is_enabled_pusher_broadcast)
        @script
            <script>
                document.addEventListener('livewire:initialized', function () {
                    const channel = PUSHER.subscribe('today-orders');
                    channel.bind('today-orders.updated', function(data) {
                        @this.call('refreshOrders');
                        new Audio("{{ asset('sound/new_order.wav')}}").play();
                        console.log('✅ Pusher received data for today orders!. Refreshing...');
                    });
                    PUSHER.connection.bind('connected', () => {
                        console.log('✅ Pusher connected for Today Orders!');
                    });
                    channel.bind('pusher:subscription_succeeded', () => {
                        console.log('✅ Subscribed to today-orders channel!');
                    });
                });
            </script>
        @endscript
    @elseif($playSound)
        @script
            <script>
                console.log('✅ Playing sound for today orders!', "{{ asset('sound/new_order.wav')}}");
                new Audio("{{ asset('sound/new_order.wav')}}").play();
            </script>
        @endscript
    @endif
@endpush
