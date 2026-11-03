<x-layouts.app>
    <section class="space-y-6">
        <h1 class="text-3xl font-semibold">Organise your building</h1>
        <p class="text-lg text-stone-700">
            Bring the tenants of your apartment block or HMO together, make demands of the building owner,
            and hold landlords and letting agents to account.
        </p>

        <ol class="list-decimal space-y-2 pl-5 text-stone-700">
            <li>Register your building, or join it from a neighbour's link or QR code.</li>
            <li>Once {{ \App\Support\ConstitutionRules::DEFAULTS['min_signups'] }} tenants have signed up, vouch for each other in person by scanning QR codes.</li>
            <li>Tenants vouched for by {{ \App\Support\ConstitutionRules::DEFAULTS['vouches_required'] }} neighbours become union members.</li>
        </ol>

        <div class="flex gap-3">
            <a href="{{ route('register') }}" class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Start a union for your building</a>
            <a href="{{ route('login') }}" class="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">Log in</a>
        </div>

        @if (config('demo.enabled'))
            <p class="text-sm"><a href="{{ route('demo.show') }}" class="underline">Look around a demo union first</a></p>
        @endif

        <p class="text-sm text-stone-500">Has a neighbour already started one? Ask them for your building's join link.</p>
    </section>
</x-layouts.app>
