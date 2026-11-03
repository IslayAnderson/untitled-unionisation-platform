@props(['reply'])

<li id="reply-{{ $reply->id }}" @class(['app-reply', 'app-reply--email' => $reply->isEmailReply()])>
    <p class="app-reply__header">
        @if ($reply->isEmailReply())
            <strong class="govuk-tag">Email reply</strong>
            <span class="app-reply__author">{{ $reply->contact?->name ?? $reply->from_name ?? $reply->from_email }}</span>
            <span>{{ $reply->from_email }}</span>
            @unless ($reply->contact)
                <strong class="govuk-tag govuk-tag--yellow">Not on the mailing list</strong>
            @endunless
        @elseif ($reply->author?->isEnrolled())
            <strong class="govuk-tag govuk-tag--grey">Member</strong>
            <span class="app-reply__author">{{ $reply->author->name }}</span>
        @elseif ($reply->author)
            <strong class="govuk-tag govuk-tag--yellow">Not yet vouched for</strong>
            <span class="app-reply__author">{{ $reply->author->name }}</span>
        @else
            <span class="app-reply__author">Someone who has left</span>
        @endif
        <span>{{ $reply->published_at->format('j F Y, g:ia') }}</span>
    </p>
    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <div class="app-rich-text">{!! $reply->rich_text !!}</div>
    @if (($viewer = auth()->user()?->contact) && $reply->canBeChangedBy($viewer))
        <div class="app-reply__actions">
            <a href="{{ route('replies.edit', $reply) }}" class="govuk-link">Change<span class="govuk-visually-hidden"> your reply</span></a>
            <x-delete-button :action="route('replies.destroy', $reply)" confirm="Delete your reply?">Delete<span class="govuk-visually-hidden"> your reply</span></x-delete-button>
        </div>
    @endif
</li>
