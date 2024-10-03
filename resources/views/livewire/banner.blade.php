@props(['style', 'display', 'fixed', 'position'])

@if(app('impersonate')->isImpersonating())

@php
$display = $display ?? Filament\Facades\Filament::getUserName(Filament\Facades\Filament::auth()->user());
$fixed = $fixed ?? config('filament-impersonate.banner.fixed');
$position = $position ?? config('filament-impersonate.banner.position');
$borderPosition = $position === 'top' ? 'bottom' : 'top';

$style = $style ?? config('filament-impersonate.banner.style');
$styles = config('filament-impersonate.banner.styles');
$default = $style === 'auto' ? 'light' : $style;
$flipped = $default === 'dark' ? 'light' : 'dark';
@endphp

<div class="text-sm">
    <a class="mx-1 text-pink-600 dark:text-pink-600 hover:text-pink-200 dark:hover:text-pink-800" href="{{ route('filament-impersonate.leave') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="inline " width="22" height="22" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M18 6l-12 12" />
            <path d="M6 6l12 12" />
        </svg>
    </a>
    <span>Logged as </span>
    <span class="text-gray-700 dark:text-gray-300">{{ $display }}</span>
</div>
@else
<div class="text-sm">&bull;</div>
@endIf
