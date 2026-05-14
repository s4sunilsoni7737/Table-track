@php
    $now = \Carbon\Carbon::now(timezone());
    $color = 'text-gray-500';
    $isToday = false;

    $date = $date->setTimezone(timezone());

    if ($date->isToday()) {
        $color = 'text-green-600';
        $isToday = true;
    } elseif ($date->isYesterday()) {
        $color = 'text-blue-800';
    }

    // Get restaurant date format
    $restaurantDateFormat = restaurant()->date_format ?? dateFormat();
    $time = $date->format(timeFormat());

    // Format date using restaurant format
    $formattedDate = $date->translatedFormat($restaurantDateFormat);
    $dateFormat = "{$formattedDate} {$time}";
@endphp

@if($date)
    @if(!$isToday)
        <span class="{{ $color }} text-xs">{!! $dateFormat !!} </span>
    @endif
    @if($isToday)
        <span class="{{ $color }} text-xs">{{ $time }}</span>
    @endif
    <p class="text-[11px] text-gray-400">{{ $date?->diffForHumans(short:true) }}</p>
@else
    -
@endif
