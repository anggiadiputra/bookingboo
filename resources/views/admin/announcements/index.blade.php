<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Papan Pengumuman') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Daftar Pengumuman / Promo</h3>
                        <p class="text-sm text-gray-500">Konten banner berjalan di beranda (homepage).</p>
                    </div>
                    <a href="{{ route('admin.announcements.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none transition ease-in-out duration-150">
                        <x-lucide-plus class="w-4 h-4 mr-2" />
                        Buat Baru
                    </a>
                </div>

                <!-- Filter -->
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @foreach (['all' => 'Semua', 'promo' => 'Promo', 'announcement' => 'Pengumuman', 'ad' => 'Iklan'] as $key => $label)
                        <a href="{{ route('admin.announcements.index', ['type' => $key, 'status' => $status]) }}"
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ $type === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach

                    <span class="mx-1 text-gray-300">|</span>

                    @foreach (['all' => 'Semua Status', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $key => $label)
                        <a href="{{ route('admin.announcements.index', ['type' => $type, 'status' => $key]) }}"
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ $status === $key ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Konten</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urutan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($announcements as $item)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="flex items-start gap-3">
                                            @if($item->image_path)
                                                <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title }}"
                                                     class="w-20 h-12 rounded-md object-cover ring-1 ring-gray-200 shrink-0" loading="lazy" />
                                            @else
                                                <div class="w-20 h-12 rounded-md bg-gradient-to-r {{ $item->accentClasses() }} shrink-0 flex items-center justify-center">
                                                    <x-lucide-megaphone class="w-4 h-4 text-white" />
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900">{{ $item->title }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2 max-w-md">{{ $item->message }}</p>
                                                @if ($item->badge_label)
                                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">{{ $item->badge_label }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->type === 'promo' ? 'bg-rose-50 text-rose-700' : ($item->type === 'ad' ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700') }}">
                                            {{ ucfirst($item->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->sort_order }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-500">
                                        @if ($item->starts_at || $item->ends_at)
                                            {{ $item->starts_at?->format('d M Y') ?: '—' }} s/d {{ $item->ends_at?->format('d M Y') ?: '∞' }}
                                        @else
                                            <span class="text-gray-400">Selalu tampil</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if ($item->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                        <form method="POST" action="{{ route('admin.announcements.toggle', $item) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold {{ $item->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}"
                                                    title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.announcements.edit', $item) }}" class="ml-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Edit</a>
                                        <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" class="inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-3 text-xs font-semibold text-red-600 hover:text-red-800">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">Belum ada pengumuman. Klik "Buat Baru" untuk menambahkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $announcements->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
