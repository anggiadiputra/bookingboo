<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifikasi') }}
        </h2>
    </x-slot>

    <div class="p-4 space-y-4">
        @if (session('status'))
            <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-center justify-between">
            <p class="text-xs text-slate-500">
                {{ $notifications->total() }} notifikasi
                @if($unread = $notifications->where('status', \App\Models\Notification::STATUS_UNREAD)->count())
                    · <span class="font-bold text-rose-600">{{ $unread }} belum dibaca</span>
                @endif
            </p>
            @if($notifications->where('status', \App\Models\Notification::STATUS_UNREAD)->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                        <x-lucide-check-check class="w-3.5 h-3.5" />
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>

        @forelse($notifications as $notification)
            <div class="bg-white rounded-2xl border p-4 shadow-sm flex items-start gap-3
                        {{ $notification->status === \App\Models\Notification::STATUS_UNREAD ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200/80' }}">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $notification->status === \App\Models\Notification::STATUS_UNREAD ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-400' }}">
                    @php
                        $icon = match($notification->type) {
                            'booking' => 'lucide-calendar',
                            'payment' => 'lucide-credit-card',
                            'complaint' => 'lucide-flag',
                            'payout' => 'lucide-wallet',
                            'review' => 'lucide-star',
                            default => 'lucide-bell',
                        };
                    @endphp
                    <x-dynamic-component :component="$icon" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-800 leading-snug {{ $notification->status === \App\Models\Notification::STATUS_UNREAD ? 'font-semibold' : '' }}">
                        {{ $notification->content }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        {{ $notification->sent_at?->diffForHumans() ?: $notification->created_at->diffForHumans() }}
                    </p>
                </div>
                @if($notification->status === \App\Models\Notification::STATUS_UNREAD)
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" title="Tandai dibaca" class="p-1.5 text-slate-300 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <x-lucide-circle class="w-4 h-4 fill-current" />
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="p-10 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                <x-lucide-bell-off class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                <p class="text-sm font-semibold text-slate-600">Belum ada notifikasi</p>
                <p class="text-xs text-slate-400 mt-1">Notifikasi status booking & pembayaran akan muncul di sini.</p>
            </div>
        @endforelse

        <div class="pt-2">
            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
