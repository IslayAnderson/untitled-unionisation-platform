<x-layouts.app title="Write instant mail">
    <h1 class="mb-2 text-2xl font-semibold">Write instant mail</h1>
    <p class="mb-6 text-stone-600">
        For when the union needs to act quickly. {{ $rules->instantMailApproverPercent }}% of members are picked at random to approve it,
        and it's sent once {{ $rules->instantMailApprovalPercent }}% of the union has approved. It expires after
        {{ $rules->instantMailExpiryHours }} hours. Once sent, it's also posted as a bulletin.
    </p>

    <x-privacy-reminder class="mb-6" />

    @if ($lists->isEmpty())
        <p class="text-stone-700">
            You need a mailing list first. <a href="{{ route('mailing-lists.create') }}" class="underline">Create one</a>.
        </p>
    @else
        <form method="POST" action="{{ route('mail.store') }}" class="space-y-4">
            @csrf
            <x-select name="mailing_list_id" label="To" :options="$lists->pluck('name', 'id')" :value="$selectedList" placeholder="Choose a mailing list" required />
            <x-field name="subject" label="Subject" required />
            <x-rich-editor name="body" label="Message" hint="It will be signed by the union, not by you." />
            <x-button>Ask for approval</x-button>
        </form>
    @endif
</x-layouts.app>
