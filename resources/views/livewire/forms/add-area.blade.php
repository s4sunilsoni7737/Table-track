<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
               
            <div>
                <x-label for="areaName" value="{{ __('modules.table.areaName') }}" />
                <x-input id="areaName" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.areaNamePlaceholder') }}" autofocus wire:model='areaName' />
                <x-input-error for="areaName" class="mt-2" />
            </div>
        </div>
           
        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button>@lang('app.save')</x-button>
            <button type="button" @click="$dispatch('close-add-area-modal')"
                class="inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                @lang('app.cancel')
            </button>
        </div>
    </form>
</div>
