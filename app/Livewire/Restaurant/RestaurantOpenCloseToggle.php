<?php

namespace App\Livewire\Restaurant;

use App\Models\Restaurant;
use Livewire\Attributes\On;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RestaurantOpenCloseToggle extends Component
{
    use LivewireAlert;

    public bool $canManageOpenClose = false;
    public bool $showToggle = false;
    public bool $isRestaurantOpen = true;
    public bool $showConfirmModal = false;

    public function mount(): void
    {
        $this->loadState();
    }

    #[On('settingsUpdated')]
    public function refreshToggleState(): void
    {
        $this->loadState();
    }

    public function toggleRestaurantState(): void
    {
        if (!$this->canManageOpenClose) {
            abort(403);
        }

        $currentRestaurant = Restaurant::find(restaurant()->id);

        if (!$currentRestaurant) {
            return;
        }

        $isToggleMode = ($currentRestaurant->restaurant_open_close_mode ?? 'auto') === 'manual'
            && ($currentRestaurant->restaurant_manual_open_close_type ?? 'time') === 'toggle';

        if (!$isToggleMode) {
            $this->loadState();
            return;
        }

        $currentRestaurant->is_temporarily_closed = !$currentRestaurant->is_temporarily_closed;
        $currentRestaurant->save();

        session()->forget('restaurant');

        $this->loadState();
        $this->showConfirmModal = false;

        $this->alert('success', $this->isRestaurantOpen ? __('messages.restaurantOpened') : __('messages.restaurantClosed'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);
    }

    public function openConfirmModal(): void
    {
        if (!$this->canManageOpenClose || !$this->showToggle) {
            return;
        }

        $this->showConfirmModal = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
    }

    public function render()
    {
        return view('livewire.restaurant.restaurant-open-close-toggle');
    }

    private function loadState(): void
    {
        $this->canManageOpenClose = user()->hasRole('Admin_' . user()->restaurant_id)
            || user_can('Show Restaurant Open/Close');

        $currentRestaurant = Restaurant::find(restaurant()->id);

        if (!$currentRestaurant) {
            $this->showToggle = false;
            $this->isRestaurantOpen = true;
            return;
        }

        $this->showToggle = $this->canManageOpenClose
            && ($currentRestaurant->restaurant_open_close_mode ?? 'auto') === 'manual'
            && ($currentRestaurant->restaurant_manual_open_close_type ?? 'time') === 'toggle';

        $this->isRestaurantOpen = !$currentRestaurant->is_temporarily_closed;
    }
}
