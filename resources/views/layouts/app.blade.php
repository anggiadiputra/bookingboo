@if(auth()->check() && auth()->user()->isStaff())
    <x-admin-layout :header="$header ?? null" :title="$title ?? null">
        {{ $slot }}
    </x-admin-layout>
@else
    <x-mobile-layout :header="$header ?? null" :title="$title ?? null">
        @isset($header)
            <div class="px-4 py-3 bg-white border-b border-slate-100 flex items-center justify-between">
                <div>{!! $header !!}</div>
            </div>
        @endisset
        {{ $slot }}
    </x-mobile-layout>
@endif

