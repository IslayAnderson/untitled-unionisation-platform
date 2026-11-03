<x-layouts.app :title="$bulletin->title">
    <h1 class="text-2xl font-semibold">{{ $bulletin->title }}</h1>
    <p class="mb-6 text-sm text-stone-600">
        {{ $bulletin->author?->name ?? 'A former member' }} · {{ $bulletin->published_at->format('j M Y, H:i') }}
    </p>

    <article class="whitespace-pre-line rounded-lg border border-stone-200 bg-white p-5 text-stone-800">{{ $bulletin->rich_text }}</article>

    @if ($bulletin->mails->isNotEmpty())
        <section class="mt-6">
            <h2 class="mb-2 text-lg font-semibold">Mail</h2>
            <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
                @foreach ($bulletin->mails as $mail)
                    <x-mail-row :mail="$mail" />
                @endforeach
            </ul>
        </section>
    @endif

    @if ($canMail)
        <section class="mt-6 rounded-lg border border-stone-200 bg-white p-5">
            <h2 class="text-lg font-semibold">Send as mail</h2>
            <p class="mt-1 text-sm text-stone-600">
                Email this bulletin to one of your mailing lists, signed by the union. Every other member will be asked to approve it,
                and it's sent once {{ $rules->bulletinMailApprovalPercent }}% of the union has approved. It expires after
                {{ intdiv($rules->bulletinMailExpiryHours, 24) }} days.
            </p>

            <x-privacy-reminder class="mt-4" />

            @if ($lists->isEmpty())
                <p class="mt-4 text-sm text-stone-700">You need a mailing list first. <a href="{{ route('mailing-lists.create') }}" class="underline">Create one</a>.</p>
            @else
                <form method="POST" action="{{ route('bulletins.mail', $bulletin) }}" class="mt-4 flex max-w-xl items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-select name="mailing_list_id" label="To" :options="$lists->pluck('name', 'id')" placeholder="Choose a mailing list" required />
                    </div>
                    <x-button>Ask the union</x-button>
                </form>
            @endif
        </section>
    @endif
</x-layouts.app>
