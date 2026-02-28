@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-success-foreground bg-success border success-border']) }}> <!-- text-green-600 -->
        {{ $status }}
    </div>
@endif
