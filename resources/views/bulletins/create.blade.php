<x-layouts.app title="Write a bulletin" :back-link="route('bulletins.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Write a bulletin</h1>
            <p class="govuk-body">
                Bulletins are posted on your union's noticeboard for all members. You can later turn it into mail to a
                mailing list, which needs half the union to approve.
            </p>

            <x-privacy-reminder />

            <form method="POST" action="{{ route('bulletins.store') }}" novalidate>
                @csrf
                <x-field name="title" label="Title" required />
                <x-rich-editor name="body" label="Text" />
                <x-button>Post bulletin</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
