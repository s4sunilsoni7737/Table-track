<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->name ?? config('app.name') }} - {{ __('modules.menu.allMenus') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        h2 { font-size: 14px; margin: 16px 0 8px 0; }
        .muted { color: #6B7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #E5E7EB; padding: 6px 8px; vertical-align: top; }
        th { background: #F9FAFB; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $settings->name ?? config('app.name') }}</h1>
    <div class="muted">{{ __('modules.menu.allMenus') }}</div>

    @forelse($menus as $menu)
        <h2>{{ $menu->menu_name }}</h2>

        @if($menu->items && $menu->items->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>{{ __('modules.menu.itemName') }}</th>
                        <th>{{ __('modules.menu.itemCategory') }}</th>
                        <th class="right">{{ __('modules.order.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($menu->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->item_name }}</strong>
                                @if($item->description)
                                    <br><span class="muted">{{ $item->description }}</span>
                                @endif
                            </td>
                            <td>{{ $item->category?->category_name ?? '-' }}</td>
                            <td class="right">{{ currency_format($item->price ?? 0, $settings->currency_id) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="muted">{{ __('messages.noRecordFound') ?? 'No items found' }}</div>
        @endif
    @empty
        <div class="muted">{{ __('messages.noRecordFound') ?? 'No menus found' }}</div>
    @endforelse
</body>
</html>


