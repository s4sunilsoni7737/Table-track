<div class="relative">
    <a @if(pusherSettings()->is_enabled_pusher_broadcast) wire:poll.15s @endif
    href="{{ route('reservations.index') }}" wire:navigate
    class="hidden lg:inline-flex items-center px-2 py-1 group text-sm font-medium text-center text-gray-600 bg-white border-skin-base border rounded-md focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-gray-800 dark:text-gray-300"
    data-tooltip-target="today-reservations-tooltip-toggle"
    >
    <svg height="24px"  viewBox="0 0 512 512" class="w-5 h-5 fill-gray-600 group-hover:fill-gray-800 dark:fill-gray-400 dark:group-hover:fill-gray-100" width="24px" xmlns="http://www.w3.org/2000/svg"><path d="M368 42.668h-26.668V21.332C341.332 9.559 331.797 0 320 0s-21.332 9.559-21.332 21.332v21.336H128V21.332C128 9.559 118.465 0 106.668 0S85.332 9.559 85.332 21.332v21.336H58.668C26.305 42.668 0 68.968 0 101.332v240c0 35.285 28.715 64 64 64h106.668c11.797 0 21.332-9.555 21.332-21.332s-9.535-21.332-21.332-21.332H64c-11.777 0-21.332-9.578-21.332-21.336V170.668H384c0 11.773 9.535 21.332 21.332 21.332s21.336-9.559 21.336-21.332v-69.336c0-32.363-26.305-58.664-58.668-58.664m0 0"/><path d="M373.332 234.668c-76.457 0-138.664 62.207-138.664 138.664 0 76.461 62.207 138.668 138.664 138.668C449.793 512 512 449.793 512 373.332c0-76.457-62.207-138.664-138.668-138.664m64.129 115.305-69.332 80c-3.883 4.48-9.453 7.125-15.36 7.359H352a21.3 21.3 0 0 1-15.082-6.25l-37.332-37.332c-8.344-8.344-8.344-21.824 0-30.164 8.34-8.344 21.82-8.344 30.164 0l21.14 21.14 54.317-62.68c7.723-8.917 21.203-9.831 30.102-2.151 8.894 7.699 9.855 21.183 2.152 30.078m0 0"/></svg>
    <span
        class="inline-flex items-center justify-center px-2 py-0.5 ms-2 text-xs font-semibold text-white bg-skin-base rounded-md">
        {{ $count }}
    </span>
</a>
<div id="today-reservations-tooltip-toggle" role="tooltip"
    class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
    @lang('modules.reservation.newReservations')
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
</div>

@push('scripts')

    @if(pusherSettings()->is_enabled_pusher_broadcast)
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const channel = PUSHER.subscribe('today-reservations');
                channel.bind('today-reservations.created', function(data) {
                    @this.call('refreshReservations');
                    console.log('✅ Pusher received data for today reservations!. Refreshing...');
                });
                PUSHER.connection.bind('connected', () => {
                    console.log('✅ Pusher connected for Today Reservations!');
                });
                channel.bind('pusher:subscription_succeeded', () => {
                    console.log('✅ Subscribed to today-reservations channel!');
                });
            });
        </script>
    @endif
@endpush
