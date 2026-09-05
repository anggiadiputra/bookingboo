<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Moderasi Review') }}
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
                            <h3 class="text-lg font-medium text-gray-900">Daftar Review</h3>
                            <p class="text-sm text-gray-500">Sembunyikan review yang melanggar ketentuan. Rating caregiver dihitung ulang otomatis.</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            <x-lucide-users class="w-3 h-3 mr-1" />
                            Moderasi Akun
                        </a>
                    </div>

                    <div class="mb-4 flex space-x-2">
                        @foreach (['all' => 'Semua', 'published' => 'Tampil', 'hidden' => 'Disembunyikan'] as $key => $label)
                            <a href="{{ route('admin.reviews.index', ['tab' => $key]) }}"
                               class="px-3 py-1.5 rounded-md text-sm font-medium {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemberi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caregiver</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($reviews as $review)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-gray-900 max-w-xs">
                                            @if ($review->comment)
                                                <p>{{ $review->comment }}</p>
                                            @else
                                                <span class="text-gray-400">Tanpa komentar</span>
                                            @endif
                                            @if ($review->booking)
                                                <p class="text-xs text-gray-500 mt-1">Booking {{ $review->booking->code }}</p>
                                            @endif
                                            @if ($review->status === \App\Models\Review::STATUS_HIDDEN)
                                                <p class="text-xs text-red-600 mt-1">
                                                    Alasan: {{ $review->hidden_reason }}
                                                    @if ($review->hiddenBy)
                                                        &middot; oleh {{ $review->hiddenBy->name }}
                                                    @endif
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $review->reviewer->name }}
                                            <span class="block text-xs text-gray-400">{{ $review->reviewer->public_id }}</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $review->reviewee->name }}
                                            <span class="block text-xs text-gray-400">{{ $review->reviewee->public_id }}</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $review->rating)
                                                        <x-lucide-star class="w-4 h-4 text-yellow-400 fill-yellow-400" />
                                                    @else
                                                        <x-lucide-star class="w-4 h-4 text-gray-300" />
                                                    @endif
                                                @endfor
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($review->isPublished())
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <x-lucide-eye class="w-3 h-3 mr-1" />
                                                    Tampil
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <x-lucide-eye-off class="w-3 h-3 mr-1" />
                                                    Disembunyikan
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            @if ($review->isPublished())
                                                <form action="{{ route('admin.reviews.hide', $review) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="reason" required maxlength="255" placeholder="Alasan penyembunyian" class="w-40 border-gray-300 rounded-md text-xs focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                                        <x-lucide-eye-off class="w-3 h-3 mr-1" />
                                                        Sembunyikan
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.reviews.unhide', $review) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                                                        <x-lucide-eye class="w-3 h-3 mr-1" />
                                                        Tampilkan
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada review.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $reviews->links() }}</div>
                </div>
            </div>
        </div>
</x-app-layout>