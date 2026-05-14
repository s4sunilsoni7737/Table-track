@php
    // Get restaurant from prop, helper, or request hash (similar to datepicker)
    $restaurantObj = $restaurant ?? null;

    // If restaurant prop not provided, try restaurant() helper
    if (!$restaurantObj) {
        $restaurantObj = restaurant();
    }

    // If still not available, try to get from request hash (for customer pages)
    if (!$restaurantObj) {
        $hash = request()->route('hash') ?? request()->query('hash');
        if ($hash) {
            // Try shop() helper first
            $restaurantObj = shop($hash);
            // If shop() doesn't work, query directly
            if (!$restaurantObj) {
                $restaurantObj = \App\Models\Restaurant::where('hash', $hash)->first();
            }
        }
    }

    // Get time format from restaurant or use default
    $timeFormat = $restaurantObj?->time_format ?? 'h:i A';
    $currentTime = $value ?? now()->format('H:i');

    // Normalize time format
    if ($currentTime && preg_match('/(\d{1,2}):(\d{1,2})/', $currentTime, $matches)) {
        $currentTime = str_pad((int)$matches[1], 2, '0', STR_PAD_LEFT) . ':' . str_pad((int)$matches[2], 2, '0', STR_PAD_LEFT);
    } else {
        $currentTime = now()->format('H:i');
    }

    $timeObj = now()->setTimeFromTimeString($currentTime);
    $hour24 = (int)$timeObj->format('H');
    $minute = (int)$timeObj->format('i');
    $is24Hour = $timeFormat === 'H:i';
    $hour12 = $is24Hour ? $hour24 : ($hour24 % 12 ?: 12);

    // Detect if format uses uppercase 'A' or lowercase 'a' for AM/PM
    $isUpperCaseAmPm = strpos($timeFormat, 'A') !== false;
    $ampmValue = $is24Hour ? '' : ($isUpperCaseAmPm ? strtoupper($timeObj->format('A')) : strtolower($timeObj->format('A')));
@endphp

<div x-data="{
    is24Hour: {{ $is24Hour ? 'true' : 'false' }},
    timeFormat: '{{ $timeFormat }}',
    hours: {{ $hour12 }},
    minutes: {{ $minute }},
    ampm: '{{ $ampmValue }}',
    isUpperCaseAmPm: {{ $isUpperCaseAmPm ? 'true' : 'false' }},
    showPicker: false,
    dropdownPosition: 'bottom',
    get displayValue() {
        const h = String(this.hours).padStart(2, '0');
        const m = String(this.minutes).padStart(2, '0');
        if (this.is24Hour) {
            return `${h}:${m}`;
        }
        const ampmDisplay = this.isUpperCaseAmPm ? this.ampm.toUpperCase() : this.ampm.toLowerCase();
        return `${this.hours}:${m} ${ampmDisplay}`;
    },
    get internalValue() {
        const m = String(this.minutes).padStart(2, '0');
        let h24 = this.hours;
        if (!this.is24Hour) {
            const ampmUpper = this.ampm.toUpperCase();
            if (ampmUpper === 'PM' && this.hours !== 12) h24 += 12;
            if (ampmUpper === 'AM' && this.hours === 12) h24 = 0;
        }
        return String(h24).padStart(2, '0') + ':' + m;
    },
    init() {
        this.$watch('hours', () => this.updateDisplay());
        this.$watch('minutes', () => this.updateDisplay());
        this.$watch('ampm', () => this.updateDisplay());
    },
    togglePicker() {
        this.showPicker = !this.showPicker;
        if (this.showPicker) this.$nextTick(() => this.adjustPosition());
    },
    adjustPosition() {
        const rect = this.$refs.dropdown?.getBoundingClientRect();
        if (!rect) return;
        this.dropdownPosition = (window.innerHeight - rect.bottom < 200 && rect.top > window.innerHeight - rect.bottom) ? 'top' : 'bottom';
    },
    updateDisplay() {
        // Livewire listens to input/change events on the actual input element.
        // Dispatch bubbled input + change events so wire:model and inline
        // onchange handlers (used by POS) both receive updates.
        if (this.$refs.input) {
            this.$refs.input.value = this.internalValue;
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            this.$dispatch('input', this.internalValue);
            this.$dispatch('change', this.internalValue);
        }
    },
    adjustHour(delta) {
        if (this.is24Hour) {
            this.hours = (this.hours + delta + 24) % 24;
        } else {
            this.hours = ((this.hours - 1 + delta + 12) % 12) + 1;
        }
    },
    adjustMinute(delta) {
        this.minutes = (this.minutes + delta + 60) % 60;
    },
    toggleAmPm() {
        const currentUpper = this.ampm.toUpperCase();
        const newValue = currentUpper === 'AM' ? 'PM' : 'AM';
        this.ampm = this.isUpperCaseAmPm ? newValue : newValue.toLowerCase();
    },
    handleInput(e) {
        const match = e.target.value.match(/(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)?/i);
        if (match) {
            let h = parseInt(match[1]);
            const m = parseInt(match[2]);
            const ap = match[3];

            if (!this.is24Hour && ap) {
                const apUpper = ap.toUpperCase();
                // Set ampm with correct case based on format
                this.ampm = this.isUpperCaseAmPm ? apUpper : apUpper.toLowerCase();
                if (apUpper === 'PM' && h !== 12) h += 12;
                if (apUpper === 'AM' && h === 12) h = 0;
            }
            this.hours = this.is24Hour ? h : (h % 12 || 12);
            this.minutes = m;
        }
    }
}" x-on:click.away="showPicker = false" class="relative">
    <input type="text" x-ref="input" x-model="displayValue" @input="handleInput($event)" @click="togglePicker()" readonly
        {!! $attributes->merge(['class' => 'inline-flex items-center pl-4 pr-5 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-lg text-gray-700 dark:text-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-500 w-full cursor-pointer relative z-10']) !!}>
    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none z-20">
        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>
    <div x-show="showPicker" x-ref="dropdown" x-cloak
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        :class="dropdownPosition === 'top' ? 'absolute left-0 bottom-full mb-2' : 'absolute left-0 top-full mt-2'"
        class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg shadow-xl p-2 min-w-[200px]"
        style="display: none; z-index: 99999;" @click.stop>
        <div class="flex items-center justify-center gap-2 py-1">
            <div class="flex flex-col items-center gap-1">
                <button type="button" @click="adjustHour(1)" class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>
                <input type="number" x-model="hours" @input="updateDisplay()" :min="is24Hour ? 0 : 1" :max="is24Hour ? 23 : 12" disabled
                    class="time-picker-input w-12 text-center text-lg font-bold bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-md px-1 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white cursor-default">
                <button type="button" @click="adjustHour(-1)" class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </div>
            <span class="text-lg font-bold text-gray-900 dark:text-white">:</span>
            <div class="flex flex-col items-center gap-1">
                <button type="button" @click="adjustMinute(1)" class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                </button>
                <input type="number" x-model="minutes" @input="updateDisplay()" min="0" max="59" disabled
                    class="time-picker-input w-12 text-center text-lg font-bold bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-md px-1 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white cursor-default">
                <button type="button" @click="adjustMinute(-1)" class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </div>
            <template x-if="!is24Hour">
                <div class="flex flex-col items-center ml-1">
                    <button type="button" @click="toggleAmPm()" class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600" x-text="ampm"></button>
                </div>
            </template>
        </div>
    </div>
</div>
