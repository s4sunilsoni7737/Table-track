<div
    class="flex h-dvh w-full flex-col overflow-hidden md:grid md:h-screen md:w-screen md:grid-cols-2"
    @if(!pusherSettings()->is_enabled_pusher_broadcast) wire:poll.2s @endif
>
    <!-- Preparing (top on mobile, left on md+) -->
    <div class="flex min-h-0 flex-1 flex-col bg-gray-700 text-white md:h-full md:flex-none">
        <div class="shrink-0 px-4 pb-3 pt-5 sm:px-6 md:px-8 md:pb-4 md:pt-8">
            <h3 class="text-2xl font-semibold tracking-wide sm:text-3xl">
                @lang('modules.order.preparing')
            </h3>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-6 sm:px-6 md:px-8 md:pb-10">
            <div class="grid grid-cols-1 gap-3 place-content-start sm:grid-cols-2 sm:gap-4 md:grid-cols-3 md:gap-5">
                @forelse($preparingOrders as $o)
                    @php($num = $o['token'] ?? $o['display_number'])
                    <div class="rounded-md bg-gray-800 shadow-md">
                        <div class="px-4 py-4 text-center sm:px-5 sm:py-5 md:px-6 md:py-5">
                            <div class="text-3xl font-extrabold tracking-wide sm:text-4xl">{{ $num }}</div>
                            @if(isset($o['show_order_number']) && $o['show_order_number'] && isset($o['display_number']) && $o['display_number'])
                                <div class="mt-1 text-sm font-semibold tracking-wide text-gray-300 sm:text-base">{{ $o['display_number'] }}</div>
                            @endif
                            @if(isset($o['order_type']))
                                <div class="mt-2 text-xs text-gray-300 sm:text-sm">{{ $o['order_type'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full mt-8 text-center text-lg text-gray-300 sm:text-xl">@lang('modules.order.noOrders')</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Ready (bottom on mobile, right on md+) -->
    <div class="flex min-h-0 flex-1 flex-col bg-black text-white md:h-full md:flex-none">
        <div class="shrink-0 px-4 pb-3 pt-4 sm:px-6 md:px-8 md:pb-4 md:pt-8">
            <h3 class="text-2xl font-semibold tracking-wide sm:text-3xl">
                @lang('modules.order.readyForPickup')
            </h3>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-6 sm:px-6 md:px-8 md:pb-10">
            <div class="grid grid-cols-1 gap-4 place-content-start sm:grid-cols-2 md:gap-6">
                @forelse($readyOrders as $o)
                    @php($num = $o['token'] ?? $o['display_number'])
                    <div class="rounded-md bg-green-600 shadow-md">
                        <div class="px-5 py-5 text-center sm:px-6 sm:py-6 md:px-8 md:py-6">
                            <div class="text-3xl font-extrabold tracking-wide sm:text-4xl">{{ $num }}</div>
                            @if(isset($o['show_order_number']) && $o['show_order_number'] && isset($o['display_number']) && $o['display_number'])
                                <div class="mt-1 text-sm font-semibold tracking-wide text-green-100 sm:text-base">{{ $o['display_number'] }}</div>
                            @endif
                            @if(isset($o['order_type']))
                                <div class="mt-2 text-xs text-green-100 sm:text-sm">{{ $o['order_type'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full mt-8 text-center text-lg text-gray-400 sm:text-xl">@lang('modules.order.noOrders')</div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
        @if(pusherSettings()->is_enabled_pusher_broadcast)
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const channel = PUSHER.subscribe('orders');
                    channel.bind('order.updated', function() {
                        @this.call('refreshBoard');
                    });
                });
            </script>
        @endif
    @endpush
</div>

