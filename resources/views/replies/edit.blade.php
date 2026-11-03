<x-layouts.app title="Edit reply">
    <h1 class="mb-2 text-2xl font-semibold">Edit reply</h1>
    <p class="mb-6 text-stone-600">You can change it until someone replies after it.</p>

    <form method="POST" action="{{ route('replies.update', $reply) }}" class="space-y-4">
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
</x-layouts.app>
