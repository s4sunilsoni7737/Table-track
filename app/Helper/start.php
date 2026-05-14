<?php

use App\Models\EmailSetting;
use App\Models\GlobalSetting;
use App\Models\LanguageSetting;
use App\Helper\Files;
use App\Models\Package;
use App\Models\PaymentGatewayCredential;
use App\Models\PusherSetting;
use App\Models\Restaurant;
use App\Models\StorageSetting;
use App\Models\SuperadminPaymentGateway;
use App\Models\GlobalCurrency;
use App\Models\Currency;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use App\Models\OrderNumberSetting;
use Intervention\Image\Facades\Image;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Branch;
use App\Models\BranchOperationalShift;
use App\Models\DeliveryExecutive;
use Carbon\Carbon;


if (!function_exists('user')) {

    /**
     * Return current logged-in user
     */
    function user()
    {

        if (session()->has('user')) {
            return session('user');
        }


        session(['user' => auth()->user()]);

        return session('user');
    }
}


function customer()
{
    if (session()->has('customer')) {
        return session('customer');
    }

    return null;
}

function delivery_executive()
{
    if (session()->has('delivery_executive')) {
        return session('delivery_executive');
    }

    if (session()->has('delivery_executive_id')) {
        $executive = DeliveryExecutive::with('branch.restaurant.currency')
            ->find(session('delivery_executive_id'));

        if ($executive) {
            session(['delivery_executive' => $executive]);
            return $executive;
        }
    }

    return null;
}

function restaurant()
{
    if (session()->has('restaurant')) {
        return session('restaurant');
    }

    if (user()) {
        if (user()->restaurant_id) {
            session(['restaurant' => Restaurant::find(user()->restaurant_id)]);
            return session('restaurant');
        }
    }

    // session(['restaurant' => Restaurant::first()]); // Used in Non-saas

    // return session('restaurant');  // Used in Non-saas
    return false;  // Used in Saas

}

function shop($hash = null)
{
    if (session()->has('shop')) {
        return session('shop');
    }

    if (!is_null($hash)) {
        session(['shop' => Restaurant::where('hash', $hash)->first()]);
        return session('shop');
    }

    return false;  // Used in Saas

}

function branch()
{
    if (session()->has('branch')) {
        return session('branch');
    }

    if (restaurant()) {
        session(['branch' => user()->branch ?? restaurant()->branches->first()]);
        return session('branch');
    }

    return false;
}

function shop_branch()
{
    if (session()->has('shop_branch')) {
        return session('shop_branch');
    }

    if (shop()) {
        session(['shop_branch' => shop()->branches->first()]);
        return session('shop_branch');
    }

    return false;
}

function currency()
{
    if (session()->has('currency')) {
        return session('currency');
    }

    if (restaurant()) {
        session(['currency' => restaurant()->currency->currency_symbol]);

        return session('currency');
    }

    return false;
}

function dateFormat()
{
    // Always get fresh format from database to avoid stale cache
    $user = auth()->user();
    if ($user && $user->restaurant_id) {
        // Get fresh restaurant data to avoid stale cache
        $restaurant = Restaurant::find($user->restaurant_id);
        if ($restaurant && $restaurant->date_format) {
            session(['date_format' => $restaurant->date_format]);
            return $restaurant->date_format;
        }
    }

    // Fallback to global setting
    $globalSetting = GlobalSetting::first();
    if ($globalSetting && $globalSetting->date_format) {
        session(['date_format' => $globalSetting->date_format]);
        return $globalSetting->date_format;
    }

    return 'd/m/Y';
}

function timeFormat()
{
    // Always get fresh format from database to avoid stale cache
    $user = auth()->user();
    if ($user && $user->restaurant_id) {
        // Get fresh restaurant data to avoid stale cache
        $restaurant = Restaurant::find($user->restaurant_id);
        if ($restaurant && $restaurant->time_format) {
            session(['time_format' => $restaurant->time_format]);
            return $restaurant->time_format;
        }
    }

    // Fallback to global setting
    $globalSetting = GlobalSetting::first();
    if ($globalSetting && $globalSetting->time_format) {
        session(['time_format' => $globalSetting->time_format]);
        return $globalSetting->time_format;
    }

    return 'h:i A';
}



