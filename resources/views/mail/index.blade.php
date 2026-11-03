<x-layouts.app title="Mail">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Mail</h1>
        <div class="flex gap-2">
            <a href="{{ route('bulletins.create') }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-100">Write a bulletin</a>
            <a href="{{ route('mail.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Write instant mail</a>
        </div>
    </div>

    <p class="mb-8 text-stone-600">
        Emails to your landlord, agents and other contacts need members' approval before they're sent.
        <strong>Instant mail</strong> is approved by a few members picked at random.
        <strong>Bulletin mail</strong> starts as a bulletin and needs the approval of half the union.
    </p>

    @if ($awaitingMe->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-2 text-lg font-semibold">Waiting for your approval</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border-2 border-sky-300 bg-white">
                @foreach ($awaitingMe as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mb-8">
        <h2 class="mb-2 text-lg font-semibold">Waiting for approval</h2>
        @if ($pending->isEmpty())
            <p class="text-sm text-stone-600">Nothing waiting.</p>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($pending as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        @endif
    </section>

    <section>
        <h2 class="mb-2 text-lg font-semibold">History</h2>
        @if ($decided->isEmpty())
            <p class="text-sm text-stone-600">No mail yet.</p>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($decided as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
