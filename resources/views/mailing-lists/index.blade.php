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
        <p class="mt-1 text-sm text-stone-600">
            @if (App\Support\ReplyAddress::enabled())
                Replies to your union's emails appear on the mail's bulletin, and a copy (with any attachments) is sent here.
            @else
                Replies to your union's emails go here. If it's blank, replies go to the platform's address and won't reach you.
            @endif
            Use a shared inbox the union controls, not a member's personal address.
        </p>
        <form method="POST" action="{{ route('mailing-lists.reply-to') }}" class="mt-4 flex max-w-xl items-end gap-3">
            @csrf
            @method('PATCH')
            <div class="flex-1">
                <x-field name="contact_email" label="Union email" type="email" :value="$building->contact_email" placeholder="e.g. examplecourtunion@gmail.com" />
            </div>
            <x-button>Save</x-button>
        </form>
    </section>
</x-layouts.app>
