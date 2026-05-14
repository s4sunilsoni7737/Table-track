<div class="relative">
    <a @if(pusherSettings()->is_enabled_pusher_broadcast) wire:poll.15s @else wire:poll.10s @endif
    href="{{ route('waiter-requests.index') }}" wire:navigate
    class="hidden lg:inline-flex items-center px-2 py-1 text-sm group font-medium text-center text-gray-600 bg-white border-skin-base border rounded-md focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-gray-800 dark:text-gray-300"
    data-tooltip-target="active-waiter-requests-tooltip-toggle"
    >
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-gray-600 group-hover:fill-gray-800 dark:fill-gray-400 dark:group-hover:fill-gray-100" width="128" height="128" viewBox="0 -2.89 122.88 122.88" xml:space="preserve"><path d="M36.82 107.86 35.65 78.4l13.25-.53c5.66.78 11.39 3.61 17.15 6.92l10.29-.41c4.67.1 7.3 4.72 2.89 8-3.5 2.79-8.27 2.83-13.17 2.58-3.37-.03-3.34 4.5.17 4.37 1.22.05 2.54-.29 3.69-.34 6.09-.25 11.06-1.61 13.94-6.55l1.4-3.66 15.01-8.2c7.56-2.83 12.65 4.3 7.23 10.1-10.77 8.51-21.2 16.27-32.62 22.09-8.24 5.47-16.7 5.64-25.34 1.01zm-7.08-44.89h91.9c.68 0 1.24.57 1.24 1.24v5.41c0 .67-.56 1.24-1.24 1.24h-91.9c-.68 0-1.24-.56-1.24-1.24v-5.41c0-.68.56-1.24 1.24-1.24m49.52-51.74c25.16 2.01 46.35 23.16 43.22 48.06H28.91C25.82 34.23 47.09 13.05 72.43 11.2V7.14h-4c-.7 0-1.28-.58-1.28-1.28V1.28c0-.7.57-1.28 1.28-1.28h14.72c.7 0 1.28.58 1.28 1.28v4.58c0 .7-.58 1.28-1.28 1.28h-3.89zM0 77.39l31.55-1.66 1.4 35.25-31.55 1.65z" style="fill-rule:evenodd;clip-rule:evenodd"/></svg>

    <span
        class="inline-flex items-center justify-center px-2 py-0.5 ms-2 text-xs font-semibold text-white bg-skin-base rounded-md">
        {{ $count }}
    </span>
</a>
<div id="active-waiter-requests-tooltip-toggle" role="tooltip"
    class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
    @lang('modules.waiterRequest.newWaiterRequests')
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
</div>

@push('scripts')

    @if(pusherSettings()->is_enabled_pusher_broadcast)
        @script
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                const channel = PUSHER.subscribe('active-waiter-requests');
                channel.bind('active-waiter-requests.created', function(data) {
                    @this.call('refreshActiveWaiterRequests');
                    console.log('✅ Pusher received data for active waiter requests!. Refreshing...');
                    });
                    PUSHER.connection.bind('connected', () => {
                    console.log('✅ Pusher connected for Active Waiter Requests!');
                    });
                    channel.bind('pusher:subscription_succeeded', () => {
                    console.log('✅ Subscribed to active-waiter-requests channel!');
                    });
                });
            </script>
        @endscript
    @endif

    <script>
        // Listen for custom event to play sound - setup immediately
        document.addEventListener('livewire:init', () => {
            console.log('🔧 Setting up waiter request event listeners...');

            // Listen for the play-waiter-sound event
            window.addEventListener('play-waiter-sound', (event) => {
                console.log('🔔 Playing waiter request sound! (window event)', event);
                const audio = new Audio("{{ asset('sound/new_order.wav')}}");
                audio.play().then(() => {
                    console.log('✅ Sound played successfully!');
                }).catch(error => {
                    console.error('❌ Error playing sound:', error);
                });
            });

            // Also listen via Livewire events
            Livewire.on('play-waiter-sound', (event) => {
                console.log('🔔 Playing waiter request sound! (Livewire event)', event);
                const audio = new Audio("{{ asset('sound/new_order.wav')}}");
                audio.play().then(() => {
                    console.log('✅ Sound played successfully!');
                }).catch(error => {
                    console.error('❌ Error playing sound:', error);
                });
            });

            // Listen for waiterRequestCreated event
            Livewire.on('waiterRequestCreated', (data) => {
                console.log('✅ Livewire event received for waiter request!', data);
                // Refresh the component to show new count and popup
                @this.call('refreshActiveWaiterRequests');
            });

            console.log('🔧 Waiter request component event listeners ready!');
        });
    </script>
@endpush
