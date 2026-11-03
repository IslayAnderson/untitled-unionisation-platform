<x-layouts.app title="Contacts">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Contacts</h1>
        <div class="flex gap-2">
            <a href="{{ route('landlords.create') }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-100">Add landlord or agent</a>
            <a href="{{ route('contacts.create') }}" class="rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Add contact</a>
        </div>
    </div>

    <p class="mb-6 text-stone-600">
        The landlords, agents and other people {{ $building->name }} deals with. Only members can see this.
    </p>

    @if ($landlords->isEmpty() && $unaffiliated->isEmpty())
        <div class="rounded-lg border border-dashed border-stone-300 p-8 text-center text-stone-600">
            No contacts yet. Start by adding your building owner or managing agent.
        </div>
    @endif

    <div class="space-y-6">
        @foreach ($landlords as $landlord)
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ $landlord->name }}
                            @if ($building->building_owner_id === $landlord->id)
                                <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 align-middle text-xs font-medium text-amber-900">Building owner</span>
                            @endif
                        </h2>
                        <p class="text-sm text-stone-600">
                            {{ collect([$landlord->type?->type, $landlord->company_number ? "Company no. {$landlord->company_number}" : null])->filter()->implode(' · ') }}
                        </p>
                        <x-contact-details :email="$landlord->email" :telephone="$landlord->telephone" :address="$landlord->address" />
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('contacts.create', ['landlord' => $landlord->id]) }}" class="hover:underline">Add person</a>
                        <a href="{{ route('landlords.edit', $landlord) }}" class="hover:underline">Edit</a>
                    </div>
                </div>

                @if ($landlord->contacts->isNotEmpty())
                    <ul class="mt-4 divide-y divide-stone-100 border-t border-stone-100">
                        @foreach ($landlord->contacts as $contact)
                            <x-contact-row :contact="$contact" :primary="$landlord->primary_contact_id === $contact->id" />
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        @if ($unaffiliated->isNotEmpty())
            <section class="rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="text-lg font-semibold">Other contacts</h2>
                <ul class="mt-2 divide-y divide-stone-100">
                    @foreach ($unaffiliated as $contact)
                        <x-contact-row :contact="$contact" />
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
