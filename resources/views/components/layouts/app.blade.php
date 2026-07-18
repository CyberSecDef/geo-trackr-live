<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'GpsPuzzle — Reverse Geocache' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-full flex flex-col">
        <header class="border-b border-slate-200 bg-white">
            <nav class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
                <a href="{{ route('home') }}" class="font-semibold tracking-tight">🧭 GpsPuzzle</a>
                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('treasures.index') }}" class="hover:underline">My Treasures</a>
                        <a href="{{ route('treasures.create') }}" class="rounded bg-slate-900 px-3 py-1.5 text-white">Create</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:underline">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:underline">Sign in</a>
                    @endauth
                </div>
            </nav>
        </header>

        @if (session('error'))
            <div class="mx-auto mt-4 w-full max-w-3xl px-4">
                <div class="rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-400">
            Find the treasure. Distance only — you figure out where.
        </footer>
    </div>
</body>
</html>
