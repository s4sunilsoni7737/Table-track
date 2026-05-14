<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $restaurant->name ?? __('app.connecting') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #333;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            padding: 30px;
            text-align: center;
        }
        .wifi-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            color: #667eea;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1a202c;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .wifi-details {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        .wifi-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .wifi-item:last-child {
            margin-bottom: 0;
        }
        .wifi-item svg {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            color: #667eea;
            flex-shrink: 0;
        }
        .wifi-item-content {
            flex: 1;
            min-width: 0;
        }
        .wifi-label {
            font-size: 12px;
            color: #718096;
            margin-bottom: 4px;
        }
        .wifi-value {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            word-break: break-all;
        }
        .wifi-password {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 12px;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        .btn svg {
            width: 20px;
            height: 20px;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .status-offline {
            background: #fed7d7;
            color: #c53030;
        }
        .status-online {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-connecting {
            background: #feebc8;
            color: #c05621;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .copy-success {
            background: #c6f6d5;
            color: #22543d;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 8px;
            display: none;
        }
        .copy-success.show {
            display: block;
        }
        .instructions {
            background: #edf2f7;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #4a5568;
            text-align: left;
        }
        .instructions ol {
            margin-left: 20px;
            margin-top: 8px;
        }
        .instructions li {
            margin-bottom: 6px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.show {
            display: flex;
        }
        .loading-content {
            text-align: center;
        }
        .loading-content .spinner {
            width: 40px;
            height: 40px;
            border-width: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <svg class="wifi-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
        </svg>
        
        <h1>{{ __('app.connectToWiFi') }}</h1>
        <p class="subtitle">{{ __('app.connectToWiFiDescription') }}</p>

        <div id="statusIndicator" class="status-indicator status-offline">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
            </svg>
            <span id="statusText">{{ __('app.offline') }}</span>
        </div>

        @if($restaurant && $restaurant->show_wifi_icon && $restaurant->wifi_name && $restaurant->wifi_password)
            <div class="wifi-details">
                <div class="wifi-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                    <div class="wifi-item-content">
                        <div class="wifi-label">{{ __('modules.settings.wifiName') }}</div>
                        <div class="wifi-value" id="wifiName">{{ $restaurant->wifi_name }}</div>
                    </div>
                </div>

                <div class="wifi-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <div class="wifi-item-content">
                        <div class="wifi-label">{{ __('modules.settings.wifiPassword') }}</div>
                        <div class="wifi-value wifi-password" id="wifiPassword">{{ $restaurant->wifi_password }}</div>
                    </div>
                    <button onclick="copyPassword()" style="background: none; border: none; cursor: pointer; padding: 4px; margin-left: 8px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="copy-success" id="copySuccess">{{ __('app.copiedToClipboard') }}</div>

            @php
                $wifiQrPayload = 'WIFI:T:WPA;S:' . $restaurant->wifi_name . ';P:' . $restaurant->wifi_password . ';;';
            @endphp

            <button class="btn btn-primary" onclick="connectToWifi()" id="connectBtn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                </svg>
                {{ __('app.connectToWiFi') }}
            </button>

            <div class="instructions">
                <strong>{{ __('app.instructions') }}:</strong>
                <ol>
                    <li>{{ __('app.wifiInstruction1') }}</li>
                    <li>{{ __('app.wifiInstruction2') }}</li>
                    <li>{{ __('app.wifiInstruction3') }}</li>
                </ol>
            </div>
        @else
            <div class="wifi-details">
                <p style="color: #718096;">{{ __('app.wifiNotConfigured') }}</p>
            </div>
            <button class="btn btn-primary" onclick="redirectToMenu()">
                {{ __('app.continueToMenu') }}
            </button>
        @endif
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>{{ __('app.redirectingToMenu') }}</p>
        </div>
    </div>

    <script>
        // Configuration
        const config = {
            restaurantId: {{ $restaurant->id ?? 'null' }},
            tableHash: '{{ $tableHash ?? '' }}',
            branchHash: '{{ $branchHash ?? '' }}',
            restaurantHash: '{{ $restaurant->hash ?? '' }}',
            menuUrl: '{{ $menuUrl ?? '' }}',
            wifiName: '{{ $restaurant->wifi_name ?? '' }}',
            wifiPassword: '{{ $restaurant->wifi_password ?? '' }}',
            wifiQrPayload: '{{ $wifiQrPayload ?? '' }}'
        };

        let redirectAttempts = 0;
        const maxRedirectAttempts = 30; // 30 attempts = 1 minute
        let checkInterval = null;

        // Update status indicator
        function updateStatus(isOnline) {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            
            if (isOnline) {
                indicator.className = 'status-indicator status-online';
                statusText.textContent = '{{ __('app.online') }}';
            } else {
                indicator.className = 'status-indicator status-offline';
                statusText.textContent = '{{ __('app.offline') }}';
            }
        }

        // Check internet connectivity
        function checkConnectivity() {
            return fetch('/manifest.json', { 
                method: 'HEAD',
                cache: 'no-cache',
                mode: 'no-cors'
            }).then(() => true).catch(() => false);
        }

        // Copy password to clipboard
        function copyPassword() {
            const password = config.wifiPassword;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(password).then(() => {
                    showCopySuccess();
                }).catch(() => {
                    fallbackCopy(password);
                });
            } else {
                fallbackCopy(password);
            }
        }

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                console.error('Copy failed:', err);
            }
            document.body.removeChild(textarea);
        }

        function showCopySuccess() {
            const success = document.getElementById('copySuccess');
            success.classList.add('show');
            setTimeout(() => {
                success.classList.remove('show');
            }, 2000);
        }

        // Connect to Wi-Fi (Android intent / iOS instructions)
        function connectToWifi() {
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;
            const isAndroid = /android/i.test(userAgent);
            const isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;

            if (isAndroid && config.wifiQrPayload) {
                // Android: Try to open Wi-Fi connection intent
                try {
                    window.location.href = config.wifiQrPayload;
                    // Fallback: Show instructions
                    setTimeout(() => {
                        alert('{{ __('app.androidWifiInstructions') }}');
                    }, 1000);
                } catch (e) {
                    alert('{{ __('app.manualWifiInstructions') }}');
                }
            } else if (isIOS) {
                // iOS: Show manual instructions
                alert('{{ __('app.iosWifiInstructions') }}');
            } else {
                // Desktop/Other: Show instructions
                alert('{{ __('app.manualWifiInstructions') }}');
            }
        }

        // Build menu URL
        function buildMenuUrl() {
            if (config.tableHash) {
                return '{{ route("table_order", ["HASH"]) }}'.replace('HASH', config.tableHash) + '?from_qr=1';
            } else if (config.branchHash && config.restaurantHash) {
                return '{{ route("table_order", ["RESTAURANT_ID"]) }}'.replace('RESTAURANT_ID', config.restaurantId) + 
                       '?branch=' + config.branchHash + '&hash=' + config.restaurantHash + '&from_qr=1';
            } else if (config.restaurantHash) {
                return '{{ route("shop_restaurant", ["HASH"]) }}'.replace('HASH', config.restaurantHash);
            }
            return config.menuUrl || '/';
        }

        // Redirect to menu
        function redirectToMenu() {
            const menuUrl = buildMenuUrl();
            document.getElementById('loadingOverlay').classList.add('show');
            
            // Small delay to show loading
            setTimeout(() => {
                window.location.href = menuUrl;
            }, 500);
        }

        // Check if online and redirect
        function checkAndRedirect() {
            redirectAttempts++;
            
            if (redirectAttempts > maxRedirectAttempts) {
                clearInterval(checkInterval);
                return;
            }

            // Update status
            const isOnline = navigator.onLine;
            updateStatus(isOnline);

            // Try to verify actual connectivity
            checkConnectivity().then(connected => {
                if (connected) {
                    clearInterval(checkInterval);
                    redirectToMenu();
                }
            }).catch(() => {
                // Still offline, continue checking
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initial status
            updateStatus(navigator.onLine);

            // If already online, redirect immediately
            if (navigator.onLine) {
                checkConnectivity().then(connected => {
                    if (connected) {
                        redirectToMenu();
                        return;
                    }
                });
            }

            // Listen for online event
            window.addEventListener('online', function() {
                updateStatus(true);
                setTimeout(() => {
                    checkConnectivity().then(connected => {
                        if (connected) {
                            clearInterval(checkInterval);
                            redirectToMenu();
                        }
                    });
                }, 1000);
            });

            // Listen for offline event
            window.addEventListener('offline', function() {
                updateStatus(false);
            });

            // Start periodic check (every 2 seconds)
            checkInterval = setInterval(checkAndRedirect, 2000);
        });

        // Register service worker for offline caching
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
                    .then(registration => {
                        console.log('Service Worker registered for offline access');
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>

