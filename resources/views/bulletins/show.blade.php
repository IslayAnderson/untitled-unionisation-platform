<x-layouts.app :title="$bulletin->title" :back-link="route('bulletins.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-m">
                @if ($bulletin->isVouching())
                    For everyone who has signed up to {{ $bulletin->building->name }}
                @else
                    {{ $bulletin->author?->name ?? 'A former member' }} · {{ $bulletin->published_at->format('j M Y, H:i') }}
                @endif
            </span>
            <h1 class="govuk-heading-l">
                {{ $bulletin->title }}
                @if ($bulletin->pinned_at)
                    <strong class="govuk-tag govuk-tag--purple">Pinned</strong>
                @endif
            </h1>

            {{-- Cleaned by App\Support\RichText before it was stored. --}}
            <article class="app-rich-text">{!! $bulletin->rich_text !!}</article>
            @if ($canChange)
                <p class="govuk-body-s">
                    <a href="{{ route('bulletins.edit', $bulletin) }}" class="govuk-link">Edit<span class="govuk-visually-hidden"> bulletin</span></a>
                    ·
                    <x-delete-button :action="route('bulletins.destroy', $bulletin)" confirm="Delete this bulletin? This can't be undone." />
                    <span class="govuk-hint govuk-!-display-inline govuk-!-font-size-16">(until someone replies)</span>
                </p>
            @endif

            <hr class="govuk-section-break govuk-section-break--m govuk-section-break--visible">

            @foreach ($bulletin->polls as $poll)
                <x-poll :poll="$poll" :has-voted="in_array($poll->id, $votedPollIds, true)" :is-author="$isAuthor" :member-count="$memberCount" />
            @endforeach

            @if ($isAuthor && ! $bulletin->isVouching())
                <p class="govuk-body"><a href="{{ route('bulletins.polls.create', $bulletin) }}" class="govuk-link">Add a poll</a></p>
            @endif

            @if ($bulletin->mails->isNotEmpty())
                <h2 class="govuk-heading-m">Mail</h2>
                <ul class="app-item-list">
                    @foreach ($bulletin->mails as $mail)
                        <x-mail-row :mail="$mail" />
                    @endforeach
                </ul>
            @endif

            @if ($canMail)
                <h2 class="govuk-heading-m">Send as mail</h2>
                <p class="govuk-body">
                    Email this bulletin to one of your mailing lists, signed by the union. Every other member will be asked to approve it,
                    and it's sent once {{ $rules->bulletinMailApprovalPercent }}% of the union has approved. It expires after
                    {{ intdiv($rules->bulletinMailExpiryHours, 24) }} days.
                </p>
                <p class="govuk-body">
                    You can rework the subject and message first, for example to reflect the discussion in the replies.
                    The bulletin itself stays as it is.
                </p>
                <a href="{{ route('bulletins.mail.create', $bulletin) }}" role="button" draggable="false" class="govuk-button govuk-button--secondary" data-module="govuk-button">Write the mail</a>
            @endif

            <h2 class="govuk-heading-m">Replies</h2>
            <p class="govuk-body">
                @if ($bulletin->isVouching())
                    Members and neighbours waiting to be vouched for can read and reply here. Nothing here is emailed.
                @else
                    Email replies to this bulletin's mail appear here. Members' posts stay inside the union and are never emailed.
                @endif
            </p>

            @if ($bulletin->replies->isNotEmpty())
                <ul class="app-reply-list">
                    @foreach ($bulletin->replies as $reply)
                        <x-reply :reply="$reply" />
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('bulletins.replies.store', $bulletin) }}" novalidate>
                @csrf
                @if ($isMember)
                    <x-rich-editor name="reply" :label="$bulletin->isVouching() ? 'Reply' : 'Reply to members'" label-class="govuk-label--s" compact
                        :hint="$bulletin->isVouching()
                            ? 'Neighbours who haven\'t been vouched for yet can read this too.'
                            : 'Only members of your union can see this. To answer an email, write instant mail or send a bulletin as mail.'" />
                @else
                    <x-textarea name="reply" label="Reply" rows="3" maxlength="2000"
                        hint="Text only until you've been vouched for. Everyone who has signed up can read this, so don't share your flat number or phone number." />
                @endif
                <x-button>Post reply</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
