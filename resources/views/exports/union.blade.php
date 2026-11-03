{{--
    The readable part of a union's export (App\Services\UnionExport). It has to work offline, opened
    from the zip, so styles are inline and links point at files in the zip. Rich text is stored cleaned
    (App\Support\RichText), as it's shown in the app.
--}}
@php($fmt = fn ($date) => $date?->format('j M Y, H:i'))
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $building->name }}: union records</title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; color: #1c1917; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.8rem; margin-bottom: 0; }
        h2 { margin-top: 3rem; border-bottom: 2px solid #1c1917; padding-bottom: .25rem; }
        h3 { margin: 2rem 0 .25rem; }
        .meta { color: #78716c; font-size: .875rem; margin: 0; }
        .box { border: 1px solid #e7e5e4; border-radius: .5rem; padding: .75rem 1rem; margin-top: .75rem; }
        .reply { border-left: 3px solid #d6d3d1; padding-left: 1rem; margin-top: 1rem; }
        table { border-collapse: collapse; width: 100%; font-size: .875rem; }
        td, th { text-align: left; padding: .25rem .5rem .25rem 0; border-bottom: 1px solid #e7e5e4; vertical-align: top; }
        img { max-width: 100%; height: auto; }
        nav a { margin-right: 1rem; }
    </style>
</head>
<body>
    <h1>{{ $building->name }}</h1>
    <p class="meta">{{ $building->formattedAddress() }} · records exported {{ $fmt($exportedAt) }}, before the union was deleted from {{ config('app.name') }}</p>
    <p>
        Everything members could see in the union. Landlord and agent contacts are also in <code>landlords.csv</code> and
        <code>contacts.csv</code>, and images and attachments are in the <code>files</code> folder. Votes were secret, so
        only totals are included.
    </p>
    <nav>
        <a href="#constitution">Constitution</a><a href="#policies">Policies</a><a href="#roles">Roles</a><a href="#referendums">Referendums</a>
        <a href="#bulletins">Bulletins</a><a href="#discussions">Discussions</a><a href="#mail">Mail</a><a href="#mailing-lists">Mailing lists</a>
    </nav>

    <h2 id="constitution">Constitution</h2>
    @if ($constitution = $building->currentConstitution)
        <p class="meta">In force since {{ $fmt($constitution->published_at ?? $constitution->created_at) }}</p>
        <div>{!! $constitution->renderedText() !!}</div>
        <table>
            @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
                <tr><td>{{ $field['label'] }}</td><td>{{ $constitution->parsedRules()->toArray()[$name] }} {{ $field['unit'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p>No constitution.</p>
    @endif

    <h2 id="policies">Policies</h2>
    @forelse ($building->policies as $policy)
        <h3>{{ $policy->title }}</h3>
        <p class="meta">{{ $policy->status() }}@if ($policy->published_at) · adopted {{ $fmt($policy->published_at) }}@endif</p>
        <div>{!! $policy->rich_text !!}</div>
    @empty
        <p>No policies.</p>
    @endforelse

    <h2 id="roles">Roles</h2>
    @forelse ($building->roles as $role)
        <h3>{{ $role->title }}</h3>
        <p class="meta">{{ $role->status() }}@if ($role->contacts->isNotEmpty()) · held by {{ $role->contacts->pluck('name')->implode(', ') }}@endif</p>
        <p>{{ $role->description }}</p>
    @empty
        <p>No roles.</p>
    @endforelse

    <h2 id="referendums">Referendums</h2>
    @forelse ($building->referendums as $referendum)
        <h3>{{ $referendum->title }}</h3>
        <p class="meta">
            Opened {{ $fmt($referendum->created_at) }} ·
            @if ($referendum->closed_at)
                {{ $referendum->outcome?->label() }} on {{ $fmt($referendum->closed_at) }}:
                {{ $referendum->for_count }} for, {{ $referendum->against_count }} against, {{ $referendum->abstain_count }} abstaining,
                of {{ $referendum->member_count }} members
            @else
                still open when the union was deleted, so not counted
            @endif
        </p>
        @if ($referendum->description)
            <p>{{ $referendum->description }}</p>
        @endif
    @empty
        <p>No referendums.</p>
    @endforelse

    <h2 id="bulletins">Bulletins</h2>
    @forelse ($building->bulletins as $bulletin)
        <h3>{{ $bulletin->title }}</h3>
        <p class="meta">{{ $bulletin->isVouching() ? 'For everyone who signed up' : ($bulletin->author?->name ?? 'A former member') }} · {{ $fmt($bulletin->published_at) }}</p>
        <div>{!! $bulletin->rich_text !!}</div>
        @foreach ($bulletin->polls as $poll)
            <div class="box">
                <strong>Poll: {{ $poll->title }}</strong>
                @if ($poll->closedAt())
                    <p class="meta">Closed {{ $fmt($poll->closedAt()) }} · {{ $poll->ballot_count }} {{ str('ballot')->plural($poll->ballot_count) }}</p>
                    <table>
                        @foreach ($poll->answers as $answer)
                            <tr><td>{{ $answer->title }}</td><td>{{ $answer->vote_count }}</td></tr>
                        @endforeach
                    </table>
                @else
                    <p class="meta">Still open when the union was deleted, so results weren't released.</p>
                @endif
            </div>
        @endforeach
        @include('exports.replies', ['replies' => $bulletin->replies])
    @empty
        <p>No bulletins.</p>
    @endforelse

    <h2 id="discussions">Discussions</h2>
    @forelse ($building->threads as $thread)
        <h3>{{ $thread->title }}</h3>
        <p class="meta">{{ $thread->author?->name ?? 'A former member' }} · {{ $fmt($thread->published_at) }}</p>
        <div>{!! $thread->rich_text !!}</div>
        @include('exports.replies', ['replies' => $thread->replies])
    @empty
        <p>No discussions.</p>
    @endforelse

    <h2 id="mail">Mail</h2>
    @forelse ($building->unionMails as $mail)
        <h3>{{ $mail->subject }}</h3>
        <p class="meta">
            {{ $mail->status->label() }} · {{ $mail->kind->label() }} to {{ $mail->mailingList?->name ?? 'a deleted list' }} ·
            written by {{ $mail->author?->name ?? 'a former member' }} on {{ $fmt($mail->created_at) }}
            @if ($mail->recipient_count) · sent to {{ $mail->recipient_count }} {{ str('person')->plural($mail->recipient_count) }}@endif
        </p>
        <div>{!! $mail->body !!}</div>
        @if ($mail->attachments->isNotEmpty())
            <p class="meta">Attachments:
                @foreach ($mail->attachments as $attachment)
                    @if (isset($attachmentPaths[$attachment->id]))
                        <a href="{{ $attachmentPaths[$attachment->id] }}">{{ $attachment->name }}</a>@if (! $loop->last), @endif
                    @endif
                @endforeach
            </p>
        @endif
    @empty
        <p>No mail.</p>
    @endforelse

    <h2 id="mailing-lists">Mailing lists</h2>
    @forelse ($building->mailingLists as $list)
        <h3>{{ $list->name }}</h3>
        <p>{{ $list->contacts->map(fn ($contact) => $contact->email ? "{$contact->name} <{$contact->email}>" : $contact->name)->implode(', ') ?: 'Nobody' }}</p>
    @empty
        <p>No mailing lists.</p>
    @endforelse
</body>
</html>
