@foreach ($replies->sortBy('created_at') as $reply)
    <div class="reply">
        <p class="meta">
            @if ($reply->isEmailReply())
                Email reply from {{ $reply->from_name ? "{$reply->from_name} <{$reply->from_email}>" : $reply->from_email }}
            @else
                {{ $reply->author?->name ?? 'A former member' }}
            @endif
            · {{ $fmt($reply->published_at ?? $reply->created_at) }}
        </p>
        <div>{!! $reply->rich_text !!}</div>
    </div>
@endforeach
