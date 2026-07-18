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
                <a href="{{ route('home') }}" class="font-semibold tracking-tight">🧭 Geo.Trackr.Live</a>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('treasures.index') }}" class="hover:underline">My Treasures</a>
                        <a href="{{ route('treasures.create') }}" class="rounded bg-slate-900 px-3 py-1.5 text-white dark:bg-slate-100 dark:text-slate-900">Create</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:underline dark:text-slate-400">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:underline">Sign in</a>
                    @endauth

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
