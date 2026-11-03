<x-layouts.app :title="'Send '.$bulletin->title.' as mail'">
    <h1 class="mb-2 text-2xl font-semibold">Send as mail</h1>
    <p class="mb-6 text-stone-600">
        Starting from <a href="{{ route('bulletins.show', $bulletin) }}" class="underline">{{ $bulletin->title }}</a>.
        Rework it to reflect the discussion below if you like. Every other member will be asked to approve exactly what you
        write here, and it's sent once {{ $rules->bulletinMailApprovalPercent }}% of the union has approved. It expires after
        {{ intdiv($rules->bulletinMailExpiryHours, 24) }} days, and can't be changed once you ask for approval.
    </p>

    <x-privacy-reminder class="mb-6" />

    @if ($lists->isEmpty())
        <p class="text-stone-700">
            You need a mailing list first. <a href="{{ route('mailing-lists.create') }}" class="underline">Create one</a>.
        </p>
    @else
        <form method="POST" action="{{ route('bulletins.mail', $bulletin) }}" class="space-y-4">
            @csrf
            <x-select name="mailing_list_id" label="To" :options="$lists->pluck('name', 'id')" placeholder="Choose a mailing list" required />
            <x-field name="subject" label="Subject" :value="$bulletin->title" required />
            <x-rich-editor name="body" label="Message" :value="$bulletin->rich_text" hint="It will be signed by the union, not by you." />
            <x-button>Ask the union</x-button>
        </form>
    @endif

    <section class="mt-10">
        <h2 class="text-lg font-semibold">Discussion so far</h2>
        @if ($bulletin->replies->isEmpty())
            <p class="mt-1 text-sm text-stone-600">Nobody has replied to the bulletin yet.</p>
        @else
            <ul class="mt-3 space-y-3">
                @foreach ($bulletin->replies as $reply)
                    <li class="rounded-lg border border-stone-200 bg-white p-4">
                        <p class="text-sm">
                            <span class="font-medium">{{ $reply->isEmailReply() ? ($reply->contact?->name ?? $reply->from_name ?? $reply->from_email).' (email)' : ($reply->author?->name ?? 'Someone who has left') }}</span>
                            <span class="text-stone-500">· {{ $reply->published_at->format('j M Y, H:i') }}</span>
                        </p>
                        {{-- Cleaned by App\Support\RichText before it was stored. --}}
                        <div class="rich-text mt-2 text-stone-800">{!! $reply->rich_text !!}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
