<div>
    @if ($unlocked)
        {{-- ------- Reveal ------- --}}
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <div class="text-4xl">🎉</div>
            <h1 class="mt-2 text-xl font-semibold text-emerald-900">Treasure unlocked!</h1>
            @if ($revealHasImage)
                <img src="{{ route('treasure.image', $treasureId) }}" alt="Treasure image"
                     class="mx-auto mt-4 max-h-96 rounded-lg object-contain">
            @endif
            <p class="mx-auto mt-4 max-w-prose whitespace-pre-line text-slate-800">{{ $revealMessage }}</p>
        </div>
    @else
        {{-- ------- Test screen ------- --}}
        <div x-data="geocacheTester()" class="space-y-6">
            <div class="text-center">
                <h1 class="text-2xl font-semibold tracking-tight">Find the treasure</h1>
                <p class="mx-auto mt-2 max-w-prose text-sm text-slate-500">
                    Enter a code and test it. We only tell you how far away you are —
                    move, test again, and zero in on the spot. It unlocks within about 10&nbsp;feet.
                </p>
            </div>

            <div class="mx-auto max-w-sm space-y-3">
                <label class="block text-sm font-medium text-slate-700">Treasure code</label>
                <input type="text" wire:model="code" maxlength="8" autocapitalize="characters"
                       placeholder="ABCD2345"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-lg tracking-[0.3em] uppercase focus:border-slate-900 focus:outline-none">
                @error('code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Units</span>
                    <div class="inline-flex overflow-hidden rounded-md border border-slate-300">
                        <button type="button" wire:click="$set('unit','imperial')"
                                @class(['px-3 py-1', 'bg-slate-900 text-white' => $unit==='imperial'])>ft / mi</button>
                        <button type="button" wire:click="$set('unit','metric')"
                                @class(['px-3 py-1', 'bg-slate-900 text-white' => $unit==='metric'])>m / km</button>
                    </div>
                </div>

                <button type="button" x-on:click="runTest()" x-bind:disabled="busy"
                        class="w-full rounded-lg bg-slate-900 px-4 py-3 font-medium text-white disabled:opacity-50">
                    <span x-show="!busy">Test my distance</span>
                    <span x-show="busy">Locating…</span>
                </button>

                <p x-show="geoError" x-text="geoError" class="text-sm text-red-600"></p>
            </div>

            {{-- Result --}}
            @if ($notFound)
                <p class="text-center text-sm text-red-600">No treasure found for that code.</p>
            @elseif ($paused)
                <p class="text-center text-sm text-amber-600">This treasure is not currently available.</p>
            @elseif ($result)
                <div class="mx-auto max-w-sm rounded-xl border border-slate-200 bg-white p-5 text-center">
                    <div class="text-lg font-semibold">{{ $result }}</div>
                    @if ($accuracyNote)
                        <div class="mt-1 text-xs text-slate-400">{{ $accuracyNote }}</div>
                    @endif
                </div>
            @endif
        </div>

        @script
        <script>
            Alpine.data('geocacheTester', () => ({
                busy: false,
                geoError: '',
                runTest() {
                    this.geoError = '';
                    if (!navigator.geolocation) {
                        this.geoError = 'Your browser does not support geolocation.';
                        return;
                    }
                    this.busy = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.busy = false;
                            this.$wire.test(
                                pos.coords.latitude,
                                pos.coords.longitude,
                                pos.coords.accuracy,
                            );
                        },
                        (err) => {
                            this.busy = false;
                            this.geoError = err.code === err.PERMISSION_DENIED
                                ? 'Location permission is required to play.'
                                : 'Could not get your location. Try again.';
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
                    );
                },
            }));
        </script>
        @endscript
    @endif
</div>