if (!function_exists('restaurantToYmd')) {
    /**
     * Convert date from restaurant/global date format to Y-m-d format
     *
     * @param string $date
     * @return string
     */
    function restaurantToYmd($date)
    {
        try {
            return Carbon::createFromFormat(dateFormat(), $date)->format('Y-m-d');
        } catch (\Exception $e) {
            // If parsing fails, try to parse as is and return in Y-m-d format
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }
    }
}

if (!function_exists('restaurantToDateString')) {
    /**
     * Convert date from restaurant/global date format to date string (Y-m-d)
     *
     * @param string $date
     * @return string
     */
    function restaurantToDateString($date)
    {
        try {
            return Carbon::createFromFormat(dateFormat(), $date)->toDateString();
        } catch (\Exception $e) {
            // If parsing fails, try to parse as is and return date string
            try {
                return Carbon::parse($date)->toDateString();
            } catch (\Exception $e) {
                return $date;
            }
        }
    }
}

function timezone()
{
    if (session()->has('timezone')) {
        return session('timezone');
    }

    if (restaurant()) {
        session(['timezone' => restaurant()->timezone]);
        return session('timezone');
    }

    // For superadmin, use global setting timezone
    if (user() && is_null(user()->restaurant_id)) {
        $globalTimezone = global_setting()->timezone ?? 'UTC';
        session(['timezone' => $globalTimezone]);
        return session('timezone');
    }

    return 'UTC';
}

function paymentGateway()
{
    if (session()->has('paymentGateway')) {
        return session('paymentGateway');
    }

    if (shop()) {
        $payment = PaymentGatewayCredential::where('restaurant_id', shop()->id)->first();

        session(['paymentGateway' => $payment]);

        return session('paymentGateway');
    }

    return false;
}

if (!function_exists('check_migrate_status')) {

    // @codingStandardsIgnoreLine
    function check_migrate_status()
    {

        if (!session()->has('check_migrate_status')) {

            $status = Artisan::call('migrate:check');

            if ($status && !request()->ajax()) {
                Artisan::call('migrate', ['--force' => true, '--schema-path' => 'do not run schema path']); // Migrate database
                Artisan::call('optimize:clear');
            }

            session(['check_migrate_status' => 'Good']);
        }

        return session('check_migrate_status');
    }
}

if (!function_exists('role_permissions')) {

    function role_permissions()
    {
        $user = user();
        if (is_null($user)) {
            return [];
        }

        $userId = $user->id;
        
        // Check if permissions are cached for this user
        if (cache()->has('role_permissions_' . $userId)) {
            return cache()->get('role_permissions_' . $userId);
        }

        $roleId = $user->roles->first()->id;
        if (!$roleId) {
            return [];
        }

        // Load permissions from database
        $permissions = Role::where('id', $roleId)
            ->first()->permissions->pluck('name')->toArray();

        // Cache permissions for this user
        cache()->put('role_permissions_' . $userId, $permissions);
        
        return $permissions;
    }
}

if (!function_exists('user_can')) {

    function user_can($permission)
    {
        $rolePermissions = role_permissions() ?? [];

        return in_array($permission, $rolePermissions);
    }
}

if (!function_exists('restaurant_modules')) {
    function restaurant_modules($restaurant = null): array
    {
        $restaurant = $restaurant ?: restaurant();

        if (!$restaurant) {
            return [];
        }

        $cacheKey = 'restaurant_modules_' . $restaurant->id;

        if (cache()->has($cacheKey)) {
            return cache($cacheKey);
        }

        $user = user();

        if ($user && is_null($user->restaurant_id) && is_null($user->branch_id)) {
            return [];
        }

        $restaurant = Restaurant::with('package.modules')->find($restaurant->id);
        session(['restaurant' => $restaurant]);

        $package = $restaurant->package;

        $packageModules = $package->modules->pluck('name')->toArray();
        $additionalFeatures = json_decode($package->additional_features ?? '[]', true);

        $allModules = array_unique(array_merge($packageModules, $additionalFeatures));

        cache([$cacheKey => $allModules]);

        return cache($cacheKey);
    }
}


if (!function_exists('global_setting')) {

    // @codingStandardsIgnoreLine
    function global_setting()
    {

        if (cache()->has('global_setting')) {
            return cache('global_setting');
        }

        cache(['global_setting' => GlobalSetting::first()]);

        return cache('global_setting');
    }
}

