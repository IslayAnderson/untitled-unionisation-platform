@props(['reply'])

<li id="reply-{{ $reply->id }}" @class([
    'rounded-lg border bg-white p-4',
    'border-sky-200' => $reply->isEmailReply(),
    'border-stone-200' => ! $reply->isEmailReply(),
])>
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        @if ($reply->isEmailReply())
            <span class="rounded bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-800">Email reply</span>
            <span class="font-medium">{{ $reply->contact?->name ?? $reply->from_name ?? $reply->from_email }}</span>
            <span class="text-stone-500">{{ $reply->from_email }}</span>
            @unless ($reply->contact)
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800" title="The sender's address isn't on the mailing list this was sent to.">Not on the mailing list</span>
            @endunless
        @elseif ($reply->author?->isEnrolled())
            <span class="rounded bg-stone-100 px-1.5 py-0.5 text-xs font-medium text-stone-700">Member</span>
            <span class="font-medium">{{ $reply->author->name }}</span>
        @elseif ($reply->author)
            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800" title="Hasn't been vouched for by members yet, so may not live here.">Not yet vouched for</span>
            <span class="font-medium">{{ $reply->author->name }}</span>
        @else
            <span class="font-medium">Someone who has left</span>
        @endif
        <span class="text-stone-500">· {{ $reply->published_at->format('j M Y, H:i') }}</span>
    </p>
    @if (($viewer = auth()->user()?->contact) && $reply->canBeChangedBy($viewer))
        <div class="float-right flex items-center gap-3 text-sm">
            <a href="{{ route('replies.edit', $reply) }}" class="text-stone-600 hover:underline">Edit</a>
            <x-delete-button :action="route('replies.destroy', $reply)" confirm="Delete your reply?" />
        </div>
    @endif
    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <div class="rich-text mt-2 text-stone-800">{!! $reply->rich_text !!}</div>
</li>
