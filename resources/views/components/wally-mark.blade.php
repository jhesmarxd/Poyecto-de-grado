@props(['size' => 'md'])

<svg {{ $attributes->class(['wally-mark', 'wally-mark--'.$size]) }} viewBox="0 0 48 48" fill="none"
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="24" cy="24" r="20" fill="currentColor" fill-opacity=".12"/>
    <circle cx="24" cy="24" r="18.5" stroke="currentColor" stroke-width="3"/>
    <path d="M10.5 19.5c5.1.1 9.1 1.8 12 5.1 2.9 3.2 4.2 7.4 3.8 12.7M18.2 7.4c-.8 5.1.2 9.3 3.1 12.6 2.8 3.3 6.7 5.3 11.8 5.9M37.8 11.8c-4.3 2.3-7.1 5.5-8.2 9.5-1.2 4-.6 8.1 1.8 12.4M8.4 31c4.4-2.3 8.6-2.9 12.6-1.7 4 1.1 7.3 3.7 10 7.8M27.8 6.9c.3 4.7 2.2 8.5 5.5 11.4 2.4 2 5.2 3.3 8.5 3.8"
          stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M14.8 37.4c1.4-3.9 3.9-7.1 7.5-9.4M36.5 35.2l4.2 1.8" stroke="var(--accent, #B8E000)"
          stroke-width="3.5" stroke-linecap="round"/>
</svg>