if (!function_exists('restaurantOrGlobalSetting')) {

    function restaurantOrGlobalSetting()
    {
        if (user()) {

            if (user()->restaurant_id) {
                return restaurant();
            }
        }

        return global_setting();
    }
}

if (!function_exists('branches')) {

    function branches()
    {

        if (session()->has('branches')) {
            return session('branches');
        }

        if (restaurant()) {
            return session(['branches' => restaurant()->branches]);
        }

        return false;
    }
}

if (!function_exists('isRtl')) {

    function isRtl()
    {

        if (session()->has('isRtl')) {
            return session('isRtl');
        }

        if (user()) {
            $language = LanguageSetting::where('language_code', auth()->user()->locale)->first();
            $isRtl = ($language->is_rtl == 1);
            session(['isRtl' => $isRtl]);
        }

        return false;
    }
}

if (!function_exists('languages')) {

    function languages()
    {

        if (cache()->has('languages')) {
            return cache('languages');
        }

        $languages = LanguageSetting::where('active', 1)->get();
        cache(['languages' => $languages]);

        return cache('languages');
    }
}

if (!function_exists('asset_url_local_s3')) {

    // @codingStandardsIgnoreLine
    function asset_url_local_s3($path)
    {
        if (in_array(config('filesystems.default'), StorageSetting::S3_COMPATIBLE_STORAGE)) {
            // Check if the URL is already cached
            if (Cache::has(config('filesystems.default') . '-' . $path)) {
                $temporaryUrl = Cache::get(config('filesystems.default') . '-' . $path);
            } else {
                // Generate a new temporary URL and cache it
                $temporaryUrl = Storage::disk(config('filesystems.default'))->temporaryUrl($path, now()->addMinutes(StorageSetting::HASH_TEMP_FILE_TIME));
                Cache::put(config('filesystems.default') . '-' . $path, $temporaryUrl, StorageSetting::HASH_TEMP_FILE_TIME * 60);
            }

            return $temporaryUrl;
        }

        $path = Files::UPLOAD_FOLDER . '/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;
    }
}

if (!function_exists('download_local_s3')) {

    // @codingStandardsIgnoreLine
    function download_local_s3($file, $path)
    {

        if (in_array(config('filesystems.default'), StorageSetting::S3_COMPATIBLE_STORAGE)) {
            return Storage::disk(config('filesystems.default'))->download($path, basename($file->filename));
        }

        $path = Files::UPLOAD_FOLDER . '/' . $path;
        $ext = pathinfo($file->filename, PATHINFO_EXTENSION);

        $filename = $file->name ? $file->name . '.' . $ext : $file->filename;
        try {
            return response()->download($path, $filename);
        } catch (\Exception $e) {
            return response()->view('errors.file_not_found', ['message' => $e->getMessage()], 404);
        }
    }
}


if (!function_exists('asset_url')) {

    // @codingStandardsIgnoreLine
    function asset_url($path)
    {
        $path = \App\Helper\Files::UPLOAD_FOLDER . '/' . $path;
        $storageUrl = $path;

        if (!Str::startsWith($storageUrl, 'http')) {
            return url($storageUrl);
        }

        return $storageUrl;
    }
}

if (!function_exists('getDomain')) {

    function getDomain($host = false)
    {
        if (!$host) {
            $host = $_SERVER['SERVER_NAME'] ?? 'tabletrack.test';
        }

        $shortDomain = config('app.short_domain_name');
        $dotCount = ($shortDomain === true) ? 2 : 1;

        $myHost = strtolower(trim($host));
        $count = substr_count($myHost, '.');

        if (!is_null(config('app.main_domain_name'))) {
            return config('app.main_domain_name');
        }

        if ($count === $dotCount || $count === 1) {
            return $myHost;
        }

        $myHost = explode('.', $myHost, 2);

        return end($myHost);
    }
}

