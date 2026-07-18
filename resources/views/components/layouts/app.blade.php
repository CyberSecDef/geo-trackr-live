@php
    // Server-side dark class from the JS-set `theme` cookie. Rendering it here
    // (not only via JS) means it survives Livewire wire:navigate morphs, which
    // otherwise reset <html> to the server markup and drop the class.
    $prefersDark = request()->cookie('theme') === 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full{{ $prefersDark ? ' dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Geo.Trackr.Live — Find the Signal' }}</title>

    {{-- Apply the saved theme before paint (no flash). Reads the `theme` cookie,
         falling back to the OS preference when unset, and persists the result so
         server-rendered pages (incl. wire:navigate responses) know the theme.
         Re-applies after Livewire navigations, whose morph can drop the class. --}}
    <script>
        (function () {
            function applyTheme() {
                try {
                    var m = document.cookie.match(/(?:^|; )theme=(dark|light)/);
                    var theme = m ? m[1]
                        : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    if (!m) {
                        document.cookie = 'theme=' + theme + '; path=/; max-age=31536000; samesite=lax';
                    }
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                } catch (e) {}
            }
            applyTheme();
            if (!window.__themeNavBound) {
                window.__themeNavBound = true;
                document.addEventListener('livewire:navigated', applyTheme);
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-full flex flex-col">
        <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <nav class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <a href="{{ route('home') }}" wire:navigate class="font-semibold tracking-tight">Geo.Trackr.Live</a>
                <div class="flex items-center gap-2">
                    {{-- Day / night toggle. Persists the choice to the `theme` cookie. --}}
                    <button type="button" aria-label="Toggle day / night mode"
                            x-data="{ dark: document.documentElement.classList.contains('dark') }"
                            x-on:click="
                                dark = document.documentElement.classList.toggle('dark');
                                document.cookie = 'theme=' + (dark ? 'dark' : 'light') + '; path=/; max-age=31536000; samesite=lax';
                            "
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-base hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                        <span x-cloak x-show="!dark">🌙</span>
                        <span x-cloak x-show="dark">☀️</span>
                    </button>

                    {{-- Hamburger menu holding the page links. --}}
                    <div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
                        <button type="button" @click="open = ! open" :aria-expanded="open"
                                aria-label="Menu" aria-haspopup="true"
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div x-show="open" x-cloak x-transition @click.outside="open = false"
                             class="absolute right-0 z-50 mt-2 w-48 origin-top-right overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            @auth
                                <a href="{{ route('treasures.create') }}" wire:navigate @click="open = false"
                                   class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">Create treasure</a>
                                <a href="{{ route('treasures.index') }}" wire:navigate @click="open = false"
                                   class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">View treasures</a>
                                <a href="{{ route('home') }}" wire:navigate @click="open = false"
                                   class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">Home</a>
                                <div class="my-1 border-t border-slate-200 dark:border-slate-700"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="block w-full px-4 py-2 text-left text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">Sign out</button>
                                </form>
                            @else
                                <a href="{{ route('home') }}" wire:navigate @click="open = false"
                                   class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">Home</a>
                                <a href="{{ route('login') }}" wire:navigate @click="open = false"
                                   class="block px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">Sign in</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        @if (session('error'))
            <div class="mx-auto mt-4 w-full max-w-3xl px-4">
                <div class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 px-4 py-6 text-center dark:border-slate-800">
            <p class="text-xs text-slate-400 dark:text-slate-500">Find the treasure. Distance only — you figure out where.</p>
            <p class="mx-auto mt-2 max-w-xl text-[11px] leading-relaxed text-slate-400/80 dark:text-slate-600">
                Geo.Trackr.Live is an independent game and is not affiliated with, sponsored by, or endorsed by
                Geocaching.com or Groundspeak Inc.
            </p>
        </footer>
    </div>
</body>
</html>
