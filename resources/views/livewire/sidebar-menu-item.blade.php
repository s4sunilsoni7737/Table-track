<li>
    <a href="{{ $link }}" @if($navigate) wire:navigate @endif
    @class(['flex items-center gap-2.5 px-2 py-2 rounded-lg text-sm font-medium cursor-pointer w-full text-gray-500', 'hover:text-gray-800  text-skin-base  font-bold bg-skin-base/[.20]' => $active])>
        <span @class(['w-4 h-4 rounded bg-brand-600 flex items-center justify-center text-[9px]', 'text-skin-base' => $active])>{!! $customIcon ?? $icon !!}</span>
        {{ $name }}
    </a>
    
</li>
