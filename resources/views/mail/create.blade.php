<x-layouts.app title="Write instant mail" :back-link="route('mail.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Write instant mail</h1>
            <p class="govuk-body">For when the union needs to act quickly.</p>
            <div class="govuk-inset-text">
                {{ $rules->instantMailApproverPercent }}% of members are picked at random to approve it,
                and it's sent once {{ $rules->instantMailApprovalPercent }}% of the union has approved. It expires after
                {{ $rules->instantMailExpiryHours }} hours. Once sent, it's also posted as a bulletin.
            </div>

            <x-privacy-reminder />

            @if ($lists->isEmpty())
                <p class="govuk-body">
                    You need a mailing list first. <a href="{{ route('mailing-lists.create') }}" class="govuk-link">Create a mailing list</a>.
                </p>
            @else
                <form method="POST" action="{{ route('mail.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <x-select name="mailing_list_id" label="To" :options="$lists->pluck('name', 'id')" :value="$selectedList" placeholder="Choose a mailing list" required />
                    <x-field name="subject" label="Subject" required />
                    <x-rich-editor name="body" label="Message" hint="It will be signed by the union, not by you." />
                    <x-attachments-field />
                    <x-button>Ask for approval</x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
