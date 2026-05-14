<li x-data="{ active: @entangle('active') }" x-init="if (active) { setTimeout(() => { $el.scrollIntoView({ behavior: 'smooth' }); }, 400); }">
    <a href="{{ $link }}" wire:navigate
        @class(['flex items-center p-1 text-sm text-gray-400 transition duration-75 rounded-lg pl-11 group hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700', 'text-skin-base font-bold' => $active])>{{ $name }}</a>
</li>
