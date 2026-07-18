<x-layouts.app title="Privacy Policy — Geo.Trackr.Live">
    <article class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight">Privacy Policy</h1>
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Last updated: July 18, 2026</p>

        <p class="mt-6 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            Geo.Trackr.Live is a small GPS treasure-hunt game. This policy explains what we collect,
            why, and what we do with it. The short version: <strong>we only use your information to run
            the game and sign you in — we never sell it or use it for advertising.</strong>
        </p>

        <h2 class="mt-8 text-lg font-semibold">Signing in with Google</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            You only need an account to <em>create</em> treasures — anyone can hunt with just a code.
            When you sign in, Google shares your <strong>name, email address, and profile picture</strong>
            with us. We use these solely to create and manage your account and to show which treasures
            are yours. We do <strong>not</strong> sell, rent, share, or use your email for marketing — it
            exists only so Google can identify you when you sign back in.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Treasures you create</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            When you place a treasure we store its coordinates, your message, and an optional image so
            the game can work. Uploaded images are re-encoded on our server, which strips all embedded
            metadata — including any GPS location tags — before the image is saved. You can delete any
            treasure you create at any time from <em>My Treasures</em>; deleting it also removes its image
            and unlock history.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Hunting for treasures</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            To tell you how close you are, your browser reads your device's location and sends it to our
            server only to calculate the distance to the treasure. <strong>We do not store your location
            while you hunt.</strong> When a treasure unlocks, we record that it was unlocked and when — not
            where you were. Location access is requested by your browser and you can allow or deny it.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Cookies</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            We use a small number of essential cookies: one to keep you signed in and maintain your
            session, and one to remember your light/dark theme choice. We don't use advertising or
            third-party tracking cookies.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Third-party services</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            <li><strong>Google</strong> handles sign-in. See Google's Privacy Policy for how they process your data.</li>
            <li><strong>OpenStreetMap</strong> provides the map tiles shown to treasure creators; loading the
                map preview sends the map view to their tile servers.</li>
        </ul>

        <h2 class="mt-8 text-lg font-semibold">Abuse prevention</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            To limit spam and abuse of the distance test, we may briefly process a hashed (non-reversible)
            form of your IP address. It isn't used to identify you and isn't retained long-term.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Deleting your data</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            You can delete your treasures yourself at any time. To delete your account and associated data
            entirely, contact us at the address below and we'll remove it.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Changes</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            We may update this policy as the game evolves. Material changes will be reflected by the
            "last updated" date above.
        </p>

        <h2 class="mt-8 text-lg font-semibold">Contact</h2>
        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            Questions about your privacy? Email
            <a href="mailto:privacy@geo.trackr.live" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">privacy@geo.trackr.live</a>.
        </p>

        <p class="mt-10 text-sm">
            <a href="{{ route('home') }}" wire:navigate class="text-slate-500 hover:underline dark:text-slate-400">← Back to Geo.Trackr.Live</a>
        </p>
    </article>
</x-layouts.app>
