<?php

namespace App\Http\Controllers;

use App\Models\DeliveryExecutive;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use App\Services\RestaurantAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Services\LoyaltyService;
use App\Scopes\BranchScope;
use App\Models\KotCancelReason;
use App\Models\KotPlace;
use App\Models\Menu;
use App\Models\OrderType;
use App\Models\Tax;
use App\Models\DeliveryPlatform;

class PosController extends Controller
{

    public function posvue()
    {
        return view('pos.posvue');
    }

    public function ordervue($id)
    {
        return view('pos.ordervue', compact('id'));
    }

    public function kotvue($id)
    {
        return view('pos.posvue', compact('id'));
    }

    public function assignedWaiterFromTable($tableId)
    {
        if (!$tableId) {
            return null;
        }

        $today = now()->format('Y-m-d');

        $assignment = DB::table('assign_waiter_to_tables')
            ->where('table_id', $tableId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        // Use waiter_id if available, otherwise fallback to backup_waiter_id
        $waiterId = $assignment?->waiter_id ?? $assignment?->backup_waiter_id ?? null;

        if ($waiterId) {
            $selectWaiter = $waiterId;
        }

        return $waiterId ? User::find($waiterId) : null;
    }

    /**
     * Whether loyalty (points redemption) is enabled for POS.
     * Matches tt Livewire Pos::isLoyaltyEnabled() / PosAjaxController::isLoyaltyEnabledForPos().
     */
    private function isLoyaltyEnabledForPos(): bool
    {
        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            return false;
        }

        if (function_exists('restaurant_modules') && !in_array('Loyalty', restaurant_modules())) {
            return false;
        }

        if (!class_exists(\Modules\Loyalty\Services\PosLoyaltyHandler::class)) {
            return false;
        }

        try {
            $handler = new \Modules\Loyalty\Services\PosLoyaltyHandler(new \stdClass());
            if (method_exists($handler, 'isPointsEnabledForPOS')) {
                return (bool) $handler->isPointsEnabledForPOS();
            }
        } catch (\Throwable $e) {
            return false;
        }

        return class_exists(\Modules\Loyalty\Services\LoyaltyService::class)
            && class_exists(\Modules\Loyalty\Entities\LoyaltySetting::class);
    }

    /**
     * Get all delivery executives and their busy state.
     * Executive is busy when assigned to any order that is not delivered.
     */
    private function getDeliveryExecutivesWithBusyState()
    {
        // Get all delivery executives
        $deliveryExecutives = DeliveryExecutive::where('status', 'available')->where('is_online', true)->get();

        // Build map: executive id => is busy (has at least one assigned order not delivered)
        $deliveryExecutiveBusyMap = [];
        foreach ($deliveryExecutives as $executive) {
            $hasUndeliveredOrder = Order::where('delivery_executive_id', $executive->id)
                ->where('order_status', '!=', 'delivered')
                ->whereDate('date_time', '=', now()->toDateString())
                ->exists();
            $deliveryExecutiveBusyMap[$executive->id] = $hasUndeliveredOrder;
        }

        return [$deliveryExecutives, $deliveryExecutiveBusyMap];
    }

