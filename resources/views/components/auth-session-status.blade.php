@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-success bg-success border border-success']) }}> <!-- text-green-600 -->
        {{ $status }}
    </div>
@endif
