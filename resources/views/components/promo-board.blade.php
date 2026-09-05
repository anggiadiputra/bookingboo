@props(['announcements'])

@if($announcements->isNotEmpty())
    <section x-data="{
        active: 0,
        total: {{ $announcements->count() }},
        timer: null,
        start() {
            if (this.total < 2) return;
            this.timer = setInterval(() => {
                this.active = (this.active + 1) % this.total;
            }, 5000);
        },
        stop() { clearInterval(this.timer); },
        go(i) { this.stop(); this.active = i; this.start(); },
    }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        class="relative overflow-hidden rounded-2xl shadow-soft"
        style="height: 160px;">
        <div class="flex h-full transition-transform duration-500 ease-out" x-bind:style="'transform: translateX(-' + active * 100 + '%)'">
            @foreach($announcements as $item)
                <div class="w-full h-full shrink-0 relative overflow-hidden {{ $item->accentClasses() }}">
                    @if($item->image_path)
                        <img src="{{ asset('storage/' . $item->image_path) }}"
                             alt="{{ $item->title }}"
                             class="absolute inset-0 w-full h-full object-cover"
                             loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-transparent"></div>
                    @endif

                    <div class="relative h-full z-10 flex items-center p-4 sm:p-5 {{ $item->image_path ? 'justify-end text-right' : '' }}">
                        @if($item->image_path)
                            <!-- Konten di sisi kanan bila ada gambar -->
                            <div class="max-w-[60%] sm:max-w-[55%] ml-auto">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $item->badgeClasses() }} text-[9px] font-bold uppercase tracking-wider">
                                    {{ $item->badge_label ?? ucfirst($item->type) }}
                                </span>
                                <h3 class="text-sm sm:text-base font-bold text-white mt-1.5 leading-snug drop-shadow-sm">{{ $item->title }}</h3>
                                <p class="text-[11px] sm:text-xs text-white/90 mt-1 leading-snug drop-shadow-sm">{{ $item->message }}</p>
                                @if($item->link_url)
                                    <a href="{{ $item->link_url }}"
                                        class="inline-flex items-center gap-1 mt-2.5 px-3.5 py-1.5 rounded-full bg-white text-slate-900 text-xs font-bold hover:bg-slate-100 transition-transform active:scale-95 shadow-xs">
                                        {{ $item->link_label ?? 'Selengkapnya' }}
                                        <x-lucide-chevron-right class="w-3 h-3" />
                                    </a>
                                @endif
                            </div>
                        @else
                            <!-- Tanpa gambar: konten kiri penuh seperti semula -->
                            <div class="flex items-center gap-3 w-full">
                                <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                                    @if($item->type === \App\Models\Announcement::TYPE_PROMO)
                                        <x-lucide-gift class="w-5 h-5 text-white stroke-[2.2]" />
                                    @elseif($item->type === \App\Models\Announcement::TYPE_AD)
                                        <x-lucide-sparkles class="w-5 h-5 text-white stroke-[2.2]" />
                                    @else
                                        <x-lucide-megaphone class="w-5 h-5 text-white stroke-[2.2]" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full {{ $item->badgeClasses() }} text-[9px] font-bold uppercase tracking-wider">
                                            {{ $item->badge_label ?? ucfirst($item->type) }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white mt-1 leading-snug">{{ $item->title }}</h3>
                                    <p class="text-[11px] mt-0.5 leading-snug line-clamp-2">{{ $item->message }}</p>
                                    @if($item->link_url)
                                        <a href="{{ $item->link_url }}"
                                            class="inline-flex items-center gap-1 mt-2 px-3 py-1.5 rounded-full bg-white/95 text-xs font-bold text-slate-900 hover:bg-white transition-transform active:scale-95">
                                            {{ $item->link_label ?? 'Selengkapnya' }}
                                            <x-lucide-chevron-right class="w-3 h-3" />
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($announcements->count() > 1)
            <!-- Dots navigation -->
            <div class="absolute bottom-2 right-3 z-20 flex items-center gap-1.5">
                @foreach($announcements as $i => $item)
                    <button type="button"
                        @click="go({{ $i }})"
                        aria-label="Slide {{ $i + 1 }}"
                        class="w-1.5 h-1.5 rounded-full transition-all {{ $loop->first ? 'bg-white w-4' : 'bg-white/50 hover:bg-white/80' }}"
                        x-bind:class="active === {{ $i }} ? 'bg-white w-4' : 'bg-white/50'">
                    </button>
                @endforeach
            </div>
        @endif
    </section>
@endif
