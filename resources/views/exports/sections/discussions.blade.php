@foreach ($entries as $thread)
    <h2>{{ $thread->title }}</h2>
    <p class="meta">{{ $thread->author?->name ?? 'A former member' }} · {{ $fmt($thread->published_at) }}</p>
    <div>{!! $thread->rich_text !!}</div>
    @include('exports.replies', ['replies' => $thread->replies])
@endforeach
