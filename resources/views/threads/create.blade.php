<x-layouts.app title="Start a discussion">
    <h1 class="mb-2 text-2xl font-semibold">Start a discussion</h1>
    <p class="mb-6 text-stone-600">Only members of your union can read it, and it's never emailed.</p>

    <x-privacy-reminder class="mb-6" />

    <form method="POST" action="{{ route('threads.store') }}" class="space-y-4">
        @csrf
        <x-field name="title" label="Title" required maxlength="200" />
        <x-rich-editor name="body" label="What do you want to talk about?" />
        <x-button>Start discussion</x-button>
    </form>
</x-layouts.app>
