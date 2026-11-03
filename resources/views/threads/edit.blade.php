<x-layouts.app title="Edit discussion">
    <h1 class="mb-2 text-2xl font-semibold">Edit discussion</h1>
    <p class="mb-6 text-stone-600">You can change it until someone replies.</p>

    <x-privacy-reminder class="mb-6" />

    <form method="POST" action="{{ route('threads.update', $thread) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <x-field name="title" label="Title" :value="$thread->title" required maxlength="200" />
        <x-rich-editor name="body" label="What do you want to talk about?" :value="$thread->rich_text" />
        <div class="flex items-center gap-4">
            <x-button>Save</x-button>
            <a href="{{ route('threads.show', $thread) }}" class="text-sm text-stone-600 hover:underline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
