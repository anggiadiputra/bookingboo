<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Moderasi Akun') }}
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
                            <h3 class="text-lg font-medium text-gray-900">Daftar Pengguna</h3>
                            <p class="text-sm text-gray-500">Nonaktifkan akun customer atau caregiver yang melanggar ketentuan. Akun staf dikelola lewat panel staf.</p>
                        </div>
                        <a href="{{ route('admin.reviews.index') }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            <x-lucide-message-square class="w-3 h-3 mr-1" />
                            Moderasi Review
                        </a>
                    </div>

                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        @foreach (['all' => 'Semua', 'active' => 'Aktif', 'suspended' => 'Nonaktif'] as $key => $label)
                            <a href="{{ route('admin.users.index', ['tab' => $key]) }}"
                               class="px-3 py-1.5 rounded-md text-sm font-medium {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                {{ $label }}
                            </a>
                        @endforeach

                        <form action="{{ route('admin.users.index') }}" method="GET" class="ml-auto flex items-center gap-2">
                            <input type="hidden" name="tab" value="{{ $tab }}" />
                            <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, email, atau ID"
                                   class="w-56 border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                <x-lucide-search class="w-3 h-3 mr-1" />
                                Cari
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peran</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                            <span class="block text-xs text-gray-500">{{ $user->email }}</span>
                                            <span class="block text-xs text-gray-400">{{ $user->public_id }}</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->role === \App\Models\User::ROLE_CAREGIVER ? 'Caregiver' : 'Pasien/Keluarga' }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <x-lucide-ban class="w-3 h-3 mr-1" />
                                                    Nonaktif
                                                </span>
                                                @if ($user->suspended_reason)
                                                    <p class="text-xs text-red-600 mt-1">Alasan: {{ $user->suspended_reason }}</p>
                                                @endif
                                            @elseif ($user->status === \App\Models\User::STATUS_ACTIVE)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <x-lucide-user-check class="w-3 h-3 mr-1" />
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <x-lucide-clock class="w-3 h-3 mr-1" />
                                                    {{ ucfirst($user->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                                <form action="{{ route('admin.users.reactivate', $user) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                                                        <x-lucide-rotate-ccw class="w-3 h-3 mr-1" />
                                                        Aktifkan
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="reason" required maxlength="255" placeholder="Alasan penonaktifan" class="w-40 border-gray-300 rounded-md text-xs focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                                        <x-lucide-user-x class="w-3 h-3 mr-1" />
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada pengguna ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
</x-app-layout>