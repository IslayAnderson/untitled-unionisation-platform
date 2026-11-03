<x-layouts.app title="Edit reply">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Edit reply</h1>
            <p class="govuk-body">You can change it until someone replies after it.</p>

            <form method="POST" action="{{ route('replies.update', $reply) }}" novalidate>
                @csrf
                @method('PUT')
                @if ($richText)
                    <x-rich-editor name="reply" label="Reply" :value="$reply->rich_text" compact />
                @else
                    <x-textarea name="reply" label="Reply" rows="4" maxlength="2000" :value="App\Support\RichText::toPlainText($reply->rich_text)"
                        hint="Text only until you've been vouched for." />
                @endif
                <x-button>Save</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
