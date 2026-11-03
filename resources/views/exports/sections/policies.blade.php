@foreach ($entries as $policy)
    <h2>{{ $policy->title }}</h2>
    <p class="meta">{{ $policy->status() }}@if ($policy->published_at) · adopted {{ $fmt($policy->published_at) }}@endif</p>
    <div>{!! $policy->rich_text !!}</div>
@endforeach
