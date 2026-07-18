<div>
    @php
        // Reusable faded wireframe globe (meridians + parallels).
        $globe = <<<'SVG'
        <svg class="game-globe" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="0.7" aria-hidden="true">
            <circle cx="100" cy="100" r="92"/>
            <ellipse cx="100" cy="100" rx="92" ry="30"/>
            <ellipse cx="100" cy="100" rx="92" ry="60"/>
            <ellipse cx="100" cy="100" rx="92" ry="86"/>
            <line x1="8" y1="100" x2="192" y2="100"/>
            <ellipse cx="100" cy="100" rx="30" ry="92"/>
            <ellipse cx="100" cy="100" rx="60" ry="92"/>
            <line x1="100" y1="8" x2="100" y2="192"/>
        </svg>
        SVG;
    @endphp

    @if ($unlocked)
        {{-- ------- Reveal: victory ------- --}}
        <section class="game-hero px-6 py-12 text-center">
            <div class="game-stars"></div>
            <span class="shooting-star" style="top:8%;left:66%;animation-delay:0s"></span>
            <span class="shooting-star" style="top:2%;left:40%;animation-delay:3s;width:100px"></span>
            {!! $globe !!}

            <div class="relative z-10 mx-auto max-w-md">
                <div class="text-6xl drop-shadow-[0_0_25px_rgba(250,204,21,0.5)]">🏆</div>
                <p class="game-kicker mt-4 text-[11px] font-semibold uppercase">Treasure secured</p>
                <h1 class="game-title mt-1 text-4xl font-extrabold tracking-tight sm:text-5xl">You found it!</h1>

                @if ($revealHasImage)
                    <img src="{{ route('treasure.image', $treasureId) }}" alt="Treasure image"
                         class="mx-auto mt-6 max-h-96 rounded-xl border border-white/15 object-contain shadow-2xl">
                @endif

                <div class="mt-6 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <p class="whitespace-pre-line text-indigo-50">{{ $revealMessage }}</p>
                </div>
            </div>
        </section>
    @else
        {{-- ------- Check-code / hunt screen ------- --}}
        <section x-data="geocacheTester()" class="game-hero px-6 py-12 sm:py-16">
            <div class="game-stars"></div>
            <span class="shooting-star" style="top:6%;left:70%;animation-delay:0s"></span>
            <span class="shooting-star" style="top:0%;left:44%;animation-delay:2.6s"></span>
            <span class="shooting-star" style="top:20%;left:88%;animation-delay:4.4s;width:100px"></span>
            {!! $globe !!}

            <div class="relative z-10 mx-auto max-w-sm text-center">
                <p class="game-kicker text-[11px] font-semibold uppercase">◆ GPS Treasure Hunt ◆</p>
                <h1 class="game-title mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Find the Signal</h1>
                <p class="mx-auto mt-3 max-w-xs text-sm leading-relaxed text-indigo-100/80">
                    Got a code? Test it. We only reveal your <span class="text-amber-200">distance</span> —
                    roam, retest, and close in until it unlocks within ~10&nbsp;feet.
                </p>

                <div class="mt-8 space-y-3 text-left">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-indigo-200">Enter treasure code</label>
                    <input type="text" wire:model="code" maxlength="8" autocapitalize="characters"
                           placeholder="ABCD2345"
                           class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3.5 text-center text-xl font-bold uppercase tracking-[0.4em] text-white placeholder-white/30 backdrop-blur focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-400/40">
                    @error('code') <p class="text-sm text-rose-300">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-between pt-1 text-xs text-indigo-200/80">
                        <span>Units</span>
                        <div class="inline-flex overflow-hidden rounded-lg border border-white/20">
                            <button type="button" wire:click="$set('unit','imperial')"
                                    @class(['px-3 py-1 transition', 'bg-white/20 text-white' => $unit==='imperial', 'text-indigo-200/70' => $unit!=='imperial'])>ft / mi</button>
                            <button type="button" wire:click="$set('unit','metric')"
                                    @class(['px-3 py-1 transition', 'bg-white/20 text-white' => $unit==='metric', 'text-indigo-200/70' => $unit!=='metric'])>m / km</button>
                        </div>
                    </div>

                    <button type="button" x-on:click="runTest()" x-bind:disabled="busy"
                            class="group relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 px-4 py-4 text-base font-bold text-white shadow-[0_10px_30px_-8px_rgba(139,92,246,0.7)] transition hover:brightness-110 disabled:opacity-60">
                        <span x-show="!busy" class="flex items-center justify-center gap-2">
                            <span>📡</span> Test my distance
                        </span>
                        <span x-show="busy" class="flex items-center justify-center gap-2">
                            <span class="animate-pulse">🛰️</span> Locating…
                        </span>
                    </button>

                    <p x-show="geoError" x-text="geoError" class="text-center text-sm text-rose-300"></p>

                    {{-- Shown when the browser has location hard-blocked for this
                         site: JS can't re-prompt, so guide the user to re-enable it.
                         Auto-continues once they do (permission-change listener). --}}
                    <div x-show="denied" x-cloak
                         class="rounded-2xl border border-rose-300/40 bg-rose-500/10 p-4 text-left text-sm text-rose-100 backdrop-blur">
                        <p class="font-semibold">📍 Location is blocked for this site</p>
                        <p class="mt-1 text-rose-100/80">Your browser is remembering an earlier “Block”. Re-enable location, then it'll continue automatically:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-rose-100/80">
                            <li><span class="font-semibold">iPhone / Safari:</span> tap <span class="font-semibold">aA</span> at the left of the address bar → <span class="font-semibold">Website Settings</span> → Location → <span class="font-semibold">Allow</span>.</li>
                            <li><span class="font-semibold">Android / Chrome:</span> tap the <span class="font-semibold">🔒</span> (or ⓘ) left of the address → <span class="font-semibold">Permissions</span> → Location → <span class="font-semibold">Allow</span>.</li>
                            <li>Make sure your phone's system Location is also on for the browser.</li>
                        </ul>
                        <button type="button" x-on:click="runTest()"
                                class="mt-3 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/25">
                            I've allowed it — try again
                        </button>
                    </div>
                </div>

                {{-- Result --}}
                <div class="mt-6">
                    @if ($notFound)
                        <p class="text-sm text-rose-300">No treasure found for that code.</p>
                    @elseif ($paused)
                        <p class="text-sm text-amber-300">This treasure is resting — not available right now.</p>
                    @elseif ($result)
                        <div class="mx-auto rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                            <div class="text-xl font-bold text-amber-200 drop-shadow-[0_0_12px_rgba(251,191,36,0.35)]">{{ $result }}</div>
                            @if ($accuracyNote)
                                <div class="mt-1 text-xs text-indigo-200/70">{{ $accuracyNote }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @script
            <script>
                Alpine.data('geocacheTester', () => ({
                    busy: false,
                    geoError: '',
                    denied: false,       // browser has location hard-blocked for this site
                    watchingPerm: false, // avoid stacking permission-change listeners
                    async runTest() {
                        this.geoError = '';
                        this.denied = false;
                        if (!navigator.geolocation) {
                            this.geoError = 'Your browser does not support geolocation.';
                            return;
                        }
                        // If the Permissions API is available, check up front. A hard
                        // "denied" can't be re-prompted from JS, so we show guidance
                        // and re-run automatically if the user flips it to allow.
                        if (navigator.permissions?.query) {
                            try {
                                const status = await navigator.permissions.query({ name: 'geolocation' });
                                if (status.state === 'denied') {
                                    this.denied = true;
                                    if (!this.watchingPerm) {
                                        this.watchingPerm = true;
                                        status.onchange = () => {
                                            if (status.state !== 'denied') { this.denied = false; this.acquire(); }
                                        };
                                    }
                                    return;
                                }
                            } catch (e) { /* older Safari: no geolocation permission name — fall through */ }
                        }
                        // 'granted' or 'prompt' (or unknown): request a fix. In the
                        // 'prompt' state this is what surfaces the allow/deny dialog.
                        this.acquire();
                    },
                    acquire() {
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
                                if (err.code === err.PERMISSION_DENIED) {
                                    // Denied at (or after) the prompt — show re-enable help.
                                    this.denied = true;
                                } else if (err.code === err.TIMEOUT) {
                                    this.geoError = 'Timed out getting your location. Make sure GPS is on and try again.';
                                } else {
                                    this.geoError = 'Could not get your location. Check that location services are on, then try again.';
                                }
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
                        );
                    },
                }));
            </script>
            @endscript
        </section>
    @endif
</div>
