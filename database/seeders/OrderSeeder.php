<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\MenuItem;
use App\Models\OnboardingStep;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\Payment;
use App\Models\Table;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run($branch): void
    {
        // Preload IDs/collections once per branch to avoid `ORDER BY RAND()` and N+1 queries.
        $tableIds = Table::where('branch_id', $branch->id)->pluck('id')->all();
        $waiterIds = User::where('branch_id', $branch->id)->pluck('id')->all();
        $menuItems = MenuItem::where('branch_id', $branch->id)->get(['id', 'price']);
        $taxes = Tax::withoutGlobalScopes()->where('branch_id', $branch->id)->get(['id', 'tax_percent']);

        // Counters are used to generate unique numbers without per-order `max()` queries.
        $orderNumberCounter = ((int) Order::where('branch_id', $branch->id)->max('id')) + 1;
        $kotNumberCounter = ((int) Kot::max('id')) + 1;

        $this->submitCustomerName(
            $branch,
            $tableIds,
            $waiterIds,
            $menuItems,
            $taxes,
            $orderNumberCounter,
            $kotNumberCounter
        );

        OnboardingStep::where('branch_id', $branch->id)->update([
            'add_area_completed' => 1,
            'add_table_completed' => 1,
            'add_menu_completed' => 1,
            'add_menu_items_completed' => 1
        ]);
    }

    public function submitCustomerName(
        $branch,
        array $tableIds,
        array $waiterIds,
        $menuItems,
        $taxes,
        int &$orderNumberCounter,
        int &$kotNumberCounter
    ): void
    {
        // Required entities for placeOrder().
        if (empty($tableIds) || empty($waiterIds) || $menuItems->isEmpty()) {
            return;
        }

        // Create 5 orders for today
        for ($i = 0; $i < 5; $i++) {
            $customer = new Customer();
            $customer->restaurant_id = $branch->restaurant_id;
            $customer->name = fake()->firstName() . ' ' . fake()->lastName();
            $customer->email = fake()->unique()->safeEmail();
            $customer->delivery_address = fake()->address();
            $customer->save();

            $this->placeOrder(
                $customer,
                $branch,
                true,
                $tableIds,
                $waiterIds,
                $menuItems,
                $taxes,
                $orderNumberCounter,
                $kotNumberCounter
            ); // true for today
        }

        // Create 6 orders for previous days
        for ($i = 0; $i < 4; $i++) {
            $customer = new Customer();
            $customer->restaurant_id = $branch->restaurant_id;
            $customer->name = fake()->firstName() . ' ' . fake()->lastName();
            $customer->email = fake()->unique()->safeEmail();
            $customer->delivery_address = fake()->address();
            $customer->save();

            $this->placeOrder(
                $customer,
                $branch,
                false,
                $tableIds,
                $waiterIds,
                $menuItems,
                $taxes,
                $orderNumberCounter,
                $kotNumberCounter
            ); // false for previous days
        }
    }

    public function placeOrder(
        $customer,
        $branch,
        bool $isToday = true,
        array $tableIds = [],
        array $waiterIds = [],
        $menuItems = null,
        $taxes = null,
        int &$orderNumberCounter = 0,
        int &$kotNumberCounter = 0
    ): void
    {
        if (empty($tableIds) || empty($waiterIds) || !$menuItems || $menuItems->isEmpty()) {
            return;
        }

        $tableId = $tableIds[array_rand($tableIds)];
        $waiterId = $waiterIds[array_rand($waiterIds)];

        $orderNumber = $orderNumberCounter++;
        $kotNumber = $kotNumberCounter++;
        $now = now();

        $order = Order::create([
            'order_number' => (string) $orderNumber,
            'table_id' => $tableId,
            'customer_id' => $customer->id,
            'waiter_id' => $waiterId,
            'date_time' => $isToday ? $now->toDateTimeString() : $now->copy()->subDays(rand(1, 3))->toDateTimeString(),
            'sub_total' => 0,
            'total' => 0,
            'status' => 'draft',
            'branch_id' => $branch->id,
            'placed_via' => 'pos',
        ]);

        $kot = Kot::create([
            'kot_number' => (string) $kotNumber,
            'order_id' => $order->id,
            'branch_id' => $branch->id,
            'status' => 'in_kitchen',
        ]);

        $menuItemsCount = $menuItems->count();
        $itemsCount = min(rand(1, 5), $menuItemsCount);

        $selectedItems = $itemsCount === 1
            ? collect([$menuItems->random()])
            : $menuItems->random($itemsCount);

        $orderItemRows = [];
        $kotItemRows = [];

        $subTotal = 0.0;

        foreach ($selectedItems as $value) {
            $quantity = rand(1, 3);
            // Keep monetary math aligned with the DB decimal(16,2) columns.
            $amount = round(((float) $quantity) * (float) $value->price, 2);

            $subTotal += $amount;

            $kotItemRows[] = [
                'kot_id' => $kot->id,
                'menu_item_id' => $value->id,
                'menu_item_variation_id' => null,
                'quantity' => $quantity,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $orderItemRows[] = [
                'order_id' => $order->id,
                'menu_item_id' => $value->id,
                'menu_item_variation_id' => null,
                'quantity' => $quantity,
                'price' => $value->price,
                'amount' => $amount,
                'branch_id' => $branch->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($orderItemRows)) {
            // Bulk insert avoids N+1 `create()` calls.
            OrderItem::insert($orderItemRows);
        }

        if (!empty($kotItemRows)) {
            KotItem::insert($kotItemRows);
        }

        $total = $subTotal;

        if ($taxes && !$taxes->isEmpty()) {
            $orderTaxRows = [];
            foreach ($taxes as $value) {
                $orderTaxRows[] = [
                    'order_id' => $order->id,
                    'tax_id' => $value->id,
                ];

                $total += ((float) $value->tax_percent / 100) * $subTotal;
            }

            if (!empty($orderTaxRows)) {
                OrderTax::insert($orderTaxRows);
            }
        }

        $total = round($total);

        // Match old behavior: update totals first, then create payment, then mark as paid.
        Order::where('id', $order->id)->update([
            'sub_total' => $subTotal,
            'total' => $total,
        ]);

        $paymentMethod = ['card', 'cash', 'upi'];

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod[array_rand($paymentMethod)],
            'amount' => $total,
            'branch_id' => $branch->id,
        ]);

        Order::where('id', $order->id)->update([
            'status' => 'paid',
            'amount_paid' => $total,
        ]);
    }
}
