<x-layouts.app title="Write a bulletin">
    <h1 class="mb-2 text-2xl font-semibold">Write a bulletin</h1>
    <p class="mb-6 text-stone-600">
        Bulletins are posted on your union's noticeboard for all members. You can later turn it into mail to a
        mailing list, which needs half the union to approve.
    </p>

    <x-privacy-reminder class="mb-6" />

    <form method="POST" action="{{ route('bulletins.store') }}" class="space-y-4">
        @csrf
        <x-field name="title" label="Title" required />
        <x-textarea name="body" label="Text" rows="10" required hint="Plain text." />
        <x-button>Post bulletin</x-button>
    </form>
</x-layouts.app>
