<div>
    <div
    class="items-center justify-between p-4 bg-white border border-gray-200 rounded-lg shadow-sm sm:flex dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <div class="w-full">
            <h3 class="text-sm font-medium text-gray-700 mb-3 dark:text-gray-400">@lang('modules.dashboard.topDish') (@lang('app.today'))
            </h3>
            <ul class="divide-y divide-gray-50 dark:divide-gray-700">
                @forelse ($menuItems as $item)
                    <li class="py-1 sm:py-2">
                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                            <div class="flex-1 min-w-0">
                                <div class="w-full max-w-sm space-y-2">
                                    <div >
                                        <div class="flex items-center gap-1.5">  
                                            
                                            <span class="text-[11px] text-gray-300 w-4">{{ $loop->index+1 }}</span>
                                            
                                            <img class="rounded-md object-cover h-8 w-8" src="{{ $item->item_photo_url }}" alt="{{ $item->item_name }}" />

                                            <div>
                                                <h5 class="text-sm font-medium tracking-tight text-gray-900 dark:text-white">{{ $item->item_name }}</h5>
                                                <span class="text-gray-600 dark:text-white text-xs">{{ $item->orders->sum('quantity') }} @lang('modules.order.qty')</span>                                            
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[13px] font-medium text-gray-800 dark:text-white">
                                {{ currency_format($item->orders->sum('amount'), restaurant()->currency_id) }}
                            </div>
                        </div>
                    </li>
                @empty
                <li class="py-2">
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                            @lang('messages.noPaymentFound')
                        </p>
                    </div>
                    </div>
                </li>
                @endforelse
                

            </ul>
        
        </div>
    </div>
</div>
