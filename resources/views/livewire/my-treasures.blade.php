<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">My treasures</h1>
        <a href="{{ route('treasures.create') }}" wire:navigate
           class="rounded bg-slate-900 px-4 py-2 text-sm text-white">Create</a>
    </div>

    @forelse ($treasures as $treasure)
        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-lg font-bold tracking-widest">{{ $treasure->code }}</span>
                    @if (! $treasure->isActive())
                        <span class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-700">paused</span>
                    @endif
                </div>
                <p class="mt-1 truncate text-sm text-slate-500">{{ $treasure->message }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    {{ $treasure->unlocks_count }} unlock{{ $treasure->unlocks_count === 1 ? '' : 's' }}
                    · created {{ $treasure->created_at->diffForHumans() }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2 text-sm">
                <button wire:click="togglePause({{ $treasure->id }})"
                        class="rounded border border-slate-300 px-3 py-1.5">
                    {{ $treasure->isActive() ? 'Pause' : 'Resume' }}
                </button>
                <button wire:click="delete({{ $treasure->id }})"
                        wire:confirm="Delete this treasure permanently? Its code will stop working."
                        class="rounded border border-red-200 px-3 py-1.5 text-red-600">
                    Delete
                </button>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
            <p>You haven't created any treasures yet.</p>
            <a href="{{ route('treasures.create') }}" wire:navigate
               class="mt-3 inline-block rounded bg-slate-900 px-4 py-2 text-sm text-white">Create your first</a>
        </div>
    @endforelse
</div>
