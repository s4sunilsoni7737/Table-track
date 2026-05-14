<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use App\Models\RefundReason;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class AddRefundReason extends Component
{
    use LivewireAlert;
    public $reason;

     public function submitForm()
    {
        $this->validate([
            'reason' => 'required|string|max:50',
        ]);

        if (!branch()) {
            $this->addError('reason', 'No branch found. Please select a branch.');
            return;
        }

        $refundReason = new RefundReason();
        $refundReason->reason = $this->reason;
        $refundReason->branch_id = branch()->id;
        $refundReason->save();

        $this->dispatch('hideAddRefundReason');
         $this->alert('success', __('messages.reasonAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        // Reset form
        $this->reason = '';
    }

    public function render()
    {
        return view('livewire.forms.add-refund-reason');
    }
}

