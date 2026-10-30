@props([
    'href' => null,
    'icon',
    'title',
    'description',
    'tone' => 'teal',
    'chatbot' => false,
])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['client-action-card', 'client-action-card--'.$tone]) }}>
@else
    <button type="button" {{ $attributes->class(['client-action-card', 'client-action-card--'.$tone]) }}
            @if($chatbot) data-chatbot-open aria-controls="chatbot-ventana" aria-expanded="false" @endif>
@endif
        <span class="client-action-card__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
        <span class="client-action-card__copy">
            <strong>{{ $title }}</strong>
            <small>{{ $description }}</small>
        </span>
        <span class="client-action-card__arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
@if ($href)
    </a>
@else
    </button>
@endif
