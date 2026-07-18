<x-layouts.app :title="'Sign in — GpsPuzzle'">
    <div class="mx-auto max-w-sm">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <div class="text-3xl">🧭</div>
            <h1 class="mt-2 text-xl font-semibold tracking-tight">Sign in to GpsPuzzle</h1>
            <p class="mt-2 text-sm text-slate-500">
                You only need an account to <strong>create</strong> treasures.
                Anyone can hunt with just a code.
            </p>

            <div class="mt-6 space-y-3">
                <a href="{{ route('auth.redirect', 'google') }}"
                   class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 px-4 py-2.5 font-medium hover:bg-slate-50">
                    <span class="text-lg">G</span> Continue with Google
                </a>
                <a href="{{ route('auth.redirect', 'microsoft') }}"
                   class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 px-4 py-2.5 font-medium hover:bg-slate-50">
                    <span class="text-lg">⊞</span> Continue with Microsoft
                </a>
                <a href="{{ route('auth.redirect', 'facebook') }}"
                   class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 px-4 py-2.5 font-medium hover:bg-slate-50">
                    <span class="text-lg">f</span> Continue with Facebook
                </a>
            </div>
        </div>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('home') }}" wire:navigate class="text-slate-500 hover:underline">← Just here to find a treasure?</a>
        </p>
    </div>
</x-layouts.app>
