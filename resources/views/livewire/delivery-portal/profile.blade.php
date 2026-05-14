<div class="px-4">
    <h2 class="text-2xl font-extrabold dark:text-white">@lang('menu.profile')</h2>

    <div class="mt-4 p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
        <form wire:submit="submitForm" class="space-y-4">
            <div>
                <x-label for="fullName" value="{{ __('modules.staff.name') }}" />
                <x-input id="fullName" class="block mt-1 w-full" type="text" wire:model='fullName' />
                <x-input-error for="fullName" class="mt-2" />
            </div>

            <div>
                <x-label for="email" value="{{ __('app.email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" wire:model='email' />
                <x-input-error for="email" class="mt-2" />
            </div>

            <div>
                <x-label for="phone" value="{{ __('modules.restaurant.phone') }}" />
                <div class="flex gap-2 mt-1">
                    <div x-data="{ isOpen: @entangle('phoneCodeIsOpen').live }" @click.away="isOpen = false" class="relative w-32">
                        <div @click="isOpen = !isOpen" class="p-2 bg-gray-100 border rounded cursor-pointer dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <div class="flex items-center justify-between">
                                <span class="text-sm">{{ $phoneCode ? '+' . $phoneCode : __('modules.settings.select') }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                        <ul x-show="isOpen" x-transition class="absolute z-10 w-full mt-1 overflow-auto bg-white rounded-lg shadow-lg max-h-60 dark:bg-gray-900 dark:text-gray-300">
                            <li class="sticky top-0 px-3 py-2 bg-white dark:bg-gray-900 z-10">
                                <x-input wire:model.live.debounce.300ms="phoneCodeSearch" class="block w-full" type="text" placeholder="{{ __('placeholders.search') }}" />
                            </li>
                            @forelse ($phonecodes as $phonecode)
                                <li @click="$wire.selectPhoneCode('{{ $phonecode }}')" wire:key="delivery-phone-code-{{ $phonecode }}" class="py-2 pl-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">+{{ $phonecode }}</li>
                            @empty
                                <li class="py-2 pl-3 text-gray-500">{{ __('modules.settings.noPhoneCodesFound') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <x-input id="phone" class="block w-full" type="tel" wire:model='phone' placeholder="1234567890" />
                </div>
                <x-input-error for="phoneCode" class="mt-2" />
                <x-input-error for="phone" class="mt-2" />
            </div>

            <div>
                <x-label for="availabilityStatus" value="{{ __('app.availability') }}" />
                <x-select id="availabilityStatus" class="mt-1 block w-full" wire:model='availabilityStatus'>
                    <option value="1">@lang('app.online')</option>
                    <option value="0">@lang('app.offline')</option>
                </x-select>
                <x-input-error for="availabilityStatus" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-button>@lang('app.save')</x-button>
                <a href="{{ route('delivery.dashboard') }}" class="inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">@lang('app.cancel')
                </a>
            </div>
        </form>
    </div>
</div>
