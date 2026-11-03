<x-layouts.app title="Print flyers and posters">
    <div x-data="{
        kind: 'invite',
        design: 'poster',
        headline: @js($headlines[0]),
        custom: false,
        message: @js($message),
        bulk: false,
        setKinds: @js(array_keys($versions)),
        copies: 10,
        versions: @js(array_map(fn ($version) => $version[0], $versions)),
        cardsPerSheet: { poster: 1, flyers: 2, slips: 4 },
        qr: { join: @js($joinQr), demo: @js($demoQr) },
        get orderedSetKinds() {
            return Object.keys(this.versions).filter(version => this.setKinds.includes(version));
        },
        get sheetKinds() {
            return this.bulk ? this.orderedSetKinds : [this.kind];
        },
        get copyCount() {
            return this.bulk ? Math.min(Math.max(parseInt(this.copies) || 1, 1), 50) : 1;
        },
        init() {
            {{-- Hundreds of cards share two QR codes, so give each a short blob URL rather than its own copy of the data URI. --}}
            for (const [name, uri] of Object.entries(this.qr)) {
                if (uri) {
                    fetch(uri).then(response => response.blob()).then(blob => this.qr[name] = URL.createObjectURL(blob));
                }
            }
        },
    }">
        <div class="print:hidden">
            <h1 class="mb-2 text-2xl font-semibold">Print flyers and posters</h1>
            @if ($building->is_demo)
                <p class="mb-6 text-stone-600">
                    These are the demo union's flyers and posters, as its members would print them. A real union's code
                    takes neighbours to its building's sign-up page; the demo's takes them to
                    <a href="{{ route('demo.show') }}" class="underline">the demo</a>. Try every version and size, and
                    print one if you like.
                </p>
            @else
                <p class="mb-6 text-stone-600">
                    Put them up in shared spaces or through doors. The code takes neighbours to your building's sign-up page.
                    They never name you or any other member.
                </p>
            @endif

            <div class="space-y-5 rounded-lg border border-stone-200 bg-white p-5">
                <fieldset>
                    <legend class="text-sm font-medium">Size</legend>
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

                <fieldset>
                    <legend class="text-sm font-medium">How many</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (['one' => [false, 'One sheet', 'Print a single sheet of one version'], 'sets' => [true, 'Sets to cut and staple', 'Lots of sheets, in order, so each pile you cut is ready to staple']] as $value => [$bulk, $label, $hint])
                            <label class="flex cursor-pointer gap-2 rounded-md border border-stone-200 p-3 has-[:checked]:border-stone-900 has-[:checked]:bg-stone-50">
                                <input type="radio" name="bulk" value="{{ $value }}" @checked(! $bulk) @change="bulk = @js($bulk)" class="mt-1 border-stone-300">
                                <span>
                                    <span class="block text-sm font-medium">{{ $label }}</span>
                                    <span class="block text-xs text-stone-500">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset x-show="! bulk">
                    <legend class="text-sm font-medium">Version</legend>
                    <div @class(['mt-2 grid gap-2', 'sm:grid-cols-2' => count($versions) === 2, 'sm:grid-cols-3' => count($versions) === 3])>
                        @foreach ($versions as $value => [$label, $hint])
                            <label class="flex cursor-pointer gap-2 rounded-md border border-stone-200 p-3 has-[:checked]:border-stone-900 has-[:checked]:bg-stone-50">
                                <input type="radio" name="kind" value="{{ $value }}" x-model="kind" class="mt-1 border-stone-300">
                                <span>
                                    <span class="block text-sm font-medium">{{ $label }}</span>
                                    <span class="block text-xs text-stone-500">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div x-show="bulk" x-cloak class="space-y-4">
                    <fieldset>
                        <legend class="text-sm font-medium">In each set</legend>
                        <div @class(['mt-2 grid gap-2', 'sm:grid-cols-2' => count($versions) === 2, 'sm:grid-cols-3' => count($versions) === 3])>
                            @foreach ($versions as $value => [$label, $hint])
                                <label class="flex cursor-pointer gap-2 rounded-md border border-stone-200 p-3 has-[:checked]:border-stone-900 has-[:checked]:bg-stone-50">
                                    <input type="checkbox" value="{{ $value }}" x-model="setKinds" class="mt-1 rounded border-stone-300">
                                    <span>
                                        <span class="block text-sm font-medium">{{ $label }}</span>
                                        <span class="block text-xs text-stone-500">{{ $hint }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div>
                        <label for="copies" class="block text-sm font-medium">Sheets of each version</label>
                        <input id="copies" type="number" min="1" max="50" x-model="copies"
                            class="mt-1 block w-28 rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm">
                    </div>

                    <div class="rounded-md bg-stone-50 px-4 py-3 text-sm text-stone-700" x-show="orderedSetKinds.length">
                        <p class="font-medium" x-text="`${copyCount * orderedSetKinds.length} sheets, making ${copyCount * cardsPerSheet[design]} ${copyCount * cardsPerSheet[design] === 1 ? 'set' : 'sets'} of ${orderedSetKinds.map(version => versions[version].toLowerCase()).join(', ')}.`"></p>
                        <p class="mt-1">
                            The sheets print in order: one of each version, then the next set.
                            <span x-show="design !== 'poster'">Print single-sided and keep the sheets in the order they come out. Square up the stack, cut along the dashed lines through the whole stack, and each pile is in order.</span>
                            <span x-show="design === 'poster'">Print single-sided and keep the sheets in the order they come out.</span>
                            <span x-show="orderedSetKinds.length > 1" x-text="`Staple each group of ${orderedSetKinds.length} from the top.`"></span>
                        </p>
                    </div>
                    <p class="text-sm text-red-700" x-show="! orderedSetKinds.length">Pick at least one version for each set.</p>
                </div>

                <div x-show="sheetKinds.some(version => version !== 'demo')">
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
                    <p class="mt-1 text-xs text-stone-500" x-show="sheetKinds.includes('demo')">The demo version has its own headline and message.</p>
                </div>

                <div x-show="sheetKinds.some(version => version !== 'demo')">
                    <label for="message" class="block text-sm font-medium">Message</label>
                    <textarea id="message" x-model="message" rows="3" maxlength="300"
                        class="mt-1 block w-full rounded-md border border-stone-300 bg-white px-3 py-2 shadow-sm"></textarea>
                    <p class="mt-1 text-xs text-stone-500">
                        Keep it short. Don't add names, flat numbers or phone numbers: anyone can read a poster.
                        <span x-show="design === 'slips' || (sheetKinds.includes('info') && design === 'flyers')">This size leaves the message out to fit.</span>
                        <span x-show="sheetKinds.includes('info') && design === 'slips'">Information door slips list what the union does, without the details.</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="button" @click="window.print()" x-bind:disabled="! sheetKinds.length">Print</x-button>
                    <span class="text-xs text-stone-500">Print on A4 at 100% scale, with headers and footers turned off.</span>
                </div>
            </div>

            <h2 class="mb-2 mt-8 text-sm font-semibold text-stone-600" x-text="bulk ? 'Preview of the first set' : 'Preview'">Preview</h2>
        </div>

        {{-- The printed sheets: one, or each set in turn. Only the first set is shown on screen. --}}
        <div class="space-y-8 print:space-y-0">
            <template x-for="copy in copyCount" :key="copy">
                <div class="space-y-8 print:space-y-0" :class="copy > 1 && 'hidden print:block'">
                    <template x-for="(kind, sheet) in sheetKinds" :key="kind">
                        <div>
                            <p class="mb-2 text-xs text-stone-500 print:hidden" x-show="bulk" x-text="`Sheet ${sheet + 1}: ${versions[kind]}`"></p>
                            <div class="invite-sheet mx-auto shadow-md print:shadow-none">
                                <template x-if="design === 'poster'">
                                    <div class="h-full">
                                        <x-invite-card size="poster" :building="$building" :join-url="$joinUrl" :demo-url="$demoUrl" :features="$features" />
                                    </div>
                                </template>
                                <template x-if="design === 'flyers'">
                                    <div class="grid h-full grid-rows-2">
                                        @foreach (range(1, 2) as $i)
                                            <x-invite-card size="flyer" :building="$building" :join-url="$joinUrl" :demo-url="$demoUrl" :features="$features" class="invite-cut" />
                                        @endforeach
                                    </div>
                                </template>
                                <template x-if="design === 'slips'">
                                    <div class="grid h-full grid-cols-2 grid-rows-2">
                                        @foreach (range(1, 4) as $i)
                                            <x-invite-card size="slip" :building="$building" :join-url="$joinUrl" :demo-url="$demoUrl" :features="$features" class="invite-cut" />
                                        @endforeach
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</x-layouts.app>
