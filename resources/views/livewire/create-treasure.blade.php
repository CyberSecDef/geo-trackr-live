<div>
    @if ($createdCode)
        {{-- ------- Success: show code + share link ------- --}}
        <div class="mx-auto max-w-md rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
            <div class="text-3xl">✅</div>
            <h1 class="mt-2 text-lg font-semibold text-emerald-900">Treasure created!</h1>
            <p class="mt-2 text-sm text-slate-600">Share this code — anyone can hunt for it.</p>
            <div class="mt-4 rounded-lg bg-white px-4 py-3 text-2xl font-bold tracking-[0.3em]">{{ $createdCode }}</div>
            <input readonly value="{{ route('treasure.test', $createdCode) }}"
                   class="mt-3 w-full rounded border border-slate-200 bg-white px-3 py-2 text-center text-xs text-slate-500"
                   onclick="this.select()">
            <div class="mt-4 flex justify-center gap-3 text-sm">
                <a href="{{ route('treasures.create') }}" wire:navigate class="rounded bg-slate-900 px-4 py-2 text-white">Create another</a>
                <a href="{{ route('treasures.index') }}" wire:navigate class="rounded border border-slate-300 px-4 py-2">My treasures</a>
            </div>
        </div>
    @else
        <form wire:submit="save" x-data="locationCapture()" class="mx-auto max-w-md space-y-6">
            <h1 class="text-2xl font-semibold tracking-tight">Create a treasure</h1>
            <p class="text-sm text-slate-500">
                Stand where you want to hide it, capture your location, then write your message.
            </p>

            {{-- Location capture --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <button type="button" x-on:click="capture()" x-bind:disabled="busy"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-medium text-white disabled:opacity-50">
                    <span x-show="!busy">📍 Capture my location</span>
                    <span x-show="busy">Locating…</span>
                </button>

                @if ($latitude !== null)
                    <p class="mt-3 text-center text-sm text-emerald-700">
                        Location captured
                        @if ($accuracy) <span class="text-slate-400">(±{{ number_format($accuracy) }} m)</span> @endif
                    </p>
                    @if ($this->accuracyIsPoor)
                        <label class="mt-2 flex items-start gap-2 rounded bg-amber-50 p-2 text-xs text-amber-800">
                            <input type="checkbox" wire:model="accuracyConfirmed" class="mt-0.5">
                            Your GPS accuracy is low, which makes the puzzle hard to solve fairly.
                            Place it here anyway.
                        </label>
                    @endif
                @endif
                <p x-show="geoError" x-text="geoError" class="mt-2 text-center text-sm text-red-600"></p>
                @error('latitude') <p class="mt-2 text-center text-sm text-red-600">{{ $message }}</p> @enderror
                @error('accuracy') <p class="mt-2 text-center text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium text-slate-700">Secret message</label>
                <textarea wire:model="message" rows="4" maxlength="1000"
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-900 focus:outline-none"
                          placeholder="What the finder will see when they reach the spot…"></textarea>
                @error('message') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Optional image --}}
            <div>
                <label class="block text-sm font-medium text-slate-700">Image <span class="text-slate-400">(optional)</span></label>
                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                       class="mt-1 block w-full text-sm">
                <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-400">Uploading…</div>
                @error('photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Hidden coords, set from JS --}}
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-medium text-white disabled:opacity-50">
                Create treasure
            </button>
        </form>

        @script
        <script>
            Alpine.data('locationCapture', () => ({
                busy: false,
                geoError: '',
                capture() {
                    this.geoError = '';
                    if (!navigator.geolocation) {
                        this.geoError = 'Your browser does not support geolocation.';
                        return;
                    }
                    this.busy = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.busy = false;
                            this.$wire.setLocation(
                                pos.coords.latitude,
                                pos.coords.longitude,
                                pos.coords.accuracy,
                            );
                        },
                        (err) => {
                            this.busy = false;
                            this.geoError = err.code === err.PERMISSION_DENIED
                                ? 'Location permission is required to place a treasure.'
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
