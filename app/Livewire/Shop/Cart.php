<?php

namespace App\Livewire\Shop;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\Area;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use Razorpay\Api\Api;
use App\Models\Country;
use App\Models\KotItem;
use App\Models\Payment;
use App\Models\Printer;
use Livewire\Component;
use App\Models\Customer;
use App\Models\KotPlace;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\TapPayment;
use App\Models\EpayPayment;
use App\Models\OrderCharge;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Events\OrderUpdated;
use App\Models\ItemCategory;
use App\Models\PaypalPayment;
use App\Models\StripePayment;
use App\Models\XenditPayment;
use App\Models\ModifierOption;
use App\Traits\PrinterSetting;
use App\Events\NewOrderCreated;
use App\Models\RazorpayPayment;
use Illuminate\Validation\Rule;
use Mollie\Api\MollieApiClient;
use App\Models\RestaurantCharge;
use App\Models\MenuItemVariation;
use Livewire\Attributes\Computed;
use App\Models\AdminMolliePayment;
use App\Models\FlutterwavePayment;
use Illuminate\Support\Facades\DB;
use App\Models\AdminPayfastPayment;
use Illuminate\Support\Facades\Log;
use App\Events\SendNewOrderReceived;
use App\Models\AdminPaystackPayment;
use App\Notifications\SendOrderBill;
use Illuminate\Support\Facades\Http;
use App\Scopes\AvailableMenuItemScope;
use App\Models\PaymentGatewayCredential;
use App\Models\OfflinePaymentMethod;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Services\RestaurantAvailabilityService;

class Cart extends Component
{

    use LivewireAlert;
    use PrinterSetting;

    // Note: HasLoyaltyIntegration trait is conditionally loaded
    // If the Loyalty module doesn't exist, stub methods below handle it gracefully

    public $search;
    public $tableID;
    public $filterCategories;
    public $kotList = [];
    public $showVariationModal = false;
    public $showCartVariationModal = false;
    public $showCustomerNameModal = false;
    public $showPaymentModal = false;
    public $showMenu = true;
    public $showCart = false;
    public $orderItemList = [];
    public $orderItemVariation = [];
    public $orderItemQty = [];
    public $cartItemQty = [];
    public $orderItemAmount = [];
    public $orderItemModifiersPrice = [];
    public $menuItem;
    public $subTotal;
    public $total;
    public $taxes;
    public $customer;
    public $customerId; // For loyalty integration trait
    public $customerName;
    public $customerPhone;
    public $customerPhoneCode;
    public $customerAddress;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;
    public $orderNumber;
    public $paymentGateway;
    public $paymentOrder;
    public $showVeg;
    public $razorpayStatus;
    public $stripeStatus;
    public $cartQty;
    public $restaurantHash;
    public $restaurant;
    public $shopBranch;
    public $orderType;
    public $orderTypeId; // Add orderTypeId for pricing context
    public $orderTypeSlug; // Add orderTypeSlug
    public $showOrderTypeModal = false; // Modal for order type selection
    public $cameFromQR = false; // Track if user came from QR code
    public $payNow = false;
    public $offline_payment_status;
    public $menuId;
    public $orderID;
    public $order;
    public $table;
    public $tables;
    public $getTable;
    public $qrCodeImage;
    public $enableQrPayment;
    public $showQrCode = false;
    public $showPaymentDetail = false;
    public $showTableModal = false;
    public $canCreateOrder;
    public $orderBeingProcessed = false;
    public $showModifiersModal = false;
    public $itemModifiersSelected = [];
    public $selectedModifierItem;
    public $showItemDetailModal = false;
    public $selectedItem;
    public $extraCharges;
    public $orderNote;
    public $showItemVariationsModal = false;
    public $showDeliveryAddressModal = false;
    public $addressLat;
    public $addressLng;
    public $deliveryAddress;
    public $deliveryFee = null;
    public $maxPreparationTime;
    public $etaMin;
    public $etaMax;
    public $itemNotes = [];
    public $orderItemTaxDetails = [];
    public $totalTaxAmount = 0;
    public $taxMode;
    public $taxBase = 0;
    public $showPickupDateTimeModal = false;
    public $pickupRange;
    public $now;
    public $minDate;
    public $maxDate;
    public $defaultDate;
    public $deliveryDateTime;
    public $pickupDate;
    public $pickupTime;
    public $showHalal;
    public $headerType = 'text';
    public $headerText;
    public $headerImages = [];
    public $isHeaderDisabled = false;
    public $showLocationModal = false;
    public $is_within_radius = true;
    public $menuItemsLoaded = 50;
    public $menuItemsPerLoad = 50;
    public $offlinePaymentMethods = [];
    public $selectedOfflinePaymentMethod = null;

    // Loyalty properties - defined here so they exist even if trait doesn't
    public $loyaltyPointsRedeemed = 0;
    public $loyaltyDiscountAmount = 0;
    public $availableLoyaltyPoints = 0;
    public $pointsToRedeem = 0;
    public $maxRedeemablePoints = 0;
    public $minRedeemPoints = 0;
    public $showLoyaltyRedemptionModal = false;
    public $loyaltyPointsValue = 0;
    public $maxLoyaltyDiscount = 0;

    public function mount()
    {
        if ($this->tableID) {
            $this->table = Table::where('hash', $this->tableID)->firstOrFail();
            $restaurant = $this->table->branch->restaurant;

            $fetchActiveOrder = Order::where('table_id', $this->table->id)->where('status', 'kot')->whereDate('date_time', '=', now($restaurant->timezone)->toDateString())->first();

            if ($fetchActiveOrder) {
                $this->orderID = $fetchActiveOrder->id;
                $this->order = $fetchActiveOrder;
            }

            $this->restaurant = $restaurant;
            $this->restaurantHash = $restaurant->hash;
        }

        if (!$this->restaurant) {
            abort(404);
        }

        // Detect if user came from QR code
        $this->cameFromQR = request()->query('hash') === $this->restaurant->hash ||
                           request()->boolean('from_qr') ||
                           !is_null($this->tableID);
        $this->paymentGateway = PaymentGatewayCredential::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->first();
        $this->taxes = Tax::withoutGlobalScopes()->where('branch_id', $this->shopBranch->id)->get();

        // Load enabled offline payment methods
        $this->offlinePaymentMethods = OfflinePaymentMethod::where('restaurant_id', $this->restaurant->id)->where('status', 'active')->orderBy('created_at', 'desc')->get();
        // Load additional charges for this restaurant (only enabled ones)
        $this->extraCharges = RestaurantCharge::withoutGlobalScopes()
            ->where('restaurant_id', $this->restaurant->id)
            ->where('is_enabled', true)
            ->get();
        $this->customer = customer();
        $this->customerId = $this->customer?->id; // Set customerId for loyalty integration trait

        // Load loyalty points if module is enabled and customer is logged in
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            try {
                if (module_enabled('Loyalty')) {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $restaurantId = restaurant()->id;
                    $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
                    $this->updateLoyaltyValues();
                }
            } catch (\Exception $e) {
                // Silently fail if module doesn't exist
            }
        }

        $this->razorpayStatus = (bool)($this->paymentGateway->razorpay_status ?? false);
        $this->stripeStatus = (bool)($this->paymentGateway->stripe_status ?? false);

        // Initialize phone codes
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
        $this->customerPhoneCode = $this->customer?->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();

        // If came from QR code, auto-set to dine_in and don't show modal
        if ($this->cameFromQR) {
            $this->orderType = 'dine_in';
            $this->setDefaultOrderType();
            $this->showOrderTypeModal = false;
        } else {
            // For regular users, determine default order type but show modal first
            $this->orderType = $this->restaurant->allow_dine_in_orders ? 'dine_in' : ($this->restaurant->allow_customer_delivery_orders ? 'delivery' : 'pickup');

            // Check if we have multiple order types to show modal
            $availableOrderTypes = OrderType::where('branch_id', $this->shopBranch->id)
                ->where('is_active', true)
                ->where('enable_from_customer_site', true)
                ->count();

            // Show modal if more than one order type available
            $this->showOrderTypeModal = $availableOrderTypes > 1;

            // If only one order type, set it automatically
            if ($availableOrderTypes == 1) {
                $this->setDefaultOrderType();
            }
        }

        if (request()->has('current_order')) {
            $this->orderID = request()->get('current_order');
            $this->order = Order::find($this->orderID);
            if ($this->order->status == 'paid') {
                $this->redirect(module_enabled('Subdomain') ? url('/') : route('shop_restaurant', ['hash' => $this->order->branch->restaurant->hash]));
            }
        }

        // Fetch QR code image from database
        $this->qrCodeImage = $this->restaurant->qr_code_image;

        // Only call these if order type is already selected
        if (!$this->showOrderTypeModal) {
            $this->updatedOrderType($this->orderType);
        }
        $this->taxMode = $this->restaurant->tax_mode ?? 'order';

        $this->pickupRange = $this->restaurant->pickup_days_range ?? 1;
        $dateFormat = $this->restaurant->date_format ?? 'd-m-Y';
        $this->minDate = now()->format($dateFormat);
        $this->maxDate = now()->addDays($this->pickupRange - 1)->endOfDay()->format($dateFormat);

