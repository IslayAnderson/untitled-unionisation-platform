@foreach ($entries as $list)
    <h2>{{ $list->name }}</h2>
    <p>{{ $list->contacts->map(fn ($contact) => $contact->email ? "{$contact->name} <{$contact->email}>" : $contact->name)->implode(', ') ?: 'Nobody' }}</p>
@endforeach
