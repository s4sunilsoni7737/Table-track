<?php

namespace App\Livewire\Forms;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditRefundReason extends Component
{
    use LivewireAlert;
    public $refundReason;
    public $reason;

    public function mount()
    {
        $this->reason = $this->refundReason->reason;
    }

    public function submitForm()
    {
        $this->validate([
            'reason' => 'required|string|max:50',
        ]);

        $this->refundReason->reason = $this->reason;
        $this->refundReason->save();

        $this->dispatch('hideEditRefundReason');
         $this->alert('success', __('messages.reasonUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.edit-refund-reason');
    }
}

