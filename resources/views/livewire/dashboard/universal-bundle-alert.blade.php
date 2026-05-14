<div>
@if($showAlert)
<div x-data="{ showModal: false }">
    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg shadow-sm mb-6">
        <div class="p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Universal Bundle Available</h3>
                            <span class="bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-xs font-medium px-2 py-0.5 rounded">Best Value</span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                            Get all current and future modules with a single purchase.
                            <a href="{{ $universalBundleLink }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Learn more →</a>
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $universalBundleLink }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                                View Bundle <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            <button type="button" @click="showModal = true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-900/70 rounded transition-colors">
                                See What's Included ({{ count($modules) }})
                            </button>
                        </div>
                    </div>
                </div>
                <button wire:click="dismissAlert" type="button" class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors group relative" title="Close alert permanently – it will not show in future">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span class="absolute right-full mr-2 top-1/2 -translate-y-1/2 z-[100] px-2 py-1.5 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded shadow-lg whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">Close permanently – will not show in future</span>
                </button>
            </div>
        </div>
    </div>

    @if(count($modules) > 0)
    <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden" @click.stop>
                <div class="p-4">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Universal Bundle - Included Modules</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Get access to all these modules plus future releases.</p>
                        </div>
                        <button @click="showModal = false" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto pr-1 -mx-1">
                        <div class="space-y-2">
                            @foreach($modules as $item)
                            <div class="flex items-center gap-3 p-2 rounded border border-gray-200 dark:border-gray-700 @if(stripos($item['product_name'], 'universal') !== false) ring-1 ring-blue-500 @endif">
                                <a href="{{ $item['product_link'] ?? '#' }}" target="_blank" class="flex-shrink-0">
                                    <img src="{{ $item['product_thumbnail'] ?? '' }}" class="w-12 h-12 object-cover rounded border border-gray-200 dark:border-gray-700" alt="{{ $item['product_name'] ?? 'Module' }}">
                                </a>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $item['product_link'] ?? '#' }}" target="_blank" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block truncate">{{ $item['product_name'] ?? 'Unknown Module' }}</a>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $item['summary'] ?? '' }}</p>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs">
                                        @if(isset($item['rating']))<span class="text-gray-600 dark:text-gray-300">{{ $item['rating'] ? number_format($item['rating'], 1) : '-' }} ★</span>@endif
                                        @if(isset($item['number_of_sales']))<span class="text-gray-500 dark:text-gray-400">{{ $item['number_of_sales'] }} sales</span>@endif
                                        @if(isset($item['price']))<span class="font-semibold text-emerald-600 dark:text-emerald-400">${{ number_format($item['price'], 2) }}</span>@endif
                                    </div>
                                </div>
                                <a href="{{ $item['product_link'] ?? '#' }}" target="_blank" class="flex-shrink-0 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View →</a>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400"><strong>{{ count($modules) }}</strong> modules</span>
                        <a href="{{ $universalBundleLink }}" target="_blank" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                            Buy Universal Bundle <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
</div>
