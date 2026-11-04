@foreach ($entries as $list)
    <h2>{{ $list->name }}</h2>
    @if ($list->contacts->isEmpty())
        <p>Nobody</p>
    @else
        <ul class="plain-list">
            @foreach ($list->contacts as $contact)
                <li>{{ $contact->name }}@if ($contact->email) &lt;{{ $contact->email }}&gt;@endif</li>
            @endforeach
        </ul>
    @endif
@endforeach
