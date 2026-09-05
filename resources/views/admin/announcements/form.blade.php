<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($announcement) ? __('Edit Pengumuman') : __('Buat Pengumuman') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST"
                      action="{{ isset($announcement) ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
                      enctype="multipart/form-data"
                      class="space-y-5">
                    @csrf
                    @if (isset($announcement))
                        @method('PATCH')
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Latar <span class="text-gray-400">(opsional)</span></label>
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 w-36 h-24 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center" id="image-preview-wrap">
                                @if(isset($announcement) && $announcement->image_path)
                                    <img src="{{ asset('storage/' . $announcement->image_path) }}" alt="Pratinjau" class="w-full h-full object-cover" id="image-preview">
                                @else
                                    <x-lucide-image class="w-8 h-8 text-gray-300" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="image" id="image-input" accept="image/jpeg,image/png,image/webp"
                                       class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <p class="text-xs text-gray-400 mt-1">JPG/PNG/WebP, maks 2MB. Ukuran ideal: lebar 800px (rasio ~4:1). Gambar akan menjadi latar slide; tanpa gambar memakai warna gradien.</p>
                                @if(isset($announcement) && $announcement->image_path)
                                    <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 hover:text-red-800 cursor-pointer">
                                        <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                        Hapus gambar saat ini
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                        <select name="type" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach (['promo' => 'Promo', 'announcement' => 'Pengumuman', 'ad' => 'Iklan'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $announcement?->type ?? 'announcement') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                        <input type="text" name="title" value="{{ old('title', $announcement?->title ?? '') }}" maxlength="100" required
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
                        <textarea name="message" rows="3" maxlength="255" required
                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('message', $announcement?->message ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Label Badge <span class="text-gray-400">(opsional)</span></label>
                            <input type="text" name="badge_label" value="{{ old('badge_label', $announcement?->badge_label ?? '') }}" maxlength="30" placeholder="Promo"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teks Tombol <span class="text-gray-400">(opsional)</span></label>
                            <input type="text" name="link_label" value="{{ old('link_label', $announcement?->link_label ?? '') }}" maxlength="50" placeholder="Coba Sekarang"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Tujuan <span class="text-gray-400">(opsional)</span></label>
                        <input type="url" name="link_url" value="{{ old('link_url', $announcement?->link_url ?? '') }}" placeholder="https://..."
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $announcement?->sort_order ?? 0) }}" min="0" max="9999"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mulai Tampil</label>
                            <input type="date" name="starts_at" value="{{ old('starts_at', $announcement?->starts_at?->format('Y-m-d') ?? '') }}"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Berakhir</label>
                            <input type="date" name="ends_at" value="{{ old('ends_at', $announcement?->ends_at?->format('Y-m-d') ?? '') }}"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked((bool) old('is_active', $announcement?->is_active ?? true)) />
                            Aktif (langsung tampil di beranda)
                        </label>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                            <x-lucide-save class="w-4 h-4 mr-2" />
                            {{ isset($announcement) ? 'Perbarui' : 'Simpan' }}
                        </button>
                        <a href="{{ route('admin.announcements.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none transition ease-in-out duration-150">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const imageInput = document.getElementById('image-input');
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrap = document.getElementById('image-preview-wrap');
                    wrap.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Pratinjau';
                    img.className = 'w-full h-full object-cover';
                    wrap.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</x-app-layout>
