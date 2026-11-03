<x-layouts.app title="Edit bulletin" :back-link="route('bulletins.show', $bulletin)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Edit bulletin</h1>
            <p class="govuk-body">You can change it until someone replies, or it gets a poll or mail.</p>

            <x-privacy-reminder />

            <form method="POST" action="{{ route('bulletins.update', $bulletin) }}" novalidate>
                @csrf
                @method('PUT')
                <x-field name="title" label="Title" :value="$bulletin->title" required maxlength="200" />
                <x-rich-editor name="body" label="Text" :value="$bulletin->rich_text" />
                <div class="govuk-button-group">
                    <x-button>Save</x-button>
                    <a href="{{ route('bulletins.show', $bulletin) }}" class="govuk-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
