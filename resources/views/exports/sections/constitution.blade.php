@foreach ($entries as $constitution)
    <p class="meta">In force since {{ $fmt($constitution->published_at ?? $constitution->created_at) }}</p>
    <div>{!! $constitution->renderedText() !!}</div>
    <h2>The constitution's numbers</h2>
    <table>
        @foreach (App\Support\ConstitutionRules::FIELDS as $name => $field)
            <tr><td>{{ $field['label'] }}</td><td>{{ $constitution->parsedRules()->toArray()[$name] }} {{ $field['unit'] }}</td></tr>
        @endforeach
    </table>
@endforeach