        // Initialize pickupDate and pickupTime from deliveryDateTime if it exists
        if ($this->deliveryDateTime) {
            try {
                $dateTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $this->deliveryDateTime);
                $this->pickupDate = $dateTime->format($dateFormat);
                $this->pickupTime = $dateTime->format('H:i');
            } catch (\Exception $e) {
                $this->pickupDate = now()->format($dateFormat);
                $this->pickupTime = now()->format('H:i');
            }
        } else {
            $this->pickupDate = now()->format($dateFormat);
            $this->pickupTime = now()->format('H:i');
        }

        $this->defaultDate = old('deliveryDateTime', $this->deliveryDateTime ?? $this->minDate);

        $this->taxMode = $this->order?->tax_mode ?? ($this->restaurant->tax_mode ?? 'order');

        // Initialize header settings
        $this->initializeHeaderSettings();

        // Handle location for QR orders only when restrictions are enabled
        if ($this->cameFromQR && $this->restaurant->restrict_qr_order_by_location && !empty($this->restaurant->qr_order_radius_meters)) {
            // Check session first for stored location
            $sessionLocation = session('customer_location');

            if ($sessionLocation && !empty($sessionLocation['lat']) && !empty($sessionLocation['lng'])) {
                // Load location from session
                $this->addressLat = $sessionLocation['lat'];
                $this->addressLng = $sessionLocation['lng'];
                if (!empty($sessionLocation['address'])) {
                    $this->deliveryAddress = $sessionLocation['address'];
                }

                // Re-validate radius if needed
                $this->checkRadiusRestriction();
            } else {
                // Show location modal only if came from QR, restrictions enabled, and no session location
                if (empty($this->addressLat) || empty($this->addressLng)) {
                    $this->showLocationModal = true;
                }
            }
        }
    }

    // Loyalty methods - use trait if available, otherwise stub methods handle it

    /**
     * Check if loyalty module is enabled (stub method if trait doesn't exist)
     */
    public function isLoyaltyEnabled()
    {
        // Check if module is enabled
        if (!module_enabled('Loyalty')) {
            return false;
        }

        // Check if module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                return false;
            }
        }

        // Check platform-specific setting for Customer Site
        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = restaurant()->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    return $settings->enabled && ($settings->enable_for_customer_site ?? true);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return false;
    }

    /**
     * Update loyalty values (stub method if trait doesn't exist)
     */
    public function updateLoyaltyValues()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        // If module exists, implement the logic directly
        if (module_enabled('Loyalty')) {
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

                // Load loyalty settings
                if (module_enabled('Loyalty')) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);

                    if ($settings && $settings->isEnabled()) {
                        $valuePerPoint = $settings->value_per_point ?? 1;
                        $maxDiscountPercent = $settings->max_discount_percent ?? 0;

                        // Calculate loyalty points value (total value of all available points)
                        $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $valuePerPoint;

                        // Calculate max discount (percentage of subtotal)
                        $this->maxLoyaltyDiscount = ($this->subTotal * $maxDiscountPercent) / 100;

                        // Calculate max redeemable points based on max discount
                        $this->maxRedeemablePoints = $this->maxLoyaltyDiscount > 0 ? floor($this->maxLoyaltyDiscount / $valuePerPoint) : 0;
                        $this->minRedeemPoints = $settings->min_redeem_points ?? 0;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error updating loyalty values: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reset loyalty redemption (stub method if trait doesn't exist)
     */
    public function resetLoyaltyRedemption()
    {
        if (module_enabled('Loyalty')) {
            $traits = class_uses_recursive(static::class);
            if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
                // Trait exists and is used, it will handle this
                return;
            }
        }
        // Stub: reset loyalty properties to defaults
        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        $this->availableLoyaltyPoints = 0;
        $this->pointsToRedeem = 0;
        $this->maxRedeemablePoints = 0;
        $this->minRedeemPoints = 0;
        $this->showLoyaltyRedemptionModal = false;
    }

    /**
     * Get loyalty order data for saving to database (stub method if trait doesn't exist)
     */
    public function getLoyaltyOrderData()
    {
        if (module_enabled('Loyalty')) {
            $traits = class_uses_recursive(static::class);
            if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
                // Trait exists and is used, try to call trait method if it exists
                if (method_exists($this, 'traitGetLoyaltyOrderData')) {
                    return $this->traitGetLoyaltyOrderData();
                }
            }
        }
        // Stub: return empty array if module doesn't exist
        return [
            'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
        ];
    }

    /**
     * Set default order type and ID
     */
    private function setDefaultOrderType()
    {
        // Get default order type for the current order type
        $orderTypeModel = OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->first();

        if ($orderTypeModel) {
            $this->orderTypeId = $orderTypeModel->id;
            $this->orderTypeSlug = $orderTypeModel->slug;
        }
    }

    public function requestCustomerLocation()
    {
        $this->dispatch('requestGeolocation');
    }
    /**
     * Haversine formula to calculate distance between two points (meters)
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        $latDiff = $lat2 - $lat1;
        $lngDiff = $lng2 - $lng1;
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos($lat1) * cos($lat2) * sin($lngDiff / 2) * sin($lngDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Check if customer is within allowed radius for QR orders
     * Returns true if allowed, false if restricted
     */
    private function checkRadiusRestriction()
    {
        // Early return if restrictions don't apply
        if (!$this->cameFromQR || !$this->restaurant->restrict_qr_order_by_location || empty($this->restaurant->qr_order_radius_meters)) {
            return true; // No restriction, allow
        }

        // Restriction is enabled, check if location is set
        if (empty($this->addressLat) || empty($this->addressLng)) {
            $this->showLocationModal = true;
            return false; // Location required
        }

        // Get branch coordinates
        $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
        $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

        if (empty($branchLat) || empty($branchLng)) {
            return true; // Allow if branch coordinates missing
        }

        // Calculate distance
        $distance = $this->calculateDistance(
            $this->addressLat, $this->addressLng,
            $branchLat, $branchLng
        );

        // Update the flag
        $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

        return $this->is_within_radius;
    }

    public function setCustomerLocation($lat = null, $lng = null, $address = null)
    {
        if (is_null($lat) || is_null($lng)) {
            return;
        }

        $this->addressLat = $lat;
        $this->addressLng = $lng;
        if ($address) {
            $this->deliveryAddress = $address;
        }

        // Store location in session for current customer/order
        session([
            'customer_location' => [
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address,
                'stored_at' => now()->toDateTimeString(),
            ]
        ]);

        $this->showLocationModal = false;

        // QR order radius enforcement
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            // Check both lat/lng and latitude/longitude column names for branch
            $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
            $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

            if (!empty($branchLat) && !empty($branchLng)) {
                $distance = $this->calculateDistance(
                    $lat, $lng,
                    $branchLat,
                    $branchLng
                );
                $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

                if (!$this->is_within_radius) {
                    $this->alert('error', __('app.outsideAllowedArea'), [
                        'toast' => false,
                        'position' => 'center',
                    ]);

                    // Clear any existing cart items if outside radius
                    $this->orderItemList = [];
                    $this->orderItemQty = [];
                    $this->orderItemVariation = [];
                    $this->orderItemAmount = [];
                    $this->cartItemQty = [];
                    $this->orderItemModifiersPrice = [];
                    $this->itemModifiersSelected = [];
                    $this->itemNotes = [];
                    $this->calculateTotal();
                }
            } else {
                // If branch coordinates are missing, allow by default
                $this->is_within_radius = true;
            }
        } else {
            $this->is_within_radius = true;
        }

        // Recalculate delivery-related estimates if needed
        $this->calculateMaxPreparationTime();
        $this->calculateTotal();
    }

    /**
     * Handle order type change and update pricing context
     */
    public function updatedOrderTypeId($value)
    {
        if (!$value) {
            return;
        }

        // Get the order type information
        $orderType = OrderType::find($value);

        if (!$orderType) {
            return;
        }

        // Update the local variables
        $this->orderType = $orderType->type;
        $this->orderTypeSlug = $orderType->slug;

        // Get extra charges for this order type
        $mainExtraCharges = RestaurantCharge::withoutGlobalScopes()
            ->whereJsonContains('order_types', $this->orderTypeSlug)
            ->where('is_enabled', true)
            ->where('restaurant_id', $this->restaurant->id)
            ->get();

        // Update extra charges
        if (!$this->orderID) {
            // Only clear delivery-related fields if the order type is not delivery
            if ($this->orderTypeSlug !== 'delivery') {
                $this->addressLat = null;
                $this->addressLng = null;
                $this->deliveryAddress = null;
                $this->deliveryFee = null;
            }

            $this->calculateMaxPreparationTime();
            $this->extraCharges = $mainExtraCharges;
        } else {
            // For existing orders, keep existing charges if order type is unchanged
            $orderTypeFromOrder = $this->order->order_type_id
                ? (OrderType::where('id', $this->order->order_type_id)->value('slug') ?? $this->order->order_type)
                : $this->order->order_type;

            $this->extraCharges = $orderTypeFromOrder === $this->orderTypeSlug ? $this->order->extraCharges : $mainExtraCharges;
        }

        // Recalculate prices for all items in cart when order type changes
        foreach ($this->orderItemList ?? [] as $key => $item) {
            if ($this->orderTypeId) {
                $item->setPriceContext($this->orderTypeId, null);
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
                }
            }

            // Recalculate modifier prices
            if (isset($this->itemModifiersSelected[$key]) && is_array($this->itemModifiersSelected[$key])) {
                $modifierPrice = 0;
                foreach ($this->itemModifiersSelected[$key] as $modifierId) {
                    $modifier = ModifierOption::find($modifierId);
                    if ($modifier) {
                        if ($this->orderTypeId) {
                            $modifier->setPriceContext($this->orderTypeId, null);
                        }
                        $modifierPrice += $modifier->price;
                    }
                }
                $this->orderItemModifiersPrice[$key] = $modifierPrice;
            }

            // Recalculate item amount
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $item->price;
            $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
        }

        $this->calculateTotal();
    }

    /**
     * Handle order type selection from modal
     */
    public function selectOrderTypeFromModal($orderTypeId)
    {
        if (!$orderTypeId) {
            return;
        }

        // Set the order type ID which will trigger updatedOrderTypeId
        $this->orderTypeId = $orderTypeId;

        $orderType = OrderType::find($this->orderTypeId);
        $this->orderTypeSlug = $orderType->slug;
        $this->orderType = $orderType->type;
        // Close the modal
        $this->showOrderTypeModal = false;
    }

    public function initializeHeaderSettings()
    {
        $cartHeaderSetting = $this->restaurant->cartHeaderSetting;
        if ($cartHeaderSetting) {
            $this->headerType = $cartHeaderSetting->header_type;
            $this->headerText = $cartHeaderSetting->header_text;
            $this->headerImages = $cartHeaderSetting->images;
            $this->isHeaderDisabled = $cartHeaderSetting->is_header_disabled ?? false;
        } else {
            $this->headerText = __('messages.frontHeroHeading');
            $this->isHeaderDisabled = false;
        }
    }

    public function filterMenuItems($id)
    {
        $this->menuId = $id;
        $this->resetMenuItemsLoaded();
    }

    public function filterMenu($id = null)
    {
        $this->filterCategories = $id;
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when search changes
    public function updatedSearch()
    {
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when veg filter changes
    public function updatedShowVeg()
    {
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when halal filter changes
    public function updatedShowHalal()
    {
        $this->resetMenuItemsLoaded();
    }

    // Helper method to reset menu items loaded
    private function resetMenuItemsLoaded()
    {
        $this->menuItemsLoaded = $this->menuItemsPerLoad;
    }

    public function showItemVariations($id)
    {
        $this->showItemVariationsModal = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->where('show_on_customer_site', true)->findOrFail($id);
    }

    public function addCartItems($id, $variationCount, $modifierCount)
    {
        // Check radius restriction before allowing cart operations
        if (!$this->checkRadiusRestriction()) {
            // Check if location is set
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        if (!$this->canCreateOrder) {
            $this->alert('error', __('messages.CartAddPermissionDenied'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        // Check order limit
        $orderStats = $this->shopBranch ? getRestaurantOrderStats($this->shopBranch->id) : null;
        if (!$orderStats || (!$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'])) {
            return;
        }

        $this->menuItem = MenuItem::where('show_on_customer_site', true)->find($id);


        if ($variationCount > 0) {
            $this->showVariationModal = true;
        } elseif ($modifierCount > 0) {
            $this->selectedModifierItem = $id;
            $this->showModifiersModal = true;
        } else {
            $this->syncCart($id);
        }

        // Ensure itemNotes key is initialized
        if (!isset($this->itemNotes[$id])) {
            $this->itemNotes[$id] = '';
        }

        // Close item detail modal after add button is clicked
        $this->showItemDetailModal = false;
    }

    public function subCartItems($id)
    {
        $this->menuItem = MenuItem::find($id);
        $this->showCartVariationModal = true;
    }

    public function subModifiers($id)
    {
        $this->menuItem = MenuItem::find($id);
        // $this->showModifiersModal = true;
    }

    public function syncCart($id)
    {
        // Check radius restriction before adding items
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        if (!isset($this->orderItemList[$id])) {

            $this->orderItemList[$id] = $this->menuItem;
            $this->orderItemQty[$id] = $this->orderItemQty[$id] ?? 1;

            // Set price context before using price
            if ($this->orderTypeId) {
                if (isset($this->orderItemVariation[$id])) {
                    $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
                }
                if (isset($this->orderItemList[$id])) {
                    $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
                }
            }

            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
            $this->cartItemQty[$id] = isset($this->cartItemQty[$this->menuItem->id]) ? ($this->cartItemQty[$this->menuItem->id] + 1) : 1;
            $this->calculateTotal();
        } else {
            $this->addQty($id);
        }

        if (!isset($this->itemNotes[$id])) {
            $this->itemNotes[$id] = '';
        }
    }

    #[On('addQty')]
    public function addQty($id)
    {
        // Check radius restriction before allowing quantity increase
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = isset($this->orderItemQty[$id]) ? ($this->orderItemQty[$id] + 1) : 1;
        $this->cartItemQty[$id] = isset($this->cartItemQty[$id]) ? ($this->cartItemQty[$id] + 1) : 1;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    #[On('subQty')]
    public function subQty($id)
    {
        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = (isset($this->orderItemQty[$id]) && $this->orderItemQty[$id] > 1) ? ($this->orderItemQty[$id] - 1) : 0;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $menuID = explode('_', $id);

        if (isset($menuID[0])) {
            $menuID = str_replace('"', '', $menuID[0]);
        }

        $this->cartItemQty[$menuID] = isset($this->cartItemQty[$menuID]) ? ($this->cartItemQty[$menuID] - 1) : 0;

        if ($this->orderItemQty[$id] == 0) {
            unset($this->orderItemList[$id]);
            unset($this->orderItemVariation[$id]);
            unset($this->orderItemAmount[$id]);
            unset($this->orderItemQty[$id]);
        }

        if ($this->cartItemQty[$menuID] == 0) {
            unset($this->cartItemQty[$menuID]);
        }

        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->cartQty = 0;

        foreach ($this->orderItemQty ?? [] as $qty) {
            if ($qty > 0) {
                $this->cartQty++;
            }
        }

        $this->dispatch('updateCartCount', count: $this->cartQty);

        $this->total = 0;
        $this->subTotal = 0;
        $this->totalTaxAmount = 0;
        $this->orderItemTaxDetails = [];

        if (is_array($this->orderItemAmount)) {
            // Calculate item taxes first for proper subtotal calculation
            if ($this->taxMode === 'item') {
                $this->updateOrderItemTaxDetails();
            }

            foreach ($this->orderItemAmount as $key => $value) {
                $this->total += $value;

                // For inclusive taxes, subtract tax from subtotal
                if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
                    $taxDetail = $this->orderItemTaxDetails[$key];
                    $isInclusive = $this->restaurant->tax_inclusive ?? false;

                    if ($isInclusive) {
                        // For inclusive tax: subtotal = item amount - tax amount
                        $this->subTotal += ($value - ($taxDetail['tax_amount'] ?? 0));
                    } else {
                        // For exclusive tax: subtotal = item amount (tax will be added later)
                        $this->subTotal += $value;
                    }
                } else {
                    // No item taxes or order-level taxes
                    $this->subTotal += $value;
                }
            }
        }

        // Update loyalty values if customer is set (to recalculate max discount based on current subtotal)
        if ($this->isLoyaltyEnabled() && $this->customerId && $this->loyaltyPointsRedeemed == 0) {
            $this->updateLoyaltyValues();
        }

        // Apply loyalty discount if points are redeemed (BEFORE taxes, like regular discount)
        if ($this->isLoyaltyEnabled() && $this->loyaltyPointsRedeemed > 0 && $this->customerId) {
            // Recalculate loyalty discount based on current subtotal using trait method
            $this->recalculateLoyaltyDiscount(restaurant()->id, $this->subTotal ?? 0);

            // Apply discount to total (subtract from subtotal before taxes)
            if ($this->loyaltyDiscountAmount > 0) {
                $this->total -= $this->loyaltyDiscountAmount;
            }
        }

        // Step 2: Calculate service charges on net
        $serviceTotal = 0;
        $applicableExtraCharges = $this->filteredExtraCharges();

        foreach ($applicableExtraCharges as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                // Calculate charges on discounted subtotal (subtotal - loyalty discount)
                $discountedSubtotal = $this->subTotal - ($this->loyaltyDiscountAmount ?? 0);
                $serviceChargeAmount = $charge->getAmount($discountedSubtotal);
                $serviceTotal += $serviceChargeAmount;
                $this->total += $serviceChargeAmount;
            }
        }

        // Step 3: Calculate tax_base based on setting
        // Tax base = (subtotal - regular discount - loyalty discount) + service charges (if enabled)
        $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? false;

        // Calculate net after all discounts (regular + loyalty)
        $regularDiscount = $this->discountAmount ?? 0;
        $loyaltyDiscount = $this->loyaltyDiscountAmount ?? 0;
        $netAfterDiscounts = $this->subTotal - $regularDiscount - $loyaltyDiscount;

        if ($includeChargesInTaxBase) {
            $this->taxBase = $netAfterDiscounts + $serviceTotal;
        } else {
            $this->taxBase = $netAfterDiscounts;
        }

        // Step 4: Calculate taxes on tax_base (only once, after all discounts and charges are applied)
        // For item-based taxes, updateOrderItemTaxDetails() was already called at line 960
        // For order-based taxes, calculate on the correct taxBase
        $this->recalculateTaxTotals($this->taxBase);

        $this->total += (float)$this->deliveryFee ?: 0;
    }

    public function removeExtraCharge($chargeId, $orderType = null)
    {
        $charges = collect($this->extraCharges ?? []);
        $charge = $charges->firstWhere('id', $chargeId);

        // Skip work when the charge is not present
        if (!$charge) {
            return;
        }

        // Detach only when an order exists
        if ($this->order) {
            $this->order->extraCharges()->detach($chargeId);
        }

        // Keep the in-memory list in sync
        $this->extraCharges = $charges
            ->reject(fn($item) => $item->id == $chargeId)
            ->values();

        $this->calculateTotal();

        if ($this->order) {
            $this->order->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'tax_base' => $this->taxBase,
            ]);
        }
    }

    #[Computed]
    public function getApplicableExtraChargesProperty()
    {
        return $this->filteredExtraCharges();
    }

    private function filteredExtraCharges()
    {
        $orderType = $this->orderTypeSlug ?? $this->orderType ?? null;

        if (!$orderType) {
            return collect();
        }

        return collect($this->extraCharges ?? [])
            ->filter(function ($charge) use ($orderType) {
                $orderTypes = $charge->order_types ?? [];

                if (is_string($orderTypes)) {
                    $decoded = json_decode($orderTypes, true);
                    $orderTypes = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }

                if (!is_array($orderTypes) || empty($orderTypes)) {
                    return false;
                }

                return in_array($orderType, $orderTypes, true);
            })
            ->values();
    }

    /**
     * Legacy method kept for backward compatibility
     * Use updatedOrderTypeId for new implementation
     */
    public function updatedOrderType($value)
    {

        // Find the order type by slug or type
        $orderTypeModel = OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->where(function ($q) use ($value) {
                $q->where('slug', $value)
                    ->orWhere('type', $value);
            })
            ->first();

        if ($orderTypeModel) {
            $this->orderTypeId = $orderTypeModel->id;
            $mainExtraCharges = RestaurantCharge::withoutGlobalScopes()
                ->whereJsonContains('order_types', $value)
                ->where('is_enabled', true)
                ->where('restaurant_id', $this->restaurant->id)
                ->get();
            // Early return for new orders
            if (!$this->orderID) {
                // Only clear delivery-related fields if the order type is not delivery
                if ($value !== 'delivery') {
                    $this->addressLat = null;
                    $this->addressLng = null;
                    $this->deliveryAddress = null;
                    $this->deliveryFee = null;
                }

                $this->calculateMaxPreparationTime();
                $this->extraCharges = $mainExtraCharges;
                $this->calculateTotal();
                return;
            }

            // Early return if no valid order or order is paid
            if (!$this->order || $this->order->status === 'paid') {
                return;
            }

            // Efficiently get the slug from the order's order type
            $orderTypeFromOrder = $this->order->order_type_id
                ? (OrderType::where('id', $this->order->order_type_id)->value('slug') ?? $this->order->order_type)
                : $this->order->order_type;

            // Keep existing charges if order type is unchanged, otherwise set new ones
            $this->extraCharges = $orderTypeFromOrder === $value ? $this->order->extraCharges : $mainExtraCharges;

            $this->calculateTotal();
        }
    }

    #[On('setPosVariation')]
    public function setPosVariation($variationId)
    {
        $this->showVariationModal = false;
        $menuItemVariation = MenuItemVariation::find($variationId);

        // Set price context before using variation
        if ($this->orderTypeId) {
            $menuItemVariation->setPriceContext($this->orderTypeId, null);
        }

        $modifiersAvailable = $menuItemVariation->menuItem->modifiers->count();

        if ($modifiersAvailable) {
            $this->selectedModifierItem = $menuItemVariation->menu_item_id . '_' . $variationId;
            $this->showModifiersModal = true;
        } else {
            $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId] = $menuItemVariation;
            $this->cartItemQty[$menuItemVariation->menu_item_id] = isset($this->cartItemQty[$menuItemVariation->menu_item_id]) ? ($this->cartItemQty[$menuItemVariation->menu_item_id] + 1) : 1;
            $this->orderItemAmount[$menuItemVariation->menu_item_id . '_' . $variationId] = (1 * (isset($this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]) ? $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]->price : $this->orderItemList[$menuItemVariation->menu_item_id . '_' . $variationId]->price));
            $this->syncCart($menuItemVariation->menu_item_id . '_' . $variationId);
        }
    }

    #[On('setCustomer')]
    public function setCustomer($customer)
    {

        $customer = Customer::find($customer['id']);
        $this->customer = $customer;

        // For pickup orders, continue flow after customer is known
        if ($this->orderType === 'pickup' || $this->orderTypeSlug === 'pickup') {
            // If we still need mandatory customer details, show that modal first
            if (is_null($this->customer) || is_null($this->customer->name) || is_null($this->customer->phone)) {
                $this->customerName = $this->customer?->name;
                $this->customerPhone = $this->customer?->phone;
                $this->customerPhoneCode = $this->customer?->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();
                $this->showCustomerNameModal = true;
                return;
            }

            // Otherwise move to pickup date/time selection if not set
            if (empty($this->deliveryDateTime)) {
                $this->showPickupDateTimeModal = true;
            }
        }
        $this->customerId = $this->customer?->id; // Set customerId for loyalty integration trait

        // Load loyalty points if module is enabled (but don't auto-open modal - user clicks button)
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            try {
                if (module_enabled('Loyalty')) {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $restaurantId = restaurant()->id;
                    $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
                    $this->updateLoyaltyValues();
                }
            } catch (\Exception $e) {
                // Silently fail if module doesn't exist
            }
        }
    }

    /**
     * Open loyalty redemption modal and load loyalty values
     */
    public function openLoyaltyRedemptionModal()
    {
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            // Load loyalty points and values
            try {
                if (!module_enabled('Loyalty')) {
                    return;
                }

                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;

                // Get available points
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

                if ($this->availableLoyaltyPoints > 0) {
                    // Update loyalty values
                    $this->updateLoyaltyValues();

                    // Set default points to redeem
                    $this->pointsToRedeem = $this->minRedeemPoints > 0 ? $this->minRedeemPoints : ($this->maxRedeemablePoints > 0 ? $this->maxRedeemablePoints : 0);

                    // Open modal
                    $this->showLoyaltyRedemptionModal = true;
                } else {
                    $this->alert('info', __('loyalty::app.noPointsAvailable'), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            } catch (\Exception $e) {
                // Silently fail if module doesn't exist
            }
        }
    }

    /**
     * Close loyalty redemption modal and reset values if not redeemed
     */
    public function closeLoyaltyRedemptionModal()
    {
        // Only reset if points were not actually redeemed (i.e., just previewing)
        // If loyaltyPointsRedeemed is 0, it means redemption was never applied, so clear the discount
        if ($this->loyaltyPointsRedeemed == 0) {
            // Clear preview discount amount (this was just a preview, not actual redemption)
            $this->loyaltyDiscountAmount = 0;
            $this->pointsToRedeem = 0;
        }
        $this->showLoyaltyRedemptionModal = false;
    }

    /**
     * Redeem loyalty points for cart (before order is placed)
     */
    public function redeemLoyaltyPoints($points = null)
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            $this->alert('error', __('loyalty::app.loyaltyProgramNotEnabled'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Use points parameter if provided, otherwise use pointsToRedeem from input
        $pointsToRedeem = $points ?? $this->pointsToRedeem ?? 0;

        if ($pointsToRedeem <= 0) {
            $this->alert('error', __('loyalty::app.invalidPointsAmount'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Validate points
        if ($pointsToRedeem > $this->availableLoyaltyPoints) {
            $this->alert('error', __('loyalty::app.insufficientLoyaltyPointsAvailable'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Check if module service exists
        if (!module_enabled('Loyalty')) {
            $this->alert('error', 'Loyalty module is not available', [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = restaurant()->id;

            // Get loyalty settings
            if (!module_enabled('Loyalty')) {
                $this->alert('error', 'Loyalty module is not available', [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            if (!$settings || !$settings->isEnabled()) {
                $this->alert('error', __('loyalty::app.loyaltyProgramNotEnabled'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            // Check minimum redeem points
            if ($settings->min_redeem_points > 0 && $pointsToRedeem < $settings->min_redeem_points) {
                $this->alert('error', __('loyalty::app.minPointsRequired', ['min_points' => $settings->min_redeem_points]), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            // Calculate discount amount
            $valuePerPoint = $settings->value_per_point ?? 1;
            $pointsDiscount = $pointsToRedeem * $valuePerPoint;

            // Calculate max discount (percentage of subtotal)
            $subtotal = $this->subTotal ?? 0;
            $maxDiscountPercent = $settings->max_discount_percent ?? 0;
            $maxDiscountAmount = ($subtotal * $maxDiscountPercent) / 100;

            // Use the smaller of points discount or max discount
            $discountAmount = min($pointsDiscount, $maxDiscountAmount);

            // Set loyalty redemption values (will be applied when order is placed)
            $this->loyaltyPointsRedeemed = $pointsToRedeem;
            $this->loyaltyDiscountAmount = $discountAmount;

            // Close modal
            $this->showLoyaltyRedemptionModal = false;

            // Recalculate total with loyalty discount
            $this->calculateTotal();

            $this->alert('success', __('loyalty::app.pointsRedeemedSuccessfully'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to redeem loyalty points in Cart: ' . $e->getMessage());
            $this->alert('error', __('loyalty::app.failedToRedeemPoints'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    #[On('showCartItems')]
    public function showCartItems()
    {
        $this->showCart = true;
        $this->showMenu = false;
    }

    #[On('showMenuItems')]
    public function showMenuItems()
    {
        $this->showCart = false;
        $this->showMenu = true;
    }

    public function updatedPhoneCodeIsOpen($value)
    {
        if (!$value) {
            $this->reset(['phoneCodeSearch']);
            $this->updatedPhoneCodeSearch();
        }
    }

    public function updatedPhoneCodeSearch()
    {
        $this->filteredPhoneCodes = $this->allPhoneCodes->filter(function ($phonecode) {
            return str_contains($phonecode, $this->phoneCodeSearch);
        })->values();
    }

    public function selectPhoneCode($phonecode)
    {
        $this->customerPhoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    public function submitCustomerName()
    {
        $rules = [
            'customerName' => 'required',
            'customerPhoneCode' => 'required',
            'customerPhone' => [
                'required',
                Rule::unique('customers', 'phone')->ignore($this->customer->id ?? null),
            ],
        ];

        // Require address when order type is delivery
        if ($this->orderType === 'delivery' || $this->orderTypeSlug === 'delivery') {
            $rules['customerAddress'] = 'required';
        }

        $this->validate($rules);


        $this->customer->name = $this->customerName;
        $this->customer->phone = $this->customerPhone;
        $this->customer->phone_code = $this->customerPhoneCode;
        $this->customer->delivery_address = $this->customerAddress;
        $this->customer->save();

        session(['customer' => $this->customer]);
        $this->customerId = $this->customer->id; // Set customerId for loyalty integration trait
        $this->dispatch('setCustomer', customer: $this->customer);

        // Load loyalty points if module is enabled (but don't auto-open modal - user clicks button)
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
                $this->updateLoyaltyValues();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to load loyalty points in submitCustomerName: ' . $e->getMessage());
            }
        }

        $this->showCustomerNameModal = false;

        // For pickup orders, show pickup date/time modal after customer name is submitted
        if ($this->orderType == 'pickup' || $this->orderTypeSlug == 'pickup') {
            if (empty($this->deliveryDateTime)) {
                $this->showPickupDateTimeModal = true;
                return;
            }
        }

        $this->placeOrder($this->payNow);
    }

    public function selectTableOrder($tableID = null)
    {
        if ($this->getTable) {
            $this->tableID = $tableID;
            $this->getTable = false;
            $this->showTableModal = false;
            $this->placeOrder($this->payNow);
        }
    }

    public function getShouldShowWaiterButtonMobileProperty()
    {

        $this->dispatch('refreshComponent');

        if (!$this->restaurant->is_waiter_request_enabled || !$this->restaurant->is_waiter_request_enabled_on_mobile) {
            return false;
        }

        $cameFromQR = request()->query('hash') === $this->restaurant->hash || request()->boolean('from_qr');

        if ($this->restaurant->is_waiter_request_enabled_open_by_qr && !$cameFromQR) {
            return false;
        }

        return true;
    }

    /**
     * Check if customer can create order based on radius restrictions
     */
    public function getCanCreateOrderProperty()
    {
        // For QR orders, check if within radius
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            // If location is not set, allow for now (will be checked when adding items)
            if (empty($this->addressLat) || empty($this->addressLng)) {
                return true; // Allow to show UI, but will be blocked when adding itemss
            }

            // Return whether within radius
            return $this->is_within_radius ?? true;
        }

        // For non-QR orders or if no radius restriction, allow
        return true;
    }

    public function getAvailableTable()
    {
        $this->tables = Area::where('branch_id', $this->shopBranch->id)
            ->withCount([
                'tables' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->with([
                'tables' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->get();
    }

    public function updatedPickupDate()
    {
        $this->updateDeliveryDateTime();
    }

    public function updatedPickupTime()
    {
        $this->updateDeliveryDateTime();
    }

    private function updateDeliveryDateTime()
    {
        if ($this->pickupDate && $this->pickupTime) {
            $dateFormat = $this->restaurant->date_format ?? 'd-m-Y';
            try {
                $dateTime = \Carbon\Carbon::createFromFormat($dateFormat . ' H:i', $this->pickupDate . ' ' . $this->pickupTime);
                $this->deliveryDateTime = $dateTime->format('Y-m-d\TH:i');
            } catch (\Exception $e) {
                // If parsing fails, try to set a default
                $this->deliveryDateTime = now()->format('Y-m-d\TH:i');
            }
        }
    }

    public function savePickupDateTime()
    {
        $this->validate([
            'deliveryDateTime' => 'required|date',
        ]);

        $this->showPickupDateTimeModal = false;
        $this->placeOrder($this->payNow);
    }

    public function showPickupDateTime()
    {
        $this->showPickupDateTimeModal = true;
        $this->pickupDate = now()->format($this->restaurant->date_format ?? 'd-m-Y');
        $this->pickupTime = now()->format($this->restaurant->time_format ?? 'H:i');
        $this->updateDeliveryDateTime();
    }

    public function placeOrder($pay = false, $updateOrder = null, $method = null)
    {
        $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->shopBranch);

        if (!($availability['is_open'] ?? true)) {
            $this->alert('error', RestaurantAvailabilityService::getMessage($availability, $this->restaurant), [
                'toast' => false,
                'position' => 'center',
            ]);
            return;
        }

        // FINAL QR RADIUS CHECK - always validate fresh
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            // Check both lat/lng and latitude/longitude column names for branch
            $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
            $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

            if (!empty($branchLat) && !empty($branchLng)) {
                // Require customer coordinates for QR orders with radius configured
                if (empty($this->addressLat) || empty($this->addressLng)) {
                    $this->showLocationModal = true;
                    $this->alert('error', __('app.locationAccessRequiredRadius'), [
                        'toast' => false,
                        'position' => 'center',
                    ]);
                    return;
                }

                // Recalculate distance to ensure fresh check
                $distance = $this->calculateDistance(
                    $this->addressLat, $this->addressLng,
                    $branchLat, $branchLng
                );
                $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

                if (!$this->is_within_radius) {
                    $this->alert('error', __('app.orderNotAllowedMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                        'toast' => false,
                        'position' => 'center',
                    ]);
                    return;
                }
            }
        }

        // Restrict QR order if outside allowed radius (double-check flag)
        if ($this->cameFromQR && !$this->is_within_radius) {
            $this->alert('error', __('app.orderNotAllowedMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                'toast' => false,
                'position' => 'center',
            ]);
            return;
        }

        if ($updateOrder) {
            $this->order = Order::find($updateOrder);

            Payment::create([
                'order_id' => $this->order->id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => $method,
                'amount' => $this->total,
            ]);

            Order::where('id', $this->order->id)->update([
                'status' => 'pending_verification',
            ]);
            if($this->restaurant->auto_confirm_orders_after_payment){
            $this->printKot($this->order);
            }
            $this->sendNotifications($this->order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', [$this->order->uuid]));
            return;
        }

        if ($this->orderType == 'delivery') {
            $deliverySetting = $this->shopBranch->deliverySetting ?? null;
        }

        if ($this->customer && (is_null($this->customer->name) || ($this->orderType == 'delivery' && is_null($this->customerAddress)) && is_null($deliverySetting))) {
            $this->customerName = $this->customer->name;
            $this->customerAddress = $this->customer->delivery_address;
            $this->customerPhone = $this->customer->phone;
            $this->customerPhoneCode = $this->customer->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();
            $this->showCustomerNameModal = true;
            $this->payNow = $pay;
            return;
        }

        // Show customer name modal for pickup orders if customer name/phone is missing
        if (
            $this->customer &&
            (
                is_null($this->customer->name) ||
                (
                    ($this->orderType == 'pickup' || $this->orderTypeSlug == 'pickup') &&
                    is_null($this->customer->phone)
                )
            )
        ) {
            $this->customerName = $this->customer->name;
            $this->customerPhone = $this->customer->phone;
            $this->customerPhoneCode = $this->customer->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();
            $this->showCustomerNameModal = true;
            $this->payNow = $pay;
            return;
        }

        if ($this->customer && $this->orderType === 'delivery' && empty($this->addressLat) && empty($this->addressLng) && empty($this->deliveryAddress) && isset($deliverySetting)) {
            $this->customerAddress = $this->customer->delivery_address;
            $this->showDeliveryAddressModal = true;
            $this->payNow = $pay;
            return;
        }

        // Show pickup date/time modal for pickup orders if not already set
        if (($this->orderType == 'pickup' || $this->orderTypeSlug == 'pickup') && empty($this->deliveryDateTime)) {
            $this->payNow = $pay;
            $this->showPickupDateTimeModal = true;
            return;
        }

        if ($this->orderType == 'dine_in' && $this->getTable) {
            $this->getAvailableTable();
            $this->payNow = $pay;
            $this->showTableModal = true;
            return;
        }

        if (!is_null($this->tableID)) {
            $table = Table::where('hash', $this->tableID)->firstOrFail();
        }

        if ($this->order && ($this->order->status == 'kot' || $this->order->status == 'draft')) {
            $order = $this->order;
            if (!is_null($this->tableID)) {
                $order->update(['table_id' => $table->id]);
            }
        } else {
            $orderNumberData = Order::generateOrderNumber($this->shopBranch);

            // Use the already selected order type ID if available
            if ($this->orderTypeId) {
                $orderTypeModel = OrderType::find($this->orderTypeId);
                $orderTypeId = $orderTypeModel->id ?? null;
                $orderTypeName = $orderTypeModel->order_type_name ?? $this->orderType;
                // Ensure slug is set in case it wasn't already
                if (!$this->orderTypeSlug && $orderTypeModel) {
                    $this->orderTypeSlug = $orderTypeModel->slug;
                }
            } else {
                // Fallback to finding default order type
                $orderTypeModel = OrderType::where('is_default', 1)
                    ->where('type', $this->orderType)
                    ->first();

                $orderTypeId = $orderTypeModel->id ?? null;
                $orderTypeName = $orderTypeModel->order_type_name ?? $this->orderType;
                $this->orderTypeSlug = $orderTypeModel->slug ?? $this->orderType;
            }





            $orderData = [
                'order_number' => $orderNumberData['order_number'],
                'formatted_order_number' => $orderNumberData['formatted_order_number'],
                'branch_id' => $this->shopBranch->id,
                'table_id' => $table->id ?? null,
                'date_time' => now(),
                'customer_id' => $this->customer->id ?? null,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'order_type' => $this->orderTypeSlug ?? $this->orderType,
                'order_type_id' => $orderTypeId,
                'custom_order_type_name' => $orderTypeName,
                'pickup_date' => $this->deliveryDateTime,
                'delivery_address' => $this->customerAddress,
                'status' => $this->restaurant->auto_confirm_orders_before_payment ? 'kot' : 'draft',
                'order_status' => $this->restaurant->auto_confirm_orders_before_payment ? 'confirmed' : 'placed',
                'auto_confirm_orders_after_payment' => $this->restaurant->auto_confirm_orders_after_payment,
                'auto_confirm_orders_before_payment' => $this->restaurant->auto_confirm_orders_before_payment,
                'customer_lat' => $this->addressLat ?? null,
                'customer_lng' => $this->addressLng ?? null,
                'delivery_fee' => $this->deliveryFee ?? 0,
                'is_within_radius' => true,
                'delivery_started_at' => null,
                'delivered_at' => null,
                'estimated_eta_min' => $this->etaMin ?? null,
                'estimated_eta_max' => $this->etaMax ?? null,
                'placed_via' => 'shop',
                'tax_base' => $this->taxBase,
                'tax_mode' => $this->taxMode,
            ];

            // Add loyalty points redemption if module is enabled and points are redeemed
            if ($this->isLoyaltyEnabled() && $this->loyaltyPointsRedeemed > 0) {
                $orderData['loyalty_points_redeemed'] = $this->loyaltyPointsRedeemed;
                $orderData['loyalty_discount_amount'] = $this->loyaltyDiscountAmount;
            }

            $order = Order::create($orderData);
        }

        if ($this->customer && $this->orderType === 'delivery' && !empty($this->deliveryAddress) && isset($deliverySetting)) {
            $this->customer->delivery_address = $this->deliveryAddress;
            $this->customer->save();

            session(['customer' => $this->customer]);
        }

        $transactionId = uniqid('TXN_', true) . '_' . random_int(100000, 999999);

        session(['transaction_id' => $transactionId]);

        // CRITICAL: Create order_items FIRST so we can link kot_items to them
        // This ensures kot_items have price and amount from order_items
        $orderItems = [];
        
        // Only create KOT if there are items to add (new items for existing order, or all items for new order)
        $kot = null;
        if (!empty($this->orderItemList)) {
            $kot = Kot::create([
                'branch_id' => $this->shopBranch->id,
                'kot_number' => (Kot::generateKotNumber($this->shopBranch) + 1),
                'order_id' => $order->id,
                'order_type_id' => $order->order_type_id,
                'token_number' => Kot::generateTokenNumber($this->shopBranch->id, $order->order_type_id),
                'note' => $this->orderNote,
                'transaction_id' => $transactionId
            ]);
        }

        // Only create order items for new items (existing items remain untouched)
        foreach ($this->orderItemList ?? [] as $key => $value) {
            $menuItemId = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id;

            $menuItemVariationId = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null;

            $orderItem = OrderItem::create([
                'branch_id' => $this->shopBranch->id,
                'order_id' => $order->id,
                'menu_item_id' => $menuItemId,
                'menu_item_variation_id' => $menuItemVariationId,
                'quantity' => $this->orderItemQty[$key],
                'price' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price),
                'amount' => $this->orderItemAmount[$key],
                'transaction_id' => $transactionId,
                'note' => $this->itemNotes[$key] ?? null,
                // Add tax fields for item-level tax mode
                'tax_amount' => $this->orderItemTaxDetails[$key]['tax_amount'] ?? null,
                'tax_percentage' => $this->orderItemTaxDetails[$key]['tax_percent'] ?? null,
                'tax_breakup' => isset($this->orderItemTaxDetails[$key]['tax_breakup']) ? json_encode($this->orderItemTaxDetails[$key]['tax_breakup']) : null,
            ]);


            $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
            $orderItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);

            // Store order item for linking with kot_item
            $orderItems[$key] = $orderItem;
        }

        // Now create kot_items with price and amount from order_items (only for new items)
        if ($kot) {
            foreach ($this->orderItemList ?? [] as $key => $value) {
                $orderItem = $orderItems[$key] ?? null;

                // CRITICAL: Ensure order_item exists before creating kot_item
                if (!$orderItem || !$orderItem->id) {
                    Log::error('Missing order_item for key: ' . $key, [
                        'order_id' => $order->id,
                        'orderItemList_keys' => array_keys($this->orderItemList ?? []),
                        'orderItems_keys' => array_keys($orderItems),
                    ]);
                    continue; // Skip this kot_item if order_item is missing
                }

                // Get price and amount from order_item (which has the correct values)
                $itemPrice = $orderItem->price;
                $itemAmount = $orderItem->amount;

                $menuItemId = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id;

                $menuItemVariationId = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null;

                $kotItem = KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $orderItem->id, // Link to order_item - must exist
                    'menu_item_id' => $menuItemId,
                    'menu_item_variation_id' => $menuItemVariationId,
                    'quantity' => $this->orderItemQty[$key],
                    'price' => $itemPrice, // Copy from order_item
                    'amount' => $itemAmount, // Copy from order_item
                    'transaction_id' => $transactionId,
                    'note' => $this->itemNotes[$key] ?? null,
                ]);

                $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                $kotItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
            }
        }

        // Create order taxes BEFORE calculating totals (so they're available for calculation)
        if ($this->taxMode === 'order') {
            foreach ($this->taxes ?? [] as $key => $value) {
                OrderTax::firstOrCreate([
                    'order_id' => $order->id,
                    'tax_id' => $value->id
                ]);
            }
        }

        if ($this->orderID) {
            $order->extraCharges()->detach();
        }

        foreach ($this->extraCharges ?? [] as $key => $value) {
            if (!OrderCharge::where('order_id', $order->id)->where('charge_id', $value->id)->exists()) {
                OrderCharge::create([
                    'order_id' => $order->id,
                    'charge_id' => $value->id
                ]);
            }
        }

        // Reload order with all relationships
        $order->refresh();
        $order->load(['taxes.tax', 'items', 'charges.charge', 'kot.items.menuItem', 'kot.items.menuItemVariation', 'kot.items.modifierOptions']);

        // Recalculate totals using all KOT items for KOT orders to avoid overwriting previous totals
        $this->subTotal = 0;
        if ($order->status === 'kot' && $order->kot && $order->kot->count() > 0) {
            foreach ($order->kot as $kot) {
                foreach ($kot->items->where('status', '!=', 'cancelled') as $kotItem) {
                    if (!is_null($kotItem->amount)) {
                        $this->subTotal += (float)$kotItem->amount;
                        continue;
                    }

                    $menuItem = $kotItem->menuItem;
                    $variation = $kotItem->menuItemVariation;
                    $itemPrice = $variation ? ($variation->price ?? 0) : ($menuItem->price ?? 0);
                    $modifierPrice = $kotItem->modifierOptions ? $kotItem->modifierOptions->sum('price') : 0;
                    $this->subTotal += ($itemPrice + $modifierPrice) * ($kotItem->quantity ?? 1);
                }
            }
        } else {
            $this->subTotal = $order->items->sum('amount') ?? 0;
        }

        $discountedBase = $this->subTotal;
        $discountedBase -= ($order->discount_amount ?? 0);
        $discountedBase -= ($order->loyalty_discount_amount ?? 0);
        $discountedBase = max(0, (float)$discountedBase);

        // Step 1: Calculate service charges on discounted base
        $serviceTotal = 0;
        if ($order->charges && $order->charges->count() > 0) {
            foreach ($order->charges as $orderCharge) {
                $charge = $orderCharge->charge;
                if ($charge && method_exists($charge, 'getAmount')) {
                    $serviceTotal += $charge->getAmount($discountedBase);
                }
            }
        }

        // Step 2: Calculate tax base
        $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
        $this->taxBase = $includeChargesInTaxBase ? ($discountedBase + $serviceTotal) : $discountedBase;

        // Step 3: Calculate taxes
        $this->totalTaxAmount = 0;
        if ($this->taxMode === 'order') {
            foreach ($this->taxes ?? [] as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $this->taxBase;
                $this->totalTaxAmount += $taxAmount;
            }
        } else {
            if ($order->items && $order->items->count() > 0) {
                $this->totalTaxAmount = (float)($order->items->sum('tax_amount') ?? 0);
            } elseif ($order->kot && $order->kot->count() > 0) {
                $this->totalTaxAmount = (float)$order->kot->sum(function ($kot) {
                    return $kot->items->sum('tax_amount');
                });
            }
        }

        // Step 4: Build total
        $this->total = $discountedBase + $serviceTotal;
        if ($this->taxMode === 'order') {
            $this->total += $this->totalTaxAmount;
        } else {
            $isInclusive = $this->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $this->total += $this->totalTaxAmount;
            }
        }

        // Step 5: Add delivery and tip
        $this->total += (float)$this->deliveryFee ?: 0;
        $this->total += $order->tip_amount ?? 0;

        // Update order with calculated values
        Order::where('id', $order->id)->update([
            'sub_total' => round($this->subTotal, 2),
            'total' => round($this->total, 2),
            'total_tax_amount' => round($this->totalTaxAmount, 2),
            'tax_base' => $this->taxBase,
            'tax_mode' => $this->taxMode,
        ]);

        // Deduct loyalty points if module is enabled and points are redeemed
        // This happens AFTER taxes are calculated so we can recalculate totals correctly
        if ($this->isLoyaltyEnabled() && $this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0 && $order->customer_id) {
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

                if ($result['success']) {
                    // Reload order to get updated loyalty_discount_amount
                    $order->refresh();
                    $order->load(['taxes.tax', 'items', 'charges.charge', 'restaurant']);

                    // Recalculate total with loyalty discount
                    // Start fresh from item amounts to ensure correct calculation
                    $correctSubTotal = $order->items->sum('amount') ?? 0;
                    $correctTotal = $correctSubTotal;

                    // Apply discounts
                    $correctTotal -= ($order->discount_amount ?? 0);
                    $correctTotal -= ($order->loyalty_discount_amount ?? 0);
                    $discountedBase = $correctTotal;

                    // Calculate taxes on discounted amount (ensure float precision)
                    $correctTaxAmount = 0.0;
                    if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                        // Order-level taxes - calculate on discounted base
                        // IMPORTANT: Don't round individual tax amounts, only round the final sum
                        foreach ($order->taxes as $orderTax) {
                            $tax = $orderTax->tax ?? null;
                            if ($tax) {
                                $taxPercent = (float)($tax->tax_percent ?? 0);
                                // Calculate tax amount with full precision (no rounding)
                                $taxAmount = ($taxPercent / 100.0) * (float)$discountedBase;
                                // Add to running total with full precision
                                $correctTaxAmount += $taxAmount;
                                $correctTotal += $taxAmount; // Always add order-level taxes
                            }
                        }
                        // Round ONLY the final sum to 2 decimal places
                        $correctTaxAmount = round($correctTaxAmount, 2);
                    } else {
                        // Item-level taxes - sum from order items
                        $correctTaxAmount = $order->items->sum('tax_amount') ?? 0;
                        // Check if taxes are inclusive or exclusive
                        $isInclusive = ($order->restaurant->tax_inclusive ?? $this->restaurant->tax_inclusive ?? false);
                        if (!$isInclusive && $correctTaxAmount > 0) {
                            // For exclusive taxes, add to total
                            // CRITICAL: Always add exclusive taxes to ensure total includes tax
                            $correctTotal += $correctTaxAmount;
                        }
                        // For inclusive taxes, tax is already included in item prices (amount field)
                        // So we don't add it to total, but we still track it for total_tax_amount
                    }

                    // Apply extra charges (on discounted base)
                    if ($order->charges && $order->charges->count() > 0) {
                        foreach ($order->charges as $orderCharge) {
                            $charge = $orderCharge->charge;
                            if ($charge) {
                                $correctTotal += $charge->getAmount($discountedBase);
                            }
                        }
                    }

                    // Add tip and delivery
                    $correctTotal += ($order->tip_amount ?? 0);
                    $correctTotal += ($order->delivery_fee ?? 0);

                    // Update total and tax_amount in database
                    \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update([
                        'total' => round($correctTotal, 2),
                        'total_tax_amount' => round($correctTaxAmount, 2),
                    ]);

                    $order->refresh();
                    $this->total = $order->total;
                } else {
                    // Redemption failed - clear discount
                    $order->update([
                        'loyalty_points_redeemed' => 0,
                        'loyalty_discount_amount' => 0,
                    ]);
                    $this->resetLoyaltyRedemption();
                    $this->alert('error', $result['message'] ?? __('messages.loyaltyRedemptionFailed'), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to redeem loyalty points in Cart: ' . $e->getMessage());
                $order->update([
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_amount' => 0,
                ]);
                $this->resetLoyaltyRedemption();
            }
        }

        // Check if auto_confirm_orders_before_payment is enabled (use order value or fallback to restaurant setting)
        $autoConfirmBeforePayment = $order->auto_confirm_orders_before_payment ?? $this->restaurant->auto_confirm_orders_before_payment ?? false;

        // Only print KOT if a new KOT was created and order is confirmed
        if ($kot && $order->status != 'draft' && $autoConfirmBeforePayment) {
            $this->printKot($order, $kot);
        }

        event(new OrderUpdated($order, 'updated'));

        if (!is_null($this->tableID)) {
            $table->available_status = 'running';
            $table->saveQuietly();
        }

        if ($pay) {
            $this->showPaymentModal = true;
            $this->paymentOrder = $order;
        } else {
            Order::where('id', $order->id)->update([
                'status' => 'kot'
            ]);

            $this->sendNotifications($order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            cache()->forget('branch_' . $this->shopBranch->id . '_order_stats');
            $this->redirect(route('order_success', [$order->uuid]));
        }
    }

    public function initiatePayment($id)
    {
        $total = round($this->total, 2);

        $payment = RazorpayPayment::create([
            'order_id' => $id,
            'amount' => $total
        ]);

        $orderData = [
            'amount' => ($total * 100),
            'currency' => $this->restaurant->currency->currency_code
        ];

        $apiKey = $this->restaurant->paymentGateways->razorpay_key;
        $secretKey = $this->restaurant->paymentGateways->razorpay_secret;

        $api  = new Api($apiKey, $secretKey);
        $razorpayOrder = $api->order->create($orderData);
        $payment->razorpay_order_id = $razorpayOrder->id;
        $payment->save();

        $this->dispatch('paymentInitiated', payment: $payment);
    }

    public function initiateStripePayment($id)
    {
        $payment = StripePayment::create([
            'order_id' => $id,
            'amount' => $this->total
        ]);

        $this->dispatch('stripePaymentInitiated', payment: $payment);
    }

    #[On('razorpayPaymentCompleted')]
    public function razorpayPaymentCompleted($razorpayPaymentID, $razorpayOrderID, $razorpaySignature)
    {
        $payment = RazorpayPayment::where('razorpay_order_id', $razorpayOrderID)
            ->where('payment_status', 'pending')
            ->first();

        if ($payment) {
            $payment->razorpay_payment_id = $razorpayPaymentID;
            $payment->payment_status = 'completed';
            $payment->payment_date = now()->toDateTimeString();
            $payment->razorpay_signature = $razorpaySignature;
            $payment->save();

            $order = Order::find($payment->order_id);
            $order->amount_paid = $this->total;
            $order->status = 'paid';
            $order->save();

            Payment::create([
                'order_id' => $payment->order_id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => 'razorpay',
                'amount' => $payment->amount,
                'transaction_id' => $razorpayPaymentID
            ]);

            $this->sendNotifications($order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            // Check if order was placed via kiosk and redirect accordingly
            if ($order->placed_via === 'kiosk') {
                $this->redirect(route('kiosk.order-confirmation', $payment->order->uuid));
            } else {
                $this->redirect(route('order_success', $payment->order->uuid));
            }
        }
    }

    public function initiateFlutterwavePayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $apiSecret = $paymentGateway->flutterwave_secret;
            $amount = $this->total;
            $tx_ref = 'txn_' . time();

            $user = $this->customer ?? $this->restaurant;


            $data = [
                'tx_ref' => $tx_ref,
                'amount' => $amount,
                'currency' => $this->restaurant->currency->currency_code,
                'redirect_url' => route('flutterwave.success'),
                'payment_options' => 'card',
                'customer' => [
                    'email' => $user->email ?? 'no-email@example.com',
                    'name' => $user->name ?? 'Guest',
                    'phone_number' => $user->phone ?? '0000000000',
                ],
            ];
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiSecret",
                'Content-Type' => 'application/json'
            ])->post('https://api.flutterwave.com/v3/payments', $data);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                FlutterwavePayment::create([
                    'order_id' => $id,
                    'flutterwave_payment_id' => $tx_ref,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['link']);
            } else {
                return redirect()->route('flutterwave.failed')->withErrors(['error' => 'Payment initiation failed', 'message' => $responseData]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function initiatePaypalPayment($id)
    {
        $amount = $this->total;
        $currency = strtoupper($this->restaurant->currency->currency_code);

        $unsupportedCurrencies = ['INR'];
        if (in_array($currency, $unsupportedCurrencies)) {
            $order = Order::find($id);
            session()->flash('flash.banner', __('messages.paypalCurrencyNotSupported'));
            session()->flash('flash.bannerStyle', 'warning');
            return redirect()->route('order_success', $order->uuid ?? $id);
        }

        $clientId = $this->paymentGateway->paypal_payment_client_id;
        $secret = $this->paymentGateway->paypal_payment_secret;

        $paypalPayment = new PaypalPayment();
        $paypalPayment->order_id = $id;
        $paypalPayment->amount = $amount;
        $paypalPayment->payment_status = 'pending';
        $paypalPayment->save();

        $returnUrl = route('paypal.success');
        $cancelUrl = route('paypal.cancel');

        $paypalData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ],
                'reference_id' => (string)$paypalPayment->id
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];
        info('Paypal Data: ' . json_encode($paypalData));

        $auth = base64_encode("$clientId:$secret");

        $response = Http::withHeaders([
            'Authorization' => "Basic $auth",
            'Content-Type' => 'application/json'
        ])->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', $paypalData);

        if ($response->successful()) {
            $paypalResponse = $response->json();

            $paypalPayment->paypal_payment_id = $paypalResponse['id'];
            $paypalPayment->payment_status = 'pending';
            $paypalPayment->save();

            $approvalLink = collect($paypalResponse['links'])->firstWhere('rel', 'approve')['href'];
            return redirect($approvalLink);
        }
        $paypalPayment->payment_status = 'failed';
        $paypalPayment->save();

        return redirect()->route('paypal.cancel');
    }

    public function initiateEpayPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $amount = $this->total;
            $isSandbox = $paymentGateway->epay_mode === 'sandbox';

            $clientId = $isSandbox ? $paymentGateway->test_epay_client_id : $paymentGateway->epay_client_id;
            $clientSecret = $isSandbox ? $paymentGateway->test_epay_client_secret : $paymentGateway->epay_client_secret;
            $terminalId = $isSandbox ? $paymentGateway->test_epay_terminal_id : $paymentGateway->epay_terminal_id;

            $order = Order::find($id);
            if (!$order) {
                session()->flash('flash.banner', __('messages.orderNotFound'));
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->back();
            }

            if (!$clientId || !$clientSecret || !$terminalId) {
                session()->flash('flash.banner', __('messages.epayCredentialsNotConfigured'));
                session()->flash('flash.bannerStyle', 'warning');
                return redirect()->route('order_success', $order->uuid);
            }

            // Generate secret hash (random string for security)
            $secretHash = bin2hex(random_bytes(16));

            // Create payment record first to get unique ID
            $epayPayment = EpayPayment::create([
                'order_id' => $id,
                'amount' => $amount,
                'payment_status' => 'pending',
                'epay_secret_hash' => $secretHash,
            ]);

            // Generate unique invoice ID for THIS payment attempt - must be 6-15 digits
            // Use payment ID + timestamp to ensure uniqueness across all attempts
            // Format: payment_id (padded) + last 4 digits of timestamp = always unique
            $paymentIdStr = (string)$epayPayment->id;
            $timestampSuffix = substr((string)time(), -4); // Last 4 digits of timestamp
            $invoiceIdBase = $paymentIdStr . $timestampSuffix;

            // Ensure it's between 6-15 digits as per Epay requirements
            if (strlen($invoiceIdBase) < 6) {
                // Pad with zeros to reach minimum 6 digits
                $invoiceId = str_pad($invoiceIdBase, 6, '0', STR_PAD_LEFT);
            } elseif (strlen($invoiceIdBase) > 15) {
                // Truncate to 15 digits if too long
                $invoiceId = substr($invoiceIdBase, -15);
            } else {
                $invoiceId = $invoiceIdBase;
            }

            // Update payment record with invoice ID
            $epayPayment->epay_invoice_id = $invoiceId;
            $epayPayment->save();

            // Get access token with payment details - returns full token object
            $tokenResponse = $this->getEpayAccessToken($paymentGateway, $isSandbox, $invoiceId, $secretHash, $amount);
            if (!$tokenResponse || !isset($tokenResponse['access_token'])) {
                $epayPayment->payment_status = 'failed';
                $epayPayment->save();
                $order = Order::find($id);
                session()->flash('flash.banner', __('messages.epayFailedToAuthenticate'));
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->route('order_success', $order->uuid ?? $id);
            }

            // Store full token response as JSON in payment record
            $epayPayment->epay_access_token = json_encode($tokenResponse);
            $epayPayment->save();

            // Store invoiceId in session for success/cancel callbacks (Epay doesn't always send it in redirect)
            session([
                'epay_invoice_id' => $invoiceId,
                'epay_order_id' => $id,
                'epay_payment_id' => $epayPayment->id,
            ]);

            // Reload payment with all relationships for JavaScript
            $epayPayment->load(['order.customer']);

            // Dispatch event to trigger payment directly on current page (like Razorpay)
            $this->dispatch('epayPaymentInitiated', payment: $epayPayment);
        } catch (\Exception $e) {
            Log::error('Epay Payment Initiation Error: ' . $e->getMessage());
            $order = Order::find($id);
            session()->flash('flash.banner', __('messages.paymentInitiationFailedWithError', ['message' => $e->getMessage()]));
            session()->flash('flash.bannerStyle', 'danger');
            return redirect()->route('order_success', $order->uuid ?? $id);
        }
    }

    private function getEpayAccessToken($paymentGateway, $isSandbox, $invoiceId, $secretHash, $amount)
    {
        $clientId = $isSandbox ? $paymentGateway->test_epay_client_id : $paymentGateway->epay_client_id;
        $clientSecret = $isSandbox ? $paymentGateway->test_epay_client_secret : $paymentGateway->epay_client_secret;
        $terminalId = $isSandbox ? $paymentGateway->test_epay_terminal_id : $paymentGateway->epay_terminal_id;

        if (!$clientId || !$clientSecret || !$terminalId) {
            session()->flash('flash.banner', __('messages.epayCredentialsNotConfigured'));
            session()->flash('flash.bannerStyle', 'warning');
            return null;
        }

        // Correct token URL according to documentation
        $tokenUrl = $isSandbox
            ? 'https://test-epay-oauth.epayment.kz/oauth2/token'
            : 'https://epay-oauth.homebank.kz/oauth2/token';

        $currency = strtoupper($this->restaurant->currency->currency_code);
        $postLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);
        $failurePostLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'scope' => 'payment',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'invoiceID' => $invoiceId,
            'secret_hash' => $secretHash,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'terminal' => $terminalId,
            'postLink' => $postLink,
            'failurePostLink' => $failurePostLink,
        ]);

        if ($response->successful()) {
            $tokenData = $response->json();
            // Return the complete token object, not just access_token
            // The auth field in halyk.pay() expects the full token response
            return $tokenData;
        }

        $errorResponse = $response->json();
        Log::error('Epay Token Error: ' . json_encode($errorResponse));
        session()->flash('flash.banner', __('messages.epayFailedToAuthenticateCheckCredentials'));
        session()->flash('flash.bannerStyle', 'danger');
        return null;
    }

    function generateSignature($data, $passPhrase)
    {
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }

        return md5($getString);
    }

    public function initiatePayfastPayment($id)
    {
        $paymentGateway = $this->restaurant->paymentGateways;
        $isSandbox = $paymentGateway->payfast_mode === 'sandbox';
        $merchantId = $isSandbox ? $paymentGateway->test_payfast_merchant_id : $paymentGateway->payfast_merchant_id;
        $merchantKey = $isSandbox ? $paymentGateway->test_payfast_merchant_key : $paymentGateway->payfast_merchant_key;
        $passphrase = $isSandbox ? $paymentGateway->test_payfast_passphrase : $paymentGateway->payfast_passphrase;
        $amount = number_format($this->total, 2, '.', '');
        $itemName = "Order Payment #$id";
        $reference = 'pf_' . time();
        $data = [
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => route('payfast.success', ['reference' => $reference]),
            'cancel_url' => route('payfast.failed', ['reference' => $reference]),
            'notify_url' => route('payfast.notify', ['company' => $this->restaurant->hash, 'reference' => $reference]),

            'name_first' => auth()->user()->name,
            'email_address' => auth()->user()->email,
            'm_payment_id' => $id, // Your internal ID
            'amount' => $amount,
            'item_name' => $itemName,
        ];


        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        AdminPayfastPayment::create([
            'order_id' => $id,
            'payfast_payment_id' => $reference,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        $payfastBaseUrl = $isSandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://api.payfast.co.za/eng/process';
        $redirectUrl = $payfastBaseUrl . '?' . http_build_query($data);
        return redirect($redirectUrl);
    }

    public function initiatePaystackPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;

            $secretKey = $paymentGateway->paystack_secret_data;
            $user = auth()->user();
            $amount = $this->total;
            $reference = 'psk_' . time();
            $data = [
                'reference' => $reference,
                'amount' => (int)($amount * 100), // Paystack expects amount in kobo
                'email' => $user->email ?? 'guest@example.com',
                'currency' => $this->restaurant->currency->currency_code,
                'callback_url' => route('paystack.success'),
                'metadata' => [
                    'cancel_action' => route('paystack.failed', ['reference' => $reference])
                ]

            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer $secretKey",
                'Content-Type' => 'application/json'
            ])->post('https://api.paystack.co/transaction/initialize', $data);

            $responseData = $response->json();
            if (isset($responseData['status']) && $responseData['status'] === true) {
                AdminPaystackPayment::create([
                    'order_id' => $id,
                    'paystack_payment_id' => $reference,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['authorization_url']);
            } else {

                session()->flash('error', __('messages.paymentInitiationFailed'));
                return redirect()->route('paystack.failed');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function initiateXenditPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $secretKey = $paymentGateway->xendit_secret_key;
            $amount = $this->total;
            $externalId = 'xendit_' . time();

            $user = $this->customer ?? auth()->user();

            $data = [
                'external_id' => $externalId,
                'amount' => $amount,
                'description' => 'Order Payment #' . $id,
                'currency' => 'PHP',
                'success_redirect_url' => route('xendit.success', ['external' => $externalId]),
                'failure_redirect_url' => route('xendit.failed'),
                'payment_methods' => ['CREDIT_CARD', 'BCA', 'BNI', 'BSI', 'BRI', 'MANDIRI', 'OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY'],
                'should_send_email' => true,
                'customer' => [
                    'given_names' => $user->name ?? 'Guest',
                    'email' => $user->email ?? 'guest@example.com',
                    'mobile_number' => $user->phone ?? '+6281234567890',
                ],
                'items' => [
                    [
                        'name' => 'Order #' . $id,
                        'quantity' => 1,
                        'price' => $amount,
                        'category' => 'FOOD_AND_BEVERAGE'
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $data);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                XenditPayment::create([
                    'order_id' => $id,
                    'xendit_payment_id' => $externalId,
                    'xendit_invoice_id' => $responseData['id'],
                    'xendit_external_id' => $externalId,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['invoice_url']);
            } else {
                session()->flash('error', __('messages.xenditPaymentInitiationFailed', ['message' => $responseData['message'] ?? 'Unknown error']));
                return redirect()->route('xendit.failed');
            }
        } catch (\Exception $e) {
            session()->flash('error', __('messages.xenditPaymentError', ['message' => $e->getMessage()]));
            return redirect()->route('xendit.failed');
        }
    }

    public function initiateMolliePayment($id)
    {
        try {

            $paymentGateway = $this->restaurant->paymentGateways;
            $isSandbox = $paymentGateway->mollie_mode === 'test';
            $apiKey = $isSandbox ? $paymentGateway->test_mollie_key : $paymentGateway->live_mollie_key;
            $amount = $this->total;
            $currency = $this->restaurant->currency->currency_code;
            // Initialize Mollie API client
            $mollie = new MollieApiClient();
            $mollie->setApiKey($apiKey);

            // Format amount - Mollie expects amount in smallest currency unit (e.g., cents for EUR)
            // Format as string with 2 decimal places
            $amountValue = number_format($amount, 2, '.', '');
            // Create payment
            $payment = $mollie->payments->create([

                "amount" => [
                    "currency" => $currency,
                    "value" => $amountValue,
                ],
                "description" => "Order Payment #" . $id,
                "redirectUrl" => route('mollie.success', ['order_id' => $id]),
                // Pass restaurant hash using expected route parameter name
                "webhookUrl"  => route('mollie.webhook', ['hash' => $this->restaurant->hash]),

            ]);


            // Store payment record
            AdminMolliePayment::create([
                'order_id' => $id,
                'mollie_payment_id' => $payment->id,
                'amount' => $amount,
                'payment_status' => 'pending',
            ]);


            // Redirect to Mollie checkout page
            return redirect($payment->getCheckoutUrl());


        } catch (\Exception $e) {
            Log::error('Mollie payment error: ' . $e->getMessage());

        }
    }

    public function initiateTapPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $amount = $this->total;
            $isSandbox = $paymentGateway->tap_mode === 'sandbox';

            $secretKey = $isSandbox ? $paymentGateway->test_tap_secret_key : $paymentGateway->live_tap_secret_key;
            $publicKey = $isSandbox ? $paymentGateway->test_tap_public_key : $paymentGateway->live_tap_public_key;
            $merchantId = $paymentGateway->tap_merchant_id;

            $order = Order::find($id);
            if (!$order) {
                session()->flash('flash.banner', __('messages.orderNotFound'));
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->back();
            }

            if (!$secretKey || !$publicKey || !$merchantId) {
                session()->flash('flash.banner', __('messages.tapCredentialsNotConfigured'));
                session()->flash('flash.bannerStyle', 'warning');
                return redirect()->route('order_success', $order->uuid);
            }

            $currency = strtoupper($this->restaurant->currency->currency_code);
            $customer = $this->customer ?? $order->customer;

            // Create payment record first
            $tapPayment = TapPayment::create([
                'order_id' => $id,
                'amount' => $amount,
                'payment_status' => 'pending',
            ]);

            // Prepare charge data for Tap Charge API
            $chargeData = [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'threeDSecure' => true,
                'save_card' => false,
                'description' => 'Order Payment #' . $id,
                'statement_descriptor' => 'Order #' . $id,
                'metadata' => [
                    'udf1' => 'Order ID: ' . $id,
                    'udf2' => 'Restaurant: ' . $this->restaurant->name,
                ],
                'reference' => [
                    'transaction' => 'txn_' . $id,
                    'order' => 'ord_' . $id,
                ],
                'receipt' => [
                    'email' => false,
                    'sms' => false,
                ],
                'customer' => [
                    'first_name' => $customer->name ?? 'Guest',
                    'email' => $customer->email ?? 'guest@example.com',
                    'phone' => [
                        'country_code' => $customer->phone_code ?? '966',
                        'number' => $customer->phone ?? '000000000',
                    ],
                ],
                'merchant' => [
                    'id' => $merchantId,
                ],
                'source' => [
                    'id' => 'src_all',
                ],
                'redirect' => [
                    'url' => route('tap.success', ['order_id' => $id]),
                ],
                'post' => [
                    'url' => route('tap.webhook', ['hash' => $this->restaurant->hash]),
                ],
            ];

            // Make API call to Tap Charge API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.tap.company/v2/charges', $chargeData);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                // Update payment record with charge ID
                $tapPayment->tap_payment_id = $responseData['id'];
                $tapPayment->save();

                // Store order ID in session for fallback
                session(['tap_order_id' => $id]);

                $checkoutUrl = $responseData['transaction']['url'] ?? null;

                if ($checkoutUrl) {
                    return redirect()->away($checkoutUrl);
                } else {
                    if (isset($responseData['status']) && $responseData['status'] === 'CAPTURED') {
                        return redirect()->route('tap.success', ['order_id' => $id, 'tap_id' => $responseData['id']]);
                    } else {
                        session()->flash('flash.banner', __('messages.paymentInitiationFailedTryAgain'));
                        session()->flash('flash.bannerStyle', 'danger');
                        return redirect()->route('order_success', $order->uuid);
                    }
                }
            } else {
                // Payment initiation failed
                $tapPayment->payment_status = 'failed';
                $tapPayment->payment_error_response = $responseData;
                $tapPayment->save();

                $errorMessage = $responseData['errors'][0]['message'] ?? __('messages.paymentInitiationFailedTryAgain');
                session()->flash('flash.banner', $errorMessage);
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->route('order_success', $order->uuid);
            }
        } catch (\Exception $e) {
            Log::error('Tap Payment Initiation Error: ' . $e->getMessage());
            $order = Order::find($id);
            session()->flash('flash.banner', __('messages.paymentInitiationFailedWithError', ['message' => $e->getMessage()]));
            session()->flash('flash.bannerStyle', 'danger');
            return redirect()->route('order_success', $order->uuid ?? $id);
        }
    }

    public function hidePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->showQrCode = false;
        $this->showPaymentDetail = false;
        $this->selectedOfflinePaymentMethod = null;
        Order::where('id', $this->paymentOrder->id)->where('status', 'draft')->delete();

        Kot::where('transaction_id', session('transaction_id'))->delete();
        KotItem::where('transaction_id', session('transaction_id'))->delete();
        OrderItem::where('transaction_id', session('transaction_id'))->delete();

        session()->forget('transaction_id');

        $this->paymentOrder = null;
    }

    public function sendNotifications($order)
    {
        NewOrderCreated::dispatch($order);

        SendNewOrderReceived::dispatch($order);
        if ($order->customer_id) {
            try {
                $order->customer->notify(new SendOrderBill($order));
            } catch (\Exception $e) {
                Log::error('Error sending order bill email: ' . $e->getMessage());
            }
        }
    }

    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function togglePaymenntDetail()
    {
        $this->showPaymentDetail = !$this->showPaymentDetail;
    }

    /**
     * Select an offline payment method (bank transfer / cash etc) and show its description.
     * The actual order placement happens when the user clicks the modal footer "paymentDone" button.
     */
    public function selectOfflinePaymentMethod($method)
    {
        if (empty($method)) {
            return;
        }

        $this->selectedOfflinePaymentMethod = $method;
        $this->showQrCode = false;
        $this->showPaymentDetail = true;
    }

    #[On('closeModifiersModal')]
    public function closeModifiersModal()
    {
        $this->selectedModifierItem = null;
        $this->showModifiersModal = false;
    }

    #[On('setPosModifier')]
    public function setPosModifier($modifierIds)
    {
        // Check radius restriction before adding items with modifiers
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        $this->showModifiersModal = false;

        $sortNumber = Str::of(implode('', Arr::flatten($modifierIds)))
            ->split(1)->sort()->implode('');

        $keyId = $this->selectedModifierItem . '-' . $sortNumber;

        if (isset(explode('_', $this->selectedModifierItem)[1])) {
            $menuItemVariation = MenuItemVariation::find(explode('_', $this->selectedModifierItem)[1]);

            // Set price context on variation
            if ($this->orderTypeId) {
                $menuItemVariation->setPriceContext($this->orderTypeId, null);
            }

            $this->orderItemVariation[$keyId] = $menuItemVariation;
            $this->selectedModifierItem = explode('_', $this->selectedModifierItem)[0];
            $this->orderItemAmount[$keyId] = 1 * ($this->orderItemVariation[$keyId]->price ?? $this->orderItemList[$keyId]->price);
        }

        $this->cartItemQty[$keyId] = ($this->cartItemQty[$keyId] ?? 0) + 1;
        $this->itemModifiersSelected[$keyId] = Arr::flatten($modifierIds);

        // Set price context on modifiers before calculating total
        $modifierTotal = 0;
        foreach ($this->itemModifiersSelected[$keyId] ?? [] as $modifierId) {
            $modifier = ModifierOption::find($modifierId);
            if ($modifier) {
                if ($this->orderTypeId) {
                    $modifier->setPriceContext($this->orderTypeId, null);
                }
                $modifierTotal += $modifier->price;
            }
        }

        $this->orderItemModifiersPrice[$keyId] = $modifierTotal;

        $this->syncCart($keyId);
    }

    public function getModifierOptionsProperty()
    {
        return ModifierOption::whereIn('id', collect($this->itemModifiersSelected)->flatten()->all())->get()->keyBy('id');
    }

    public function showItemDetail($id)
    {
        // Load counts so the detail modal button behaves like the item card
        $this->selectedItem = MenuItem::withCount(['variations', 'modifierGroups'])->find($id);
        $this->showItemDetailModal = true;
    }

    #[On('selectedDeliveryDetails')]
    public function handleSelectedDeliveryDetails($details)
    {
        $this->addressLat = $details['lat'] ?? null;
        $this->addressLng = $details['lng'] ?? null;
        $this->deliveryAddress = $details['address'] ?? null;
        $this->deliveryFee = $details['deliveryFee'] ?? null;
        $this->etaMin = $details['eta_min'];
        $this->etaMax = $details['eta_max'];

        $this->calculateMaxPreparationTime();
        $this->calculateTotal();
        $this->showDeliveryAddressModal = false;
    }

    public function calculateMaxPreparationTime()
    {
        $this->maxPreparationTime = !empty($this->orderItemList) ? max(array_map(fn($item) => $item->preparation_time ?? 0, $this->orderItemList)) : 0;
    }

    // Centralized tax calculation methods to eliminate code duplication
    private function recalculateTaxTotals($taxBase = null)
    {
        $this->totalTaxAmount = 0;

        if ($this->taxMode === 'order') {
            // Order-based taxation: calculate on tax_base (net + service_total)
            $baseForTax = $taxBase ?? $this->subTotal;

            foreach ($this->taxes ?? [] as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $baseForTax;
                $this->totalTaxAmount += $taxAmount;
                $this->total += $taxAmount;
            }
        } elseif ($this->taxMode === 'item' && !empty($this->orderItemAmount)) {
            // Item-based taxation - taxes are already calculated in calculateTotal()
            $totalInclusiveTax = 0;
            $totalExclusiveTax = 0;
            $isInclusive = $this->restaurant->tax_inclusive ?? false;

            // Calculate total tax amounts
            foreach ($this->orderItemTaxDetails ?? [] as $itemTaxDetail) {
                $taxAmount = $itemTaxDetail['tax_amount'] ?? 0;

                if ($isInclusive) {
                    $totalInclusiveTax += $taxAmount;
                } else {
                    $totalExclusiveTax += $taxAmount;
                }
            }

            $this->totalTaxAmount = $totalInclusiveTax + $totalExclusiveTax;

            // For exclusive taxes, add them to the total
            // (Inclusive taxes are already included in the item prices)
            if ($totalExclusiveTax > 0) {
                $this->total += $totalExclusiveTax;
            }
        }
    }

    public function updateOrderItemTaxDetails()
    {
        $this->orderItemTaxDetails = [];

        if ($this->taxMode !== 'item' || !is_array($this->orderItemAmount)) {
            return;
        }

        foreach ($this->orderItemAmount as $key => $value) {
            $menuItem = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menuItem : $this->orderItemList[$key];

            // Set price context before using prices
            if ($this->orderTypeId) {
                $menuItem->setPriceContext($this->orderTypeId, null);
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
                }
            }

            $qty = $this->orderItemQty[$key] ?? 1;
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $menuItem->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
            $itemPriceWithModifiers = $basePrice + $modifierPrice;
            $taxes = $menuItem->taxes ?? collect();
            $isInclusive = $this->restaurant->tax_inclusive;
            $taxResult = MenuItem::calculateItemTaxes($itemPriceWithModifiers, $taxes, $isInclusive);
            $this->orderItemTaxDetails[$key] = [
                'tax_amount' => $taxResult['tax_amount'] * $qty,
                'tax_percent' => $taxResult['tax_percentage'],
                'tax_breakup' => $taxResult['tax_breakdown'],
                'tax_type' => $taxResult['inclusive'],
                'base_price' => $itemPriceWithModifiers,
                'display_price' => $isInclusive ? ($itemPriceWithModifiers - ($taxResult['tax_amount'] ?? 0)) : $itemPriceWithModifiers,
                'qty' => $qty,
            ];
        }
    }

    /**
     * Get the display price for an item (base price without tax for inclusive items)
     */
    public function getItemDisplayPrice($key)
    {
        if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
            return $this->orderItemTaxDetails[$key]['display_price'] ?? 0;
        }

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$key])) {
                $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$key])) {
                $this->orderItemList[$key]->setPriceContext($this->orderTypeId, null);
            }
        }

        // For non-item tax mode, return the original price
        $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $this->orderItemList[$key]->price;
        $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
        return $basePrice + $modifierPrice;
    }

    #[Computed]
    public function getMenuItemsProperty()
    {
        $locale = session('locale', app()->getLocale());

        $query = MenuItem::select('menu_items.*', 'item_categories.category_name')
            ->join('item_categories', 'menu_items.item_category_id', '=', 'item_categories.id')
            ->where('menu_items.branch_id', $this->shopBranch->id)
            ->where('show_on_customer_site', true);

        if (!empty($this->filterCategories)) {
            $query->where('menu_items.item_category_id', $this->filterCategories);
        }

        // Filter menu items by table assignment when user came from QR.
        // If the table has assigned menus, show only those menus.
        // If the table does NOT have any assigned menu, show all menus.
        if ($this->cameFromQR && $this->table && $this->table->id) {
            $assignedMenuIds = DB::table('menu_table')
                ->where('table_id', $this->table->id)
                ->where('is_active', true)
                ->pluck('menu_id')
                ->toArray();

            if (!empty($assignedMenuIds)) {
                $query->whereIn('menu_items.menu_id', $assignedMenuIds);
            }
        }

        if (!empty($this->menuId)) {
            $query->where('menu_items.menu_id', $this->menuId);
        }

        if ($this->showVeg == 1) {
            $query->where('menu_items.type', 'veg');
        }

        if ($this->showHalal == 1) {
            $query->where('menu_items.type', 'halal');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('translations', function ($q) {
                        $q->where('item_name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply limit BEFORE loading heavy relationships
        $items = $query->orderBy('item_categories.sort_order')
            ->orderBy('menu_items.item_category_id')
            ->orderBy('menu_items.sort_order')
            ->limit($this->menuItemsLoaded)
            ->get();

        // Load relationships and counts only on the limited items
        $items->load('category');
        $items->loadCount(['variations', 'modifierGroups']);

        // Group by category
        $groupedItems = $items->groupBy(function ($item) use ($locale) {
            return $item->category->getTranslation('category_name', $locale);
        });

        // Set price context on menu items in the query results
        if ($this->orderTypeId) {
            foreach ($groupedItems as $categoryItems) {
                foreach ($categoryItems as $item) {
                    $item->setPriceContext($this->orderTypeId, null);
                    // Set price context on variations
                    if ($item->relationLoaded('variations')) {
                        foreach ($item->variations as $variation) {
                            $variation->setPriceContext($this->orderTypeId, null);
                        }
                    }
                }
            }
        }

        return $groupedItems;
    }

    #[Computed]
    public function getTotalMenuItemsCountProperty()
    {
        $query = MenuItem::where('branch_id', $this->shopBranch->id)
            ->where('show_on_customer_site', true);

        // Filter menu items by table assignment when user came from QR
        // If the table has assigned menus, show only those menus.
        // If the table does NOT have any assigned menu, show all menus.
        if ($this->cameFromQR && $this->table && $this->table->id) {
            $assignedMenuIds = DB::table('menu_table')
                ->where('table_id', $this->table->id)
                ->where('is_active', true)
                ->pluck('menu_id')
                ->toArray();

            if (!empty($assignedMenuIds)) {
                $query->whereIn('menu_id', $assignedMenuIds);
            }
            // If no menus assigned, show all items (don't filter)
        }

        if (!empty($this->filterCategories)) {
            $query->where('item_category_id', $this->filterCategories);
        }

        if (!empty($this->menuId)) {
            $query->where('menu_id', $this->menuId);
        }

        if ($this->showVeg == 1) {
            $query->where('type', 'veg');
        }

        if ($this->showHalal == 1) {
            $query->where('type', 'halal');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('translations', function ($q) {
                        $q->where('item_name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->count();
    }

    #[Computed]
    public function getAllItemsLoadedProperty()
    {
        return $this->menuItemsLoaded >= $this->totalMenuItemsCount;
    }

    public function loadMoreMenuItems()
    {
        if ($this->allItemsLoaded) {
            return;
        }

        $this->menuItemsLoaded += $this->menuItemsPerLoad;
    }

    #[Computed]
    public function getCategoryListProperty()
    {
        return ItemCategory::withoutGlobalScopes()
            ->withCount(['items as items_count' => function ($query) {
                $query->where('menu_items.is_available', 1);

                if (!empty($this->menuId)) {
                    $query->where('menu_items.menu_id', $this->menuId);
                }

                if ($this->showVeg == 1) {
                    $query->where('menu_items.type', 'veg');
                }

                if ($this->showHalal == 1) {
                    $query->where('menu_items.type', 'halal');
                }
            }])
            ->where('branch_id', $this->shopBranch->id)
            ->having('items_count', '>', 0)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function getMenuListProperty()
    {
        $query = Menu::withoutGlobalScopes()
            ->where('branch_id', $this->shopBranch->id);

        // Filter menus by table assignment when user came from QR
        // If the table has assigned menus, show only those menus.
        // If the table does NOT have any assigned menu, show all menus.
        if ($this->cameFromQR && $this->table && $this->table->id) {
            $assignedMenuIds = DB::table('menu_table')
                ->where('table_id', $this->table->id)
                ->where('is_active', true)
                ->pluck('menu_id')
                ->toArray();

            if (!empty($assignedMenuIds)) {
                $query->whereIn('id', $assignedMenuIds);
            }
            // If no menus assigned, show all menus (don't filter)
        }

        return $query->withCount('items')

            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function getOrderTypesProperty()
    {
        return OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->where('enable_from_customer_site', true)
            ->get();
    }

    public function render()
    {
        $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->shopBranch);

        return view('livewire.shop.cart', [
            'orderTypes' => $this->orderTypes,
            'phonecodes' => $this->filteredPhoneCodes,
            'isRestaurantOpenForOrders' => (bool) ($availability['is_open'] ?? true),
            'restaurantClosedMessage' => RestaurantAvailabilityService::getMessage($availability, $this->restaurant),
        ]);
    }


    public function printKot($order, $kot = null, $kotIds = [])
    {
        // Check if the 'kitchen' package is enabled
        if (in_array('Kitchen', restaurant_modules()) && in_array('kitchen', custom_module_plugins())) {
            // Get all KOTs for this order (created above)

            if ($kotIds) {
                $kots = $order->kot()->whereIn('id', $kotIds)->with('items')->get();
            } else {
                $kots = $order->kot()->with('items')->get();
            }

            foreach ($kots as $kot) {
                $kotPlaceItems = [];

                foreach ($kot->items as $kotItem) {
                    if ($kotItem->menuItem && $kotItem->menuItem->kot_place_id) {
                        $kotPlaceId = $kotItem->menuItem->kot_place_id;

                        if (!isset($kotPlaceItems[$kotPlaceId])) {
                            $kotPlaceItems[$kotPlaceId] = [];
                        }

                        $kotPlaceItems[$kotPlaceId][] = $kotItem;
                    }
                }

                // Get the kot places and their printer settings
                $kotPlaceIds = array_keys($kotPlaceItems);
                $kotPlaces = KotPlace::with('printerSetting')->whereIn('id', $kotPlaceIds)->get();

                foreach ($kotPlaces as $kotPlace) {
                    $printerSetting = $kotPlace->printerSetting;

                    if ($printerSetting && $printerSetting->is_active == 0) {
                        $printerSetting = Printer::where('is_default', true)->first();
                    }

                    // If no printer is set, fallback to print URL dispatch
                    if (!$printerSetting) {
                        $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                        $this->dispatch('print_location', $url);
                        continue;
                    }

                    try {
                        switch ($printerSetting->printing_choice) {
                            case 'directPrint':
                                $this->handleKotPrint($kot->id, $kotPlace->id);
                                break;
                            default:
                        }
                    } catch (\Throwable $e) {
                        $this->alert('error', __('messages.printerNotConnected') . ' ' . $e->getMessage(), [
                            'toast' => true,
                            'position' => 'top-end',
                            'showCancelButton' => false,
                            'cancelButtonText' => __('app.close')
                        ]);
                    }
                }
            }
        } else {
            $kotPlace = KotPlace::where('is_default', 1)->first();
            $printerSetting = $kotPlace->printerSetting;

            // Get the KOT for this order
            $kot = $kot ?? $order->kot()->first();

            // If no printer is set, fallback to print URL dispatch
            if (!$printerSetting) {
                $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                $this->dispatch('print_location', $url);
            }

            try {
                switch ($printerSetting->printing_choice) {
                    case 'directPrint':
                        $this->handleKotPrint($kot->id, $kotPlace->id);
                        break;

                    default:
                }
            } catch (\Throwable $e) {
                $this->alert('error', __('messages.printerNotConnected') . ' ' . $e->getMessage(), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            }
        }
    }

}
