<x-layouts.app title="Start a discussion" :back-link="route('threads.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Start a discussion</h1>
            <p class="govuk-body">Only members of your union can read it, and it's never emailed.</p>

            <x-privacy-reminder />

            <form method="POST" action="{{ route('threads.store') }}" novalidate>
                @csrf
                <x-field name="title" label="Title" required maxlength="200" />
                <x-rich-editor name="body" label="What do you want to talk about?" />
                <x-button>Start discussion</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
