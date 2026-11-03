<x-layouts.app title="Mailing lists">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Mailing lists</h1>
        <a href="{{ route('mailing-lists.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">New list</a>
    </div>

    <p class="mb-6 text-stone-600">
        Groups of your contacts that the union can email. Mail needs members' approval, is signed by
        {{ $building->name }} and never names the member who wrote it.
    </p>

    @if ($lists->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">
            No mailing lists yet.
        </div>
    @else
        <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
            @foreach ($lists as $list)
                <li>
                    <a href="{{ route('mailing-lists.show', $list) }}" class="flex items-center justify-between px-5 py-4 hover:bg-stone-50">
                        <span>
                            <span class="font-medium">{{ $list->name }}</span>
                            <span class="block text-sm text-stone-500">
                                {{ collect([$list->type?->type, $list->landlord?->name])->filter()->implode(' · ') }}
                            </span>
                        </span>
                        <span class="text-sm text-stone-600">{{ $list->contacts_count }} {{ Str::plural('contact', $list->contacts_count) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <section class="mt-10 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Union inbox</h2>
        @if (App\Support\ReplyAddress::enabled())
            <p class="mt-1 text-sm text-stone-600">
                Replies to your union's emails are added to the mail's bulletin. A full copy of each reply, with any
                attachments, is also emailed to the union inbox, or to every member if you leave it blank.
            </p>
            <p class="mt-2 text-sm text-stone-600">
                If you set one, use a shared inbox the union controls, not a member's personal address.
            </p>
            <p class="mt-3 rounded-md bg-stone-50 px-3 py-2 text-sm text-stone-700">
                @if ($building->contact_email)
                    Copies of replies go to <span class="font-medium">{{ $building->contact_email }}</span>.
                @else
                    No inbox is set, so copies of replies go to all {{ $building->enrolledCount() }} {{ Str::plural('member', $building->enrolledCount()) }}.
                @endif
            </p>
        @else
            <p class="mt-1 text-sm text-stone-600">
                Replies to your union's emails go to this inbox. If it's blank, replies go to the platform's address and
                won't reach anyone. Use a shared inbox the union controls, not a member's personal address.
            </p>
        @endif
        <form method="POST" action="{{ route('mailing-lists.reply-to') }}" class="mt-4 flex max-w-xl items-end gap-3">
            @csrf
            @method('PATCH')
            <div class="flex-1">
                <x-field name="contact_email" type="email" :value="$building->contact_email" placeholder="e.g. examplecourtunion@gmail.com"
                    :label="App\Support\ReplyAddress::enabled() ? 'Union inbox (optional)' : 'Union inbox'" />
            </div>
            <x-button>Save</x-button>
        </form>
    </section>
</x-layouts.app>
