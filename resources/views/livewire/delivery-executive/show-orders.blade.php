<div x-data="{{ $trackingEnabled ? 'deliveryExecutiveOrderTracking(' . (int) $customer->id . ')' : '{}' }}">
    <div class=" bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700 mb-4">
        <div class="inline-flex gap-4 items-center">
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</h1>

            <span class='text-xs h-fit font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 border border-gray-400'>
                    {{ $customer->orders->count() }} @lang('menu.orders')
            </span>
        </div>
        
    </div>

    <div class="flex flex-col my-4 px-4">
        <div class="space-y-4">
            <div class="grid sm:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-4" wire:key="delivery-executive-orders-grid">
                @foreach ($orders as $orderData)
                    <x-order.order-card
                        :order='$orderData["order"]'
                        wire:key='order-{{ $orderData["order"]->id }}'
                        :showTrackButton="$trackingEnabled && $orderData['order']->order_type == 'delivery' && $orderData['isOutForDelivery']"
                        :showDeliveredButton="$orderData['order']->order_type == 'delivery' && $orderData['isDelivered']"
                        :trackEndpoint="route('delivery-executives.tracking-data', [$customer, $orderData['order']])"
                        :trackOrderLabel="'#' . $orderData['order']->show_formatted_order_number"
                        :hidePlacedVia="$trackingEnabled && $orderData['order']->order_type == 'delivery'"
                        :showLiveBlink="$trackingEnabled && $orderData['order']->order_type == 'delivery' && $orderData['isOutForDelivery']"
                    />
                @endforeach
            </div>
            
        </div>
    </div>

    @if ($trackingEnabled)
        <div x-show="trackModalOpen" class="fixed inset-0 z-50" style="display: none;">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeTrackModal()"></div>

            <div x-show="trackModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute right-0 top-0 h-full w-full sm:max-w-3xl bg-white dark:bg-gray-800 shadow-xl flex flex-col">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Order <span x-text="selectedOrderLabel"></span></h2>
                    <button type="button" @click="closeTrackModal()" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                        @lang('app.close')
                    </button>
                </div>

                <div class="flex-1 overflow-hidden relative">
                    <template x-if="trackingError">
                        <div class="absolute top-4 left-4 right-4 z-10 p-3 text-sm text-red-700 bg-red-100 border border-red-200 rounded dark:bg-red-900/20 dark:text-red-300 dark:border-red-900/40" x-text="trackingError"></div>
                    </template>

                    <div x-show="lastUpdatedAt && !trackingError" style="display: none;" class="absolute top-4 right-4 z-10 px-3 py-2 rounded bg-white/90 dark:bg-gray-900/90 text-xs text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                        <span>@lang('app.lastUpdate'): </span><span x-text="lastUpdatedAt || '--'"></span>
                    </div>

                    <div id="executive-tracking-map" class="h-full w-full"></div>

                    <template x-if="!isMapReady && !trackingError">
                        <div class="h-full w-full flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('app.loadingMap')
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @endif
</div>

