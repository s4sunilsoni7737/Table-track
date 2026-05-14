<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">

            <div>
                <x-label for="reason" value="{{ __('modules.settings.reason') }}" />
                <x-input id="reason" class="block mt-1 w-full" type="text"  wire:model='reason' />
                <x-input-error for="reason" class="mt-2" />
            </div>

        </div>

        <div class="flex pb-4 mt-6 space-x-4 w-full rtl:space-x-reverse">
            <x-button>@lang('app.update')</x-button>
            <x-button-cancel  wire:click="$dispatch('hideEditRefundReason')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>

