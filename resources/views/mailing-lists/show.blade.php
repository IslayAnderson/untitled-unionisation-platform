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
        <h2 class="text-lg font-semibold">Send a message</h2>
        <p class="mt-1 text-sm text-stone-600">
            Each contact gets their own copy from <strong>{{ $list->building->name }}</strong>. Your name isn't included.
        </p>
        <form method="POST" action="{{ route('mailing-lists.messages.store', $list) }}" class="mt-4 space-y-4">
            @csrf
            <x-field name="subject" label="Subject" required />
            <x-textarea name="body" label="Message" rows="8" required :hint="'Plain text. It will be signed “'.$list->building->name.'”.'" />
            <x-button>Send</x-button>
        </form>
    </section>

    <section class="mt-6">
        <h2 class="mb-2 text-lg font-semibold">Sent</h2>
        @forelse ($messages as $message)
            <details class="mb-2 rounded-lg border border-stone-200 bg-white px-5 py-3">
                <summary class="cursor-pointer">
                    <span class="font-medium">{{ $message->subject }}</span>
                    <span class="text-sm text-stone-500">
                        · {{ $message->sent_at->format('j M Y, H:i') }} · {{ $message->recipient_count }} {{ Str::plural('recipient', $message->recipient_count) }}
                        · sent by {{ $message->sender?->name ?? 'a former member' }}
                    </span>
                </summary>
                <p class="mt-3 whitespace-pre-line text-sm text-stone-700">{{ $message->body }}</p>
            </details>
        @empty
            <p class="text-sm text-stone-600">Nothing sent yet.</p>
        @endforelse
    </section>
</x-layouts.app>
