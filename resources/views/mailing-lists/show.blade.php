<x-layouts.app :title="$list->name">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ $list->name }}</h1>
        <a href="{{ route('mailing-lists.edit', $list) }}" class="text-sm hover:underline">Edit list</a>
    </div>
    <p class="mb-6 text-sm text-stone-600">
        {{ collect([$list->type?->type, $list->landlord?->name])->filter()->implode(' · ') }}
    </p>

    <section class="rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">On this list</h2>
        @if ($list->contacts->isEmpty())
            <p class="mt-1 text-sm text-stone-600">Nobody yet. <a href="{{ route('mailing-lists.edit', $list) }}" class="underline">Add contacts</a>.</p>
        @else
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($list->contacts as $contact)
                    <li>
                        {{ $contact->name }}
                        <span class="text-stone-500">{{ $contact->email ?? '(no email, won\'t receive messages)' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-lg font-semibold">Send mail to this list</h2>
        <p class="mt-1 text-sm text-stone-600">
            Mail to this list needs members' approval. Write <a href="{{ route('mail.create', ['list' => $list->id]) }}" class="underline">instant mail</a>
            for something urgent, or <a href="{{ route('bulletins.create') }}" class="underline">write a bulletin</a> and send it as bulletin mail.
        </p>
    </section>

    <section class="mt-6">
        <h2 class="mb-2 text-lg font-semibold">Mail to this list</h2>
        @if ($mails->isEmpty())
            <p class="text-sm text-stone-600">Nothing yet.</p>
        @else
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($mails as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
