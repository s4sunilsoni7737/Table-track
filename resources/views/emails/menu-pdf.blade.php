@component('mail::layout')

@slot('header')
@component('mail::header', ['url' => route('shop_restaurant', ['hash' => $settings->hash])])
{{ $settings->name }}
@endcomponent
@endslot

## {{ __('app.hello') }} {{ $notifiable->name }},

{{ __('email.sendMenuPdf.text1_general') }} **{{ $settings->name }}**!

@if($menu)
{{ __('email.sendMenuPdf.text1', ['menu_name' => $menu->menu_name]) }}
@endif

@if($menuItems && $menuItems->isNotEmpty())
{{ __('email.sendMenuPdf.text2', ['count' => $menuItems->count()]) }}
@endif

{{ __('email.sendMenuPdf.text3') }}

@component('mail::button', ['url' => $downloadUrl ?? '#'])
{{ __('email.sendMenuPdf.downloadPdf') }}
@endcomponent

@lang('app.regards'),<br>
{{ $settings->name }}

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
    © {{ date('Y') }} {{ $settings->name }}. @lang('app.allRightsReserved')
@endcomponent
@endslot
@endcomponent
