<x-layouts.app :title="$bulletin->title">
    <h1 class="text-2xl font-semibold">{{ $bulletin->title }}</h1>
    <p class="mb-6 flex flex-wrap items-center gap-x-2 text-sm text-stone-600">
        @if ($bulletin->pinned_at)
            <span class="rounded bg-violet-100 px-1.5 py-0.5 text-xs font-medium text-violet-800">Pinned</span>
        @endif
        @if ($bulletin->isVouching())
            <span>For everyone who has signed up to {{ $bulletin->building->name }}</span>
        @else
            <span>{{ $bulletin->author?->name ?? 'A former member' }} · {{ $bulletin->published_at->format('j M Y, H:i') }}</span>
        @endif
    </p>

    {{-- Cleaned by App\Support\RichText before it was stored. --}}
    <article class="rich-text rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{!! $bulletin->rich_text !!}</article>

    @if ($bulletin->mails->isNotEmpty())
        <section class="mt-6">
            <h2 class="mb-2 text-lg font-semibold">Mail</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($bulletin->mails as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-6">
        <h2 class="text-lg font-semibold">Replies</h2>
        <p class="mb-3 text-sm text-stone-600">
            @if ($bulletin->isVouching())
                Members and neighbours waiting to be vouched for can read and reply here. Nothing here is emailed.
            @else
                Email replies to this bulletin's mail appear here. Members' posts stay inside the union and are never emailed.
            @endif
        </p>

        @if ($bulletin->replies->isNotEmpty())
            <ul class="space-y-3">
                @foreach ($bulletin->replies as $reply)
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
                        {{-- Cleaned by App\Support\RichText before it was stored. --}}
                        <div class="rich-text mt-2 text-stone-800">{!! $reply->rich_text !!}</div>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('bulletins.replies.store', $bulletin) }}" class="mt-4 space-y-3 rounded-lg border border-stone-200 bg-white p-4">
            @csrf
            @if ($isMember)
                <x-rich-editor name="reply" :label="$bulletin->isVouching() ? 'Reply' : 'Reply to members'" compact
                    :hint="$bulletin->isVouching()
                        ? 'Neighbours who haven\'t been vouched for yet can read this too.'
                        : 'Only members of your union can see this. To answer an email, write instant mail or send a bulletin as mail.'" />
            @else
                <x-textarea name="reply" label="Reply" rows="3" maxlength="2000"
                    hint="Text only until you've been vouched for. Everyone who has signed up can read this, so don't share your flat number or phone number." />
            @endif
            <x-button>Post reply</x-button>
        </form>
    </section>

    @if ($canMail)
        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Send as mail</h2>
            <p class="mt-1 text-sm text-stone-600">
                Email this bulletin to one of your mailing lists, signed by the union. Every other member will be asked to approve it,
                and it's sent once {{ $rules->bulletinMailApprovalPercent }}% of the union has approved. It expires after
                {{ intdiv($rules->bulletinMailExpiryHours, 24) }} days.
            </p>

            <p class="mt-2 text-sm text-stone-600">
                You can rework the subject and message first, for example to reflect the discussion in the replies.
                The bulletin itself stays as it is.
            </p>
            <a href="{{ route('bulletins.mail.create', $bulletin) }}" class="mt-4 inline-block rounded-md bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Write the mail</a>
        </section>
    @endif
</x-layouts.app>
