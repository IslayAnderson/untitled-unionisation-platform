@php
    $sizes = [
        'poster' => ['Poster', 'One A4 sheet, for noticeboards and lifts'],
        'flyers' => ['Flyers', 'Two A5 flyers per A4 sheet'],
        'slips' => ['Door slips', 'Four small slips per A4 sheet'],
        'letter' => ['Letter for a DL envelope', 'One A4 sheet folded in three, with the address where a window envelope shows it'],
    ];
@endphp

<x-layouts.app title="Print flyers and posters" :back-link="match ($context) { 'demo' => route('demo.show'), 'campaign' => route('admin.unions.index'), default => route('dashboard') }" main-class="app-print-page">
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
        cardsPerSheet: { poster: 1, flyers: 2, slips: 4, letter: 1 },
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
        <div class="govuk-grid-row govuk-!-display-none-print">
            <div class="govuk-grid-column-two-thirds">
                @if ($context === 'campaign')
                    <span class="govuk-caption-l">Admin</span>
                @endif
                <h1 class="govuk-heading-l">Print flyers and posters</h1>
                @if ($context === 'campaign')
                    <p class="govuk-body">
                        Leaflets for a campaign, to put through doors in buildings that don't have a union yet. They don't
                        name a building: the code takes tenants to the home page, to start a union for their building or
                        join one a neighbour has started.
                    </p>
                @elseif ($context === 'demo')
                    <p class="govuk-body">
                        These are the demo union's flyers and posters, as its members would print them. A real union's code
                        takes neighbours to its building's sign-up page; the demo's takes them to
                        <a href="{{ route('demo.show') }}" class="govuk-link">the demo</a>. Try every version and size, and
                        print one if you like.
                    </p>
                @else
                    <p class="govuk-body">
                        Put them up in shared spaces or through doors. The code takes neighbours to your building's sign-up page.
                        They never name you or any other member.
                    </p>
                @endif

                <div class="govuk-form-group">
                    <fieldset class="govuk-fieldset">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">Size</legend>
                        <div class="govuk-radios" data-module="govuk-radios">
                            @foreach ($sizes as $value => [$label, $hint])
                                <div class="govuk-radios__item">
                                    <input class="govuk-radios__input" id="design-{{ $value }}" name="design" type="radio" value="{{ $value }}" x-model="design" aria-describedby="design-{{ $value }}-hint">
                                    <label class="govuk-label govuk-radios__label" for="design-{{ $value }}">{{ $label }}</label>
                                    <div id="design-{{ $value }}-hint" class="govuk-hint govuk-radios__hint">{{ $hint }}</div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="govuk-form-group">
                    <fieldset class="govuk-fieldset">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">How many</legend>
                        <div class="govuk-radios" data-module="govuk-radios">
                            @foreach (['one' => [false, 'One sheet', 'Print a single sheet of one version'], 'sets' => [true, 'Sets to cut and staple', 'Lots of sheets, in order, so each pile you cut is ready to staple']] as $value => [$bulk, $label, $hint])
                                <div class="govuk-radios__item">
                                    <input class="govuk-radios__input" id="bulk-{{ $value }}" name="bulk" type="radio" value="{{ $value }}" @checked(! $bulk) @change="bulk = @js($bulk)" aria-describedby="bulk-{{ $value }}-hint">
                                    <label class="govuk-label govuk-radios__label" for="bulk-{{ $value }}">{{ $label }}</label>
                                    <div id="bulk-{{ $value }}-hint" class="govuk-hint govuk-radios__hint">{{ $hint }}</div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="govuk-form-group" x-show="! bulk">
                    <fieldset class="govuk-fieldset">
                        <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">Version</legend>
                        <div class="govuk-radios" data-module="govuk-radios">
                            @foreach ($versions as $value => [$label, $hint])
                                <div class="govuk-radios__item">
                                    <input class="govuk-radios__input" id="kind-{{ $value }}" name="kind" type="radio" value="{{ $value }}" x-model="kind" aria-describedby="kind-{{ $value }}-hint">
                                    <label class="govuk-label govuk-radios__label" for="kind-{{ $value }}">{{ $label }}</label>
                                    <div id="kind-{{ $value }}-hint" class="govuk-hint govuk-radios__hint">{{ $hint }}</div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div x-show="bulk" x-cloak>
                    <div class="govuk-form-group" :class="! orderedSetKinds.length && 'govuk-form-group--error'">
                        <fieldset class="govuk-fieldset" aria-describedby="set-kinds-hint">
                            <legend class="govuk-fieldset__legend govuk-fieldset__legend--m">In each set</legend>
                            <div id="set-kinds-hint" class="govuk-hint">Select all that apply.</div>
                            <p class="govuk-error-message" x-show="! orderedSetKinds.length"><span class="govuk-visually-hidden">Error:</span> Select at least one version for each set</p>
                            <div class="govuk-checkboxes" data-module="govuk-checkboxes">
                                @foreach ($versions as $value => [$label, $hint])
                                    <div class="govuk-checkboxes__item">
                                        <input class="govuk-checkboxes__input" id="set-{{ $value }}" type="checkbox" value="{{ $value }}" x-model="setKinds" aria-describedby="set-{{ $value }}-hint">
                                        <label class="govuk-label govuk-checkboxes__label" for="set-{{ $value }}">{{ $label }}</label>
                                        <div id="set-{{ $value }}-hint" class="govuk-hint govuk-checkboxes__hint">{{ $hint }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="govuk-form-group">
                        <label class="govuk-label govuk-label--s" for="copies">Sheets of each version</label>
                        <div id="copies-hint" class="govuk-hint">Up to 50.</div>
                        <input class="govuk-input govuk-input--width-3" id="copies" type="text" inputmode="numeric" spellcheck="false" x-model="copies" aria-describedby="copies-hint">
                    </div>

                    <div class="govuk-inset-text" x-show="orderedSetKinds.length">
                        <p class="govuk-body govuk-!-font-weight-bold" x-text="`${copyCount * orderedSetKinds.length} sheets, making ${copyCount * cardsPerSheet[design]} ${copyCount * cardsPerSheet[design] === 1 ? 'set' : 'sets'} of ${orderedSetKinds.map(version => versions[version].toLowerCase()).join(', ')}.`"></p>
                        <p class="govuk-body govuk-!-margin-bottom-0">
                            The sheets print in order: one of each version, then the next set.
                            <span x-show="design !== 'poster'">Print single-sided and keep the sheets in the order they come out. Square up the stack, cut along the dashed lines through the whole stack, and each pile is in order.</span>
                            <span x-show="design === 'poster' || design === 'letter'">Print single-sided and keep the sheets in the order they come out.</span>
                            <span x-show="design !== 'letter' && orderedSetKinds.length > 1" x-text="`Staple each group of ${orderedSetKinds.length} from the top.`"></span>
                            <span x-show="design === 'letter' && orderedSetKinds.length > 1" x-text="`Fold each group of ${orderedSetKinds.length} together and put them in one envelope.`"></span>
                        </p>
                    </div>
                </div>

                <div x-show="sheetKinds.some(version => version !== 'demo')">
                    <div class="govuk-form-group">
                        <label class="govuk-label govuk-label--m" for="headline-choice">Headline</label>
                        <div id="headline-choice-hint" class="govuk-hint" x-show="sheetKinds.includes('demo')">The demo version has its own headline and message.</div>
                        <select id="headline-choice" class="govuk-select" aria-describedby="headline-choice-hint"
                            @change="custom = $event.target.value === ''; if (! custom) headline = $event.target.value">
                            @foreach ($headlines as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                            <option value="">Write your own</option>
                        </select>
                    </div>
                    <div class="govuk-form-group" x-show="custom" x-cloak>
                        <label class="govuk-label" for="headline">Your headline</label>
                        <input class="govuk-input" id="headline" type="text" x-model="headline" maxlength="60">
                    </div>

                    <div class="govuk-form-group">
                        <label class="govuk-label govuk-label--m" for="message">Message</label>
                        <div id="message-hint" class="govuk-hint">
                            Keep it short. Do not add names, flat numbers or phone numbers: anyone can read a poster.
                            <span x-show="design === 'slips' || (sheetKinds.includes('info') && design === 'flyers')">This size leaves the message out to fit.</span>
                            <span x-show="sheetKinds.includes('info') && design === 'slips'">Information door slips list what the union does, without the details.</span>
                        </div>
                        <textarea class="govuk-textarea" id="message" rows="3" maxlength="300" x-model="message" aria-describedby="message-hint"></textarea>
                    </div>
                </div>

                <div class="govuk-inset-text" x-show="design === 'letter'" x-cloak>
                    <p class="govuk-body">
                        Fold along the marks on the edges: the bottom third up, then the top third back, so the address faces out.
                        It fits a DL envelope (110 × 220mm). In a window envelope the address shows through the window.
                    </p>
                    <p class="govuk-body govuk-!-margin-bottom-0">
                        There's a line to write the flat number on by hand. Don't print it: the sheets don't name anyone.
                    </p>
                </div>

                <x-button type="button" @click="window.print()" x-bind:disabled="! sheetKinds.length">Print</x-button>
                <p class="govuk-body-s">Print on A4 at 100% scale, with headers and footers turned off.</p>

                <h2 class="govuk-heading-m govuk-!-margin-top-6" x-text="bulk ? 'Preview of the first set' : 'Preview'">Preview</h2>
            </div>
        </div>

        {{-- The printed sheets: one, or each set in turn. Only the first set is shown on screen. --}}
        <div class="app-invite-preview">
            <template x-for="copy in copyCount" :key="copy">
                <div class="app-invite-preview" :class="copy > 1 && 'app-invite-preview__repeat'">
                    <template x-for="(kind, sheet) in sheetKinds" :key="kind">
                        <div>
                            <p class="app-invite-preview__label" x-show="bulk" x-text="`Sheet ${sheet + 1}: ${versions[kind]}`"></p>
                            <div class="app-invite-sheet">
                                <template x-if="design === 'poster'">
                                    <div class="app-invite-sheet__layout">
                                        <x-invite-card size="poster" :union="$union" :join-url="$joinUrl" :join-title="$joinTitle" :join-text="$joinText" :demo-url="$demoUrl" :features="$features" />
                                    </div>
                                </template>
                                <template x-if="design === 'flyers'">
                                    <div class="app-invite-sheet__layout app-invite-sheet__layout--flyers">
                                        @foreach (range(1, 2) as $i)
                                            <x-invite-card size="flyer" :union="$union" :join-url="$joinUrl" :join-title="$joinTitle" :join-text="$joinText" :demo-url="$demoUrl" :features="$features" cut />
                                        @endforeach
                                    </div>
                                </template>
                                <template x-if="design === 'letter'">
                                    <div class="app-invite-sheet__layout">
                                        <x-invite-letter :union="$union" :recipient="$recipient" :address-lines="$addressLines" :join-url="$joinUrl" :join-title="$joinTitle" :join-text="$joinText" :demo-url="$demoUrl" :features="$features" />
                                    </div>
                                </template>
                                <template x-if="design === 'slips'">
                                    <div class="app-invite-sheet__layout app-invite-sheet__layout--slips">
                                        @foreach (range(1, 4) as $i)
                                            <x-invite-card size="slip" :union="$union" :join-url="$joinUrl" :join-title="$joinTitle" :join-text="$joinText" :demo-url="$demoUrl" :features="$features" cut />
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
