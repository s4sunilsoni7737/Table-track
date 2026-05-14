<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTax;
use App\Models\SplitOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
class OrderController extends Controller
{

    public function index()
    {
        abort_if(!in_array('Order', restaurant_modules()), 403);
        abort_if((!user_can('Show Order')), 403);
        return view('order.index');
    }

    public function show($id)
    {
        return view('order.show', compact('id'));
    }

    public function printOrder($id, $width = 80, $thermal = false, $generateImage = false)
    {
        $id = Order::where('id', $id)->orWhere('uuid', $id)->value('id') ?: $id;

        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions'])->find($id);
        $orderBranch = $order->branch ?? branch();
        $receiptSettings = $orderBranch->receiptSetting;
        $taxMode = $order?->tax_mode ?? ($restaurant->tax_mode ?? 'order');
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        // Keep one line with all variables from both views (union of both compact calls)
        $content = view('order.print', compact('order', 'orderBranch', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount', 'width', 'thermal', 'generateImage'));

        return $content;
    }

    /**
     * Generate PDF for order print
     */
    public function generateOrderPdf($id)
    {
        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions'])->find($id);
        $orderBranch = $order->branch ?? branch();
        $receiptSettings = $orderBranch->receiptSetting;
        $taxMode = $restaurant->tax_mode ?? 'order';
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        // Calculate tax_base for PDF view
        if ($order->tax_base) {
            $taxBase = $order->tax_base;
        } else {
            // Fallback for old orders
            $net = $order->sub_total - ($order->discount_amount ?? 0);
            $serviceTotal = 0;
            foreach ($order->charges as $item) {
                $serviceTotal += $item->charge->getAmount($net);
            }
            $includeChargesInTaxBase = $restaurant->include_charges_in_tax_base ?? true;
            $taxBase = $includeChargesInTaxBase ? ($net + $serviceTotal) : $net;
        }

        // Generate PDF
        $pdf = Pdf::loadView('order.print-pdf', compact('order', 'orderBranch', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount', 'taxBase'));

        // Set paper size to A4
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download($order->show_formatted_order_number . '.pdf');
    }

    /**
     * Get PDF content as string for email attachment
     */
    public function getOrderPdfContent($id)
    {
        $payment = Payment::where('order_id', $id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $order = Order::with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions'])->find($id);
        $orderBranch = $order->branch ?? branch();
        $receiptSettings = $orderBranch->receiptSetting;
        $taxMode = $restaurant->tax_mode ?? 'order';
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        // Calculate tax_base for PDF view
        if ($order->tax_base) {
            $taxBase = $order->tax_base;
        } else {
            // Fallback for old orders
            $net = $order->sub_total - ($order->discount_amount ?? 0);
            $serviceTotal = 0;
            foreach ($order->charges as $item) {
                $serviceTotal += $item->charge->getAmount($net);
            }
            $includeChargesInTaxBase = $restaurant->include_charges_in_tax_base ?? true;
            $taxBase = $includeChargesInTaxBase ? ($net + $serviceTotal) : $net;
        }

        // Generate PDF
        $pdf = Pdf::loadView('order.print-pdf', compact('order', 'orderBranch', 'receiptSettings', 'taxDetails', 'payment', 'taxMode', 'totalTaxAmount', 'taxBase'));

        // Set paper size to A4
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Update waiter response status from dropdown
     */
    public function updateWaiterResponse(Request $request, $uuid)
    {
        abort_if(!user_can('Update Order'), 403);

        $validated = $request->validate([
            'waiter_response' => 'required|in:pending,accepted,declined',
        ]);

        $order = Order::where('uuid', $uuid)->firstOrFail();

        if (!$order->waiter_id) {
            if ($request->wantsJson()) {
                return response()->json(['message' => __('messages.invalidRequest')], 422);
            }

            return back()->with('error', __('messages.invalidRequest'));
        }

        $order->waiter_response = $validated['waiter_response'];
        $order->waiter_response_at = now();
        $order->save();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __('messages.statusUpdated'),
                'waiter_response' => $order->waiter_response,
            ]);
        }

        return back()->with('status', __('messages.statusUpdated'));

    }

    public function printSplitOrder($orderId, $width = 80, $thermal = false)
    {
        // Try to find as SplitOrder first
        $splitOrder = SplitOrder::with([
            'order.items.menuItem',
            'order.items.menuItemVariation',
            'order.items.modifierOptions',
            'items.orderItem.menuItem',
            'items.orderItem.menuItemVariation',
            'items.orderItem.modifierOptions'
        ])->find($orderId);

        // If found as SplitOrder, print single split
        if ($splitOrder) {
            $order = $splitOrder->order;
            $totalSplits = $order->splitOrders()->count();
            $splitNumber = $order->splitOrders()->where('id', '<=', $orderId)->count();
        }
        // Otherwise, treat as Order ID and print all paid splits
        else {
            $order = Order::with([
                'items.menuItem',
                'items.menuItemVariation',
                'items.modifierOptions'
            ])->findOrFail($orderId);
            // Get only paid split orders with their items
            $paidSplitOrders = $order->splitOrders()
                ->where('status', 'paid')
                ->with([
                    'items.orderItem.menuItem',
                    'items.orderItem.menuItemVariation',
                    'items.orderItem.modifierOptions'
                ])
                ->get();

            $totalSplits = $paidSplitOrders->count();
            $printAllSplits = true;

            // Set paid split orders to order for view
            $order->setRelation('splitOrders', $paidSplitOrders);
        }

        $payment = Payment::where('order_id', $order->id)->first();
        $restaurant = restaurant();
        $taxDetails = RestaurantTax::where('restaurant_id', $restaurant->id)->get();
        $receiptSettings = $restaurant->receiptSetting;
        $taxMode = $order?->tax_mode ?? ($restaurant->tax_mode ?? 'order');
        $totalTaxAmount = 0;

        if ($taxMode === 'item') {
            $totalTaxAmount = $order->total_tax_amount ?? 0;
        }

        $viewData = compact(
            'order',
            'receiptSettings',
            'taxDetails',
            'payment',
            'taxMode',
            'totalTaxAmount',
            'width',
            'thermal',
            'totalSplits'
        );

        // Add single split specific data if applicable
        if (isset($splitOrder) && isset($splitNumber)) {
            $viewData['splitOrder'] = $splitOrder;
            $viewData['splitNumber'] = $splitNumber;
        }

        // Add all splits flag if applicable
        if (isset($printAllSplits)) {
            $viewData['printAllSplits'] = $printAllSplits;
        }

        return view('order.print', $viewData);
    }
}
