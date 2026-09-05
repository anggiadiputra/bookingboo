@component('mail::message')
# {{ $subject }}

@foreach ($lines as $line)
    {{ $line }}<br>
@endforeach

@if (! empty($info))
    @component('mail::panel')
        @foreach ($info as $label => $value)
            **{{ $label }}:** {{ $value }}<br>
        @endforeach
    @endcomponent
@endif

@component('mail::button', ['url' => url('/dashboard')])
Buka Dashboard
@endcomponent

{{ __('Terima kasih,') }}<br>
{{ config('app.name') }}
@endcomponent