@php
    // Get restaurant from prop, helper, or request hash (similar to Cart.php)
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

    // Get date format from restaurant or use default
    $phpFormat = $restaurantObj?->date_format ?? 'd-m-Y';

    // cSpell:ignore pikaday
    $pikadayFormat = str_replace(['d', 'm', 'Y', 'y'], ['DD', 'MM', 'YYYY', 'YY'], $phpFormat);
    $currentDate = now()->format($phpFormat);
    $currentDateJs = now()->format('Y-m-d');

    // Get value from attributes if provided
    $value = $attributes->get('value');

    // Get minDate and maxDate from attributes if provided
    $minDate = $attributes->get('minDate');
    $maxDate = $attributes->get('maxDate');

    // Parse dates to JavaScript format if provided
    $minDateJs = 'null';
    $maxDateJs = 'null';

    if ($minDate) {
        try {
            $carbonDate = \Carbon\Carbon::createFromFormat($phpFormat, $minDate);
            $minDateJs = "new Date(" . $carbonDate->year . ", " . ($carbonDate->month - 1) . ", " . $carbonDate->day . ")";
        } catch (\Exception $e) {
            // If parsing fails, try to parse as is
            $minDateJs = "new Date('" . $minDate . "')";
        }
    }

    if ($maxDate) {
        try {
            $carbonDate = \Carbon\Carbon::createFromFormat($phpFormat, $maxDate);
            $maxDateJs = "new Date(" . $carbonDate->year . ", " . ($carbonDate->month - 1) . ", " . $carbonDate->day . ")";
        } catch (\Exception $e) {
            // If parsing fails, try to parse as is
            $maxDateJs = "new Date('" . $maxDate . "')";
        }
    }

    // Remove minDate, maxDate, and value from attributes so they don't appear as HTML attributes
    $attributes = $attributes->except(['minDate', 'maxDate', 'value']);

    // Set initial value - use provided value, or current date in restaurant format if no value
    $initialValue = $value ?: $currentDate;
    @endphp

