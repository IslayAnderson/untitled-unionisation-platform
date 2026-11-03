{{-- A plain-text file, so nothing is HTML-escaped. --}}
{!! $building->name !!}: union records
{!! $building->formattedAddress() !!}
Exported {!! $exportedAt->format('j F Y, H:i') !!}, before the union was deleted from {!! config('app.name') !!}.

This is everything members could see in the union. Votes were secret, so only totals are included.
Long sections are split into parts of at most {!! App\Services\UnionExport::MAX_PAGES !!} pages.

@foreach ($contents as $file)
{!! $file !!}
@endforeach
landlords.csv and contacts.csv: landlord and agent contacts, for spreadsheets
files/mail: attachments sent with union mail, in a folder for each mail

Keep these records somewhere safe: they include other members' names and what they wrote.