if (!function_exists('getDomainSpecificUrl')) {

    function getDomainSpecificUrl($url, $restaurant = null)
    {
        // Check if Subdomain module exist
        if (!module_enabled('Subdomain')) {
            return $url;
        }

        config(['app.url' => config('app.main_app_url')]);

        // If restaurant specific
        if ($restaurant) {
            $restaurantUrl = (config('app.redirect_https') ? 'https' : 'http') . '://' . $restaurant->sub_domain;

            config(['app.url' => $restaurantUrl]);
            // Removed Illuminate\Support\Facades\URL::forceRootUrl($companyUrl);

            if (Str::contains($url, $restaurant->sub_domain)) {
                return $url;
            }

            $url = str_replace(request()->getHost(), $restaurant->sub_domain, $url);
            $url = str_replace('www.', '', $url);

            // Replace https to http for sub-domain to
            if (!config('app.redirect_https')) {
                return str_replace('https', 'http', $url);
            }

            return $url;
        }

        // Removed config(['app.url' => $url]);
        // Comment      \Illuminate\Support\Facades\URL::forceRootUrl($url);
        // If there is no restaurant and url has login means
        // New superadmin is created
        return str_replace('login', 'super-admin-login', $url);
    }
}


function module_enabled($moduleName)
{
    return Module::has($moduleName) && Module::isEnabled($moduleName);
}

if (!function_exists('isHotelModuleEnabled')) {
    /**
     * Check if Hotel module is enabled and available in restaurant modules
     * 
     * @return bool
     */
    function isHotelModuleEnabled(): bool
    {
        return function_exists('module_enabled')  && module_enabled('Hotel') && function_exists('restaurant_modules') && in_array('Hotel', restaurant_modules());
    }
}

if (!function_exists('package')) {

    function package()
    {

        if (cache()->has('package')) {
            return cache('package');
        }

        $package = Package::first();

        cache(['package' => $package]);

        return cache('package');
    }
}

function superadminPaymentGateway()
{
    if (cache()->has('superadminPaymentGateway')) {
        return cache('superadminPaymentGateway');
    }

    $payment = SuperadminPaymentGateway::first();

    cache(['superadminPaymentGateway' => $payment]);

    return cache('superadminPaymentGateway');
}


function pusherSettings()
{


    if (cache()->has('pusherSettings')) {
        return cache('pusherSettings');
    }

    $setting = PusherSetting::first();

    cache(['pusherSettings' => $setting]);

    return cache('pusherSettings');
}

if (!function_exists('clearRestaurantModulesCache')) {

    function clearRestaurantModulesCache($restaurantId)
    {
        if (is_null($restaurantId)) {
            return true;
        }

        cache()->forget('restaurant_modules_' . $restaurantId);
    }
}

if (!function_exists('currency_format_setting')) {

    // @codingStandardsIgnoreLine
    function currency_format_setting($currencyId = null)
    {
        if (!session()->has('currency_format_setting' . $currencyId) || (is_null($currencyId) && restaurant())) {
            if ($currencyId == null && restaurant()) {
                $setting = restaurant()->load('currency')->currency;
            } else {
                $setting = Currency::where('id', $currencyId)->first();
            }
            session(['currency_format_setting' . $currencyId => $setting]);
        }

        return session('currency_format_setting' . $currencyId);
    }
}

if (!function_exists('currency_format')) {

    // @codingStandardsIgnoreLine
    function currency_format($amount, $currencyId = null, $showSymbol = true, $showCode = false)
    {
        $formats = currency_format_setting($currencyId);

        $settings = $formats->restaurant ?? Restaurant::find($formats->restaurant_id);

        if ($showCode) {
            $currency_symbol = $formats->currency_code ?? '';
        } else {
            if (!$showSymbol) {
                $currency_symbol = '';
            } else {
                $settings = $formats->restaurant ?? Restaurant::find($formats->restaurant_id);
                $currency_symbol = $currencyId == null ? $settings->currency->currency_symbol : $formats->currency_symbol;
            }
        }


        $currency_position = $formats->currency_position ?? 'left';
        $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
        $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
        $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

        $amount = number_format(floatval($amount), $no_of_decimal, $decimal_separator, $thousand_separator);

        $amount = match ($currency_position) {
            'right' => $amount . $currency_symbol,
            'left_with_space' => $currency_symbol . ' ' . $amount,
            'right_with_space' => $amount . ' ' . $currency_symbol,
            default => $currency_symbol . $amount,
        };

        return $amount;
    }
}


if (!function_exists('global_currency_format_setting')) {

    // @codingStandardsIgnoreLine
    function global_currency_format_setting($currencyId = null)
    {
        if (!session()->has('global_currency_format_setting' . $currencyId)) {
            $setting = $currencyId == null ? GlobalCurrency::first() : GlobalCurrency::where('id', $currencyId)->first();
            session(['global_currency_format_setting' . $currencyId => $setting]);
        }

        return session('global_currency_format_setting' . $currencyId);
    }
}

