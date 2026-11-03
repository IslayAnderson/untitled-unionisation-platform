<x-layouts.app title="Edit discussion" :back-link="route('threads.show', $thread)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Edit discussion</h1>
            <p class="govuk-body">You can change it until someone replies.</p>

            <x-privacy-reminder />

            <form method="POST" action="{{ route('threads.update', $thread) }}" novalidate>
                @csrf
                @method('PUT')
                <x-field name="title" label="Title" :value="$thread->title" required maxlength="200" />
                <x-rich-editor name="body" label="What do you want to talk about?" :value="$thread->rich_text" />
                <div class="govuk-button-group">
                    <x-button>Save</x-button>
                    <a href="{{ route('threads.show', $thread) }}" class="govuk-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