<input x-data x-init="
    // Function to parse date based on PHP format
    function parseDate(value, phpFormat) {
        if (!value) return null;
        try {
            const parts = value.split('-');
            let day, month, year;
            if (phpFormat === 'd-m-Y') {
                day = parseInt(parts[0]);
                month = parseInt(parts[1]) - 1;
                year = parseInt(parts[2]);
            } else if (phpFormat === 'm-d-Y') {
                month = parseInt(parts[0]) - 1;
                day = parseInt(parts[1]);
                year = parseInt(parts[2]);
            } else if (phpFormat === 'Y-m-d') {
                year = parseInt(parts[0]);
                month = parseInt(parts[1]) - 1;
                day = parseInt(parts[2]);
            } else {
                // Default: try to parse as d-m-Y
                day = parseInt(parts[0]);
                month = parseInt(parts[1]) - 1;
                year = parseInt(parts[2]);
            }
            if (day && month >= 0 && year) {
                return new Date(year, month, day);
            }
        } catch (e) {
            console.error('Error parsing date:', e);
        }
        return null;
    }

    const phpFormat = '{{ $phpFormat }}';

    // Custom toString function to format date according to restaurant format
    function formatDateToString(date, format) {
        if (!date) return '';
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return format.replace(/d/g, d).replace(/m/g, m).replace(/Y/g, y).replace(/y/g, String(y).slice(-2));
    }

    // Flag to prevent infinite loops between user input and Livewire updates
    let isUpdatingFromLivewire = false;
    let isUpdatingFromUser = false;
    let isInitializing = true;

    // Store reference to input element for use in callbacks
    const inputElement = $refs.input;

    const pickerConfig = {
        field: inputElement,
        format: '{{ $pikadayFormat }}',
        position: 'bottom right',
        toString: function(date, format) {
            return formatDateToString(date, '{{ $phpFormat }}');
        },
        onSelect: function (selectedDate) {
            // Don't block if user is selecting (only block if Livewire is updating)
            if (isUpdatingFromLivewire || !inputElement) return;

            // Pikaday calls onSelect with `this` = picker instance (see pikaday.js setDate).
            const pickerInstance = this;

            try {
                isUpdatingFromUser = true;
                const formattedDate = formatDateToString(selectedDate, '{{ $phpFormat }}');

                // Update the input value
                inputElement.value = formattedDate;
                inputElement.setAttribute('value', formattedDate);

                // Dispatch input event for Livewire wire:model
                // and change event for inline onchange handlers.
                // Mark change with firedBy so Pikaday's _onInputChange ignores it; otherwise
                // a late change after hide() runs `if (!self._v) self.show()` and reopens the calendar.
                setTimeout(() => {
                    try {
                        if (inputElement) {
                            const inputEvent = new Event('input', { bubbles: true, cancelable: true });
                            inputElement.dispatchEvent(inputEvent);
                            const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                            changeEvent.firedBy = pickerInstance;
                            inputElement.dispatchEvent(changeEvent);
                        }
                        // Close after notifying listeners so Livewire/async cannot reopen via _onInputChange.
                        if (typeof pickerInstance.hide === 'function') {
                            pickerInstance.hide();
                        }
                    } catch (e) {
                        console.error('Error dispatching input event:', e);
                    }
                }, 10);
            } catch (e) {
                console.error('Error in onSelect:', e);
            } finally {
                // Reset flag after a short delay
                setTimeout(() => { isUpdatingFromUser = false; }, 200);
            }
        }
    };
    @if($minDate)
    pickerConfig.minDate = {{ $minDateJs }};
    @endif
    @if($maxDate)
    pickerConfig.maxDate = {{ $maxDateJs }};
    @endif
    const picker = new Pikaday(pickerConfig);

    // Function to initialize picker with current value
    function initializePicker() {
        if (!inputElement) return;

        try {
            // Check if input has a value (from wire:model or initial)
            let currentValue = inputElement.value;

            // Only set initial value if input is truly empty
            // Don't override if user has already selected a date or Livewire has set a value
            if (!currentValue || currentValue.trim() === '') {
                // If no value, set to current date in restaurant format
                currentValue = '{{ $initialValue }}';
                // Set value without triggering events during initialization
                isInitializing = true;
                inputElement.setAttribute('value', currentValue);
                inputElement.value = currentValue;

                // Parse and set the date in the picker
                const parsedDate = parseDate(currentValue, phpFormat);
                if (parsedDate) {
                    picker.setDate(parsedDate, false); // false = don't trigger onSelect, just set the date
                    // Ensure the input shows the correct format after setting the date
                    const formattedValue = formatDateToString(parsedDate, phpFormat);
                    // Only update if different to avoid triggering unnecessary events
                    if (inputElement.value !== formattedValue) {
                        inputElement.setAttribute('value', formattedValue);
                        inputElement.value = formattedValue;
                    }
                }
            } else {
                // Input already has a value, just sync the picker with it
                const parsedDate = parseDate(currentValue, phpFormat);
                if (parsedDate) {
                    picker.setDate(parsedDate, false);
                }
            }
        } catch (e) {
            console.error('Error in initializePicker:', e);
        } finally {
            // Mark initialization as complete
            isInitializing = false;
        }
    }

    // Note: We don't need to watch for Livewire updates here
    // The updatedDate method in the component will handle format conversion
    // and the input events will properly sync with wire:model

    // Initialize picker
    // Use a small delay to allow Livewire to set initial values via wire:model
    setTimeout(initializePicker, 100);
    " x-ref="input" type="text" {!! $attributes->merge(['class' =>
    'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-lg text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none
    focus:ring-2 focus:ring-gray-500
    disabled:opacity-25 transition ease-in-out duration-150 w-full text-xs']) !!}>
