<x-layouts.app title="Print flyers and posters">
    <div x-data="{
        design: 'poster',
        headline: @js($headlines[0]),
        custom: false,
        message: @js($message),
    }">
        <div class="print:hidden">
            <h1 class="mb-2 text-2xl font-semibold">Print flyers and posters</h1>
            <p class="mb-6 text-stone-600">
                Put them up in shared spaces or through doors. The code takes neighbours to your building's sign-up page.
                They never name you or any other member.
            </p>

            <div class="space-y-5 rounded-lg border border-stone-200 bg-white p-5">
                <fieldset>
                    <legend class="text-sm font-medium">Design</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach (['poster' => ['Poster', 'One A4 sheet, for noticeboards and lifts'], 'flyers' => ['Flyers', 'Two A5 flyers per A4 sheet'], 'slips' => ['Door slips', 'Four small slips per A4 sheet']] as $value => [$label, $hint])
                            <label class="flex cursor-pointer gap-2 rounded-md border border-stone-200 p-3 has-[:checked]:border-stone-900 has-[:checked]:bg-stone-50">
                                <input type="radio" name="design" value="{{ $value }}" x-model="design" class="mt-1 border-stone-300">
                                <span>
                                    <span class="block text-sm font-medium">{{ $label }}</span>
                                    <span class="block text-xs text-stone-500">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="headline-choice" class="block text-sm font-medium">Headline</label>
                    <select id="headline-choice" class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm"
                        @change="custom = $event.target.value === ''; if (! custom) headline = $event.target.value">
                        @foreach ($headlines as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                        <option value="">Write your own…</option>
                    </select>
                    <input x-show="custom" x-cloak type="text" x-model="headline" maxlength="60" aria-label="Your headline"
                        class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm">
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium">Message</label>
                    <textarea id="message" x-model="message" rows="3" maxlength="300"
                        class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm"></textarea>
                    <p class="mt-1 text-xs text-stone-500">
                        Keep it short. Don't add names, flat numbers or phone numbers: anyone can read a poster.
                        <span x-show="design === 'slips'">Door slips leave the message out to fit.</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="button" @click="window.print()">Print</x-button>
                    <span class="text-xs text-stone-500">Print on A4 at 100% scale, with headers and footers turned off.</span>
                </div>
            </div>

            <h2 class="mb-2 mt-8 text-sm font-semibold text-stone-600">Preview</h2>
        </div>

        {{-- The printed sheet. --}}
        <div class="invite-sheet mx-auto shadow-md print:shadow-none">
            <div x-show="design === 'poster'" class="h-full">
                <x-invite-card size="poster" :building="$building" :join-url="$joinUrl" :join-qr="$joinQr" />
            </div>
            <div x-show="design === 'flyers'" x-cloak class="grid h-full grid-rows-2">
                @foreach (range(1, 2) as $i)
                    <x-invite-card size="flyer" :building="$building" :join-url="$joinUrl" :join-qr="$joinQr" class="invite-cut" />
                @endforeach
            </div>
            <div x-show="design === 'slips'" x-cloak class="grid h-full grid-cols-2 grid-rows-2">
                @foreach (range(1, 4) as $i)
                    <x-invite-card size="slip" :building="$building" :join-url="$joinUrl" :join-qr="$joinQr" class="invite-cut" />
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
