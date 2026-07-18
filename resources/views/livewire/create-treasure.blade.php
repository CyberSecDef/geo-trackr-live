<div>
    @if ($createdCode)
        {{-- ------- Success: show code + share link ------- --}}
        <div class="mx-auto max-w-md rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center dark:border-emerald-900 dark:bg-emerald-950">
            <div class="text-3xl">✅</div>
            <h1 class="mt-2 text-lg font-semibold text-emerald-900 dark:text-emerald-200">Treasure created!</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Share this code — anyone can hunt for it.</p>
            <div class="mt-4 rounded-lg bg-white px-4 py-3 text-2xl font-bold tracking-[0.3em] dark:bg-slate-900">{{ $createdCode }}</div>
            <div x-data="{
                    copied: false,
                    copyLink() {
                        const url = this.$refs.shareLink.value;
                        const done = () => {
                            this.copied = true;
                            clearTimeout(this._t);
                            this._t = setTimeout(() => this.copied = false, 2000);
                        };
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(url).then(done).catch(() => {
                                this.$refs.shareLink.select();
                                document.execCommand('copy');
                                done();
                            });
                        } else {
                            this.$refs.shareLink.select();
                            document.execCommand('copy');
                            done();
                        }
                    }
                 }" class="mt-3 flex items-center gap-2">
                <input x-ref="shareLink" readonly value="{{ route('treasure.test', $createdCode) }}"
                       class="w-full rounded border border-slate-200 bg-white px-3 py-2 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400"
                       x-on:click="$refs.shareLink.select()">
                <button type="button" x-on:click="copyLink()" aria-label="Copy share link"
                        class="flex shrink-0 items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <span x-show="!copied">📋 Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400">✓ Copied!</span>
                </button>
            </div>
            <div class="mt-4 flex justify-center gap-3 text-sm">
                <a href="{{ route('treasures.create') }}" wire:navigate class="rounded bg-slate-900 px-4 py-2 text-white dark:bg-slate-100 dark:text-slate-900">Create another</a>
                <a href="{{ route('treasures.index') }}" wire:navigate class="rounded border border-slate-300 px-4 py-2 dark:border-slate-700">My treasures</a>
            </div>
        </div>
    @else
        <form wire:submit="save" x-data="locationCapture()" class="mx-auto max-w-md space-y-6">
            <h1 class="text-2xl font-semibold tracking-tight">Create a treasure</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Stand where you want to hide it, capture your location, then write your message.
            </p>

            {{-- Location capture --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <button type="button" x-on:click="capture()" x-bind:disabled="busy"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-medium text-white disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900">
                    <span x-show="!busy">📍 Capture my location</span>
                    <span x-show="busy">Locating…</span>
                </button>

                @if ($latitude !== null)
                    <p class="mt-3 text-center text-sm text-emerald-700 dark:text-emerald-400">
                        Location captured
                        @if ($accuracy) <span class="text-slate-400 dark:text-slate-500">(±{{ number_format($accuracy) }} m)</span> @endif
                    </p>
                    @if ($this->accuracyIsPoor)
                        <label class="mt-2 flex items-start gap-2 rounded bg-amber-50 p-2 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            <input type="checkbox" wire:model="accuracyConfirmed" class="mt-0.5">
                            Your GPS accuracy is low, which makes the puzzle hard to solve fairly.
                            Place it here anyway.
                        </label>
                    @endif
                @endif
                <p x-show="geoError" x-text="geoError" class="mt-2 text-center text-sm text-red-600 dark:text-red-400"></p>
                @error('latitude') <p class="mt-2 text-center text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @error('accuracy') <p class="mt-2 text-center text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Secret message</label>
                <textarea wire:model="message" rows="4" maxlength="1000"
                          class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-slate-900 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:focus:border-slate-100"
                          placeholder="What the finder will see when they reach the spot…"></textarea>
                @error('message') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Optional image --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Image <span class="text-slate-400 dark:text-slate-500">(optional)</span></label>
                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                       class="mt-1 block w-full text-sm text-slate-500 dark:text-slate-400
                              file:mr-3 file:rounded file:border-0 file:bg-slate-900 file:px-3 file:py-1.5
                              file:text-sm file:font-medium file:text-white hover:file:bg-slate-700
                              dark:file:bg-slate-100 dark:file:text-slate-900 dark:hover:file:bg-white">
                <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-400 dark:text-slate-500">Uploading…</div>
                @error('photo') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Disable submit while the image is still uploading, otherwise a
                 quick tap can create the treasure before the photo is attached
                 (the upload lands orphaned and the treasure has no image). --}}
            <button type="submit" wire:loading.attr="disabled" wire:target="photo, save"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-medium text-white disabled:opacity-50 dark:bg-emerald-500">
                <span wire:loading.remove wire:target="photo">Create treasure</span>
                <span wire:loading wire:target="photo">Waiting for image to finish uploading…</span>
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
