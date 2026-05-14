<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Menu;
use App\Models\ItemCategory;
use App\Models\MenuItem;
use App\Models\User;
use App\Models\Branch;
use App\Models\RestaurantCharge;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\Area;
use App\Models\TableSession;
use App\Models\Reservation;
use App\Models\OrderType;
use App\Models\DeliveryPlatform;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderCharge;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\MenuItemVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Tax;

use App\ApiResource\OrderResource;
use App\Enums\OrderStatus;
use App\Services\RestaurantAvailabilityService;

class PosApiController extends Controller
{

    private $branch;
    private $restaurant;

    public function __construct()
    {
        $this->branch = branch();
        $this->restaurant = restaurant();
    }

    /**
     * Save default order type preference (JS/AJAX equivalent of SaaS OrderTypeSelection Livewire flow).
     */
    public function saveDefaultOrderTypePreference(Request $request)
    {
        $request->validate([
            'order_type_id' => 'required|integer',
        ]);

        $orderTypeId = (int)$request->input('order_type_id');

        $orderType = OrderType::where('id', $orderTypeId)
            ->where('is_active', true)
            ->when($this->branch, function ($q) {
                $q->where('branch_id', $this->branch->id);
            })
            ->first();

        if (!$orderType) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.noOrderTypesAvailable'),
            ], 404);
        }

        $restaurant = $this->restaurant;

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 401);
        }

        $restaurant->default_order_type_id = $orderType->id;
        $restaurant->disable_order_type_popup = true;
        $restaurant->save();

        // Clear cached restaurant session data so next load respects the preference
        session()->forget('restaurant');

        return response()->json([
            'success' => true,
            'message' => __('modules.order.orderTypeSetTo', ['type' => $orderType->order_type_name]),
        ]);
    }

    public function getMenus()
    {

        $menus = cache()->remember('menus_' . $this->branch->id, 60, function () {
            return Menu::where('branch_id', $this->branch->id)->get()->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'menu_name' => $menu->getTranslation('menu_name', session('locale', app()->getLocale())),
                    'sort_order' => $menu->sort_order,
                ];
            });
        });


        return response()->json($menus);
    }

    public function getCategories(Request $request)
    {
        $menuId = $request->input('menu_id');
        $search = $request->input('search', '');

        // Build query for categories with item counts based on filters
        $categories = ItemCategory::select('id', 'category_name', 'sort_order')
            ->where('branch_id', $this->branch->id)
            ->withCount(['items' => function ($query) use ($menuId, $search) {
                $query->where('branch_id', $this->branch->id);

                if ($menuId) {
                    $query->where('menu_id', $menuId);
                }

                if ($search) {
                    $query->where('item_name', 'like', '%' . $search . '%');
                }
            }])
            ->having('items_count', '>', 0)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'count' => $category->items_count,
                    'category_name' => $category->getTranslation('category_name', session('locale', app()->getLocale())),
                    'sort_order' => $category->sort_order,
                ];
            });

        return response()->json($categories);
    }

    public function getMenuItems(Request $request)
    {
        $menuId = $request->input('menu_id');
        $categoryId = $request->input('category_id');
        $search = $request->input('search', '');
        $limit = $request->input('limit', 75);
        $orderTypeId = $request->input('order_type_id');
        $deliveryAppId = $request->input('delivery_app_id');

        // Build query
        $query = MenuItem::where('branch_id', $this->branch->id);

        if ($menuId) {
            $query->where('menu_id', $menuId);
        }

        if ($categoryId) {
            $query->where('item_category_id', $categoryId);
        }

        if ($search) {
            $query->where('item_name', 'like', '%' . $search . '%');
        }

        $totalCount = $query->count();

        $menuItems = $query->withCount(['variations', 'modifierGroups'])
            ->limit($limit)
            ->get();

        // Apply price context based on order type
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || !$deliveryAppId) ? null : (int)$deliveryAppId;
            foreach ($menuItems as $menuItem) {
                $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
            }
        }

        return response()->json([
            'success' => true,
            'items' => $menuItems,
            'total_count' => $totalCount,
            'loaded_count' => $menuItems->count()
        ]);
    }

    public function getWaiters()
    {
        $waiters = cache()->remember('waiters_' . $this->branch->id, 60, function () {
            return User::where('restaurant_id', $this->restaurant->id)->get();
        });
        return response()->json($waiters);
    }

    public function getCustomers(Request $request)
    {
        $searchQuery = $request->query('search', '');

        $query = Customer::where('restaurant_id', $this->restaurant->id);

        if (!empty($searchQuery) && strlen($searchQuery) >= 2) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        $customers = $query->orderBy('name')->limit(10)->get();

        return response()->json($customers);
    }

    public function getPhoneCodes(Request $request)
    {
        $search = $request->query('search', '');

        $phoneCodes = \App\Models\Country::pluck('phonecode')
            ->unique()
            ->filter()
            ->values();

        if (!empty($search)) {
            $phoneCodes = $phoneCodes->filter(function ($code) use ($search) {
                return str_contains($code, $search);
            })->values();
        }

        return response()->json($phoneCodes);
    }

    public function saveCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_code' => 'required',
            'phone' => 'required',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:500',
        ]);

        // Check for existing customer by email or phone
        $existingCustomer = null;

        if (!empty($validated['email'])) {
            $existingCustomer = Customer::where('restaurant_id', $this->restaurant->id)
                ->where('email', $validated['email'])
                ->first();
        }

        if (!$existingCustomer && !empty($validated['phone'])) {
            $existingCustomer = Customer::where('restaurant_id', $this->restaurant->id)
                ->where('phone', $validated['phone'])
                ->first();
        }

        $customerData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'phone_code' => $validated['phone_code'],
            'email' => $validated['email'] ?? null,
            'delivery_address' => $validated['address'] ?? null,
        ];

        // Update existing customer or create new one
        if ($existingCustomer) {
            $customer = tap($existingCustomer)->update($customerData);
        } else {
            $customerData['restaurant_id'] = $this->restaurant->id;
            $customer = Customer::create($customerData);
        }

        // Clear cache
        cache()->forget('customers_' . $this->branch->id);

        return response()->json([
            'success' => true,
            'message' => $existingCustomer ? __('messages.customerUpdated') : __('messages.customerAdded'),
            'customer' => $customer,
        ]);
    }

    public function getExtraCharges($orderType)
    {
        $extraCharges = RestaurantCharge::whereJsonContains('order_types', $orderType)
            ->where('is_enabled', true)
            ->where('restaurant_id', $this->restaurant->id)
            ->get();

        return response()->json($extraCharges);
    }

    public function getTables()
    {
        // First cleanup expired locks
        Table::cleanupExpiredLocks();

        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $isAdmin = $user ? $user->hasRole('Admin_' . $user->restaurant_id) : false;

        $tables = Table::where('branch_id', $this->branch->id)
            ->where('available_status', '<>', 'running')
            ->where('status', 'active')
            ->with(['area', 'tableSession.lockedByUser'])
            ->get()
            ->map(function ($table) use ($userId) {
                $session = $table->tableSession;
                $isLocked = $session ? $session->isLocked() : false;
                $isLockedByCurrentUser = $isLocked && $session && $session->locked_by_user_id === $userId;
                $isLockedByOtherUser = $isLocked && $session && $session->locked_by_user_id !== $userId;

                return [
                    'id' => $table->id,
                    'branch_id' => $table->branch_id,
                    'table_code' => $table->table_code,
                    'hash' => $table->hash,
                    'status' => $table->status,
                    'available_status' => $table->available_status,
                    'area_id' => $table->area_id,
                    'area_name' => $table->area ? $table->area->area_name : 'Unknown Area',
                    'seating_capacity' => $table->seating_capacity,
                    'is_locked' => $isLocked,
                    'is_locked_by_current_user' => $isLockedByCurrentUser,
                    'is_locked_by_other_user' => $isLockedByOtherUser,
                    'locked_by_user_id' => $session ? $session->locked_by_user_id : null,
                    'locked_by_user_name' => $session && $session->lockedByUser ? $session->lockedByUser->name : null,
                    'locked_at' => $session && $session->locked_at ? $session->locked_at->format('H:i') : null,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                ];
            });

        return response()->json([
            'tables' => $tables,
            'is_admin' => $isAdmin,
        ]);
    }

    public function getTodayReservations()
    {
        $restaurant = $this->branch->restaurant ?? null;
        $dateFormat = $restaurant->date_format ?? dateFormat();
        $timeFormat = $restaurant->time_format ?? timeFormat();

        $reservations = Reservation::where('branch_id', $this->branch->id)
            ->whereDate('reservation_date_time', today())
            ->whereNotNull('table_id')
            ->with('table')
            ->get()
            ->map(function ($reservation) use ($dateFormat, $timeFormat) {
                return [
                    'id' => $reservation->id,
                    'table_code' => $reservation->table ? $reservation->table->table_code : 'N/A',
                    'time' => $reservation->reservation_date_time->translatedFormat($timeFormat),
                    'datetime' => $reservation->reservation_date_time->translatedFormat($dateFormat . ' ' . $timeFormat),
                    'date' => $reservation->reservation_date_time->translatedFormat($dateFormat),
                    'party_size' => $reservation->party_size,
                    'status' => $reservation->reservation_status,
                ];
            });
        return response()->json($reservations);
    }

    public function forceUnlockTable($tableId)
    {
        $table = Table::find($tableId);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableNotFound'),
            ], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 401);
        }

        $isAdmin = $user->hasRole('Admin_' . $user->restaurant_id);
        $isLockedByCurrentUser = $table->tableSession && $table->tableSession->locked_by_user_id === $user->id;

        if (!($isAdmin || $isLockedByCurrentUser)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableUnlockFailed'),
            ], 403);
        }

        $result = $table->unlock(null, true);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => __('messages.tableUnlockedSuccess', ['table' => $table->table_code]),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableUnlockFailed'),
            ], 500);
        }
    }

    public function getOrderTypes()
    {
        $orderTypes = OrderType::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('order_type_name')
            ->get()
            ->map(function ($orderType) {
                return [
                    'id' => $orderType->id,
                    'slug' => $orderType->slug,
                    'order_type_name' => $orderType->translated_name,
                    'type' => $orderType->type,
                ];
            });

        return response()->json($orderTypes);
    }

    public function getDeliveryPlatforms()
    {
        $deliveryPlatforms = DeliveryPlatform::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'logo' => $platform->logo,
                    'logo_url' => $platform->logo_url ?? null,
                ];
            });

        return response()->json($deliveryPlatforms);
    }

    public function getOrderNumber()
    {
        $orderNumberData = Order::generateOrderNumber($this->branch);

        $formattedOrderNumber = isOrderPrefixEnabled($this->branch)
            ? $orderNumberData['formatted_order_number']
            : __('modules.order.orderNumber') . ' #' . $orderNumberData['order_number'];

        // Return as array format: [order_number, formatted_order_number]
        return response()->json([
            $orderNumberData['order_number'],
            $formattedOrderNumber,
        ]);
    }

    public function submitOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get request data
            $data = $request->all();
            $orderId = $data['order_id'] ?? null;
            $customerData = $data['customer'] ?? [];
            $items = $data['items'] ?? [];
            $taxes = $data['taxes'] ?? [];
            $actions = $data['actions'] ?? [];
            $note = $data['note'] ?? ($data['order_note'] ?? '');
            $orderTypeDisplay = $data['order_type'] ?? 'Dine In';
            $orderNumber = $data['order_number'] ?? '';
            $pax = $data['pax'] ?? 1;
            $waiterId = $data['waiter_id'] ?? null;
            $tableId = $data['table_id'] ?? null;
            $discountType = $data['discount_type'] ?? null;
            $discountValue = $data['discount_value'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $extraChargesData = $data['extra_charges'] ?? [];
            $deliveryExecutiveId = $data['delivery_executive_id'] ?? null;
            $deliveryFee = $data['delivery_fee'] ?? 0;
            $tipAmount = $data['tip_amount'] ?? 0;
            $deliveryAppId = $data['delivery_app_id'] ?? null;
            $pickupDate = $data['pickup_date'] ?? null;
            $ordersToDeleteAfterMerge = $data['orders_to_delete_after_merge'] ?? [];
            $taxMode = $data['tax_mode'] ?? 'order';
            $normalizedActions = collect(is_array($actions) ? $actions : [$actions])
                ->filter()
                ->map(fn($action) => strtolower((string) $action))
                ->values()
                ->toArray();
            $isCancelAction = in_array('cancel', $normalizedActions, true);


            // Use calculated values from frontend (already calculated by calculateTotal())
            $subTotal = $data['sub_total'] ?? 0;
            $total = $data['total'] ?? 0;
            $discountedTotal = $data['discounted_total'] ?? 0;
            $totalTaxAmount = $data['total_tax_amount'] ?? 0;

            // Calculate tax_base following billing rules
            $net = $subTotal - $discountAmount;
            $serviceTotal = 0;
            foreach ($extraChargesData as $chargeData) {
                $chargeId = is_array($chargeData) ? ($chargeData['id'] ?? null) : $chargeData;
                if ($chargeId) {
                    $chargeModel = RestaurantCharge::find($chargeId);
                    if ($chargeModel) {
                        $serviceTotal += $chargeModel->getAmount($net);
                    }
                }
            }
            $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
            $taxBase = $includeChargesInTaxBase ? ($net + $serviceTotal) : $net;

            // Validate required fields (similar to Pos.php)
            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.orderItemRequired'),
                ], 422);
            }

            // Normalize order type for validation
            $normalizedOrderType = strtolower(str_replace(' ', '_', $orderTypeDisplay));
            if ($normalizedOrderType === 'dine in') {
                $normalizedOrderType = 'dine_in';
            }

            if (!$isCancelAction) {
                $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->branch);

                if (!($availability['is_open'] ?? true)) {
                    return response()->json([
                        'success' => false,
                        'message' => RestaurantAvailabilityService::getMessage($availability, $this->restaurant),
                    ], 422);
                }
            }




            // Check if table is locked by another user (similar to Pos.php)
            $table = null;
            if ($tableId && $normalizedOrderType === 'dine_in') {
                $table = Table::find($tableId);
                if ($table && $table->tableSession && $table->tableSession->isLocked()) {
                    $lockedByUser = $table->tableSession->lockedByUser;
                    $lockedUserName = $lockedByUser ? $lockedByUser->name : 'Another user';

                    // Check if current user can access the table
                    $user = auth()->user();
                    if ($user && method_exists($table, 'canBeAccessedByUser') && !$table->canBeAccessedByUser($user->id)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('messages.tableHandledByUser', [
                                'user' => $lockedUserName,
                                'table' => $table->table_code
                            ]),
                        ], 403);
                    }
                }
            }

            // Find or create customer (similar to Pos.php)
            $customerId = null;
            if (!empty($customerData['name']) || !empty($customerData['phone']) || !empty($customerData['email'])) {
                $customer = Customer::firstOrCreate(
                    [
                        'restaurant_id' => $this->restaurant->id,
                        'phone' => $customerData['phone'] ?? null,
                    ],
                    [
                        'name' => $customerData['name'] ?? '',
                        'email' => $customerData['email'] ?? null,
                    ]
                );

                // Update customer data if provided
                if (!empty($customerData['name'])) {
                    $customer->name = $customerData['name'];
                }
                if (!empty($customerData['email'])) {
                    $customer->email = $customerData['email'];
                }
                if (!empty($customerData['phone'])) {
                    $customer->phone = $customerData['phone'];
                }
                $customer->save();
                $customerId = $customer->id;
            }

            // Find order type (similar to Pos.php)
            $orderTypeModel = null;
            $orderTypeId = null;
            $orderTypeSlug = null;
            $orderTypeName = null;

            $orderTypeModel = OrderType::where('branch_id', $this->branch->id)
                ->where('is_active', true)
                ->where(function ($q) use ($normalizedOrderType, $orderTypeDisplay) {
                    $q->where('slug', $normalizedOrderType)
                        ->orWhere('type', $normalizedOrderType)
                        ->orWhere('order_type_name', $orderTypeDisplay);
                })
                ->first();

            if ($orderTypeModel) {
                $orderTypeId = $orderTypeModel->id;
                $orderTypeSlug = $orderTypeModel->slug;
                $orderTypeName = $orderTypeModel->order_type_name;
            } else {
                // Fallback to default order type
                $orderTypeModel = OrderType::where('branch_id', $this->branch->id)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->first();

                if ($orderTypeModel) {
                    $orderTypeId = $orderTypeModel->id;
                    $orderTypeSlug = $orderTypeModel->slug;
                    $orderTypeName = $orderTypeModel->order_type_name;
                } else {
                    $orderTypeSlug = $normalizedOrderType;
                    $orderTypeName = $orderTypeDisplay;
                }
            }


            // Build extra charges array for creating OrderCharge records
            $extraCharges = [];
            if (!empty($extraChargesData) && is_array($extraChargesData)) {
                foreach ($extraChargesData as $charge) {
                    $chargeId = is_array($charge) ? ($charge['id'] ?? null) : $charge;
                    if ($chargeId) {
                        $chargeModel = RestaurantCharge::find($chargeId);
                        if ($chargeModel) {
                            $extraCharges[] = $chargeModel;
                        }
                    }
                }
            }

            // Generate order number (similar to Pos.php)
            $orderNumberData = Order::generateOrderNumber($this->branch);

            // Determine status based on actions (similar to Pos.php saveOrder)
            $status = 'draft';
            $orderStatus = 'placed';
            $tableStatus = 'available';

            $action = !empty($actions) ? $actions[0] : null;

            switch ($action) {
                case 'bill':
                case 'billed':
                    $status = 'billed';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'kot':
                    $status = 'kot';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'cancel':
                    $status = 'canceled';
                    $orderStatus = 'canceled';
                    $tableStatus = 'available';
                    break;
                default:
                    $status = 'draft';
                    $orderStatus = 'placed';
                    $tableStatus = 'available';
            }

            // Get order type name (similar to Pos.php)
            $orderTypeNameFinal = $orderTypeName ?? $orderTypeDisplay;


            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || $deliveryAppId === null) ? null : (int)$deliveryAppId;

            $posMachineId = null;
            if (module_enabled('MultiPOS') && in_array('MultiPOS', restaurant_modules()) && function_exists('pos_machine_id')) {
                $posMachineId = pos_machine_id();
            }

            // Check if updating existing order or creating new one
            $order = null;
            $wasDraft = false;

            if ($orderId) {
                $order = Order::find($orderId);

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found',
                    ], 404);
                }

                // Store original status before update
                $wasDraft = $order->status === 'draft';

                // If converting from draft to KOT/Bill, generate order number
                $orderNumberData = null;
                if ($wasDraft && $action !== 'draft' && !$order->order_number) {
                    $orderNumberData = Order::generateOrderNumber($this->branch);
                }

                // Prepare update data
                $updateData = [
                    'date_time' => now(),
                    'order_type' => $orderTypeSlug ?? $normalizedOrderType,
                    'order_type_id' => $orderTypeId,
                    'custom_order_type_name' => $orderTypeNameFinal,
                    'delivery_executive_id' => ($orderTypeSlug === 'delivery') ? $deliveryExecutiveId : null,
                    'number_of_pax' => $pax,
                    'waiter_id' => $waiterId,
                    'pickup_date' => ($orderTypeSlug === 'pickup') ? $pickupDate : null,
                    'table_id' => $tableId ?? $order->table_id,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'total_tax_amount' => $totalTaxAmount,
                    'delivery_fee' => ($orderTypeSlug === 'delivery') ? $deliveryFee : 0,
                    'delivery_app_id' => ($orderTypeSlug === 'delivery') ? $normalizedDeliveryAppId : null,
                    'tip_amount' => $tipAmount,
                    'status' => $status,
                    'order_status' => $orderStatus,
                    'customer_id' => $customerId,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                ];

                // Add order number if converting from draft
                if ($orderNumberData) {
                    $updateData['order_number'] = $orderNumberData['order_number'];
                    $updateData['formatted_order_number'] = $orderNumberData['formatted_order_number'];
                }

                // Save user ID when bill action is triggered
                $user = auth()->user();
                if ($status == 'billed' && $user) {
                    $updateData['added_by'] = $user->id;
                }

                // Update order
                $order->update($updateData);

                // Delete existing items and taxes to recreate them
                // CRITICAL: When billing a KOT order, do NOT delete order items. kot_items have order_item_id
                // FK to order_items with ON DELETE CASCADE - deleting would cascade-delete kot_items.
                // CRITICAL: Also do NOT delete when order has free stamp items (customer-site redemption).
                $isBillingKotOrder = ($status === 'billed' && $order->kot()->whereHas('items')->exists());
                $hasFreeStampItems = $status === 'billed' && $order->items()->where('is_free_item_from_stamp', true)->exists();
                $preserveOrderItemsOnBill = $isBillingKotOrder || $hasFreeStampItems;
                if ($wasDraft && $status !== 'draft') {
                    // Converting from draft to real order - delete draft items
                    $order->items()->delete();
                } elseif ($status !== 'draft' && !$preserveOrderItemsOnBill) {
                    // Updating a non-draft order - delete items to recreate (skip when billing KOT order or order has free stamp items)
                    $order->items()->delete();
                }
                // When billing KOT order or order has free stamp items, keep existing order_taxes and charges so totals stay correct
                if (!$preserveOrderItemsOnBill) {
                    $order->taxes()->delete();
                    $order->charges()->delete();
                }
            } else {
                // Create order (similar to Pos.php orderData structure)
                $order = Order::create([
                    'order_number' => $action === 'draft' ? null : ($orderNumberData['order_number'] ?? null),
                    'formatted_order_number' => $action === 'draft' ? null : ($orderNumberData['formatted_order_number'] ?? null),
                    'branch_id' => $this->branch->id,
                    'table_id' => $tableId,
                    'date_time' => now(),
                    'number_of_pax' => $pax,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'waiter_id' => $waiterId,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'total_tax_amount' => $totalTaxAmount,
                    'order_type' => $orderTypeSlug ?? $normalizedOrderType,
                    'order_type_id' => $orderTypeId,
                    'custom_order_type_name' => $orderTypeNameFinal,
                    'pickup_date' => ($orderTypeSlug === 'pickup') ? $pickupDate : null,
                    'delivery_fee' => ($orderTypeSlug === 'delivery') ? $deliveryFee : 0,
                    'delivery_executive_id' => ($orderTypeSlug === 'delivery') ? $deliveryExecutiveId : null,
                    'delivery_app_id' => ($orderTypeSlug === 'delivery') ? $normalizedDeliveryAppId : null,
                    'tip_amount' => $tipAmount,
                    'status' => $status,
                    'order_status' => $orderStatus,
                    'placed_via' => 'pos',
                    'tax_mode' => $taxMode,
                    'customer_id' => $customerId,
                    'pos_machine_id' => $posMachineId,
                ]);

                // Save user ID when bill action is triggered (similar to Pos.php)
                $user = auth()->user();
                if ($status == 'billed' && $user) {
                    $order->added_by = $user->id;
                    $order->save();
                }
            }

            // Create extra charges (similar to Pos.php)
            if (!empty($extraCharges)) {
                $chargesData = collect($extraCharges)
                    ->map(fn($charge) => [
                        'charge_id' => $charge->id,
                    ])->toArray();

                $order->charges()->createMany($chargesData);
            }

            // Handle canceled status (similar to Pos.php)
            if ($status == 'canceled') {
                if ($table) {
                    $table->available_status = $tableStatus;
                    $table->saveQuietly();
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => __('messages.orderCanceled'),
                    'order' => $order,
                ], 200);
            }

            // Handle KOT creation (similar to Pos.php)
            $kot = null;
            $kotIds = [];
            $orderItemsAlreadyCreated = false; // Flag to prevent duplicate item creation

            // Check if we need to create KOT (action is 'kot' or second action is 'bill')
            $secondAction = !empty($actions) && count($actions) > 1 ? $actions[1] : null;
            $thirdAction = !empty($actions) && count($actions) > 2 ? $actions[2] : null;
            $shouldCreateKot = ($status == 'kot');

            if ($shouldCreateKot) {
                // For now, create single KOT (can be extended for kitchen places later)
                $kot = Kot::create([
                    'branch_id' => $this->branch->id,
                    'kot_number' => Kot::generateKotNumber($this->branch),
                    'order_id' => $order->id,
                    'order_type_id' => $orderTypeId,
                    'token_number' => Kot::generateTokenNumber($this->branch->id, $orderTypeId),
                    'note' => $note,
                ]);

                $kotIds[] = $kot->id;

                // Create KOT items (similar to Pos.php)
                foreach ($items as $item) {
                    $menuItemId = $item['id'] ?? null;
                    $variantId = $item['variant_id'] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
                    $itemNote = $item['note'] ?? null;
                    $modifierIds = $item['modifier_ids'] ?? [];

                    $kotItem = KotItem::create([
                        'kot_id' => $kot->id,
                        'menu_item_id' => $menuItemId,
                        'menu_item_variation_id' => $variantId > 0 ? $variantId : null,
                        'quantity' => $quantity,
                        'note' => $itemNote,
                        'order_type_id' => $orderTypeId ?? null,
                        'order_type' => $orderTypeSlug ?? null,
                    ]);

                    // Sync modifiers if provided (similar to Pos.php)
                    if (!empty($modifierIds) && is_array($modifierIds)) {
                        $kotItem->modifierOptions()->sync($modifierIds);
                    }
                }

                // Recalculate totals after KOT creation ONLY if editing an existing order
                // This matches the Livewire component logic: if ($this->orderID) { ... }
                if ($orderId) {
                    // Recalculate from KOT items (matching Livewire component lines 2443-2497)
                    $recalculatedSubTotal = 0;
                    $recalculatedTotal = 0;

                    foreach ($order->kot as $kot) {
                        foreach ($kot->items->where('status', '!=', 'cancelled') as $item) {
                            $menuItemPrice = $item->menuItem->price ?? 0;

                            // Add modifier prices if any
                            $modifierPrice = 0;
                            if ($item->modifierOptions->isNotEmpty()) {
                                $modifierPrice = $item->modifierOptions->sum('price');
                            }

                            $recalculatedSubTotal += ($menuItemPrice + $modifierPrice) * $item->quantity;
                            $recalculatedTotal += ($menuItemPrice + $modifierPrice) * $item->quantity;
                        }
                    }

                    // Discount calculation
                    $recalculatedDiscountAmount = 0;
                    if ($order->discount_type === 'percent') {
                        $recalculatedDiscountAmount = round(($recalculatedSubTotal * $order->discount_value) / 100, 2);
                    } elseif ($order->discount_type === 'fixed') {
                        $recalculatedDiscountAmount = min($order->discount_value, $recalculatedSubTotal);
                    }
                    $recalculatedDiscountedTotal = $recalculatedTotal - $recalculatedDiscountAmount;

                    // Step 2: Calculate service charges on discountedTotal
                    $serviceTotal = 0;
                    foreach ($order->extraCharges ?? [] as $charge) {
                        $chargeAmount = $charge->getAmount($recalculatedDiscountedTotal);
                        $serviceTotal += $chargeAmount;
                        $recalculatedTotal += $chargeAmount;
                    }

                    // Step 3: Calculate tax_base based on setting
                    $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
                    if ($includeChargesInTaxBase) {
                        $recalculatedTaxBase = $recalculatedDiscountedTotal + $serviceTotal;
                    } else {
                        $recalculatedTaxBase = $recalculatedDiscountedTotal;
                    }

                    // Step 4: Calculate taxes on tax_base
                    $recalculatedTaxAmount = 0;
                    if (!empty($taxes) && is_array($taxes)) {
                        foreach ($taxes as $tax) {
                            if (isset($tax['tax_percent'])) {
                                $taxPercent = $tax['tax_percent'] ?? 0;
                                $taxAmount = ($recalculatedTaxBase * $taxPercent) / 100;
                                $recalculatedTotal += $taxAmount;
                                $recalculatedTaxAmount += $taxAmount;
                            }
                        }
                    }

                    // Add tip and delivery
                    if ($tipAmount > 0) {
                        $recalculatedTotal += $tipAmount;
                    }
                    if ($deliveryFee > 0) {
                        $recalculatedTotal += $deliveryFee;
                    }

                    $recalculatedTotal -= $recalculatedDiscountAmount;

                    // Update order with recalculated totals
                    $order->update([
                        'sub_total' => $recalculatedSubTotal,
                        'total' => $recalculatedTotal,
                        'discount_amount' => $recalculatedDiscountAmount,
                        'total_tax_amount' => $recalculatedTaxAmount,
                        'tax_base' => $recalculatedTaxBase,
                        'tax_mode' => $taxMode,
                    ]);
                }
                // For new orders, totals are already correct from frontend - no recalculation needed

                // If second action is 'bill', update order status to 'billed' and create order items
                if ($secondAction === 'bill' && $thirdAction === 'payment') {
                    // Update order status to billed
                    $order->update([
                        'status' => 'billed',
                        'order_status' => 'confirmed',
                    ]);

                    // Now create order items for billing
                    foreach ($items as $item) {
                        $menuItemId = $item['id'] ?? null;
                        $variantId = $item['variant_id'] ?? 0;
                        $quantity = $item['quantity'] ?? 1;
                        $price = $item['price'] ?? 0;
                        $itemNote = $item['note'] ?? null;
                        $amount = $price * $quantity;
                        $modifierIds = $item['modifier_ids'] ?? [];
                        $taxAmount = $item['tax_amount'] ?? 0;
                        $taxPercentage = $item['tax_percentage'] ?? 0;
                        $taxBreakup = $item['tax_breakup'] ?? null;

                        // Get menu item to set price context if needed
                        $menuItem = MenuItem::find($menuItemId);
                        if ($menuItem && $orderTypeId) {
                            if (method_exists($menuItem, 'setPriceContext')) {
                                $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                                $price = $menuItem->price ?? $price;
                            }
                        }

                        $orderItem = OrderItem::create([
                            'branch_id' => $this->branch->id,
                            'order_id' => $order->id,
                            'menu_item_id' => $menuItemId,
                            'menu_item_variation_id' => $variantId > 0 ? $variantId : null,
                            'quantity' => $quantity,
                            'price' => $price,
                            'amount' => $amount,
                            'note' => $itemNote,
                            'order_type' => $orderTypeSlug ?? null,
                            'order_type_id' => $orderTypeId ?? null,
                            'tax_amount' => $taxAmount,
                            'tax_percentage' => $taxPercentage,
                            'tax_breakup' => is_array($taxBreakup) ? json_encode($taxBreakup) : $taxBreakup,
                        ]);

                        // Sync modifiers if provided
                        if (!empty($modifierIds) && is_array($modifierIds)) {
                            $orderItem->modifierOptions()->sync($modifierIds);
                        }
                    }

                    // Create order taxes (order level)
                    if (!empty($taxes) && is_array($taxes)) {
                        foreach ($taxes as $tax) {
                            if (isset($tax['id'])) {
                                OrderTax::create([
                                    'order_id' => $order->id,
                                    'tax_id' => $tax['id'],
                                ]);
                            }
                        }
                    }

                    // Refresh order to get latest discount values
                    $order->refresh();

                    // Recalculate totals based on actual items (matching Livewire component logic)
                    $recalculatedSubTotal = $order->items()->sum('amount');
                    $recalculatedTotal = $recalculatedSubTotal;
                    $recalculatedDiscountedTotal = $recalculatedTotal;

                    // Recalculate discount amount from order (matching Livewire: uses $order->discount_type and $order->discount_value)
                    $recalculatedDiscountAmount = 0;
                    if ($order->discount_type === 'percent') {
                        $recalculatedDiscountAmount = round(($recalculatedSubTotal * $order->discount_value) / 100, 2);
                    } elseif ($order->discount_type === 'fixed') {
                        $recalculatedDiscountAmount = min($order->discount_value, $recalculatedSubTotal);
                    }

                    // Apply discount first (matching Livewire: total -= discountAmount)
                    $recalculatedTotal -= $recalculatedDiscountAmount;
                    $recalculatedDiscountedTotal = $recalculatedTotal;

                    // Step 2: Calculate service charges on discountedTotal
                    $serviceTotal = 0;
                    $orderCharges = OrderCharge::where('order_id', $order->id)->with('charge')->get();
                    foreach ($orderCharges as $orderCharge) {
                        if ($orderCharge->charge) {
                            $chargeAmount = $orderCharge->charge->getAmount($recalculatedDiscountedTotal);
                            $serviceTotal += $chargeAmount;
                            $recalculatedTotal += $chargeAmount;
                        }
                    }

                    // Step 3: Calculate tax_base based on setting
                    $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
                    if ($includeChargesInTaxBase) {
                        $recalculatedTaxBase = $recalculatedDiscountedTotal + $serviceTotal;
                    } else {
                        $recalculatedTaxBase = $recalculatedDiscountedTotal;
                    }

                    // Step 4: Calculate taxes on tax_base
                    $orderTaxes = OrderTax::where('order_id', $order->id)->with('tax')->get();
                    $recalculatedTaxAmount = 0;

                    foreach ($orderTaxes as $orderTax) {
                        if ($orderTax->tax) {
                            $taxPercent = $orderTax->tax->tax_percent ?? 0;
                            $taxAmount = ($recalculatedTaxBase * $taxPercent) / 100;
                            $recalculatedTotal += $taxAmount;
                            $recalculatedTaxAmount += $taxAmount;
                        }
                    }

                    // Add tip and delivery fees
                    if ($tipAmount > 0) {
                        $recalculatedTotal += $tipAmount;
                    }
                    if ($deliveryFee > 0) {
                        $recalculatedTotal += $deliveryFee;
                    }

                    // Update order with recalculated totals
                    $order->update([
                        'sub_total' => $recalculatedSubTotal,
                        'total' => max(0, $recalculatedTotal),
                        'discount_amount' => $recalculatedDiscountAmount,
                        'total_tax_amount' => $recalculatedTaxAmount,
                        'tax_base' => $recalculatedTaxBase,
                        'tax_mode' => $taxMode,
                    ]);

                    // Update status variable for correct response message
                    $status = 'billed';

                    // Mark that order items have been created to prevent duplicate creation
                    $orderItemsAlreadyCreated = true;
                }
            }

            // Create order items (for 'draft' status only, similar to Pos.php)
            if ($status == 'draft') {
                // Persist draft items as OrderItems so draft orders can be reopened/edited
                // Always recreate items for draft saves to match the current cart
                $order->items()->delete();

                foreach ($items as $item) {
                    $menuItemId = $item['id'] ?? null;
                    $variantId = $item['variant_id'] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
                    $price = $item['price'] ?? 0;
                    $itemNote = $item['note'] ?? null;
                    $amount = $price * $quantity;
                    $modifierIds = $item['modifier_ids'] ?? [];

                    // Set price context if possible (same as billed)
                    $menuItem = MenuItem::find($menuItemId);
                    if ($menuItem && $orderTypeId) {
                        if (method_exists($menuItem, 'setPriceContext')) {
                            $menuItem->setPriceContext($orderTypeId, null);
                            $price = $menuItem->price ?? $price;
                            $amount = $price * $quantity;
                        }
                    }

                    $orderItem = OrderItem::create([
                        'branch_id' => $this->branch->id,
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItemId,
                        'menu_item_variation_id' => $variantId > 0 ? $variantId : null,
                        'quantity' => $quantity,
                        'price' => $price,
                        'amount' => $amount,
                        'note' => $itemNote,
                        'order_type' => $orderTypeSlug ?? null,
                        'order_type_id' => $orderTypeId ?? null,
                    ]);

                    if (!empty($modifierIds) && is_array($modifierIds)) {
                        $orderItem->modifierOptions()->sync($modifierIds);
                    }
                }
            }

            // Create order items (for 'billed' status only, similar to Pos.php)
            // Skip if items were already created in KOT+Bill+Payment flow
            if ($status == 'billed' && !$orderItemsAlreadyCreated) {
                foreach ($items as $item) {
                    $menuItemId = $item['id'] ?? null;
                    $variantId = $item['variant_id'] ?? 0;
                    $quantity = $item['quantity'] ?? 1;
                    $price = $item['price'] ?? 0;
                    $itemNote = $item['note'] ?? null;
                    $amount = $price * $quantity;
                    $modifierIds = $item['modifier_ids'] ?? [];

                    // Get menu item to set price context if needed (similar to Pos.php)
                    $menuItem = MenuItem::find($menuItemId);
                    if ($menuItem && $orderTypeId) {
                        // Set price context if orderTypeId is available
                        if (method_exists($menuItem, 'setPriceContext')) {
                            $menuItem->setPriceContext($orderTypeId, null);
                            $price = $menuItem->price ?? $price;
                        }
                    }

                    $orderItem = OrderItem::create([
                        'branch_id' => $this->branch->id,
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItemId,
                        'menu_item_variation_id' => $variantId > 0 ? $variantId : null,
                        'quantity' => $quantity,
                        'price' => $price,
                        'amount' => $amount,
                        'note' => $itemNote,
                        'order_type' => $orderTypeSlug ?? null,
                        'order_type_id' => $orderTypeId ?? null,
                    ]);

                    // Sync modifiers if provided (similar to Pos.php)
                    if (!empty($modifierIds) && is_array($modifierIds)) {
                        $orderItem->modifierOptions()->sync($modifierIds);
                    }
                }

                // Create order taxes (order level, similar to Pos.php)
                if (!empty($taxes) && is_array($taxes)) {
                    foreach ($taxes as $tax) {
                        if (isset($tax['id'])) {
                            OrderTax::create([
                                'order_id' => $order->id,
                                'tax_id' => $tax['id'],
                            ]);
                        }
                    }
                }

                // Refresh order to get latest discount values
                $order->refresh();

                // Recalculate totals based on actual items (matching Livewire component logic)
                $recalculatedSubTotal = $order->items()->sum('amount');
                $recalculatedTotal = $recalculatedSubTotal;
                $recalculatedDiscountedTotal = $recalculatedTotal;

                // Recalculate discount amount from order (matching Livewire: uses $order->discount_type and $order->discount_value)
                $recalculatedDiscountAmount = 0;
                if ($order->discount_type === 'percent') {
                    $recalculatedDiscountAmount = round(($recalculatedSubTotal * $order->discount_value) / 100, 2);
                } elseif ($order->discount_type === 'fixed') {
                    $recalculatedDiscountAmount = min($order->discount_value, $recalculatedSubTotal);
                }

                // Apply discount first (matching Livewire: total -= discountAmount)
                $recalculatedTotal -= $recalculatedDiscountAmount;
                $recalculatedDiscountedTotal = $recalculatedTotal;

                // Step 2: Calculate service charges on discountedTotal
                $serviceTotal = 0;
                $orderCharges = OrderCharge::where('order_id', $order->id)->with('charge')->get();
                foreach ($orderCharges as $orderCharge) {
                    if ($orderCharge->charge) {
                        $chargeAmount = $orderCharge->charge->getAmount($recalculatedDiscountedTotal);
                        $serviceTotal += $chargeAmount;
                        $recalculatedTotal += $chargeAmount;
                    }
                }

                // Step 3: Calculate tax_base based on setting
                $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
                if ($includeChargesInTaxBase) {
                    $recalculatedTaxBase = $recalculatedDiscountedTotal + $serviceTotal;
                } else {
                    $recalculatedTaxBase = $recalculatedDiscountedTotal;
                }

                // Step 4: Calculate taxes on tax_base
                $orderTaxes = OrderTax::where('order_id', $order->id)->with('tax')->get();
                $recalculatedTaxAmount = 0;

                foreach ($orderTaxes as $orderTax) {
                    if ($orderTax->tax) {
                        $taxPercent = $orderTax->tax->tax_percent ?? 0;
                        $taxAmount = ($recalculatedTaxBase * $taxPercent) / 100;
                        $recalculatedTotal += $taxAmount;
                        $recalculatedTaxAmount += $taxAmount;
                    }
                }

                // Add tip and delivery fees
                if ($tipAmount > 0) {
                    $recalculatedTotal += $tipAmount;
                }
                if ($deliveryFee > 0) {
                    $recalculatedTotal += $deliveryFee;
                }

                // Update order with recalculated totals (matching Livewire component)
                $order->update([
                    'sub_total' => $recalculatedSubTotal,
                    'total' => max(0, $recalculatedTotal),
                    'discount_amount' => $recalculatedDiscountAmount,
                    'total_tax_amount' => $recalculatedTaxAmount,
                    'tax_base' => $recalculatedTaxBase,
                    'tax_mode' => $taxMode,
                ]);
            }

            // Update table status (similar to Pos.php)
            if ($table) {
                $table->available_status = $tableStatus;
                $table->saveQuietly();
            }

            DB::commit();

            // Delete merged table orders if order is KOT or billed (not draft)
            // This handles the case when merging tables and saving the order
            if ($status !== 'draft' && !empty($ordersToDeleteAfterMerge) && is_array($ordersToDeleteAfterMerge)) {
                try {
                    // Get all orders to delete with their relationships
                    $ordersToDelete = Order::whereIn('id', $ordersToDeleteAfterMerge)
                        ->where('branch_id', $this->branch->id) // Ensure we only delete orders from this branch
                        ->with(['kot.items', 'items', 'taxes', 'charges'])
                        ->get();

                    if ($ordersToDelete->isNotEmpty()) {
                        $orderIds = $ordersToDelete->pluck('id')->toArray();

                        // Collect KOT IDs from loaded relationships
                        $kotIds = $ordersToDelete->flatMap(function ($order) {
                            return $order->kot->pluck('id');
                        })->filter()->unique()->toArray();

                        // Bulk delete KOT items
                        if (!empty($kotIds)) {
                            KotItem::whereIn('kot_id', $kotIds)->delete();
                            Kot::whereIn('id', $kotIds)->delete();
                        }

                        // Bulk delete order items, taxes, and charges
                        OrderItem::whereIn('order_id', $orderIds)->delete();
                        OrderTax::whereIn('order_id', $orderIds)->delete();
                        OrderCharge::whereIn('order_id', $orderIds)->delete();

                        // Get table IDs from orders before deleting
                        $tableIds = $ordersToDelete->pluck('table_id')->filter()->unique()->toArray();

                        // Bulk delete orders
                        Order::whereIn('id', $orderIds)->delete();

                        // Update table statuses and unlock tables
                        if (!empty($tableIds)) {
                            Table::whereIn('id', $tableIds)->update(['available_status' => 'available']);
                            foreach ($tableIds as $tableId) {
                                $tableToUnlock = Table::find($tableId);
                                if ($tableToUnlock) {
                                    $tableToUnlock->unlock(null, true);
                                }
                            }
                        }

                        $deletedCount = count($orderIds);
                        Log::info("Deleted {$deletedCount} order(s) from merged tables via API");
                    }

                    // Clear session data after successful deletion
                    session()->forget('pos_merged_orders_to_delete');
                } catch (\Exception $e) {
                    Log::error('Error deleting merged table orders via API: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'order_ids' => $ordersToDeleteAfterMerge,
                    ]);
                    // Clear session even on error to prevent retry issues
                    session()->forget('pos_merged_orders_to_delete');
                }
            }

            // Get payment gateway QR code if applicable
            $paymentGateway = $this->restaurant->paymentGateways;
            $qrCodeImageUrl = $paymentGateway && $paymentGateway->is_qr_payment_enabled ? $paymentGateway->qr_code_image_url : null;

            if ($status === 'billed') {
                $customerDisplayData = [
                    'order_number' => $order->order_number,
                    'formatted_order_number' => $order->formatted_order_number,
                    'items' => [],
                    'sub_total' => 0,
                    'discount' => 0,
                    'total' => $order->total,
                    'taxes' => [],
                    'extra_charges' => [],
                    'tip' => $order->tip_amount ?? 0,
                    'delivery_fee' => $order->delivery_fee ?? 0,
                    'order_type' => $orderTypeDisplay,
                    'status' => 'billed',
                    'cash_due' => $order->total,
                    'qr_code_image_url' => $qrCodeImageUrl,
                ];
                $this->updateCustomerDisplayCache($customerDisplayData);
            } else {
                // For other statuses (kot, draft), reset to idle (matches Livewire pattern)
                $customerDisplayData = [
                    'order_number' => null,
                    'formatted_order_number' => null,
                    'items' => [],
                    'sub_total' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'taxes' => [],
                    'extra_charges' => [],
                    'tip' => 0,
                    'delivery_fee' => 0,
                    'order_type' => null,
                    'status' => 'idle',
                    'cash_due' => null,
                    'qr_code_image_url' => null,
                ];
                $this->updateCustomerDisplayCache($customerDisplayData);
            }

            // Load relationships for response
            $order->load(['items', 'customer', 'table', 'waiter', 'kot']);

            // Return success message based on status (similar to Pos.php)
            $successMessage = 'Order created successfully';
            if ($status == 'kot') {
                $successMessage = __('messages.kotGenerated');
            } elseif ($status == 'billed') {
                $successMessage = __('messages.billedSuccess');
            } elseif ($status == 'draft') {
                $successMessage = __('messages.orderSavedAsDraft');
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'order' => $order,
                'order_id' => $order->id,  // Also include order_id for easier access
                'kot' => $kot,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('POS Order Creation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrder($id)
    {
        $order = Order::with([
            'items',
            'customer',
            'table',
            'waiter',
            'kot' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'kot.items',
            'kot.items.menuItem'
        ])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order fetched successfully',
            'order' => $order,
        ], 200);
    }

    public function getOrders($status = null)
    {
        $orders = Order::where('branch_id', $this->branch->id)
            ->with('items', 'customer', 'table', 'waiter', 'kot', 'kot.items', 'kot.items.menuItem');

        if ($status) {
            $orders->where('order_status', OrderStatus::from($status));
        }

        $orders = OrderResource::collection($orders->get());
        return response()->json($orders);
    }

    public function getTaxes()
    {
        $taxes = Tax::get();
        return response()->json($taxes);
    }

    public function getRestaurants()
    {
        $restaurant = Restaurant::with('currency')->where('id', $this->restaurant->id)->first();
        return response()->json($restaurant);
    }

    public function addCartItem(Request $request)
    {
        $menuItemId = $request->input('menu_item_id');
        $variationId = $request->input('variation_id');
        $orderTypeId = $request->input('order_type_id');
        $deliveryAppId = $request->input('delivery_app_id');

        $menuItem = MenuItem::with(['prices', 'variations.prices', 'modifierGroups.options.prices'])->find($menuItemId);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Menu item not found'], 404);
        }

        // Set price context
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || !$deliveryAppId) ? null : (int)$deliveryAppId;
            $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
        }

        return response()->json([
            'success' => true,
            'menu_item' => $menuItem,
            'has_variations' => $menuItem->variations->count() > 0,
            'has_modifiers' => $menuItem->modifierGroups->count() > 0
        ]);
    }

    public function updateCartItem(Request $request)
    {
        // Handle cart item updates
        return response()->json(['success' => true]);
    }

    public function deleteCartItem(Request $request)
    {
        $itemKey = $request->input('item_key');
        $orderId = $request->input('order_id');

        if (!$itemKey) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.itemNotFound')
            ], 400);
        }

        // Parse the item key to determine if it's a draft order item or KOT item
        $parts = explode('_', str_replace('"', '', $itemKey));

        // Check if it's a draft order item (format: order_item_123)
        if (count($parts) >= 3 && $parts[0] === 'order' && $parts[1] === 'item') {
            $orderItemId = $parts[2];

            if ($orderId) {
                return $this->deleteOrderItem($orderId, $orderItemId);
            }

            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check if it's a KOT item (format: kot_123_456)
        if (count($parts) >= 3 && $parts[0] === 'kot') {
            $kotId = $parts[1];
            $itemId = $parts[2];

            if ($orderId) {
                return $this->deleteOrderItem($orderId, $itemId);
            }

            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // For new items not yet saved (no prefix), just return success
        // as they only exist in client-side state
        return response()->json([
            'success' => true,
            'message' => __('modules.order.itemDeleted')
        ]);
    }

    public function setTable(Request $request)
    {
        $tableId = $request->input('table_id');
        $orderId = $request->input('order_id');
        $table = Table::find($tableId);

        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Table not found'], 404);
        }

        // Check table lock
        if (!$table->canBeAccessedByUser(auth()->id())) {
            $session = $table->tableSession;
            $lockedByUser = $session?->lockedByUser;
            $lockedUserName = $lockedByUser?->name ?? 'Another user';

            return response()->json([
                'success' => false,
                'message' => __('messages.tableLockedByUser', ['user' => $lockedUserName])
            ], 403);
        }

        // Lock table
        $lockResult = $table->lockForUser(auth()->id());

        if (!$lockResult['success']) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableLockFailed')
            ], 500);
        }

        // If order ID is provided, update the order's table
        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $order->update(['table_id' => $tableId]);

                // Update table status if order is from today
                if ($order->date_time && $order->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                    Table::where('id', $tableId)->update(['available_status' => 'running']);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.tableLocked', ['table' => $table->table_code]),
            'table' => $table
        ]);
    }

    public function setCustomer(Request $request)
    {
        $customerId = $request->input('customer_id');
        $customer = Customer::find($customerId);

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    public function saveOrder(Request $request)
    {
        // This is similar to submitOrder but for updating existing orders
        return $this->submitOrder($request);
    }

    public function getMenuItem($id)
    {
        $orderTypeId = request()->input('order_type_id');
        $deliveryAppId = request()->input('delivery_app_id');

        $menuItem = MenuItem::with(['prices', 'variations.prices', 'modifierGroups.options.prices'])->find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Menu item not found'], 404);
        }

        // Set price context
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || !$deliveryAppId) ? null : (int)$deliveryAppId;
            $menuItem->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
        }

        return response()->json([
            'success' => true,
            'menu_item' => $menuItem
        ]);
    }

    public function getMenuItemVariations($id)
    {
        $orderTypeId = request()->input('order_type_id');
        $deliveryAppId = request()->input('delivery_app_id');

        $menuItem = MenuItem::with(['variations.prices'])->find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Menu item not found'], 404);
        }

        $variations = $menuItem->variations;

        // Set price context
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || !$deliveryAppId) ? null : (int)$deliveryAppId;
            foreach ($variations as $variation) {
                $variation->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
            }
        }

        // Generate HTML for variations modal
        $html = view('pos.variations-modal', [
            'menuItem' => $menuItem,
            'variations' => $variations,
            'orderTypeId' => $orderTypeId,
            'deliveryAppId' => $deliveryAppId
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'variations' => $variations
        ]);
    }

    public function getMenuItemModifiers($id)
    {
        $orderTypeId = request()->input('order_type_id');
        $deliveryAppId = request()->input('delivery_app_id');
        $variationId = request()->input('variation_id');

        $menuItem = MenuItem::with(['modifierGroups.options.prices', 'modifierGroups.itemModifiers'])->find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Menu item not found'], 404);
        }

        // Get base modifiers (where variation_id is null)
        $baseModifiers = \App\Models\ModifierGroup::whereHas('itemModifiers', function ($query) use ($id) {
            $query->where('menu_item_id', $id)
                ->whereNull('menu_item_variation_id');
        })->with(['options', 'itemModifiers' => function ($query) use ($id) {
            $query->where('menu_item_id', $id)
                ->whereNull('menu_item_variation_id');
        }])->get();

        $modifierGroups = $baseModifiers;

        // If we have a variation, add variation-specific modifiers
        if ($variationId) {
            $variationSpecificModifiers = \App\Models\ModifierGroup::whereHas('itemModifiers', function ($query) use ($id, $variationId) {
                $query->where('menu_item_id', $id)
                    ->where('menu_item_variation_id', $variationId);
            })->with(['options', 'itemModifiers' => function ($query) use ($id, $variationId) {
                $query->where('menu_item_id', $id)
                    ->where('menu_item_variation_id', $variationId);
            }])->get();

            $modifierGroups = $baseModifiers->concat($variationSpecificModifiers);
        }

        // Set price context
        if ($orderTypeId) {
            $normalizedDeliveryAppId = ($deliveryAppId === 'default' || !$deliveryAppId) ? null : (int)$deliveryAppId;
            foreach ($modifierGroups as $group) {
                foreach ($group->options as $option) {
                    $option->setPriceContext($orderTypeId, $normalizedDeliveryAppId);
                }
            }
        }

        // Generate HTML for modifiers modal
        $html = view('pos.modifiers-modal', [
            'menuItem' => $menuItem,
            'modifierGroups' => $modifierGroups,
            'orderTypeId' => $orderTypeId,
            'deliveryAppId' => $deliveryAppId,
            'variationId' => $variationId
        ])->render();

        // Prepare modifier options data for JavaScript
        $modifierOptionsData = [];
        foreach ($modifierGroups as $group) {
            foreach ($group->options as $option) {
                $modifierOptionsData[$option->id] = [
                    'id' => $option->id,
                    'name' => $option->name,
                    'price' => $option->price,
                    'groupId' => $group->id
                ];
            }
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'modifier_groups' => $modifierGroups,
            'modifier_options' => $modifierOptionsData
        ]);
    }

    public function calculateTotal(Request $request)
    {
        $items = $request->input('items', []);
        $discountType = $request->input('discount_type');
        $discountValue = $request->input('discount_value', 0);
        $extraCharges = $request->input('extra_charges', []);
        $deliveryFee = $request->input('delivery_fee', 0);
        $tipAmount = $request->input('tip_amount', 0);
        $taxMode = $request->input('tax_mode', 'order');
        $includeChargesInTaxBase = $request->input('include_charges_in_tax_base', true);

        $subTotal = 0;
        $totalTaxAmount = 0;
        $orderItemTaxDetails = [];

        // Get restaurant settings
        $restaurant = $this->restaurant;
        $taxes = $restaurant->taxes ?? [];
        $isInclusive = $restaurant->tax_inclusive ?? false;

        // Calculate subtotal and item taxes
        foreach ($items as $key => $item) {
            $price = $item['price'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $itemTotal = $price * $quantity;

            if ($taxMode === 'item') {
                // Calculate item-level taxes
                $itemTaxes = $item['taxes'] ?? $taxes;
                $itemTaxAmount = 0;

                if ($itemTaxes && count($itemTaxes) > 0) {
                    $totalTaxPercent = 0;
                    foreach ($itemTaxes as $tax) {
                        $totalTaxPercent += $tax['tax_percent'] ?? 0;
                    }

                    foreach ($itemTaxes as $tax) {
                        $taxPercent = $tax['tax_percent'] ?? 0;
                        $taxAmount = 0;

                        if ($isInclusive) {
                            $taxAmount = ($price * $taxPercent) / (100 + $totalTaxPercent);
                        } else {
                            $taxAmount = ($price * $taxPercent) / 100;
                        }

                        $itemTaxAmount += $taxAmount;
                    }
                }

                $orderItemTaxDetails[$key] = [
                    'tax_amount' => $itemTaxAmount * $quantity,
                    'base_price' => $price,
                    'qty' => $quantity
                ];

                if ($isInclusive) {
                    $subTotal += ($itemTotal - ($itemTaxAmount * $quantity));
                } else {
                    $subTotal += $itemTotal;
                }
            } else {
                $subTotal += $itemTotal;
            }
        }

        // Calculate discount
        $discountAmount = 0;
        if ($discountType === 'percent') {
            $discountAmount = ($subTotal * $discountValue) / 100;
        } elseif ($discountType === 'fixed') {
            $discountAmount = min($discountValue, $subTotal);
        }

        $discountedTotal = $subTotal - $discountAmount;

        // Calculate service charges
        $serviceTotal = 0;
        $total = $discountedTotal;

        foreach ($extraCharges as $charge) {
            if (is_array($charge) && isset($charge['amount'])) {
                $total += $charge['amount'];
                $serviceTotal += $charge['amount'];
            }
        }

        // Calculate tax_base
        $taxBase = $includeChargesInTaxBase ? $discountedTotal + $serviceTotal : $discountedTotal;

        // Calculate taxes
        if ($taxMode === 'order') {
            foreach ($taxes as $tax) {
                $taxAmount = ($tax['tax_percent'] / 100) * $taxBase;
                $totalTaxAmount += $taxAmount;
                $total += $taxAmount;
            }
        } elseif ($taxMode === 'item') {
            $totalInclusiveTax = 0;
            $totalExclusiveTax = 0;

            foreach ($orderItemTaxDetails as $taxDetail) {
                $taxAmount = $taxDetail['tax_amount'] ?? 0;

                if ($isInclusive) {
                    $totalInclusiveTax += $taxAmount;
                } else {
                    $totalExclusiveTax += $taxAmount;
                }
            }

            $totalTaxAmount = $totalInclusiveTax + $totalExclusiveTax;

            if ($totalExclusiveTax > 0) {
                $total += $totalExclusiveTax;
            }
        }

        // Add delivery fee and tip
        $total += $deliveryFee + $tipAmount;

        // Ensure total is not negative
        $total = max(0, $total);

        return response()->json([
            'success' => true,
            'sub_total' => $subTotal,
            'discount_amount' => $discountAmount,
            'discounted_total' => $discountedTotal,
            'service_total' => $serviceTotal,
            'tax_base' => $taxBase,
            'total_tax_amount' => $totalTaxAmount,
            'total' => $total,
            'order_item_tax_details' => $orderItemTaxDetails
        ]);
    }

    public function getTablesWithUnpaidOrders()
    {
        // Fetch tables that have orders which are not paid
        $unpaidOrders = Order::where('branch_id', $this->branch->id)
            ->whereNotNull('table_id')
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'canceled')
            ->with([
                'table',
                'items.menuItem',
                'items.menuItemVariation',
                'items.modifierOptions',
                'kot.items.menuItem',
                'kot.items.menuItemVariation',
                'kot.items.modifierOptions'
            ])
            ->get();

        // Group by table_id and get unique tables
        $tableIds = $unpaidOrders->pluck('table_id')->unique()->filter();

        $tables = Table::whereIn('id', $tableIds)
            ->where('branch_id', $this->branch->id)
            ->with([
                'activeOrder.items.menuItem',
                'activeOrder.items.menuItemVariation',
                'activeOrder.items.modifierOptions',
                'activeOrder.kot' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                },
                'activeOrder.kot.items.menuItem',
                'activeOrder.kot.items.menuItemVariation',
                'activeOrder.kot.items.modifierOptions'
            ])
            ->orderBy('table_code')
            ->get()
            ->map(function ($table) use ($unpaidOrders) {
                // Attach unpaid orders to each table
                $table->unpaidOrders = $unpaidOrders->where('table_id', $table->id)->values();
                return $table;
            });

        return response()->json([
            'success' => true,
            'tables' => $tables
        ]);
    }

    public function mergeTables(Request $request)
    {
        $tableIds = $request->input('table_ids', []);
        $currentTableId = $request->input('current_table_id');
        $orderTypeId = $request->input('order_type_id');

        if (empty($tableIds) || !is_array($tableIds)) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.selectAtLeastOneTable')
            ], 422);
        }
        try {
            // Get all unpaid orders for selected tables
            $ordersToMerge = [];
            $mergedOrderItemIds = [];

            foreach ($tableIds as $tableId) {
                // Skip current table if it's in the list
                if ($currentTableId && $tableId == $currentTableId) {
                    continue;
                }

                $unpaidOrders = Order::where('table_id', $tableId)
                    ->where('branch_id', $this->branch->id)
                    ->where('status', '!=', 'paid')
                    ->where('status', '!=', 'canceled')
                    ->with([
                        'items.menuItem',
                        'items.menuItemVariation',
                        'items.modifierOptions',
                        'kot' => function ($query) {
                            $query->orderBy('created_at', 'asc');
                        },
                        'kot.items.menuItem',
                        'kot.items.menuItemVariation',
                        'kot.items.modifierOptions'
                    ])
                    ->get();

                    foreach ($unpaidOrders as $order) {
                        $ordersToMerge[] = $order->id;
                        $mergedData['order_ids_to_delete'][] = $order->id;
                    }
            }

            if (empty($ordersToMerge)) {
                return response()->json([
                    'success' => false,
                    'message' => __('modules.order.noOrdersToMerge')
                ], 422);
            }

            // Store merge data in session to be used by POS after reload
            session()->put('pos_merge_data', [
                'order_item_ids' => $mergedData['order_item_ids'],
                'kot_item_ids' => $mergedData['kot_item_ids'],
                'order_ids_to_delete' => $mergedData['order_ids_to_delete'],
                'orders_to_merge' => $ordersToMerge,
                'merged_at' => now()->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => __('modules.order.tablesReadyToMerge'),
                'data' => [
                    'orders_count' => count($ordersToMerge),
                    'reload_required' => true
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error merging tables: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.somethingWentWrong')
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check permission
        if (!user_can('Update Order')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.permissionDenied')
            ], 403);
        }

        // Update order status
        $order->update([
            'order_status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => __('modules.order.statusUpdated'),
            'data' => [
                'order_id' => $order->id,
                'status' => $order->order_status
            ]
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, $id)
    {
        $request->validate([
            'cancel_reason_id' => 'nullable|exists:kot_cancel_reasons,id',
            'cancel_reason_text' => 'nullable|string|max:500'
        ]);

        // Check if at least one reason is provided
        if (!$request->cancel_reason_id && !$request->cancel_reason_text) {
            return response()->json([
                'success' => false,
                'message' => __('modules.settings.cancelReasonRequired')
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check permission
        if (!user_can('Update Order')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.permissionDenied')
            ], 403);
        }

        // Update order to cancelled status
        $order->update([
            'status' => 'canceled',
            'order_status' => 'cancelled',
            'cancel_reason_id' => $request->cancel_reason_id,
            'cancel_reason_text' => $request->cancel_reason_text,
            'cancelled_by' => auth()->id(),
            'cancel_time' => \Carbon\Carbon::now()->setTimezone(restaurant()->timezone),
        ]);

        // Make table available if it was a dine-in order
        if ($order->table_id) {
            Table::where('id', $order->table_id)->update([
                'available_status' => 'available',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.orderCanceled'),
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status
            ]
        ]);
    }

    /**
     * Delete an order completely
     */
    public function deleteOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check permission
        if (!user_can('Delete Order')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.permissionDenied')
            ], 403);
        }

        // Make table available if it was a dine-in order
        if ($order->table_id) {
            Table::where('id', $order->table_id)->update([
                'available_status' => 'available',
            ]);
        }

        // Delete associated KOT records
        $order->kot()->delete();

        // Delete the order
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.orderDeleted')
        ]);
    }

    /**
     * Delete an individual order item
     */
    public function deleteOrderItem($orderId, $itemId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check permission
        if (!user_can('Delete Order')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.permissionDenied')
            ], 403);
        }

        // Cannot delete items from paid orders
        if ($order->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.cannotDeletePaidOrderItem')
            ], 400);
        }

        $orderItem = OrderItem::where('id', $itemId)
            ->where('order_id', $orderId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.itemNotFound')
            ], 404);
        }

        // Delete related KOT items first (same logic as Livewire version)
        if ($orderItem) {
            $kotItems = KotItem::where('menu_item_id', $orderItem->menu_item_id)
                ->where('menu_item_variation_id', $orderItem->menu_item_variation_id)
                ->where('quantity', $orderItem->quantity)
                ->whereHas('kot', function ($query) use ($orderItem) {
                    $query->where('order_id', $orderItem->order_id);
                })
                ->get();

            foreach ($kotItems as $kotItem) {
                $kotItem->delete();
            }
        }

        // Delete the order item
        $orderItem->delete();

        // Refresh order to check remaining items
        $order->refresh();

        // If no items left, delete the entire order
        if ($order->items()->count() === 0) {
            // Delete associated KOT items
            $kots = Kot::where('order_id', $order->id)->get();
            foreach ($kots as $kot) {
                KotItem::where('kot_id', $kot->id)->delete();
                $kot->delete();
            }

            // Delete order taxes and charges
            OrderTax::where('order_id', $order->id)->delete();
            OrderCharge::where('order_id', $order->id)->delete();

            // Unlock table if assigned
            if ($order->table_id) {
                $table = Table::find($order->table_id);

                if ($table) {
                    $table->update(['available_status' => 'available']);
                    // Release table session lock if exists
                    if ($table->tableSession) {
                        $table->tableSession->releaseLock();
                    }
                }
            }

            // Delete the order
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => __('messages.orderDeleted'),
                'redirect' => route('pos.index')
            ]);
        }

        // Recalculate order totals
        $this->recalculateOrderTotals($order);

        // Refresh order to get updated data
        $order->refresh();

        // Calculate total tax from order's tax calculations
        $totalTaxAmount = 0;
        if ($this->restaurant->tax_mode === 'order') {
            // For order-level taxes, get from taxes relationship
            $taxes = Tax::all();
            foreach ($taxes as $tax) {
                $totalTaxAmount += ($order->sub_total * $tax->percent) / 100;
            }
        } else {
            // For item-level taxes, sum from order items
            $totalTaxAmount = $order->items()->sum('tax_amount');
        }

        return response()->json([
            'success' => true,
            'message' => __('modules.order.itemDeleted'),
            'order' => [
                'items_count' => $order->items()->count(),
                'sub_total' => $order->sub_total,
                'tax_amount' => $totalTaxAmount,
                'total' => $order->total
            ]
        ]);
    }


    /**
     * Calculate totals from cart items (matching Livewire Pos::calculateTotal)
     * This is the core calculation logic used throughout POS
     *
     * @param array $items Cart items with price, quantity, etc.
     * @param array $params Additional params (discount, charges, fees, etc.)
     * @return array Calculated totals
     */
    private function calculateTotalFromCart($items, $params = [])
    {
        $subTotal = 0;
        $total = 0;
        $totalTaxAmount = 0;
        $orderItemTaxDetails = [];

        $discountType = $params['discount_type'] ?? null;
        $discountValue = $params['discount_value'] ?? 0;
        $extraCharges = $params['extra_charges'] ?? [];
        $deliveryFee = $params['delivery_fee'] ?? 0;
        $tipAmount = $params['tip_amount'] ?? 0;
        $taxMode = $params['tax_mode'] ?? 'order';

        // Step 1: Calculate subtotal (matching Livewire)
        if (is_array($items)) {
            // Calculate item taxes first for proper subtotal calculation (matching Livewire)
            if ($taxMode === 'item') {
                $this->updateOrderItemTaxDetailsForCart($items, $orderItemTaxDetails, $totalTaxAmount);
            }

            foreach ($items as $key => $item) {
                $itemAmount = is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0);
                $total += $itemAmount;

                // For inclusive taxes, subtract tax from subtotal (matching Livewire)
                if ($taxMode === 'item' && isset($orderItemTaxDetails[$key])) {
                    $taxDetail = $orderItemTaxDetails[$key];
                    $isInclusive = $this->restaurant->tax_inclusive ?? false;

                    if ($isInclusive) {
                        $subTotal += ($itemAmount - ($taxDetail['tax_amount'] ?? 0));
                    } else {
                        $subTotal += $itemAmount;
                    }
                } else {
                    $subTotal += $itemAmount;
                }
            }
        }

        $discountedTotal = $total;

        // Step 2: Apply discounts (matching Livewire)
        $discountAmount = 0;
        if ($discountValue > 0 && $discountType) {
            if ($discountType === 'percent') {
                $discountAmount = round(($subTotal * $discountValue) / 100, 2);
            } elseif ($discountType === 'fixed') {
                $discountAmount = min($discountValue, $subTotal);
            }

            $total -= $discountAmount;
        }
        $discountedTotal = $total;

        // Step 3: Calculate service charges on discountedTotal (matching Livewire)
        $serviceTotal = 0;
        $applicableCharges = is_array($extraCharges) ? $extraCharges : [];

        if (!empty($items) && !empty($applicableCharges)) {
            foreach ($applicableCharges as $charge) {
                $chargeAmount = is_object($charge)
                    ? $charge->getAmount($discountedTotal)
                    : ($charge['amount'] ?? 0);
                $total += $chargeAmount;
                $serviceTotal += $chargeAmount;
            }
        }

        // Step 4: Calculate tax_base based on setting (matching Livewire)
        $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
        $taxBase = $includeChargesInTaxBase ? $discountedTotal + $serviceTotal : $discountedTotal;

        // Step 5: Calculate taxes on tax_base (matching Livewire recalculateTaxTotals)
        if ($taxMode === 'order') {
            $totalTaxAmount = 0;
            $taxes = $this->restaurant->taxes ?? [];

            foreach ($taxes as $tax) {
                $taxAmount = ($taxBase * $tax->rate_percent) / 100;
                $totalTaxAmount += $taxAmount;
            }
            $total += $totalTaxAmount;
        } elseif ($taxMode === 'item') {
            // Item taxes already calculated above
            $isInclusive = $this->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $total += $totalTaxAmount;
            }
        }

        // Step 6: Add tip and delivery fees (matching Livewire)
        if ($tipAmount > 0) {
            $total += $tipAmount;
        }

        if ($deliveryFee > 0) {
            $total += $deliveryFee;
        }

        return [
            'sub_total' => $subTotal,
            'discount_amount' => $discountAmount,
            'discounted_total' => $discountedTotal,
            'service_total' => $serviceTotal,
            'tax_base' => $taxBase,
            'total_tax_amount' => $totalTaxAmount,
            'total' => max(0, $total),
            'order_item_tax_details' => $orderItemTaxDetails,
        ];
    }

    /**
     * Update order item tax details for cart items (matching Livewire)
     */
    private function updateOrderItemTaxDetailsForCart($items, &$orderItemTaxDetails, &$totalTaxAmount)
    {
        $taxes = $this->restaurant->taxes ?? [];
        $isInclusive = $this->restaurant->tax_inclusive ?? false;

        foreach ($items as $key => $item) {
            $price = is_array($item) ? ($item['price'] ?? 0) : ($item->price ?? 0);
            $quantity = is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1);

            $itemTaxAmount = 0;
            $totalTaxPercent = 0;

            // Calculate total tax percent
            foreach ($taxes as $tax) {
                $totalTaxPercent += $tax->rate_percent;
            }

            // Calculate tax amount
            foreach ($taxes as $tax) {
                $taxPercent = $tax->rate_percent;
                $taxAmount = 0;

                if ($isInclusive) {
                    $taxAmount = ($price * $taxPercent) / (100 + $totalTaxPercent);
                } else {
                    $taxAmount = ($price * $taxPercent) / 100;
                }

                $itemTaxAmount += $taxAmount;
            }

            $orderItemTaxDetails[$key] = [
                'tax_amount' => $itemTaxAmount * $quantity,
                'base_price' => $price,
                'qty' => $quantity
            ];

            $totalTaxAmount += $itemTaxAmount * $quantity;
        }
    }

    /**
     * Recalculate order totals (matching Livewire Pos::calculateTotal flow)
     * Uses calculateTotalFromCart for consistent calculation logic
     */
    private function recalculateOrderTotals($order)
    {
        // Step 1: Prepare items array from order
        $items = [];

        if ($order->status === 'draft') {
            $items = $order->items->toArray();
        } else {
            // KOT orders: build items array from kot items
            $order->load(['kot.items' => function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'ready']);
            }]);

            foreach ($order->kot as $kot) {
                foreach ($kot->items as $kotItem) {
                    $items[] = [
                        'price' => $kotItem->price,
                        'quantity' => $kotItem->quantity,
                        'amount' => $kotItem->price * $kotItem->quantity,
                    ];
                }
            }
        }

        // Step 2: Reload charges
        $order->load('charges.charge');

        // Step 3: Calculate using shared logic (matching Livewire calculateTotal)
        $totals = $this->calculateTotalFromCart($items, [
            'discount_type' => $order->discount_type,
            'discount_value' => $order->discount_value,
            'extra_charges' => $order->charges,
            'delivery_fee' => $order->delivery_fee ?? 0,
            'tip_amount' => $order->tip_amount ?? 0,
            'tax_mode' => $order->tax_mode ?? 'order',
        ]);

        // Step 4: Update order (matching Livewire)
        $order->update([
            'sub_total' => $totals['sub_total'],
            'discount_amount' => $totals['discount_amount'],
            'total' => $totals['total'],
            'total_tax_amount' => $totals['total_tax_amount'],
            'tax_base' => $totals['tax_base'],
        ]);
    }

    /**
     * Remove an extra charge from an order (matching Livewire Pos::removeExtraCharge)
     */
    public function removeExtraCharge($orderId, $chargeId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Check permission
        if (!user_can('Update Order')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.permissionDenied')
            ], 403);
        }

        // Cannot modify paid orders
        if ($order->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.cannotModifyPaidOrder')
            ], 400);
        }

        // Detach the charge (matching Livewire: $order->extraCharges()->detach($chargeId))
        $detached = $order->extraCharges()->detach($chargeId);

        if ($detached === 0) {
            return response()->json([
                'success' => false,
                'message' => __('messages.chargeNotFound')
            ], 404);
        }

        // Recalculate totals (matching Livewire: $this->calculateTotal())
        $this->recalculateOrderTotals($order);

        // Refresh order to get updated values
        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => __('messages.extraChargeRemoved'),
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'sub_total' => number_format($order->sub_total, 2, '.', ''),
                'discount_amount' => number_format($order->discount_amount ?? 0, 2, '.', ''),
                'total_tax_amount' => number_format($order->total_tax_amount ?? 0, 2, '.', ''),
                'total' => number_format($order->total, 2, '.', ''),
                'tax_base' => number_format($order->tax_base ?? 0, 2, '.', ''),
            ]
        ]);
    }

    /**
     * Update waiter for an order
     */
    public function updateWaiter(Request $request, $orderId)
    {
        $request->validate([
            'waiter_id' => 'nullable|integer|exists:users,id',
        ]);

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('modules.order.orderNotFound')
            ], 404);
        }

        // Allow null to clear waiter assignment
        $waiterId = $request->waiter_id ? intval($request->waiter_id) : null;
        $order->update(['waiter_id' => $waiterId]);

        return response()->json([
            'success' => true,
            'message' => $waiterId ? __('messages.waiterUpdated') : __('messages.waiterRemoved'),
            'waiter_id' => $order->waiter_id
        ]);
    }

    /**
     * Clear merge session data
     */
    public function clearMergeSession()
    {
        session()->forget('pos_merge_data');
        session()->forget('pos_merged_orders_to_delete');

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Update customer display cache with current cart data
     * Follows the pattern from Livewire Pos.php calculateTotal() method
     *
     * @param array $displayData Complete display data with items, totals, etc.
     * @return void
     */
    private function updateCustomerDisplayCache($displayData)
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        // Store in cache (matches Livewire Pos.php pattern)
        $cacheKey = 'customer_display_cart_user_' . $userId;
        Cache::put($cacheKey, $displayData, now()->addMinutes(30));

        // Broadcast customer display update if Pusher is enabled (matches Livewire pattern)
        if (pusherSettings()->is_enabled_pusher_broadcast) {
            broadcast(new \App\Events\CustomerDisplayUpdated($displayData, $userId));
        }
    }

    /**
     * Update customer display - called from JavaScript calculateTotal()
     * Mirrors Livewire Pos.php calculateTotal() customer display update logic
     */
    public function updateCustomerDisplay(Request $request)
    {
        $items = $request->input('items', []);
        $orderNumber = $request->input('order_number');
        $formattedOrderNumber = $request->input('formatted_order_number');
        $subTotal = $request->input('sub_total', 0);
        $discount = $request->input('discount', 0);
        $total = $request->input('total', 0);
        $taxes = $request->input('taxes', []);
        $extraCharges = $request->input('extra_charges', []);
        $tip = $request->input('tip', 0);
        $deliveryFee = $request->input('delivery_fee', 0);
        $orderType = $request->input('order_type');
        $status = $request->input('status', 'idle');

        // Get payment gateway QR code (matching Livewire pattern)
        $paymentGateway = $this->restaurant->paymentGateways;
        $qrCodeImageUrl = $paymentGateway && $paymentGateway->is_qr_payment_enabled ? $paymentGateway->qr_code_image_url : null;

        // Prepare customer display data (matching Livewire pattern exactly)
        $customerDisplayData = [
            'order_number' => $orderNumber,
            'formatted_order_number' => $formattedOrderNumber,
            'items' => $items,
            'sub_total' => $subTotal,
            'discount' => $discount,
            'total' => $total,
            'taxes' => $taxes,
            'extra_charges' => $extraCharges,
            'tip' => $tip,
            'delivery_fee' => $deliveryFee,
            'order_type' => $orderType,
            'status' => $status,
            'cash_due' => $status === 'billed' ? $total : null,
            'qr_code_image_url' => $qrCodeImageUrl,
        ];

        // Update cache and broadcast (matching Livewire pattern)
        $this->updateCustomerDisplayCache($customerDisplayData);

        return response()->json([
            'success' => true,
            'message' => 'Customer display updated'
        ]);
    }
}