if (!function_exists('global_currency_format')) {

    // @codingStandardsIgnoreLine
    function global_currency_format($amount, $currencyId = null, $showSymbol = true)
    {
        $formats = global_currency_format_setting($currencyId);


        if (!$showSymbol) {
            $currency_symbol = '';
        } else {
            $currency_symbol = $formats->currency_symbol;
        }

        $currency_position = $formats->currency_position;
        $no_of_decimal = !is_null($formats->no_of_decimal) ? $formats->no_of_decimal : '0';
        $thousand_separator = !is_null($formats->thousand_separator) ? $formats->thousand_separator : '';
        $decimal_separator = !is_null($formats->decimal_separator) ? $formats->decimal_separator : '0';

        $amount = number_format($amount, $no_of_decimal, $decimal_separator, $thousand_separator);

        $amount = match ($currency_position) {
            'right' => $amount . $currency_symbol,
            'left_with_space' => $currency_symbol . ' ' . $amount,
            'right_with_space' => $amount . ' ' . $currency_symbol,
            default => $currency_symbol . $amount,
        };

        return $amount;
    }
}

if (!function_exists('smtp_setting')) {

    // @codingStandardsIgnoreLine
    function smtp_setting()
    {
        if (!session()->has('smtp_setting')) {
            session(['smtp_setting' => EmailSetting::first()]);
        }

        return session('smtp_setting');
    }
}

if (!function_exists('custom_module_plugins')) {

    // @codingStandardsIgnoreLine
    function custom_module_plugins()
    {

        if (!cache()->has('custom_module_plugins')) {
            $plugins = \Nwidart\Modules\Facades\Module::allEnabled();
            cache(['custom_module_plugins' => array_keys($plugins)]);
        }

        return cache('custom_module_plugins');
    }
}

if (!function_exists('isOrderPrefixEnabled')) {

    /**
     * Check if order prefix feature is enabled for the given branch
     */
    function isOrderPrefixEnabled($branch = null)
    {
        if (!$branch) {
            $branch = branch();
        }

        if (!$branch) {
            return false;
        }

        $settings = getOrderNumberSetting($branch->id);
        return $settings && $settings->enable_feature;
    }
}

if (!function_exists('getOrderNumberSetting')) {
    function getOrderNumberSetting($branchId) {
        return cache()->remember('order_number_setting_' . $branchId, 60 * 60 * 24, function () use ($branchId) {
            return OrderNumberSetting::where('branch_id', $branchId)->first() ?? new OrderNumberSetting();
        });
    }
}

if (!function_exists('getRestaurantStaffStats')) {
    /**
     * Get restaurant staff stats including staff_limit and current staff count.
     *
     * @param int|null $restaurantId
     * @return array|null
     */
    function getRestaurantStaffStats($restaurantId = null)
    {
        if (is_null($restaurantId)) {
            return null;
        }

        return cache()->rememberForever('restaurant_' . $restaurantId . '_staff_stats', function () use ($restaurantId) {
            $restaurant = Restaurant::with('package')->find($restaurantId);

            if (!$restaurant || !$restaurant->package) {
                return [
                    'staff_limit' => 0,
                    'current_count' => 0,
                    'unlimited' => false,
                ];
            }

            $staffLimit = $restaurant->package->staff_limit;
            $currentStaffCount = $restaurant->users()->count();

            return [
                'staff_limit' => $staffLimit,
                'current_count' => $currentStaffCount,
                'unlimited' => ($staffLimit == -1),
            ];
        });
    }
}

if (!function_exists('getRestaurantMenuItemStats')) {
    /**
     * Get restaurant menu item stats including menu_items_limit and current menu item count.
     * Counts menu items across all branches of the restaurant.
     *
     * @param int|null $restaurantId
     * @return array|null
     */
    function getRestaurantMenuItemStats($restaurantId = null)
    {
        if (is_null($restaurantId)) {
            return null;
        }

        return cache()->rememberForever('restaurant_' . $restaurantId . '_menu_item_stats', function () use ($restaurantId) {
            $restaurant = Restaurant::with('package', 'branches')->find($restaurantId);

            if (!$restaurant || !$restaurant->package) {
                return [
                    'menu_items_limit' => 0,
                    'current_count' => 0,
                    'unlimited' => false,
                ];
            }

            $menuItemsLimit = $restaurant->package->menu_items_limit;

            // Get all branch IDs for this restaurant
            $branchIds = $restaurant->branches->pluck('id')->toArray();

            // Count menu items across all branches
            $currentMenuItemCount = MenuItem::whereIn('branch_id', $branchIds)->count();

            return [
                'menu_items_limit' => $menuItemsLimit,
                'current_count' => $currentMenuItemCount,
                'unlimited' => ($menuItemsLimit == -1),
            ];
        });
    }
}

