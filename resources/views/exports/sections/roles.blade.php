@foreach ($entries as $role)
    <h2>{{ $role->title }}</h2>
    <p class="meta">{{ $role->status() }}@if ($role->contacts->isNotEmpty()) · held by {{ $role->contacts->pluck('name')->implode(', ') }}@endif</p>
    <p>{{ $role->description }}</p>
@endforeach
