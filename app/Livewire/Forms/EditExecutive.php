<?php

namespace App\Livewire\Forms;

use App\Models\Country;
use App\Models\DeliveryExecutive;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditExecutive extends Component
{

    use LivewireAlert;

    public $member;
    public $memberName;
    public $memberEmail;
    public $memberPhone;
    public $status;
    public $availabilityStatus = 1;
    public $phoneCode;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;

    public function mount()
    {
        $this->memberName = $this->member->name;
        $this->memberEmail = $this->member->email;
        $this->memberPhone = $this->member->phone;
        $this->phoneCode = $this->member->phone_code;
        $this->status = $this->member->status;
        $this->availabilityStatus = (int) ($this->member->is_online ?? 0);

        // Initialize phone codes
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
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
        $this->phoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    public function submitForm()
    {
        $this->validate([
            'memberName' => 'required',
            'memberEmail' => 'required|email|unique:delivery_executives,email,' . $this->member->id,
            'phoneCode' => 'required',
            'availabilityStatus' => 'required|in:0,1',
        ]);

        DeliveryExecutive::where('id', $this->member->id)->update([
            'name' => $this->memberName,
            'email' => strtolower($this->memberEmail),
            'phone' => $this->memberPhone,
            'phone_code' => $this->phoneCode,
            'status' => $this->status,
            'is_online' => (int) $this->availabilityStatus,
        ]);

        // Reset the value
        $this->memberName = '';
        $this->memberEmail = '';
        $this->memberPhone = '';
        $this->phoneCode = '';
        $this->status = 'available';
        $this->availabilityStatus = 1;

        $this->dispatch('hideEditStaff');

        cache()->forget('delivery_executives_' . restaurant()->id);

        $this->alert('success', __('messages.memberUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.edit-executive', [
            'phonecodes' => $this->filteredPhoneCodes,
        ]);
    }

}
