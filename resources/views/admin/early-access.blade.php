@extends('layouts.admin-layout')

@section('title', 'Early access')

@section('content')
    <x-owner.page-header title="Pendaftar early access"
        purpose="Bisnis yang mendaftar lewat halaman early access. Hubungi lewat WhatsApp untuk mulai setup." />

    <x-ui.panel flush>
        <x-slot:actions>
            <form method="GET" role="search" class="flex w-full items-center gap-2 sm:w-auto">
                <label for="early-access-search" class="sr-only">Cari pendaftar</label>
                <x-ui.input id="early-access-search" type="search" name="q" :value="$search" maxlength="100"
                    placeholder="Cari nama, bisnis, atau email" class="sm:w-72" />
                <x-ui.button type="submit" icon="search">Cari</x-ui.button>
            </form>
        </x-slot:actions>

        <p class="border-b border-bq-border px-4 py-2.5 text-bq-small text-bq-text-muted">
            @if ($search !== '')
                {{ number_format($signups->total(), 0, ',', '.') }} hasil untuk "{{ $search }}" dari {{ number_format($total, 0, ',', '.') }} pendaftar.
                <a href="{{ route('admin.early-access') }}" class="font-medium text-bq-primary underline">Tampilkan semua</a>
            @else
                {{ number_format($total, 0, ',', '.') }} pendaftar, terbaru di atas.
            @endif
        </p>

        @if ($signups->isEmpty())
            <x-ui.empty icon="user"
                :title="$search !== '' ? 'Tidak ada pendaftar yang cocok' : 'Belum ada pendaftar early access'"
                :description="$search !== '' ? 'Coba kata kunci lain atau tampilkan semua pendaftar.' : 'Pendaftar dari halaman early access akan muncul di sini.'" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-bq-body">
                    <thead>
                        <tr class="border-b border-bq-border text-left text-bq-small text-bq-text-muted">
                            <th scope="col" class="px-4 py-2.5 font-semibold">Nama</th>
                            <th scope="col" class="px-4 py-2.5 font-semibold">Bisnis</th>
                            <th scope="col" class="px-4 py-2.5 font-semibold">Kontak</th>
                            <th scope="col" class="px-4 py-2.5 font-semibold">Mendaftar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-bq-border">
                        @foreach ($signups as $signup)
                            <tr>
                                <td class="px-4 py-3 font-medium text-bq-ink">{{ $signup->full_name }}</td>
                                <td class="px-4 py-3">
                                    <p>{{ $signup->business_name }}</p>
                                    <p class="text-bq-small text-bq-text-muted">{{ $signup->business_type }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ $signup->whatsappUrl() }}" target="_blank" rel="noopener noreferrer"
                                        class="bq-num inline-flex items-center gap-1.5 font-medium text-bq-primary hover:underline"
                                        aria-label="Chat WhatsApp {{ $signup->full_name }} di {{ $signup->whatsapp_number }}">
                                        <x-owner.icon name="whatsapp" class="size-4" />{{ $signup->whatsapp_number }}
                                    </a>
                                    <p class="text-bq-small text-bq-text-muted">
                                        <a href="mailto:{{ $signup->email }}" class="hover:text-bq-ink">{{ $signup->email }}</a>
                                    </p>
                                </td>
                                <td class="bq-num whitespace-nowrap px-4 py-3 text-bq-text-muted">{{ $signup->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.panel>

    {{ $signups->links() }}
@endsection