if (!function_exists('getRestaurantOrderStats')) {
    /**
     * Get order stats including order_limit and current order count for the current branch.
     * Uses branch's total_orders and count_orders fields.
     *
     * @param int|null $branchId (kept for backward compatibility, but uses current branch)
     * @return array|null
     */
    function getRestaurantOrderStats($branchId = null)
    {
        if (is_null($branchId)) {
            return null;
        }

        return cache()->rememberForever('branch_' . $branchId . '_order_stats', function () use ($branchId) {
            $branch = Branch::find($branchId);

            if (!$branch) {
                return [
                    'order_limit' => 0,
                    'current_count' => 0,
                    'unlimited' => false,
                ];
            }

            $totalOrders = $branch->total_orders ?? -1;
            $countOrders = $branch->count_orders ?? 0;

            $unlimited = ($totalOrders == -1);

            return [
                'order_limit' => $totalOrders,
                'current_count' => $countOrders,
                'unlimited' => $unlimited,
            ];
        });
    }
}

if (!function_exists('getBranchOperationalShifts')) {
    /**
     * Get active operational shifts for a branch
     *
     * @param \App\Models\Branch|null $branch
     * @param string|null $dayOfWeek Optional day filter (Monday, Tuesday, etc.)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function getBranchOperationalShifts($branch = null, $dayOfWeek = null)
    {
        $branch = $branch ?? branch();
        
        if (!$branch) {
            return collect([]);
        }
        
        $query = BranchOperationalShift::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('start_time');
        
        if ($dayOfWeek) {
            $query->where(function($q) use ($dayOfWeek) {
                // Check if JSON array contains 'All' or the specific day
                $q->whereJsonContains('day_of_week', 'All')
                  ->orWhereJsonContains('day_of_week', $dayOfWeek);
            });
        } else {
            // If no day filter, only show shifts with 'All'
            $query->whereJsonContains('day_of_week', 'All');
        }
        
        return $query->get();
    }
}

if (!function_exists('getBusinessDayBoundaries')) {
    /**
     * Get business day boundaries based on operational shifts
     * Returns start and end times in restaurant timezone
     *
     * @param \App\Models\Branch|null $branch
     * @param \Carbon\Carbon|string|null $date
     * @return array ['start' => Carbon, 'end' => Carbon, 'timezone' => string, 'calendar_date' => string]
     */
    function getBusinessDayBoundaries($branch = null, $date = null)
    {
        $branch = $branch ?? branch();
        
        if (!$branch) {
            // Fallback to calendar day
            $date = $date ? Carbon::parse($date) : now();
            $tz = timezone() ?? 'UTC';
            return [
                'start' => Carbon::parse($date, $tz)->startOfDay(),
                'end' => Carbon::parse($date, $tz)->endOfDay(),
                'timezone' => $tz,
                'calendar_date' => $date->toDateString()
            ];
        }
        
        $restaurant = $branch->restaurant;
        $restaurantTimezone = $restaurant->timezone ?? 'UTC';
        $toRestaurantTime = function ($utcTime) use ($restaurantTimezone) {
            return Carbon::now('UTC')
                ->setTimeFromTimeString($utcTime)
                ->setTimezone($restaurantTimezone)
                ->format('H:i:s');
        };
        
        // Get current time in restaurant timezone
        $currentTime = $date 
            ? Carbon::parse($date, $restaurantTimezone)
            : Carbon::now($restaurantTimezone);
        
        $calendarDate = $currentTime->toDateString(); // e.g., "2024-12-30"
        $dayOfWeek = $currentTime->format('l'); // e.g., "Monday"
        
        // Get all active shifts (we'll filter by day of week manually)
        $allShifts = BranchOperationalShift::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
        
        // Filter shifts that apply to the current day of week
        $shifts = $allShifts->filter(function($shift) use ($dayOfWeek) {
            $shiftDays = $shift->day_of_week ?? [];
            return in_array('All', $shiftDays) || in_array($dayOfWeek, $shiftDays);
        });
        
        if ($shifts->isEmpty()) {
            // No shifts configured - use calendar day in restaurant timezone
            return [
                'start' => $currentTime->copy()->startOfDay(),
                'end' => $currentTime->copy()->endOfDay(),
                'timezone' => $restaurantTimezone,
                'calendar_date' => $calendarDate
            ];
        }
        
        // Check for shifts from previous day that ended in current day
        $previousDay = Carbon::parse($calendarDate, $restaurantTimezone)->subDay();
        $previousDayOfWeek = $previousDay->format('l');
        $previousDayShifts = $allShifts->filter(function($shift) use ($previousDayOfWeek) {
            $shiftDays = $shift->day_of_week ?? [];
            return in_array('All', $shiftDays) || in_array($previousDayOfWeek, $shiftDays);
        });
        $previousDayEndTime = null;
        
        foreach ($previousDayShifts as $shift) {
            $shiftStartTime = $toRestaurantTime($shift->start_time);
            $shiftEndTime = $toRestaurantTime($shift->end_time);

            // Parse shift times for previous day
            $shiftStart = Carbon::parse(
                $previousDay->toDateString() . ' ' . $shiftStartTime,
                $restaurantTimezone
            );
            
            $shiftEnd = Carbon::parse(
                $previousDay->toDateString() . ' ' . $shiftEndTime,
                $restaurantTimezone
            );
            
            // Handle overnight shifts (end_time < start_time means it ends next day)
            if ($shiftEndTime < $shiftStartTime) {
                $shiftEnd->addDay();
            }
            
            // Check if this shift ended in the current calendar date
            if ($shiftEnd->toDateString() === $calendarDate) {
                // This shift from previous day ended in current day
                if (!$previousDayEndTime || $shiftEnd > $previousDayEndTime) {
                    $previousDayEndTime = $shiftEnd;
                }
            }
        }
        
        // Business day boundaries:
        // Start: first active shift start of the day (or previous day's overnight-end if later)
        // End: latest active shift end of the day (can cross to next day)
        $businessDayStart = null;
        $businessDayEnd = null;

        // Find earliest start and latest end from shifts that started on this calendar date.
        $latestShiftEnd = null;
        $earliestShiftStart = null;

        foreach ($shifts as $shift) {
            $shiftStartTime = $toRestaurantTime($shift->start_time);
            $shiftEndTime = $toRestaurantTime($shift->end_time);

            // Parse shift times in restaurant timezone
            $shiftStart = Carbon::parse(
                $calendarDate . ' ' . $shiftStartTime,
                $restaurantTimezone
            );
            
            $shiftEnd = Carbon::parse(
                $calendarDate . ' ' . $shiftEndTime,
                $restaurantTimezone
            );
            
            // Handle overnight shifts (end_time < start_time means it ends next day)
            if ($shiftEndTime < $shiftStartTime) {
                $shiftEnd->addDay();
            }

            if (!$earliestShiftStart || $shiftStart < $earliestShiftStart) {
                $earliestShiftStart = $shiftStart;
            }
            
            // Track the latest shift end (especially if it crosses to next day)
            if (!$latestShiftEnd || $shiftEnd > $latestShiftEnd) {
                $latestShiftEnd = $shiftEnd;
            }
        }

        // Base start/end from configured shifts
        if ($earliestShiftStart) {
            $businessDayStart = $earliestShiftStart;
        }

        if ($latestShiftEnd) {
            $businessDayEnd = $latestShiftEnd;
        }

        // If previous day's shift ended in current day and it's later than first shift start,
        // keep continuity by starting at previous shift end.
        if ($previousDayEndTime && $businessDayStart && $previousDayEndTime > $businessDayStart) {
            $businessDayStart = $previousDayEndTime;
        }

        // Safety fallback for unexpected empty boundaries.
        if (!$businessDayStart) {
            $businessDayStart = $currentTime->copy()->startOfDay();
        }
        if (!$businessDayEnd) {
            $businessDayEnd = $currentTime->copy()->endOfDay();
        }
        
        // For "today", the end should be current time (now) if we're viewing today
        // This is for query purposes - we want to show orders up to now
        $isToday = $calendarDate === Carbon::now($restaurantTimezone)->toDateString();
        if ($isToday) {
            // For queries, use current time
            // But we'll store the full business day end separately for display
            $latestEnd = Carbon::now($restaurantTimezone);
        } else {
            // For past dates, use the full business day end
            $latestEnd = $businessDayEnd;
        }
        
        // Use business day start
        $earliestStart = $businessDayStart;

        $earliestStart = $earliestStart->copy()->setTimezone($restaurantTimezone);
        $latestEnd = $latestEnd->copy()->setTimezone($restaurantTimezone);
        $businessDayEnd = $businessDayEnd->copy()->setTimezone($restaurantTimezone);
        
        return [
            'start' => $earliestStart,
            'end' => $latestEnd, // For queries - "now" if today, otherwise full business day end
            'business_day_end' => $businessDayEnd, // Full business day end for display (11:59 PM or shift end)
            'timezone' => $restaurantTimezone,
            'calendar_date' => $calendarDate
        ];
    }
}

