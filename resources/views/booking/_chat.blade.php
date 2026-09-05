{{--
    Chat booking (polling MVP). Dipakai customer & caregiver viewer.
    Variabel: $booking, $viewer
    Polling setiap 5 detik via /bookings/{id}/chat/messages?after={lastId}
--}}
<div x-data="bookingChat({{ $booking->id }}, {{ $chatActive ? 'true' : 'false' }})"
     class="mt-6 pt-5 border-t border-slate-100"
>
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                <x-lucide-message-circle class="w-4 h-4" />
            </div>
            <span>Pesan Langsung</span>
        </h3>
        <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1" x-show="polling">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live update</span>
        </span>
    </div>

    <div x-ref="box" class="h-64 overflow-y-auto rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3.5 space-y-2.5 shadow-inner">
        <template x-for="m in messages" :key="m.id">
            <div :class="m.mine ? 'flex justify-end' : 'flex justify-start'">
                <div :class="m.mine
                    ? 'bg-rose-500 text-white rounded-2xl rounded-br-none px-3.5 py-2 shadow-sm max-w-[80%]'
                    : 'bg-white border border-slate-200 text-slate-800 rounded-2xl rounded-bl-none px-3.5 py-2 shadow-sm max-w-[80%]'">
                    <template x-if="! m.mine">
                        <p class="text-[10px] font-bold text-slate-500 mb-0.5" x-text="m.sender_name"></p>
                    </template>
                    <p class="text-xs leading-relaxed whitespace-pre-wrap break-words" x-text="m.content"></p>
                    <p class="text-[9px] mt-1 text-right" :class="m.mine ? 'text-rose-100' : 'text-slate-400'" x-text="m.created_at"></p>
                </div>
            </div>
        </template>
        <div x-show="messages.length === 0" class="text-center py-10">
            <x-lucide-message-square-dashed class="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <p class="text-xs text-slate-400">Belum ada percakapan. Sampaikan pesan awal Anda di bawah.</p>
        </div>
    </div>

    <template x-if="canSend">
        <form class="mt-3 flex gap-2" @submit.prevent="send()">
            <input x-ref="input" x-model="body" type="text" maxlength="2000"
                placeholder="Ketik pesan..."
                class="flex-1 rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 text-xs px-3.5 py-2.5 placeholder:text-slate-400"
                @keydown.enter.prevent="send()">
            <button type="submit" :disabled="sending || body.trim() === ''"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-rose-500 text-white text-xs font-semibold rounded-xl hover:bg-rose-600 shadow-sm shadow-rose-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <x-lucide-send class="w-4 h-4" />
            </button>
        </form>
    </template>
    <p x-show="! canSend && messages.length > 0" class="mt-2.5 text-center text-[11px] text-slate-400 bg-slate-50 py-2 rounded-xl border border-slate-100">
        Percakapan ditutup karena status booking telah selesai / dibatalkan.
    </p>
</div>

@push('scripts')
<script>
    function bookingChat(bookingId, canSendInit) {
        const base = {
            bookingId,
            canSend: canSendInit,
            messages: [],
            body: '',
            sending: false,
            polling: true,
            lastId: 0,
            csrf: document.querySelector('meta[name=csrf-token]')?.content || '',
            timer: null,

            async load() {
                try {
                    const res = await fetch(`/bookings/${this.bookingId}/chat/messages?after=${this.lastId}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (! res.ok) { this.polling = false; return; }
                    const data = await res.json();
                    if (data.messages.length > 0) {
                        this.messages = this.messages.concat(data.messages);
                        this.lastId = data.last_id;
                        this.$nextTick(() => { this.scrollBox(); });
                    }
                } catch (e) {
                    this.polling = false;
                }
            },

            scrollBox() {
                const box = this.$refs.box;
                box.scrollTop = box.scrollHeight;
            },

            async send() {
                if (! this.canSend || this.sending || this.body.trim() === '') return;
                this.sending = true;
                try {
                    const res = await fetch(`/bookings/${this.bookingId}/chat/kirim`,  {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({ body: this.body }),
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.messages.push(data);
                        this.lastId = Math.max(this.lastId, data.id);
                        this.body = '';
                        this.$nextTick(() => { this.scrollBox(); });
                    }
                } finally {
                    this.sending = false;
                }
            },
        };

        return {
            ...base,
            init() {
                this.load();
                setInterval(() => { this.load(); }, 5000);
            },
        };
    }
</script>
@endpush