@if ($trackingEnabled)
    @push('scripts')
        <script>
            function deliveryExecutiveOrderTracking(executiveId) {
                return {
                    trackModalOpen: false,
                    selectedOrderLabel: '',
                    trackEndpoint: '',
                    trackingError: '',
                    lastUpdatedAt: null,
                    pollingTimer: null,
                    executiveLat: null,
                    executiveLng: null,
                    branchLat: null,
                    branchLng: null,
                    customerLat: null,
                    customerLng: null,
                    executivePath: [],
                    isMapReady: false,
                    map: null,
                    branchMarker: null,
                    customerMarker: null,
                    executiveMarker: null,
                    pathPolyline: null,
                    directionsService: null,
                    directionsRenderer: null,
                    branchToExecRenderer: null,
                    execToCustomerRenderer: null,
                    lastRouteKey: null,
                    mapApiKey: (@js($mapApiKey) || '').trim(),
                    errors: {
                        missingCoordinates: @js(__('messages.trackingCoordinatesMissing')),
                        missingMapKey: @js(__('messages.googleMapKeyMissing')),
                        fetchFailed: @js(__('messages.trackingFetchFailed')),
                        routeFailed: @js(__('messages.trackingRouteUnavailable')),
                    },

                    openTrackModal(endpoint, orderLabel) {
                        this.trackEndpoint = endpoint;
                        this.selectedOrderLabel = this.normalizeOrderLabel(orderLabel);
                        this.resetMapState();
                        this.trackModalOpen = true;
                        this.fetchTrackingData();
                        this.startPolling();
                    },

                    closeTrackModal() {
                        this.trackModalOpen = false;
                        this.stopPolling();
                        this.trackingError = '';
                    },

                    startPolling() {
                        this.stopPolling();
                        this.pollingTimer = setInterval(() => this.fetchTrackingData(), 5000);
                    },

                    stopPolling() {
                        if (this.pollingTimer) {
                            clearInterval(this.pollingTimer);
                            this.pollingTimer = null;
                        }
                    },

                    async fetchTrackingData() {
                        if (!this.trackEndpoint) return;

                        try {
                            const separator = this.trackEndpoint.includes('?') ? '&' : '?';
                            const noCacheUrl = `${this.trackEndpoint}${separator}_ts=${Date.now()}`;

                            const response = await fetch(noCacheUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'Cache-Control': 'no-cache'
                                },
                                cache: 'no-store'
                            });

                            const payload = await response.json();

                            if (!response.ok) {
                                throw new Error(payload.message || this.errors.fetchFailed);
                            }

                            this.trackingError = '';
                            this.lastUpdatedAt = payload.executive?.updated_at ?
                                new Date(payload.executive.updated_at).toLocaleString() :
                                new Date().toLocaleString();
                            this.executiveLat = payload.executive?.latitude ? Number(payload.executive.latitude) : null;
                            this.executiveLng = payload.executive?.longitude ? Number(payload.executive.longitude) : null;
                            this.branchLat = payload.branch?.latitude ? Number(payload.branch.latitude) : null;
                            this.branchLng = payload.branch?.longitude ? Number(payload.branch.longitude) : null;
                            this.customerLat = payload.customer?.latitude ? Number(payload.customer.latitude) : null;
                            this.customerLng = payload.customer?.longitude ? Number(payload.customer.longitude) : null;
                            this.executivePath = Array.isArray(payload.executive_path)
                                ? payload.executive_path
                                    .map((point) => ({
                                        lat: Number(point.latitude),
                                        lng: Number(point.longitude),
                                    }))
                                    .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng))
                                : [];

                            const hasExecutive = Number.isFinite(this.executiveLat) && Number.isFinite(this.executiveLng);
                            const hasBranch = Number.isFinite(this.branchLat) && Number.isFinite(this.branchLng);
                            const hasCustomer = Number.isFinite(this.customerLat) && Number.isFinite(this.customerLng);

                            if (!hasBranch || !hasCustomer || !hasExecutive) {
                                throw new Error(this.errors.missingCoordinates);
                            }

                            await this.ensureGoogleMapsLoaded();
                            this.initializeMap();
                            this.renderBranchAndCustomerMarkers();
                            this.renderRouteToCustomer();

                            this.renderExecutiveMarker();
                        } catch (error) {
                            this.trackingError = error.message || this.errors.fetchFailed;
                        }
                    },

                    resetMapState() {
                        this.trackingError = '';
                        this.lastUpdatedAt = null;
                        this.executiveLat = null;
                        this.executiveLng = null;
                        this.branchLat = null;
                        this.branchLng = null;
                        this.customerLat = null;
                        this.customerLng = null;
                        this.executivePath = [];
                        this.isMapReady = false;
                        this.map = null;
                        this.branchMarker = null;
                        this.customerMarker = null;
                        this.executiveMarker = null;
                        this.pathPolyline = null;
                        this.directionsService = null;
                        this.directionsRenderer = null;
                        if (this.branchToExecRenderer) {
                            this.branchToExecRenderer.setMap(null);
                        }
                        if (this.execToCustomerRenderer) {
                            this.execToCustomerRenderer.setMap(null);
                        }
                        this.branchToExecRenderer = null;
                        this.execToCustomerRenderer = null;
                        this.lastRouteKey = null;
                    },

                    normalizeOrderLabel(label) {
                        let value = (label ?? '').toString().trim();
                        if (!value) return '';

                        // Normalize inputs like:
                        // "Order #8", "#Order #8", "#8", "8"
                        value = value.replace(/^#?\s*order\s*#?\s*/i, '').trim();
                        value = value.replace(/^#\s*/, '').trim();

                        return `#${value}`;
                    },

                    async ensureGoogleMapsLoaded() {
                        if (window.google?.maps?.Map) return;
                        if (!this.mapApiKey) {
                            throw new Error(this.errors.missingMapKey);
                        }

                        if (!window.__ttGoogleMapsInitPromise) {
                            window.__ttGoogleMapsInitPromise = new Promise((resolve, reject) => {
                                const callbackName = '__ttInitDeliveryTrackingMap';
                                window[callbackName] = () => resolve();

                                const script = document.createElement('script');
                                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(this.mapApiKey)}&v=weekly&callback=${callbackName}`;
                                script.async = true;
                                script.defer = true;
                                script.onerror = () => reject(new Error('Unable to load Google Maps.'));
                                document.head.appendChild(script);
                            });
                        }

                        await window.__ttGoogleMapsInitPromise;
                    },

                    initializeMap() {
                        if (this.map) {
                            this.isMapReady = true;
                            return;
                        }

                        const mapElement = document.getElementById('executive-tracking-map');
                        if (!mapElement) {
                            throw new Error('Map container is not available.');
                        }

                        this.map = new google.maps.Map(mapElement, {
                            center: { lat: this.branchLat, lng: this.branchLng },
                            zoom: 14,
                            streetViewControl: false,
                            mapTypeControl: false
                        });
                        this.isMapReady = true;
                    },

                    renderBranchAndCustomerMarkers() {
                        const branchPos = { lat: this.branchLat, lng: this.branchLng };
                        const customerPos = { lat: this.customerLat, lng: this.customerLng };
                        const branchIcon = {
                            url: this.svgDataUrl('restaurant'),
                            scaledSize: new google.maps.Size(34, 34),
                            anchor: new google.maps.Point(17, 17)
                        };
                        const customerIcon = {
                            url: this.svgDataUrl('live-point'),
                            scaledSize: new google.maps.Size(28, 28),
                            anchor: new google.maps.Point(14, 14)
                        };

                        if (!this.branchMarker) {
                            this.branchMarker = new google.maps.Marker({
                                map: this.map,
                                position: branchPos,
                                icon: branchIcon,
                                title: 'Branch'
                            });
                        } else {
                            this.branchMarker.setPosition(branchPos);
                            this.branchMarker.setIcon(branchIcon);
                        }

                        if (!this.customerMarker) {
                            this.customerMarker = new google.maps.Marker({
                                map: this.map,
                                position: customerPos,
                                icon: customerIcon,
                                title: 'Customer'
                            });
                        } else {
                            this.customerMarker.setPosition(customerPos);
                            this.customerMarker.setIcon(customerIcon);
                        }
                    },

                    renderExecutiveMarker() {
                        const execPos = { lat: this.executiveLat, lng: this.executiveLng };
                        const executiveIcon = {
                            url: this.svgDataUrl('bike'),
                            scaledSize: new google.maps.Size(42, 42),
                            anchor: new google.maps.Point(21, 21)
                        };

                        if (!this.executiveMarker) {
                            this.executiveMarker = new google.maps.Marker({
                                map: this.map,
                                position: execPos,
                                icon: executiveIcon,
                                title: 'Delivery Executive'
                            });
                        } else {
                            this.executiveMarker.setPosition(execPos);
                            this.executiveMarker.setIcon(executiveIcon);
                        }
                    },

                    svgDataUrl(type) {
                        let svg = '';

                        if (type === 'bike') {
                            svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'>
                                <ellipse cx='32' cy='58' rx='13' ry='4' fill='rgba(17,24,39,0.25)'/>
                                <path d='M32 3c-10.2 0-18.5 8.3-18.5 18.5 0 13.7 18.5 31.5 18.5 31.5S50.5 35.2 50.5 21.5C50.5 11.3 42.2 3 32 3z' fill='#1d4ed8'/>
                                <circle cx='32' cy='21.5' r='13.5' fill='#eff6ff' stroke='#bfdbfe' stroke-width='1.5'/>
                                <circle cx='25.5' cy='26.5' r='4.8' fill='#111827'/>
                                <circle cx='38.5' cy='26.5' r='4.8' fill='#111827'/>
                                <circle cx='25.5' cy='26.5' r='2.2' fill='#ffffff'/>
                                <circle cx='38.5' cy='26.5' r='2.2' fill='#ffffff'/>
                                <path d='M28.5 26.5h4.8l2.9-7h-4.3l-2.1 4.2h-3.4' stroke='#2563eb' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                                <path d='M34.5 16h3.8l3 5' stroke='#2563eb' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                                <circle cx='33.4' cy='14.2' r='2.1' fill='#ef4444'/>
                            </svg>`;
                        } else if (type === 'restaurant') {
                            svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'>
                                <path d='M32 2c-12 0-22 10-22 22 0 16 22 38 22 38s22-22 22-38C54 12 44 2 32 2z' fill='#ea580c'/>
                                <circle cx='32' cy='24' r='14' fill='#fff7ed'/>
                                <path d='M24 18v12M28 18v12M24 24h4M36 18v6a3 3 0 0 0 3 3h1v3' stroke='#ea580c' stroke-width='2.5' stroke-linecap='round' fill='none'/>
                            </svg>`;
                        } else {
                            svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'>
                                <circle cx='32' cy='32' r='20' fill='rgba(34,197,94,0.25)' stroke='rgba(34,197,94,0.55)' stroke-width='4'/>
                                <circle cx='32' cy='32' r='12' fill='rgba(34,197,94,0.45)' stroke='rgba(22,163,74,0.7)' stroke-width='3'/>
                                <circle cx='32' cy='32' r='7.5' fill='#22c55e' stroke='#166534' stroke-width='2'/>
                            </svg>`;
                        }

                        return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
                    },

                    renderRouteToCustomer() {
                        const executivePoint = { lat: this.executiveLat, lng: this.executiveLng };
                        const pathToExecutive = this.samplePathPoints(this.executivePath, 20);
                        const routeKey = `${this.branchLat},${this.branchLng}|${pathToExecutive.map((p) => `${p.lat},${p.lng}`).join(';')}|${executivePoint.lat},${executivePoint.lng}|${this.customerLat},${this.customerLng}`;

                        if (this.lastRouteKey === routeKey && (this.directionsRenderer || this.pathPolyline)) {
                            return;
                        }

                        if (!this.directionsService) {
                            this.directionsService = new google.maps.DirectionsService();
                        }

                        if (!this.branchToExecRenderer) {
                            this.branchToExecRenderer = new google.maps.DirectionsRenderer({
                                suppressMarkers: true,
                                preserveViewport: true,
                                polylineOptions: {
                                    strokeColor: '#93c5fd',
                                    strokeOpacity: 0.95,
                                    strokeWeight: 6
                                }
                            });
                            this.branchToExecRenderer.setMap(this.map);
                        }

                        if (!this.execToCustomerRenderer) {
                            this.execToCustomerRenderer = new google.maps.DirectionsRenderer({
                                suppressMarkers: true,
                                preserveViewport: false,
                                polylineOptions: {
                                    strokeColor: '#0ea5e9',
                                    strokeOpacity: 0.95,
                                    strokeWeight: 6
                                }
                            });
                            this.execToCustomerRenderer.setMap(this.map);
                        }

                        const waypoints = pathToExecutive
                            .filter((point) => !this.isSamePoint(point, executivePoint))
                            .map((point) => ({
                                location: point,
                                stopover: false
                            }));

                        const toExecutiveRequest = {
                            origin: { lat: this.branchLat, lng: this.branchLng },
                            destination: executivePoint,
                            waypoints,
                            travelMode: google.maps.TravelMode.DRIVING,
                        };

                        const toCustomerRequest = {
                            origin: executivePoint,
                            destination: { lat: this.customerLat, lng: this.customerLng },
                            travelMode: google.maps.TravelMode.DRIVING,
                        };

                        this.directionsService.route(toExecutiveRequest, (toExecResult, toExecStatus) => {
                            if (toExecStatus === 'OK' && toExecResult) {
                                this.branchToExecRenderer.setDirections(toExecResult);
                            }

                            this.directionsService.route(toCustomerRequest, (toCustomerResult, toCustomerStatus) => {
                                if (toExecStatus === 'OK' && toCustomerStatus === 'OK' && toCustomerResult) {
                                    this.execToCustomerRenderer.setDirections(toCustomerResult);
                                    this.lastRouteKey = routeKey;
                                    this.trackingError = '';

                                    if (this.pathPolyline) {
                                        this.pathPolyline.setMap(null);
                                        this.pathPolyline = null;
                                    }
                                } else {
                                    const pathPoints = [
                                        { lat: this.branchLat, lng: this.branchLng },
                                        ...pathToExecutive,
                                        executivePoint,
                                        { lat: this.customerLat, lng: this.customerLng },
                                    ];

                                    if (!this.pathPolyline) {
                                        this.pathPolyline = new google.maps.Polyline({
                                            map: this.map,
                                            geodesic: true,
                                            strokeColor: '#0ea5e9',
                                            strokeOpacity: 0.95,
                                            strokeWeight: 6
                                        });
                                    }

                                    this.pathPolyline.setPath(pathPoints);
                                    this.lastRouteKey = routeKey;
                                }
                            });
                        });
                    },

                    samplePathPoints(points, maxPoints = 23) {
                        if (!Array.isArray(points) || points.length <= maxPoints) {
                            return points || [];
                        }

                        const sampled = [];
                        const step = (points.length - 1) / (maxPoints - 1);

                        for (let i = 0; i < maxPoints; i++) {
                            const idx = Math.round(i * step);
                            sampled.push(points[idx]);
                        }

                        return sampled;
                    },

                    isSamePoint(a, b) {
                        if (!a || !b) return false;

                        return Math.abs(Number(a.lat) - Number(b.lat)) < 0.00001
                            && Math.abs(Number(a.lng) - Number(b.lng)) < 0.00001;
                    }
                }
            }
        </script>
    @endpush
@endif
