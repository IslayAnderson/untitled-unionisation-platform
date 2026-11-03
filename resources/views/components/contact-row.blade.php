@props(['contact', 'primary' => false])

<li class="flex flex-wrap items-start justify-between gap-3 py-3">
    <div>
        <p class="font-medium">
            {{ $contact->name }}
            @if ($primary)
                <span class="ml-1 rounded bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-700">Primary contact</span>
            @endif
        </p>
        @if ($contact->type)
            <p class="text-sm text-stone-500">{{ $contact->type->type }}</p>
        @endif
        <x-contact-details :email="$contact->email" :telephone="$contact->telephone" :address="$contact->address" />
    </div>
    <a href="{{ route('contacts.edit', $contact) }}" class="text-sm hover:underline">Edit</a>
</li>
