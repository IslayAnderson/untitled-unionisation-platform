@foreach ($entries as $bulletin)
    <h2>{{ $bulletin->title }}</h2>
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
@endforeach
