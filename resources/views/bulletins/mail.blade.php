<x-layouts.app :title="'Send '.$bulletin->title.' as mail'" :back-link="route('bulletins.show', $bulletin)">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">{{ $bulletin->title }}</span>
            <h1 class="govuk-heading-l">Send as mail</h1>
            <p class="govuk-body">
                Starting from <a href="{{ route('bulletins.show', $bulletin) }}" class="govuk-link">{{ $bulletin->title }}</a>.
                Rework it to reflect the discussion below if you like.
            </p>
            <div class="govuk-inset-text">
                Every other member will be asked to approve exactly what you write here, and it's sent once
                {{ $rules->bulletinMailApprovalPercent }}% of the union has approved. It expires after
                {{ intdiv($rules->bulletinMailExpiryHours, 24) }} days, and can't be changed once you ask for approval.
            </div>

            <x-privacy-reminder />

            @if ($lists->isEmpty())
                <p class="govuk-body">
                    You need a mailing list first. <a href="{{ route('mailing-lists.create') }}" class="govuk-link">Create a mailing list</a>.
                </p>
            @else
                <form method="POST" action="{{ route('bulletins.mail', $bulletin) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <x-select name="mailing_list_id" label="To" :options="$lists->pluck('name', 'id')" placeholder="Choose a mailing list" required />
                    <x-field name="subject" label="Subject" :value="$bulletin->title" required />
                    <x-rich-editor name="body" label="Message" :value="$bulletin->rich_text" hint="It will be signed by the union, not by you." />
                    <x-attachments-field />
                    <x-button>Ask the union</x-button>
                </form>
            @endif

            <h2 class="govuk-heading-m govuk-!-margin-top-6">Discussion so far</h2>
            @if ($bulletin->replies->isEmpty())
                <p class="govuk-body">Nobody has replied to the bulletin yet.</p>
            @else
                <ul class="app-reply-list">
                    @foreach ($bulletin->replies as $reply)
                        <li @class(['app-reply', 'app-reply--email' => $reply->isEmailReply()])>
                            <p class="app-reply__header">
                                <span class="app-reply__author">{{ $reply->isEmailReply() ? ($reply->contact?->name ?? $reply->from_name ?? $reply->from_email).' (email)' : ($reply->author?->name ?? 'Someone who has left') }}</span>
                                <span>{{ $reply->published_at->format('j M Y, H:i') }}</span>
                            </p>
                            {{-- Cleaned by App\Support\RichText before it was stored. --}}
                            <div class="app-rich-text">{!! $reply->rich_text !!}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-layouts.app>
