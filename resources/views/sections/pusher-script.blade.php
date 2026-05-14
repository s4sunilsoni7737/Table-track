@if(pusherSettings()->is_enabled_pusher_broadcast)
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    console.log('🔧 Pusher script loading...');
    // console.log('📊 Raw pusherSettings():', @json(pusherSettings()));

    // Always update PUSHER_SETTINGS with fresh data
    window.PUSHER_SETTINGS = @json(pusherSettings());
    // console.log('📊 PUSHER_SETTINGS object:', window.PUSHER_SETTINGS);
    // console.log('📊 PUSHER_SETTINGS.pusher_key:', window.PUSHER_SETTINGS.pusher_key);
    // console.log('📊 PUSHER_SETTINGS.pusher_cluster:', window.PUSHER_SETTINGS.pusher_cluster);
    // console.log('📊 PUSHER_SETTINGS.is_enabled_pusher_broadcast:', window.PUSHER_SETTINGS.is_enabled_pusher_broadcast);

    if (!window.PUSHER_SETTINGS.pusher_key || window.PUSHER_SETTINGS.pusher_key === 'undefined') {
        console.error('❌ Pusher key is undefined or invalid:', window.PUSHER_SETTINGS.pusher_key);
    } else {
        console.log('✅ Pusher key is valid, initializing Pusher...');

        // Implement connection sharing to reduce quota usage
        if (!window.GLOBAL_PUSHER) {
            window.GLOBAL_PUSHER = new Pusher(window.PUSHER_SETTINGS.pusher_key, {
                cluster: window.PUSHER_SETTINGS.pusher_cluster,
                encrypted: true,
                maxReconnectionAttempts: 3, // Limit reconnection attempts
                maxReconnectGap: 10, // Limit reconnection frequency
                activityTimeout: 30000, // Reduce activity timeout
                pongTimeout: 15000 // Reduce pong timeout
            });
            console.log('✅ Global Pusher connection created');

            // Add connection cleanup on page unload
            window.addEventListener('beforeunload', function() {
                if (window.GLOBAL_PUSHER) {
                    console.log('🧹 Cleaning up Pusher connection on page unload');
                    window.GLOBAL_PUSHER.disconnect();
                }
            });

            // Add connection cleanup on visibility change (tab switching)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && window.GLOBAL_PUSHER) {
                    console.log('🧹 Pausing Pusher connection (tab hidden)');
                    // Don't disconnect, just pause activity
                } else if (!document.hidden && window.GLOBAL_PUSHER) {
                    console.log('🔄 Resuming Pusher connection (tab visible)');
                }
            });

        } else {
            console.log('✅ Reusing existing global Pusher connection');
        }

        window.PUSHER = window.GLOBAL_PUSHER;
        console.log('✅ Pusher initialized successfully');
        console.log('📊 Pusher connection options:', {
            key: window.PUSHER_SETTINGS.pusher_key ? '***' + window.PUSHER_SETTINGS.pusher_key.slice(-4) : 'undefined',
            cluster: window.PUSHER_SETTINGS.pusher_cluster,
            encrypted: true,
            maxReconnectionAttempts: 3,
            maxReconnectGap: 10
        });
    }

    (function initGlobalKotPusherChannel() {
        const kotPusherCurrentUserId = @json(auth()->check() ? auth()->id() : null);
        const kotPusherSoundUrl = @json(asset('sound/new_order.wav'));

        function deliverKotPusherPayload(raw) {
            if (!window.Livewire || typeof window.Livewire.getByName !== 'function') {
                return;
            }
            const data = raw && typeof raw === 'object' ? Object.assign({}, raw) : {};

            window.Livewire.getByName('kot.kots').forEach(function (wire) {
                if (wire && typeof wire.call === 'function') {
                    wire.call('refreshKotsFromPusher', data);
                }
            });

            window.Livewire.getByName('kot.kot-pusher-listener').forEach(function (wire) {
                if (wire && typeof wire.call === 'function') {
                    wire.call('showKotStatusToast', data);
                }
            });

            if (data.type === 'status_updated') {
                const actorId = data.updated_by_user_id != null ? Number(data.updated_by_user_id) : null;
                const selfId = kotPusherCurrentUserId != null ? Number(kotPusherCurrentUserId) : null;
                if (actorId === null || selfId === null || actorId !== selfId) {
                    try {
                        new Audio(kotPusherSoundUrl).play();
                    } catch (e) { /* ignore */ }
                }
            }
        }

        function bindGlobalKotChannel() {
            if (!window.PUSHER) {
                return;
            }
            try {
                window.__kotsPusherChannel = window.__kotsPusherChannel || window.PUSHER.subscribe('kots');
                const channel = window.__kotsPusherChannel;
                channel.unbind('kot.updated');
                channel.bind('kot.updated', function (data) {
                    try {
                        deliverKotPusherPayload(data);
                    } catch (error) {
                        console.error('❌ kot.updated handler:', error);
                    }
                });
            } catch (error) {
                console.error('❌ KOT Pusher channel bind failed:', error);
            }
        }

        document.addEventListener('livewire:init', function () {
            bindGlobalKotChannel();
        });

        if (!window.__kotPusherGlobalNavigateBound) {
            window.__kotPusherGlobalNavigateBound = true;
            document.addEventListener('livewire:navigated', function () {
                bindGlobalKotChannel();
            });
        }

        if (window.Livewire && typeof window.Livewire.getByName === 'function') {
            bindGlobalKotChannel();
        }
    })();

    function reloadKots() {
        document.addEventListener('livewire:initialized', function () {
            // Safe to call Livewire.emit now
            Livewire.emit('refreshOrders');
            console.log('🔄 Reloading Kots...')
            new Audio("{{ asset('sound/new_order.wav')}}").play();
        });

        // Livewire.emit('updateKots');
        // window.PUSHER.channels.get('kots').trigger('kot.updated');
    }

    function reloadOrders() {
        console.log('🔄 Reloading Orders...');
        Livewire.emit('updateOrders');
        // window.PUSHER.channels.get('orders').trigger('order.updated');
    }


</script>
@else
<script>
    console.log('📡 Pusher broadcast is disabled');
</script>
@endif
