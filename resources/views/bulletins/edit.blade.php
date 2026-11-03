<x-layouts.app title="Edit bulletin">
    <h1 class="mb-2 text-2xl font-semibold">Edit bulletin</h1>
    <p class="mb-6 text-stone-600">You can change it until someone replies, or it gets a poll or mail.</p>

    <x-privacy-reminder class="mb-6" />

    <form method="POST" action="{{ route('bulletins.update', $bulletin) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <x-field name="title" label="Title" :value="$bulletin->title" required maxlength="200" />
        <x-rich-editor name="body" label="Text" :value="$bulletin->rich_text" />
        <div class="flex items-center gap-4">
            <x-button>Save</x-button>
            <a href="{{ route('bulletins.show', $bulletin) }}" class="text-sm text-stone-600 hover:underline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
