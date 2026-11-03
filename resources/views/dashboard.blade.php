<x-layouts.app :title="$building->name">
    <div class="space-y-8">
        <section>
            <h1 class="text-2xl font-semibold">{{ $building->name }}</h1>
            <p class="text-stone-600">{{ $building->formattedAddress() }} · {{ $contact->unit->name }}</p>
            <p class="mt-2 text-sm text-stone-600">
                {{ $confirmedCount }} {{ Str::plural('tenant', $confirmedCount) }} signed up ·
                {{ $enrolledCount }} {{ Str::plural('member', $enrolledCount) }}
            </p>
        </section>

        @if ($awaitingApproval->isNotEmpty())
            <section class="rounded-lg border-2 border-sky-300 bg-white p-6">
                <h2 class="text-lg font-semibold">Mail waiting for your approval</h2>
                <ul class="mt-2 space-y-1">
                    @foreach ($awaitingApproval as $mail)
                        <li>
                            <a href="{{ route('mail.show', $mail) }}" class="underline">{{ $mail->subject }}</a>
                            <span class="text-sm text-stone-500">· {{ $mail->kind->label() }} · expires {{ $mail->expires_at->format('j M, H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($pollsToVoteIn->isNotEmpty())
            <section class="rounded-lg border-2 border-emerald-300 bg-white p-6">
                <h2 class="text-lg font-semibold">Polls waiting for your vote</h2>
                <ul class="mt-2 space-y-1">
                    @foreach ($pollsToVoteIn as $poll)
                        <li>
                            <a href="{{ route('bulletins.show', $poll->bulletin_id) }}#poll-{{ $poll->id }}" class="underline">{{ $poll->title }}</a>
                            <span class="text-sm text-stone-500">· closes {{ $poll->closes_at->format('j M, H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-lg border border-stone-200 bg-white p-6">
            @if ($contact->isEnrolled())
                <h2 class="text-lg font-semibold">You're a member</h2>
                <p class="mt-1 text-stone-600">
                    When a neighbour shows you their QR code, scan it with your phone's camera to vouch for them.
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="See who's waiting and arrange to meet" />
            @elseif (! $vouchingOpen)
                <h2 class="text-lg font-semibold">Waiting for neighbours</h2>
                <p class="mt-1 text-stone-600">
                    {{ $confirmedCount }} of {{ $rules->minSignups }} tenants have signed up. Vouching starts when
                    {{ $rules->minSignups }} have. Share your building's link below.
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="Say hello to your neighbours" />
            @else
                <h2 class="text-lg font-semibold">Get vouched for</h2>
                <p class="mt-1 text-stone-600">
                    Show this code to neighbours in person. You'll become a member when
                    {{ $rules->vouchesRequired }} of them have scanned it ({{ $vouchCount }} so far).
                </p>
                <x-vouching-bulletin-link :bulletin="$vouchingBulletin" text="Arrange to meet members" />
                <img src="{{ $vouchQr }}" alt="Your personal vouching QR code" class="mt-4 h-56 w-56">
                <p class="mt-2 break-all text-xs text-stone-500">{{ $vouchUrl }}</p>
            @endif
        </section>

        <section class="rounded-lg border border-stone-200 bg-white p-6">
            <h2 class="text-lg font-semibold">Invite your neighbours</h2>
            <p class="mt-1 text-stone-600">Share this link, or print the QR code for a noticeboard or door.</p>
            <img src="{{ $joinQr }}" alt="QR code to join {{ $building->name }}" class="mt-4 h-40 w-40">
            <p class="mt-2 break-all font-mono text-sm">{{ $joinUrl }}</p>
        </section>
    </div>
</x-layouts.app>
