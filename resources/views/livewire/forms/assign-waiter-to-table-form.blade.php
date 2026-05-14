<div>
    <div class="space-y-4">
        <!-- Waiter Select -->
        <div>
            <label for="waiter_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                @lang('modules.table.assignWaiter') <span class="text-red-500">*</span>
            </label>
            <x-select id="waiter_id" class="w-full" wire:model.defer="waiter_id">
                <option value="">@lang('modules.table.assignWaiter')</option>
                @foreach($waiters as $waiter)
                    <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                @endforeach
            </x-select>
            <x-input-error for="waiter_id" class="mt-1" />
        </div>

        <!-- Backup Waiter Select -->
        <div>
            <label for="backup_waiter_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                @lang('modules.table.backupWaiter') <span class="text-gray-500 text-xs">(@lang('app.optional'))</span>
            </label>
            <x-select id="backup_waiter_id" class="w-full" wire:model.defer="backup_waiter_id">
                <option value="">@lang('modules.table.assignWaiter')</option>
                @foreach($waiters as $waiter)
                    @if($waiter->id != $waiter_id)
                        <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                    @endif
                @endforeach
            </x-select>
            <x-input-error for="backup_waiter_id" class="mt-1" />
        </div>

        <!-- Is Active -->
        <div>
            <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                @lang('app.isActive')
            </label>
            <x-select id="is_active" class="w-full" wire:model.defer="is_active">
                <option value="1">@lang('app.yes')</option>
                <option value="0">@lang('app.no')</option>
            </x-select>
            <x-input-error for="is_active" class="mt-1" />
        </div>

        <!-- Effective From -->
        <div>
            <label for="effective_from" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                @lang('app.effectiveFrom') <span class="text-red-500">*</span>
            </label>
            @php
                $dateFormat = restaurant()->date_format ?? 'd-m-Y';
                $minDate = now()->format($dateFormat);
            @endphp
            <x-datepicker wire:model.defer="effective_from" id="effective_from" minDate="{{ $minDate }}" class="w-full" />
            <x-input-error for="effective_from" class="mt-1" />
        </div>

        <!-- Effective To (Optional) -->
        <div>
            <label for="effective_to" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                @lang('app.effectiveTo') <span class="text-gray-500 text-xs">(@lang('app.optional'))</span>
            </label>
            <x-datepicker wire:model.defer="effective_to" id="effective_to" minDate="{{ $minDate }}" class="w-full" />
            <x-input-error for="effective_to" class="mt-1" />
        </div>
    </div>
</div>

