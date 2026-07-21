{{--
    Edlink branded loader — a spinning navy ring with a yellow sweep and a
    pencil mark in the center, matching the logo.

    Supports inline, sized, and full-page overlay variants.
--}}
@props(['size' => '40', 'overlay' => false])

@php
    $navy = '#1B0B4D';
    $yellow = '#FDBB11';
@endphp

@if($overlay)
    <div {{ $attributes->merge(['class' => 'fixed inset-0 bg-white/85 backdrop-blur-sm z-[9999] flex items-center justify-center']) }}>
        <div class="flex flex-col items-center">
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Base ring -->
                <circle cx="32" cy="32" r="27" stroke="{{ $navy }}" stroke-width="5" opacity="0.15"/>
                <!-- Spinning yellow sweep -->
                <circle cx="32" cy="32" r="27" stroke="{{ $yellow }}" stroke-width="5"
                        stroke-linecap="round" stroke-dasharray="42 127"
                        transform-origin="32 32">
                    <animateTransform attributeName="transform" type="rotate" from="0 32 32" to="360 32 32" dur="0.9s" repeatCount="indefinite"/>
                </circle>
                <!-- Pencil nib mark, center -->
                <g>
                    <path d="M26 20 L26 34 L32 42 L38 34 L38 20" stroke="{{ $navy }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M32 20 L32 34" stroke="{{ $navy }}" stroke-width="3" stroke-linecap="round"/>
                </g>
            </svg>

            <div class="mt-5 flex items-baseline space-x-0.5">
                <span class="text-lg font-bold tracking-tight" style="color:{{ $navy }};">
                    <span class="inline-block animate-pulse" style="animation-delay:0ms">E</span><span class="inline-block animate-pulse" style="animation-delay:80ms">d</span><span class="inline-block animate-pulse" style="animation-delay:160ms">l</span><span class="inline-block animate-pulse" style="animation-delay:240ms">i</span><span class="inline-block animate-pulse" style="animation-delay:320ms">n</span><span class="inline-block animate-pulse" style="animation-delay:400ms">k</span>
                </span>
                <span class="text-lg font-bold animate-pulse" style="color:{{ $yellow }}; animation-delay:480ms">.</span>
            </div>
        </div>
    </div>
@else
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <circle cx="32" cy="32" r="27" stroke="{{ $navy }}" stroke-width="5" opacity="0.15"/>
        <circle cx="32" cy="32" r="27" stroke="{{ $yellow }}" stroke-width="5"
                stroke-linecap="round" stroke-dasharray="42 127"
                transform-origin="32 32">
            <animateTransform attributeName="transform" type="rotate" from="0 32 32" to="360 32 32" dur="0.9s" repeatCount="indefinite"/>
        </circle>
        <g>
            <path d="M26 20 L26 34 L32 42 L38 34 L38 20" stroke="{{ $navy }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <path d="M32 20 L32 34" stroke="{{ $navy }}" stroke-width="3" stroke-linecap="round"/>
        </g>
    </svg>
@endif
