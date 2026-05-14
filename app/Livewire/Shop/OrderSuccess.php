<?php

namespace App\Livewire\Shop;

use App\Models\Branch;
use App\Models\Order;
use Livewire\Component;

class OrderSuccess extends Component
{

    public $id;
    public $order;
    public $restaurant;
    public $shopBranch;
    public $dateFormat;
    public $timeFormat;

    public function mount()
    {
        $this->order = Order::with('taxes.tax', 'items.menuItem', 'orderCashCollection')->where('id', $this->id)->firstOrFail();

        if (is_null(customer()) && $this->restaurant->customer_login_required) {
            return $this->redirect(route('home'));
        }

        if (request()->branch && request()->branch != '') {
            $this->shopBranch = Branch::find(request()->branch);
        } else {
            $this->shopBranch = $this->restaurant->branches->first();
        }

        // Set date and time formats
        $this->dateFormat = $this->restaurant->date_format ?? dateFormat();
        $this->timeFormat = $this->restaurant->time_format ?? timeFormat();
    }

    public function render()
    {
        return view('livewire.shop.order-success');
    }

    public function refreshOrderSuccess()
    {
        $this->dispatch('$refresh');
    }
}
