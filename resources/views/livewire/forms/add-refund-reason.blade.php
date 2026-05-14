<div>
    <form wire:submit.prevent="submitForm">
        @csrf
        <div class="space-y-4">

            <div>
                <x-label for="reason" value="{{ __('modules.settings.reason') }}" />
                <x-input id="reason" class="block w-full mt-1" type="text"  wire:model='reason' />
                <x-input-error for="reason" class="mt-2" />
            </div>

        </div>

        <div class="flex w-full pb-4 mt-6 space-x-4 rtl:space-x-reverse">
            <x-button>@lang('app.save')</x-button>
            <x-button-cancel  wire:click="$dispatch('hideAddRefundReason')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>

