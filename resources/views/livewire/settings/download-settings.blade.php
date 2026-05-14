<div class="mx-4 p-6 bg-white border border-gray-200 rounded-lg shadow-sm 2xl:col-span-2 dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
            @lang('modules.settings.downloads')
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @lang('modules.settings.downloadsDescription')
        </p>
    </div>

    {{-- Desktop App --}}
    <div class="mb-8">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            @lang('superadmin.desktopApp')
        </h4>

        @if(!$app || (!$app->windows_file_path && !$app->mac_file_path))
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.downloadsDesktopNotConfigured')</p>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-900 dark:border-gray-700">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-2">

                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Desktop Printing App</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Download the official  desktop app for Windows &amp; macOS
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($app && !empty($app->windows_file_path))
                            <a href="{{ $app->windows_file_path }}" target="_blank" rel="noopener"
                               class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-colors group dark:border-gray-700 dark:hover:border-blue-400 dark:hover:bg-blue-900/20">
                                <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 dark:bg-gray-800">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-8 h-8 text-blue-600">
                                        <rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor"></rect>
                                        <rect x="14" y="3" width="7" height="7" rx="1" fill="currentColor"></rect>
                                        <rect x="3" y="14" width="7" height="7" rx="1" fill="currentColor"></rect>
                                        <rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor"></rect>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 group-hover:text-blue-800 dark:text-gray-100 dark:group-hover:text-blue-300">Windows</p>
                                    @if(\Illuminate\Support\Str::contains($app->windows_file_path, ['microsoft.com', 'apps.microsoft.com']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Get it from Microsoft Store</p>
                                    @endif
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 ml-auto flex-shrink-0 dark:text-gray-500 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @endif

                        @if($app && !empty($app->mac_file_path))
                            <a href="{{ $app->mac_file_path }}" target="_blank" rel="noopener"
                               class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-colors group dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-800/50">
                                <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 dark:bg-gray-800">
                                    <svg fill="currentColor" viewBox="0 0 24 24" class="w-8 h-8 text-gray-700 dark:text-gray-200">
                                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"></path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">macOS</p>
                                    @if(\Illuminate\Support\Str::contains($app->mac_file_path, 'apps.apple.com'))
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Download on the Mac App Store</p>
                                    @endif
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 ml-auto flex-shrink-0 dark:text-gray-500 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
        @endif
    </div>

    {{-- Mobile App --}}
    <div class="mt-8">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            @lang('superadmin.mobileApp')
        </h4>

        @if(!$app || (empty($app->partner_app_ios) && empty($app->partner_app_android)))
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.downloadsMobileNotConfigured')</p>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-900 dark:border-gray-700">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100"> Rider Mobile app</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Official delivery partner app – download 
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if(!empty($app->partner_app_android))
                            <a href="{{ $app->partner_app_android }}" target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50/50 transition-colors group dark:border-gray-700 dark:hover:border-green-400 dark:hover:bg-green-900/20">
                                <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 dark:bg-gray-800">
                                    <svg class="w-8 h-8 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.802 8.99l-2.302 2.302-8.636-8.634z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 group-hover:text-green-800 dark:text-gray-100 dark:group-hover:text-green-300">Android</p>
                                    @if(\Illuminate\Support\Str::contains($app->partner_app_android, 'play.google.com'))
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Get it on Google Play</p>
                                    @endif
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600 ml-auto flex-shrink-0 dark:text-gray-500 dark:group-hover:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @endif

                        @if(!empty($app->partner_app_ios))
                            <a href="{{ $app->partner_app_ios }}" target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-colors group dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-800/50">
                                <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0 dark:bg-gray-800">
                                    <svg class="w-8 h-8 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">iOS</p>
                                    @if(\Illuminate\Support\Str::contains($app->partner_app_ios, 'apps.apple.com'))
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Download on the App Store</p>
                                    @endif
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 ml-auto flex-shrink-0 dark:text-gray-500 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
        @endif
    </div>
</div>