if (!function_exists('getShiftForOrder')) {
    /**
     * Determine which shift an order belongs to based on order datetime
     *
     * @param string|\Carbon\Carbon $orderDateTime Order datetime (in UTC from database)
     * @param int|null $branchId
     * @return int|null Shift ID or null if no shift found (gap period)
     */
    function getShiftForOrder($orderDateTime, $branchId = null)
    {
        $branch = $branchId ? Branch::find($branchId) : branch();
        
        if (!$branch) {
            return null;
        }
        
        $restaurantTimezone = $branch->restaurant->timezone ?? 'UTC';
        $toRestaurantTime = function ($utcTime) use ($restaurantTimezone) {
            return Carbon::now('UTC')
                ->setTimeFromTimeString($utcTime)
                ->setTimezone($restaurantTimezone)
                ->format('H:i:s');
        };
        
        // Convert order datetime from UTC to restaurant timezone
        $orderTime = Carbon::parse($orderDateTime)->setTimezone($restaurantTimezone);
        
        $calendarDate = $orderTime->toDateString();
        $dayOfWeek = $orderTime->format('l'); // e.g., "Monday", "Tuesday"
        
        // Get all active shifts (without day filter to check all shifts)
        $shifts = BranchOperationalShift::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
        
        foreach ($shifts as $shift) {
            $shiftStartTime = $toRestaurantTime($shift->start_time);
            $shiftEndTime = $toRestaurantTime($shift->end_time);

            // Check if shift applies to this day of week
            $shiftDays = $shift->day_of_week ?? [];
            if (!in_array('All', $shiftDays) && !in_array($dayOfWeek, $shiftDays)) {
                continue; // Skip this shift if it doesn't apply to this day
            }
            
            // Parse shift times in restaurant timezone
            $shiftStart = Carbon::parse(
                $calendarDate . ' ' . $shiftStartTime,
                $restaurantTimezone
            );
            
            $shiftEnd = Carbon::parse(
                $calendarDate . ' ' . $shiftEndTime,
                $restaurantTimezone
            );
            
            // Handle overnight shifts
            if ($shiftEndTime < $shiftStartTime) {
                $shiftEnd->addDay();
                
                // If order is early morning (before shift end), check previous day
                if ($orderTime->format('H:i') < $shiftEnd->format('H:i')) {
                    $shiftStart->subDay();
                }
            }
            
            // Check if order falls within this shift
            if ($orderTime >= $shiftStart && $orderTime <= $shiftEnd) {
                return $shift->id;
            }
        }
        
        return null; // No shift found (gap period)
    }
}

if (!function_exists('isWithinBusinessDay')) {
    /**
     * Check if a datetime falls within the current business day
     *
     * @param string|\Carbon\Carbon $datetime
     * @param \App\Models\Branch|null $branch
     * @return bool
     */
    function isWithinBusinessDay($datetime, $branch = null)
    {
        $boundaries = getBusinessDayBoundaries($branch, $datetime);
        
        $dt = Carbon::parse($datetime);
        if ($dt->timezone->getName() !== $boundaries['timezone']) {
            $dt = $dt->setTimezone($boundaries['timezone']);
        }
        
        return $dt >= $boundaries['start'] && $dt <= $boundaries['end'];
    }
}