    public function index()
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);

        // Handle table order ID from query parameter (similar to Pos.php)
        $tableOrderID = request('tableOrderID');
        if ($tableOrderID) {
            $table = Table::with('activeOrder')->find($tableOrderID);

            if ($table) {
                $tableId = $table->id;
                $tableNo = $table->table_code;

                // If there's an active order, redirect to use the loadPosWithOrder method instead
                if ($table->activeOrder) {
                    $orderID = $table->activeOrder->id;
                    $showOrderDetail = request()->boolean('show-order-detail');
                    return $this->loadPosWithOrder($orderID, !$showOrderDetail);
                }
            }
        }

        $restaurant = restaurant()->load(['paymentGateways', 'package']);
        $branch = branch();

        // Get waiters
        $users = cache()->remember('waiters_' . $restaurant->id . '_' . $branch->id, 60 * 60 * 24, function () use ($restaurant, $branch) {
            return User::withoutGlobalScope(BranchScope::class)
                ->where(function ($q) use ($branch) {
                    return $q->where('branch_id', $branch->id)
                        ->orWhereNull('branch_id');
                })
                ->role('waiter_' . $restaurant->id)
                ->where('restaurant_id', $restaurant->id)
                ->get();
        });

        // Get taxes
        $taxes = cache()->remember('taxes_' . $restaurant->id . '_' . $branch->id, 60 * 60 * 24, function () {
            return Tax::all();
        });

        // Get delivery executives with busy status map
        [$deliveryExecutives, $deliveryExecutiveBusyMap] = $this->getDeliveryExecutivesWithBusyState();

        // Get delivery platforms
        $deliveryPlatforms = cache()->remember('delivery_platforms_' . $restaurant->id . '_' . $branch->id, 60 * 60 * 24, function () {
            return DeliveryPlatform::where('is_active', true)->orderBy('name')->get();
        });

        // Get menu list
        $menuList = Menu::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->get();

        // Get order types
        $orderTypes = OrderType::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();

        // Get cancel reasons
        $cancelReasons = \Illuminate\Support\Facades\Cache::remember(
            'cancel_reasons_' . $branch->id,
            now()->addHours(2),
            function () {
                return KotCancelReason::where('cancel_order', true)->get();
            }
        );

        // Get order places (KOT places) for printing
        $orderPlaces = KotPlace::all();

        // MultiPOS check
        $hasPosMachine = false;
        $machineStatus = null;
        $posMachine = null;
        $limitReached = false;
        $limitMessage = '';
        $shouldBlockPos = false;

        if (module_enabled('MultiPOS') && class_exists(\Modules\MultiPOS\Entities\PosMachine::class)) {
            $cookieName = config('multipos.cookie.name', 'pos_token');
            $deviceId = request()->cookie($cookieName);

            if ($deviceId) {
                $posMachine = \Modules\MultiPOS\Entities\PosMachine::where('device_id', $deviceId)
                    ->where('branch_id', $branch->id)
                    ->first();

                if ($posMachine) {
                    $hasPosMachine = true;
                    $machineStatus = $posMachine->status;
                }
            }

            if (!$hasPosMachine) {
                $packageLimit = optional($restaurant->package)->multipos_limit;
                if (!is_null($packageLimit) && $packageLimit >= 0) {
                    $currentCount = \Modules\MultiPOS\Entities\PosMachine::where('branch_id', $branch->id)
                        ->whereIn('status', ['active', 'pending'])
                        ->count();

                    if ($currentCount >= $packageLimit) {
                        $limitReached = true;
                        $limitMessage = __('multipos::messages.registration.limit_reached.message', ['limit' => $packageLimit]);
                    }
                }
            }

            $shouldBlockPos = !$hasPosMachine || $machineStatus === 'pending' || $machineStatus === 'declined';
        }

        // Restaurant availability (match Livewire Pos behavior)
        $showRestaurantClosedBanner = false;
        $restaurantClosedMessage = '';

        try {
            $availability = RestaurantAvailabilityService::getAvailability($restaurant, $branch);
            if (!($availability['is_open'] ?? true)) {
                $showRestaurantClosedBanner = true;
                $restaurantClosedMessage = RestaurantAvailabilityService::getMessage($availability, $restaurant);
            }
        } catch (\Throwable $e) {
            // Fail-safe: don't break POS if availability service errors
        }


        // Generate order number
        $orderNumberData = \App\Models\Order::generateOrderNumber($branch);
        $orderNumber = $orderNumberData['order_number'];
        $formattedOrderNumber = $orderNumberData['formatted_order_number'];

        // Check if order type popup should be disabled
        $disablePopup = $restaurant->disable_order_type_popup ?? false;
        $defaultOrderTypeId = $restaurant->default_order_type_id ?? null;
        $orderTypeId = null;

        // Check if user is explicitly requesting order type change (e.g., from Change button)
        $changeOrderType = request()->has('changeOrderType') || request()->boolean('changeOrderType');


        if ($changeOrderType) {
            $orderTypeId = null;
        } elseif (request()->has('orderTypeId')) {
            // Get order type from query parameter (set by OrderTypeSelection modal)
            $orderTypeId = request()->get('orderTypeId');
        } elseif ($disablePopup && $defaultOrderTypeId) {
            // Auto-select default order type if popup is disabled
            $defaultOrderType = \App\Models\OrderType::find($defaultOrderTypeId);
            if ($defaultOrderType && $defaultOrderType->is_active) {
                $orderTypeId = $defaultOrderType->id;
            }
        }

        // Get delivery platform from query parameter (used for delivery pricing context)
        $selectedDeliveryApp = request()->get('deliveryPlatform', null);
        $selectedDeliveryAppFromRequest = $selectedDeliveryApp;

        // Variables for menu view
        $search = request()->get('search', '');
        $menuId = request()->get('menuId', null);
        $filterCategories = request()->get('filterCategories', null);
        $menuItemsLoaded = 48;
        $normalizedDeliveryAppId = ($selectedDeliveryApp === 'default' || !$selectedDeliveryApp) ? null : (int) $selectedDeliveryApp;

        // Get categories for menu filter
        $categoryList = \App\Models\ItemCategory::select('id', 'category_name')
            ->withCount(['items' => function ($query) use ($menuId, $branch) {
                if ($menuId) {
                    $query->where('menu_id', $menuId);
                }
                $query->where('branch_id', $branch->id);
            }])
            ->having('items_count', '>', 0)
            ->get();

        // Get menu items (simplified - will be loaded via AJAX)
        $menuItemsQuery = \App\Models\MenuItem::where('branch_id', $branch->id);

        if ($menuId) {
            $menuItemsQuery->where('menu_id', $menuId);
        }

        if ($filterCategories) {
            $menuItemsQuery->where('item_category_id', $filterCategories);
        }

        if ($search) {
            $menuItemsQuery->where('item_name', 'like', '%' . $search . '%');
        }

        $totalMenuItemsCount = $menuItemsQuery->count();
        $menuItems = $menuItemsQuery->with(['taxes:id,tax_name,tax_percent'])
            ->withCount(['variations', 'modifierGroups'])
            ->limit($menuItemsLoaded)
            ->get();

        // Variables for kot_items view
        $customerId = null;
        $customer = null;
        $orderType = null;
        $orderTypeSlug = null;
        $selectedDeliveryApp = $selectedDeliveryAppFromRequest;
        // Preserve table context when opening POS from a table (pos.index?tableOrderID=…).
        // Do not overwrite with null — the earlier tableOrderID block only runs before this
        // and local $tableId/$tableNo are out of scope here.
        $tableNo = null;
        $tableId = null;
        if (request()->filled('tableOrderID')) {
            $tableForPos = Table::with('activeOrder')->find(request('tableOrderID'));
            if ($tableForPos) {
                $tableId = $tableForPos->id;
                $tableNo = $tableForPos->table_code;

                // If no active order exists for the table, POS should default to Dine In.
                if ($tableForPos->activeOrder) {
                    $orderTypeId = $tableForPos->activeOrder->order_type_id;
                    $orderType = $tableForPos->activeOrder->order_type;
                    $orderTypeSlug = $tableForPos->activeOrder->order_type_slug;
                } else {
                    $defaultOrderType = \App\Models\OrderType::where('type', 'dine_in')
                        ->where('is_active', true)
                        ->first();

                    if ($defaultOrderType) {
                        $orderType = $defaultOrderType->type;
                        $orderTypeSlug = $defaultOrderType->slug;
                        $orderTypeId = $defaultOrderType->id;
                    } else {
                        // Fallback consistent with Livewire defaulting behavior.
                        $orderType = 'dine_in';
                        $orderTypeSlug = 'dine_in';
                        $orderTypeId = null;
                    }
                }
            }
        }

        // Apply price context to menu items based on (possibly updated) order type
        if ($orderTypeId) {
            foreach ($menuItems as $menuItem) {
                $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
            }
        }
        $orderNote = null;
        $noOfPax = 1; // Default number of pax
        $selectWaiter = user()->id;
        $selectDeliveryExecutive = null;

        $pickupRange = $restaurant->pickup_days_range ?? 1;
        $minDate = now()->addMinute()->format($restaurant->date_format);
        $maxDate = now()->addDays($pickupRange - 1)->endOfDay()->format($restaurant->date_format);
        $deliveryDateTime = null;
        $defaultDate = old('deliveryDateTime', $deliveryDateTime ?? $minDate);
        $isPastTime = false;

        if ($deliveryDateTime) {
            $pickupData = $this->initializePickupDateTime($deliveryDateTime);
            $pickupDate = $pickupData['pickupDate'];
            $pickupTime = $pickupData['pickupTime'];
            $isPastTime = $pickupData['isPastTime'];
        } else {
            $pickupDate = now($restaurant->timezone)->format($restaurant->date_format);
            $pickupTime = now($restaurant->timezone)->format('H:i');
        }
        // Cart/Order variables
        $orderItemList = [];
        $orderItemVariation = [];
        $orderItemQty = [];
        $orderItemAmount = [];
        $itemModifiersSelected = [];
        $orderItemModifiersPrice = [];
        $itemNotes = [];
        $orderItemIds = [];
        $subTotal = 0;
        $total = 0;
        $discountType = null;
        $discountValue = null;
        $discountAmount = 0;
        $discountedTotal = 0;
        $deliveryFee = 0;
        $tipAmount = 0;
        $taxMode = $restaurant->tax_mode ?? 'order';
        $includeChargesInTaxBase = $restaurant->include_charges_in_tax_base ?? false;
        $taxBase = 0;
        $orderItemTaxDetails = [];
        $totalTaxAmount = 0;
        $orderID = null;
        $orderDetail = null;
        $orderStatus = null;
        $deliveryDateTime = null;
        $extraCharges = [];
        $isPastTime = false;
        $mergedOrderIdsToDelete = []; // Track orders to delete after merge
        $loyaltyPointsAvailable = 0;
        $loyaltyPointsRedeemed = 0;
        $loyaltyDiscountAmount = 0;

        // Check if there's merge data from table merge operation
        if (session()->has('pos_merge_data')) {
            $mergeData = session()->get('pos_merge_data');
            $ordersToMerge = $mergeData['orders_to_merge'] ?? [];

            foreach ($ordersToMerge as $order) {
                // Handle draft orders - they have OrderItems
                if ($order->status === 'draft' && $order->items->count() > 0) {
                    foreach ($order->items as $orderItem) {
                        $key = 'merged_order_' . $orderItem->id;

                        // Set price context on menu item and variation
                        if ($orderTypeId) {
                            $orderItem->menuItem->setPriceContext($orderTypeId, $selectedDeliveryApp);
                            if ($orderItem->menuItemVariation) {
                                $orderItem->menuItemVariation->setPriceContext($orderTypeId, $selectedDeliveryApp);
                            }
                        }

                        $orderItemList[$key] = $orderItem->menuItem;
                        if ($orderItemList[$key]) {
                            $orderItemList[$key]->loadMissing('taxes');
                        }
                        $orderItemList[$key]->is_free_item_from_stamp = (bool)($orderItem->is_free_item_from_stamp ?? false);
                        $orderItemList[$key]->stamp_rule_id = $orderItem->stamp_rule_id;
                        $orderItemQty[$key] = $orderItem->quantity;
                        $itemModifiersSelected[$key] = $orderItem->modifierOptions->pluck('id')->toArray();
                        $orderItemModifiersPrice[$key] = $orderItem->modifierOptions->sum('price');

                        // Keep persisted amount from DB (includes stamp discounts/free item adjustments).
                        $orderItemAmount[$key] = (float)($orderItem->amount ?? 0);

                        if ($orderItem->menuItemVariation) {
                            $orderItemVariation[$key] = $orderItem->menuItemVariation;
                        }

                        if ($orderItem->note) {
                            $itemNotes[$key] = $orderItem->note;
                        }

                        $orderItemIds[$key] = $orderItem->id; // Track for deletion
                    }
                } else {
                    // Handle KOT orders - they have KOT items
                    foreach ($order->kot as $kot) {
                        foreach ($kot->items->where('status', '!=', 'cancelled') as $kotItem) {
                            $key = 'merged_kot_' . $kot->id . '_' . $kotItem->id;

                            // Set price context on menu item and variation
                            if ($orderTypeId) {
                                $kotItem->menuItem->setPriceContext($orderTypeId, $selectedDeliveryApp);
                                if ($kotItem->menuItemVariation) {
                                    $kotItem->menuItemVariation->setPriceContext($orderTypeId, $selectedDeliveryApp);
                                }
                            }

                        $orderItemList[$key] = $kotItem->menuItem;
                        if ($orderItemList[$key]) {
                            $orderItemList[$key]->loadMissing('taxes');
                        }
                            $orderItemList[$key]->is_free_item_from_stamp = (bool)($kotItem->is_free_item_from_stamp ?? false);
                            $orderItemList[$key]->stamp_rule_id = $kotItem->stamp_rule_id;
                            $orderItemQty[$key] = $kotItem->quantity;
                            $itemModifiersSelected[$key] = $kotItem->modifierOptions->pluck('id')->toArray();
                            $orderItemModifiersPrice[$key] = $kotItem->modifierOptions->sum('price');

                            // Keep persisted amount from DB (includes stamp discounts/free item adjustments).
                            $orderItemAmount[$key] = (float)($kotItem->amount ?? 0);

                            if ($kotItem->menuItemVariation) {
                                $orderItemVariation[$key] = $kotItem->menuItemVariation;
                            }

                            if ($kotItem->note) {
                                $itemNotes[$key] = $kotItem->note;
                            }
                        }
                    }
                }

                // Track order for deletion
                $mergedOrderIdsToDelete[] = $order->id;
            }

            // Clear merge data from session
            session()->forget('pos_merge_data');

            // Store order IDs to delete in session for later (when order is saved)
            if (!empty($mergedOrderIdsToDelete)) {
                session()->put('pos_merged_orders_to_delete', $mergedOrderIdsToDelete);
            }

            // Recalculate totals after loading merged items
            // 1. Calculate subtotal
            $subTotal = array_sum($orderItemAmount);

            // 2. Calculate item-level taxes if tax mode is 'item'
            if ($taxMode === 'item') {
                foreach ($orderItemList as $key => $menuItem) {
                    if ($menuItem->taxes && $menuItem->taxes->count() > 0) {
                        $itemTaxes = [];
                        $itemTaxTotal = 0;
                        $qty = max(1, (int)($orderItemQty[$key] ?? 1));
                        $taxableAmount = $qty > 0 ? ((float)($orderItemAmount[$key] ?? 0) / $qty) : 0;

                        foreach ($menuItem->taxes as $tax) {
                            $taxAmount = ($tax->tax_percent / 100) * $taxableAmount;
                            $itemTaxes[$tax->tax_name] = [
                                'percent' => $tax->tax_percent,
                                'amount' => $taxAmount
                            ];
                            $itemTaxTotal += $taxAmount;
                        }

                        $orderItemTaxDetails[$key] = [
                            'qty' => $orderItemQty[$key],
                            'tax_breakup' => $itemTaxes,
                            'total_tax' => $itemTaxTotal * $orderItemQty[$key]
                        ];
                    }
                }

                // Sum up all item taxes
                $totalTaxAmount = 0;
                foreach ($orderItemTaxDetails as $itemTax) {
                    $totalTaxAmount += $itemTax['total_tax'];
                }
            } else {
                // Order-level tax will be calculated from $taxes collection
                $totalTaxAmount = 0;
                foreach ($taxes as $tax) {
                    $totalTaxAmount += ($tax->tax_percent / 100) * $subTotal;
                }
            }

            // 3. Apply discount if any (set to 0 for merged orders by default)
            $discountedTotal = $subTotal;
            if ($discountType && $discountValue) {
                if ($discountType === 'percent') {
                    $discountAmount = ($discountValue / 100) * $subTotal;
                } else {
                    $discountAmount = $discountValue;
                }
                $discountedTotal = $subTotal - $discountAmount;
            }

            // 4. Calculate final total (subtotal + tax + delivery + tip - discount)
            $total = $discountedTotal + $totalTaxAmount + $deliveryFee + $tipAmount;
        }

        // If a customer is already selected (e.g. editing existing order), load loyalty points available
        if (
            function_exists('module_enabled') && module_enabled('Loyalty')
            && $customerId
        ) {
            try {
                $restaurantId = $restaurant->id;
                /** @var LoyaltyService $loyaltyService */
                $loyaltyService = app(LoyaltyService::class);
                $loyaltyPointsAvailable = $loyaltyService->getAvailablePoints($restaurantId, $customerId);
            } catch (\Throwable $e) {
                $loyaltyPointsAvailable = 0;
            }
        }

        // If order type is set, get order type details
        $orderTypeName = null;
        if ($orderTypeId) {
            $orderTypeModel = \App\Models\OrderType::find($orderTypeId);
            if ($orderTypeModel) {
                $orderType = $orderTypeModel->type;
                $orderTypeSlug = $orderTypeModel->slug;
                $orderTypeName = $orderTypeModel->order_type_name;

                // Get extra charges for this order type
                $extraCharges = \App\Models\RestaurantCharge::whereJsonContains('order_types', $orderTypeSlug)
                    ->where('is_enabled', true)
                    ->get();

                // Set default delivery fee for delivery orders
                if ($orderTypeSlug === 'delivery') {
                    $deliverySettings = $branch->deliverySetting;
                    if ($deliverySettings && $deliverySettings->is_enabled && $deliverySettings->fee_type->value === 'fixed') {
                        $deliveryFee = $deliverySettings->fixed_fee ?? 0;
                    }
                }
            }
        }

        // Modals state
        $pendingTable = null;
        $tablesWithUnpaidOrders = [];
        $selectedTablesForMerge = [];

        // Reservation variables
        $reservationCustomer = null;
        $reservation = null;

        // Modifier options (will be populated when items are added)
        $modifierOptions = [];

        // KOT list (empty for new orders)
        $kotList = collect();

        // Modal state flags
        $showVariationModal = false;
        $showKotNote = false;
        $showTableModal = false;
        $showTableChangeConfirmationModal = false;
        $showMergeTableModal = false;
        $showErrorModal = true;
        $showNewKotButton = false;
        $showReservationModal = false;
        $showDiscountModal = false;
        $showModifiersModal = false;
        $confirmDeleteModal = false;
        $deleteOrderModal = false;

        // Order type selection flag
        $allowOrderTypeSelection = false;
        $isWaiterLocked = $this->assignedWaiterFromTable($tableId) !== null;
        $currentWaiter = $users->firstWhere('id', $selectWaiter) ?? \App\Models\User::find($selectWaiter);
        $waiterName = $currentWaiter?->name ?? __('modules.order.selectWaiter');

        $posLoyaltyEnabled = $this->isLoyaltyEnabledForPos();

        return view('pos.index', compact(
            'restaurant',
            'users',
            'taxes',
            'deliveryExecutives',
            'deliveryExecutiveBusyMap',
            'deliveryPlatforms',
            'menuList',
            'orderTypes',
            'cancelReasons',
            'orderPlaces',
            'showRestaurantClosedBanner',
            'restaurantClosedMessage',
            'hasPosMachine',
            'machineStatus',
            'posMachine',
            'limitReached',
            'limitMessage',
            'shouldBlockPos',
            'orderNumber',
            'formattedOrderNumber',
            'orderTypeId',
            'search',
            'menuId',
            'filterCategories',
            'categoryList',
            'menuItems',
            'totalMenuItemsCount',
            'menuItemsLoaded',
            'customerId',
            'customer',
            'orderType',
            'orderTypeSlug',
            'orderTypeName',
            'selectedDeliveryApp',
            'tableNo',
            'tableId',
            'orderNote',
            'noOfPax',
            'selectWaiter',
            'selectDeliveryExecutive',
            'pickupDate',
            'pickupTime',
            'minDate',
            'defaultDate',
            'maxDate',
            'orderItemList',
            'orderItemVariation',
            'orderItemQty',
            'orderItemAmount',
            'itemModifiersSelected',
            'orderItemModifiersPrice',
            'itemNotes',
            'orderItemIds',
            'subTotal',
            'total',
            'discountType',
            'discountValue',
            'discountAmount',
            'discountedTotal',
            'deliveryFee',
            'tipAmount',
            'taxMode',
            'includeChargesInTaxBase',
            'taxBase',
            'orderItemTaxDetails',
            'totalTaxAmount',
            'orderID',
            'orderDetail',
            'extraCharges',
            'isPastTime',
            'orderStatus',
            'deliveryDateTime',
            'loyaltyPointsAvailable',
            'loyaltyPointsRedeemed',
            'loyaltyDiscountAmount',
            'pendingTable',
            'tablesWithUnpaidOrders',
            'selectedTablesForMerge',
            'reservationCustomer',
            'reservation',
            'modifierOptions',
            'kotList',
            'showVariationModal',
            'showKotNote',
            'showTableModal',
            'showTableChangeConfirmationModal',
            'showMergeTableModal',
            'showErrorModal',
            'showNewKotButton',
            'showReservationModal',
            'showDiscountModal',
            'showModifiersModal',
            'confirmDeleteModal',
            'deleteOrderModal',
            'isWaiterLocked',
            'currentWaiter',
            'waiterName',
            'allowOrderTypeSelection',
            'posLoyaltyEnabled'
        ));
    }


    public function kot($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $orderID = $id;
        $order = Order::find($orderID);

        $showOrderDetail = request()->boolean('show-order-detail');

        // When show-order-detail=true → show existing KOT details
        return $this->loadPosWithOrder($orderID, !$showOrderDetail);
    }

    public function draft($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $orderID = $id;

        $order = Order::with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions', 'customer', 'table', 'orderType', 'extraCharges'])
            ->find($orderID);

        if (!$order || $order->status !== 'draft') {
            return redirect()->route('pos.index')->with('error', __('modules.order.orderNotFoundOrNotDraft'));
        }

        // Use the new index method but with orderID parameter
        return $this->loadPosWithOrder($orderID);
    }

    public function show($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;

        // Use the new index method but with tableOrderID parameter
        return $this->loadPosWithTableOrder($tableOrderID);
    }

    public function order($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;

        // Use the new index method but with tableOrderID parameter
        return $this->loadPosWithTableOrder($tableOrderID, true);
    }

    private function loadPosWithOrder($orderID, bool $isNewKot = false)
    {
        $restaurant = restaurant()->load(['paymentGateways', 'package']);
        $branch = branch();

        // Use order's tax_mode when viewing an existing order (match tt Livewire)
        $taxMode = $restaurant->tax_mode ?? 'order';

        // Get waiters
        $users = cache()->remember('waiters_' . $restaurant->id, 60 * 60 * 24, function () use ($restaurant, $branch) {
            return \App\Models\User::withoutGlobalScope(\App\Scopes\BranchScope::class)
                ->where(function ($q) use ($branch) {
                    return $q->where('branch_id', $branch->id)
                        ->orWhereNull('branch_id');
                })
                ->role('waiter_' . $restaurant->id)
                ->where('restaurant_id', $restaurant->id)
                ->get();
        });

        // Get taxes
        $taxes = cache()->remember('taxes_' . $restaurant->id, 60 * 60 * 24, function () {
            return \App\Models\Tax::all();
        });

        // Get delivery executives with busy status map
        [$deliveryExecutives, $deliveryExecutiveBusyMap] = $this->getDeliveryExecutivesWithBusyState();

        // Get delivery platforms
        $deliveryPlatforms = cache()->remember('delivery_platforms_' . $restaurant->id, 60 * 60 * 24, function () {
            return \App\Models\DeliveryPlatform::where('is_active', true)->orderBy('name')->get();
        });

        // Get menu list
        $menuList = \App\Models\Menu::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->get();

        // Get order types
        $orderTypes = \App\Models\OrderType::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();

        // Get cancel reasons
        $cancelReasons = \Illuminate\Support\Facades\Cache::remember(
            'cancel_reasons_' . $branch->id,
            now()->addHours(2),
            function () {
                return \App\Models\KotCancelReason::where('cancel_order', true)->get();
            }
        );

        // Get order places (KOT places) for printing
        $orderPlaces = \App\Models\KotPlace::all();

        // MultiPOS check
        $hasPosMachine = false;
        $machineStatus = null;
        $posMachine = null;
        $limitReached = false;
        $limitMessage = '';
        $shouldBlockPos = false;

        if (module_enabled('MultiPOS') && class_exists(\Modules\MultiPOS\Entities\PosMachine::class)) {
            $cookieName = config('multipos.cookie.name', 'pos_token');
            $deviceId = request()->cookie($cookieName);

            if ($deviceId) {
                $posMachine = \Modules\MultiPOS\Entities\PosMachine::where('device_id', $deviceId)
                    ->where('branch_id', $branch->id)
                    ->first();

                if ($posMachine) {
                    $hasPosMachine = true;
                    $machineStatus = $posMachine->status;
                }
            }

            if (!$hasPosMachine) {
                $packageLimit = optional($restaurant->package)->multipos_limit;
                if (!is_null($packageLimit) && $packageLimit >= 0) {
                    $currentCount = \Modules\MultiPOS\Entities\PosMachine::where('branch_id', $branch->id)
                        ->whereIn('status', ['active', 'pending'])
                        ->count();

                    if ($currentCount >= $packageLimit) {
                        $limitReached = true;
                        $limitMessage = __('multipos::messages.registration.limit_reached.message', ['limit' => $packageLimit]);
                    }
                }
            }

            $shouldBlockPos = !$hasPosMachine || $machineStatus === 'pending' || $machineStatus === 'declined';
        }

        // Load the existing order with all necessary relationships (match tt: taxes.tax for order-level tax display)
        $orderDetail = Order::with([
            'items.menuItem.taxes',
            'items.menuItemVariation',
            'items.modifierOptions',
            'customer',
            'table',
            'orderType',
            'extraCharges',
            'taxes.tax',
            'kot' => function($query) {
                $query->where('status', '!=', 'cancelled')
                      ->orderBy('created_at', 'desc');
            },
            'kot.items' => function($query) {
                $query->where('status', '!=', 'cancelled');
            },
            'kot.items.menuItem.taxes',
            'kot.items.menuItemVariation',
            'kot.items.modifierOptions'
        ])->find($orderID);

        if (!$orderDetail) {
            return redirect()->route('pos.index')->with('error', __('modules.order.orderNotFound'));
        }

        // Use order's tax_mode for detail view so order-level taxes display correctly (match tt)
        $taxMode = $orderDetail->tax_mode ?? $restaurant->tax_mode ?? 'order';

        // Extract order data
        $orderNumber = $orderDetail->order_number;
        $formattedOrderNumber = $orderDetail->formatted_order_number ?? $orderDetail->show_formatted_order_number ?? $orderDetail->order_number;
        $orderTypeId = $orderDetail->order_type_id;
        $orderType = $orderDetail->order_type;
        $orderTypeSlug = $orderDetail->orderType?->slug ?? $orderDetail->order_type;
        $orderTypeName = $orderDetail->orderType?->order_type_name ?? null;
        $customerId = $orderDetail->customer_id;
        $customer = $orderDetail->customer;
        $tableId = $orderDetail->table_id;
        $tableNo = $orderDetail->table?->table_code;
        $orderNote = $orderDetail->order_note;
        $noOfPax = $orderDetail->number_of_pax ?? 1;
        $selectWaiter = $orderDetail->waiter_id ?? user()->id;
        $selectDeliveryExecutive = $orderDetail->delivery_executive_id ?? null;
        $selectedDeliveryApp = $orderDetail->delivery_app_id;
        $deliveryFee = $orderDetail->delivery_fee ?? 0;
        $tipAmount = $orderDetail->tip_amount ?? 0;
        $discountType = $orderDetail->discount_type;
        $discountValue = $orderDetail->discount_value;
        $discountAmount = $orderDetail->discount_amount ?? 0;
        $subTotal = $orderDetail->sub_total ?? 0;
        $total = $orderDetail->total ?? 0;
        $discountedTotal = $orderDetail->discounted_total ?? 0;
        $totalTaxAmount = $orderDetail->total_tax_amount ?? 0;
        $orderStatus = $orderDetail->order_status;
        $deliveryDateTime = $orderDetail->pickup_date;
        $loyaltyPointsRedeemed = (int) ($orderDetail->loyalty_points_redeemed ?? 0);
        $loyaltyDiscountAmount = (float) ($orderDetail->loyalty_discount_amount ?? 0);
        $taxBase = $orderDetail->tax_base ?? 0;
        $pickupData = $this->initializePickupDateTime($deliveryDateTime);
        $pickupDate = $pickupData['pickupDate'];
        $pickupTime = $pickupData['pickupTime'];
        $isPastTime = $pickupData['isPastTime'];

        // Get KOT list for this order
        $kotList = $orderDetail->kot()->with([
            'items',
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions'
        ])->orderBy('created_at', 'asc')->get();

        // Build order item arrays
        $orderItemList = [];
        $orderItemVariation = [];
        $orderItemQty = [];
        $orderItemAmount = [];
        $itemModifiersSelected = [];
        $orderItemModifiersPrice = [];
        $itemNotes = [];
        $orderItemTaxDetails = [];
        $modifierOptions = [];
        $orderItemIds = [];

        // Normalize delivery app ID for price context
        $normalizedDeliveryAppId = ($selectedDeliveryApp === 'default' || !$selectedDeliveryApp) ? null : (int)$selectedDeliveryApp;

        // When preparing a NEW KOT for an existing order, we intentionally leave
        // the cart empty so the user can add fresh items. In that case, we skip
        // populating $orderItemList / $orderItemQty / etc from existing data.
        if (!$isNewKot) {
            // Handle draft orders - they have OrderItems instead of KOT items
            if ($orderDetail->status === 'draft' && $orderDetail->items->count() > 0) {
                foreach ($orderDetail->items as $orderItem) {
                    $key = 'order_item_' . $orderItem->id;

                    $orderItemList[$key] = $orderItem->menuItem;
                    if ($orderItemList[$key]) {
                        $orderItemList[$key]->loadMissing('taxes');
                    }
                    $orderItemList[$key]->is_free_item_from_stamp = (bool)($orderItem->is_free_item_from_stamp ?? false);
                    $orderItemList[$key]->stamp_rule_id = $orderItem->stamp_rule_id;
                    $orderItemIds[$key] = $orderItem->id;
                    $orderItemQty[$key] = $orderItem->quantity;
                    $itemModifiersSelected[$key] = $orderItem->modifierOptions->pluck('id')->toArray();

                    // Set price context
                    if ($orderTypeId) {
                        $orderItem->menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                        if ($orderItem->menuItemVariation) {
                            $orderItem->menuItemVariation->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                        }
                        foreach ($orderItem->modifierOptions as $modifier) {
                            $modifier->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                            $modifierOptions[$modifier->id] = $modifier;
                        }
                    }

                    $orderItemModifiersPrice[$key] = $orderItem->modifierOptions->sum('price');
                    // Keep persisted amount from DB (includes stamp discounts/free item adjustments).
                    $orderItemAmount[$key] = (float)($orderItem->amount ?? 0);

                    if ($orderItem->menuItemVariation) {
                        $orderItemVariation[$key] = $orderItem->menuItemVariation;
                    }

                    if ($orderItem->note) {
                        $itemNotes[$key] = $orderItem->note;
                    }

                    // Build tax details for this item if using item-level tax mode.
                    // Prefer persisted tax_breakup/tax_amount from DB; fallback to per-item tax computation.
                    if ($taxMode === 'item') {
                        $existingTaxBreakup = [];
                        if (!empty($orderItem->tax_breakup)) {
                            $decoded = is_string($orderItem->tax_breakup)
                                ? json_decode($orderItem->tax_breakup, true)
                                : $orderItem->tax_breakup;
                            if (is_array($decoded)) {
                                $existingTaxBreakup = $decoded;
                            }
                        }

                        if (!empty($existingTaxBreakup)) {
                            $orderItemTaxDetails[$key] = [
                                'qty' => $orderItem->quantity,
                                'tax_breakup' => $existingTaxBreakup,
                                'total_tax' => (float)($orderItem->tax_amount ?? 0),
                            ];
                        } elseif ($orderItem->menuItem && $orderItem->menuItem->taxes && $orderItem->menuItem->taxes->count() > 0) {
                            $itemTaxes = [];
                            $itemTaxTotal = 0;
                            $qty = max(1, (int)($orderItem->quantity ?? 1));
                            $taxableAmount = $qty > 0 ? ((float)$orderItemAmount[$key] / $qty) : 0;

                            foreach ($orderItem->menuItem->taxes as $tax) {
                                $taxAmount = ($tax->tax_percent / 100) * $taxableAmount;
                                $itemTaxes[$tax->tax_name] = [
                                    'percent' => $tax->tax_percent,
                                    'amount' => $taxAmount
                                ];
                                $itemTaxTotal += $taxAmount;
                            }

                            $orderItemTaxDetails[$key] = [
                                'qty' => $orderItem->quantity,
                                'tax_breakup' => $itemTaxes,
                                'total_tax' => $itemTaxTotal * $orderItem->quantity
                            ];
                        }
                    }
                }
            } else {
                // Handle regular orders with KOT items
                foreach ($kotList as $kot) {
                    foreach ($kot->items->where('status', '!=', 'cancelled') as $item) {
                        $key = 'kot_' . $kot->id . '_' . $item->id;

                        $orderItemList[$key] = $item->menuItem;
                        if ($orderItemList[$key]) {
                            $orderItemList[$key]->loadMissing('taxes');
                        }
                        $orderItemList[$key]->is_free_item_from_stamp = (bool)($item->is_free_item_from_stamp ?? false);
                        $orderItemList[$key]->stamp_rule_id = $item->stamp_rule_id;
                        // For KOT items, find the corresponding OrderItem
                        $orderItem = \App\Models\OrderItem::where('order_id', $orderDetail->id)
                            ->where('menu_item_id', $item->menu_item_id)
                            ->where('menu_item_variation_id', $item->menu_item_variation_id)
                            ->first();
                        if ($orderItem) {
                            $orderItemIds[$key] = $orderItem->id;
                        }
                        $orderItemQty[$key] = $item->quantity;
                        $itemModifiersSelected[$key] = $item->modifierOptions->pluck('id')->toArray();

                        // Set price context
                        if ($orderTypeId) {
                            $item->menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                            if ($item->menuItemVariation) {
                                $item->menuItemVariation->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                            }
                            foreach ($item->modifierOptions as $modifier) {
                                $modifier->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                                $modifierOptions[$modifier->id] = $modifier;
                            }
                        }

                        $orderItemModifiersPrice[$key] = $item->modifierOptions->sum('price');
                        // Keep persisted amount from DB (includes stamp discounts/free item adjustments).
                        $orderItemAmount[$key] = (float)($item->amount ?? 0);

                        if ($item->menuItemVariation) {
                            $orderItemVariation[$key] = $item->menuItemVariation;
                        }

                        if ($item->note) {
                            $itemNotes[$key] = $item->note;
                        }

                        // Build tax details for this item if using item-level tax mode
                        if ($taxMode === 'item') {
                            $existingTaxBreakup = [];
                            if (!empty($item->tax_breakup)) {
                                $decoded = is_string($item->tax_breakup)
                                    ? json_decode($item->tax_breakup, true)
                                    : $item->tax_breakup;
                                if (is_array($decoded)) {
                                    $existingTaxBreakup = $decoded;
                                }
                            }

                            if (!empty($existingTaxBreakup)) {
                                $orderItemTaxDetails[$key] = [
                                    'qty' => $item->quantity,
                                    'tax_breakup' => $existingTaxBreakup,
                                    'total_tax' => (float)($item->tax_amount ?? 0),
                                ];
                            } elseif ($item->menuItem && $item->menuItem->taxes && $item->menuItem->taxes->count() > 0) {
                                $itemTaxes = [];
                                $itemTaxTotal = 0;
                                // Tax should be derived from effective discounted item amount (tt parity).
                                $qty = max(1, (int)($item->quantity ?? 1));
                                $taxableAmount = $qty > 0 ? ((float)$orderItemAmount[$key] / $qty) : 0;

                                foreach ($item->menuItem->taxes as $tax) {
                                    $taxAmount = ($tax->tax_percent / 100) * $taxableAmount;
                                    $itemTaxes[$tax->tax_name] = [
                                        'percent' => $tax->tax_percent,
                                        'amount' => $taxAmount
                                    ];
                                    $itemTaxTotal += $taxAmount;
                                }

                                $orderItemTaxDetails[$key] = [
                                    'qty' => $item->quantity,
                                    'tax_breakup' => $itemTaxes,
                                    'total_tax' => $itemTaxTotal
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Get extra charges
        $extraCharges = $orderDetail->extraCharges;

        // Variables for menu view
        $search = request()->get('search', '');
        $menuId = request()->get('menuId', null);
        $filterCategories = request()->get('filterCategories', null);
        $menuItemsLoaded = 48;

        // Get categories for menu filter
        $categoryList = \App\Models\ItemCategory::select('id', 'category_name')
            ->withCount(['items' => function ($query) use ($menuId, $branch) {
                if ($menuId) {
                    $query->where('menu_id', $menuId);
                }
                $query->where('branch_id', $branch->id);
            }])
            ->having('items_count', '>', 0)
            ->get();

        // Get menu items
        $menuItemsQuery = \App\Models\MenuItem::where('branch_id', $branch->id);

        if ($menuId) {
            $menuItemsQuery->where('menu_id', $menuId);
        }

        if ($filterCategories) {
            $menuItemsQuery->where('item_category_id', $filterCategories);
        }

        if ($search) {
            $menuItemsQuery->where('item_name', 'like', '%' . $search . '%');
        }

        $totalMenuItemsCount = $menuItemsQuery->count();
        $menuItems = $menuItemsQuery->with(['taxes:id,tax_name,tax_percent'])
            ->withCount(['variations', 'modifierGroups'])
            ->limit($menuItemsLoaded)
            ->get();

        // Apply price context to menu items based on order type
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($selectedDeliveryApp === 'default' || !$selectedDeliveryApp) ? null : (int)$selectedDeliveryApp;
            foreach ($menuItems as $menuItem) {
                $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
            }
        }

        // Other variables
        $pickupRange = $restaurant->pickup_days_range ?? 1;
        $minDate = now()->addMinute()->format($restaurant->date_format);
        $maxDate = now()->addDays($pickupRange - 1)->endOfDay()->format($restaurant->date_format);
        $defaultDate = old('deliveryDateTime', $deliveryDateTime ?? $minDate);
        $pendingTable = null;
        $tablesWithUnpaidOrders = [];
        $selectedTablesForMerge = [];
        $reservationCustomer = null;
        $reservation = null;

        // Modal state flags
        $showVariationModal = false;
        $showKotNote = false;
        $showTableModal = false;
        $showTableChangeConfirmationModal = false;
        $showMergeTableModal = false;
        $showErrorModal = true;
        $showNewKotButton = false;
        $showReservationModal = false;
        $showDiscountModal = false;
        $showModifiersModal = false;
        $confirmDeleteModal = false;
        $deleteOrderModal = false;

        // Order type selection flag
        $allowOrderTypeSelection = false;
        $isWaiterLocked = $this->assignedWaiterFromTable($tableId) !== null;
        $currentWaiter = $users->firstWhere('id', $selectWaiter) ?? \App\Models\User::find($selectWaiter);
        $waiterName = $currentWaiter?->name ?? __('modules.order.selectWaiter');
        $includeChargesInTaxBase = $restaurant->include_charges_in_tax_base ?? false;

        // Restaurant availability (match Livewire Pos behavior)
        $showRestaurantClosedBanner = false;
        $restaurantClosedMessage = '';

        try {
            $availability = RestaurantAvailabilityService::getAvailability($restaurant, $branch);
            if (!($availability['is_open'] ?? true)) {
                $showRestaurantClosedBanner = true;
                $restaurantClosedMessage = RestaurantAvailabilityService::getMessage($availability, $restaurant);
            }
        } catch (\Throwable $e) {
            // Fail-safe: don't break POS if availability service errors
        }

        // Loyalty: available points for this order's customer (for AJAX POS modal)
        $loyaltyPointsAvailable = 0;
        if (
            function_exists('module_enabled') && module_enabled('Loyalty')
            && $customerId
        ) {
            try {
                $restaurantId = $restaurant->id;
                /** @var LoyaltyService $loyaltyService */
                $loyaltyService = app(LoyaltyService::class);
                $loyaltyPointsAvailable = $loyaltyService->getAvailablePoints($restaurantId, $customerId);
            } catch (\Throwable $e) {
                $loyaltyPointsAvailable = 0;
            }
        }

        $posLoyaltyEnabled = $this->isLoyaltyEnabledForPos();

        return view('pos.index', compact(
            'restaurant',
            'users',
            'taxes',
            'deliveryExecutives',
            'deliveryExecutiveBusyMap',
            'deliveryPlatforms',
            'menuList',
            'orderTypes',
            'cancelReasons',
            'orderPlaces',
            'hasPosMachine',
            'machineStatus',
            'posMachine',
            'limitReached',
            'limitMessage',
            'shouldBlockPos',
            'orderNumber',
            'formattedOrderNumber',
            'orderTypeId',
            'search',
            'menuId',
            'filterCategories',
            'categoryList',
            'menuItems',
            'totalMenuItemsCount',
            'menuItemsLoaded',
            'customerId',
            'customer',
            'orderType',
            'orderTypeSlug',
            'orderTypeName',
            'selectedDeliveryApp',
            'tableNo',
            'tableId',
            'orderNote',
            'noOfPax',
            'selectWaiter',
            'selectDeliveryExecutive',
            'pickupDate',
            'pickupTime',
            'minDate',
            'maxDate',
            'defaultDate',
            'orderItemList',
            'orderItemVariation',
            'orderItemQty',
            'orderItemAmount',
            'itemModifiersSelected',
            'orderItemModifiersPrice',
            'itemNotes',
            'orderItemIds',
            'subTotal',
            'total',
            'discountType',
            'discountValue',
            'discountAmount',
            'discountedTotal',
            'deliveryFee',
            'tipAmount',
            'taxMode',
            'includeChargesInTaxBase',
            'taxBase',
            'orderItemTaxDetails',
            'totalTaxAmount',
            'orderID',
            'orderDetail',
            'extraCharges',
            'isPastTime',
            'pendingTable',
            'tablesWithUnpaidOrders',
            'selectedTablesForMerge',
            'reservationCustomer',
            'reservation',
            'modifierOptions',
            'orderStatus',
            'deliveryDateTime',
            'kotList',
            'showVariationModal',
            'showKotNote',
            'showTableModal',
            'showTableChangeConfirmationModal',
            'showMergeTableModal',
            'showErrorModal',
            'showNewKotButton',
            'showReservationModal',
            'showDiscountModal',
            'showModifiersModal',
            'confirmDeleteModal',
            'deleteOrderModal',
            'isWaiterLocked',
            'currentWaiter',
            'waiterName',
            'allowOrderTypeSelection',
            'showRestaurantClosedBanner',
            'restaurantClosedMessage',
            'loyaltyPointsAvailable',
            'loyaltyPointsRedeemed',
            'loyaltyDiscountAmount',
            'posLoyaltyEnabled'
        ));
    }

    /**
     * Load POS with a table order
     */
    private function loadPosWithTableOrder($tableOrderID, $showOrderDetail = false)
    {
        return redirect()->route('pos.index', [
            'tableOrderID' => $tableOrderID,
            'show-order-detail' => $showOrderDetail ? 'true' : 'false'
        ]);
    }

    public function customerDisplay()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-display');
    }

    public function customerOrderBoard()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-order-board');
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $order = Order::findOrFail($id);

        // Check permission
        abort_unless(user_can('Update Order'), 403);

        // Update order status
        $order->update([
            'order_status' => $request->status
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('modules.order.statusUpdated'),
                'data' => [
                    'order_id' => $order->id,
                    'status' => $order->order_status
                ]
            ]);
        }

        // Redirect back with success message for regular requests
        return redirect()->back()->with('success', __('modules.order.statusUpdated'));
    }

    /**
     * Initialize pickup date and time from deliveryDateTime
     */
    private function initializePickupDateTime($deliveryDateTime)
    {
        $pickupDate = null;
        $pickupTime = null;
        $isPastTime = false;

        if ($deliveryDateTime) {
            try {
                $timezone = restaurant()->timezone ?? config('app.timezone');
                $dateTime = \Carbon\Carbon::parse($deliveryDateTime, $timezone);
                $pickupDate = $dateTime->format(restaurant()->date_format);
                $pickupTime = $dateTime->format('H:i');

                // Check if the time is in the past
                $minDateTime = now($timezone)->addMinute();
                $isPastTime = $dateTime->lt($minDateTime);
            } catch (\Exception $e) {
                // Fallback to current date/time if parsing fails
                $pickupDate = now(restaurant()->timezone)->format(restaurant()->date_format);
                $pickupTime = now(restaurant()->timezone)->format('H:i');
                $isPastTime = false;
            }
        } else {
            $pickupDate = now(restaurant()->timezone)->format(restaurant()->date_format);
            $pickupTime = now(restaurant()->timezone)->format('H:i');
            $isPastTime = false;
        }

        return ['pickupDate' => $pickupDate, 'pickupTime' => $pickupTime, 'isPastTime' => $isPastTime];
    }

}
