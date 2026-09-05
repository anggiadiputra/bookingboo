@props(['action' => null])

@if(config('turnstile.enabled'))
    <div
        class="cf-turnstile"
        data-sitekey="{{ config('turnstile.site_key') }}"
        data-callback="bookingbooTurnstileCallback"
        @if($action) data-action="{{ $action }}" @endif
    ></div>
    <input type="hidden" name="cf-turnstile-response" id="cf-turnstile-response">

    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <script>
            function bookingbooTurnstileCallback(token) {
                document.getElementById('cf-turnstile-response').value = token;
            }
        </script>
    @endpush
@endif
