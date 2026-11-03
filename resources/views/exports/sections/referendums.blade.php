@foreach ($entries as $referendum)
    <h2>{{ $referendum->title }}</h2>
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
@endforeach
