@extends('layouts.portal')

@section('title', 'Notifikasi')
@section('header', 'Notifikasi')

@section('content')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-extrabold">Notifikasi</h1>
        <p class="mt-2 text-sm text-slate-600">Pembaruan status dan antrean pekerjaan.</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn-secondary !py-2">Tandai Semua Dibaca</button>
    </form>
</div>

<div class="card mt-7 divide-y divide-slate-100 overflow-hidden">
    @forelse($notifications as $notification)
        <a href="{{ route('notifications.read', $notification->id) }}" class="flex gap-4 p-5 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-blue-50/40' }}">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-brand-100 text-brand-700' }}">
                <x-icon name="bell" class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-sm font-bold text-slate-900">{{ $notification->data['title'] ?? 'Informasi' }}</p>
                    <span class="shrink-0 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
            </div>
        </a>
    @empty
        <div class="p-12 text-center text-sm text-slate-500">Belum ada notifikasi.</div>
    @endforelse
</div>

<div class="mt-6">{{ $notifications->links() }}</div>
@endsection
