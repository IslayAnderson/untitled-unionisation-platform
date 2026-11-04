<x-layouts.app :title="$email->label" :back-link="route('demo.emails.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <span class="govuk-caption-l">The demo union's emails</span>
            <h1 class="govuk-heading-l">{{ $email->label }}</h1>

            <dl class="govuk-summary-list">
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">Who gets it</dt>
                    <dd class="govuk-summary-list__value">{{ $email->recipient }}</dd>
                </div>
                <div class="govuk-summary-list__row">
                    <dt class="govuk-summary-list__key">Subject</dt>
                    <dd class="govuk-summary-list__value">{{ $email->subject() }}</dd>
                </div>
            </dl>

            <p class="govuk-body">Links in the email open in a new tab. Ones into the union only work once you've entered the demo.</p>
        </div>
    </div>

    {{-- Sandboxed with no scripts. The frame grows to fit the email once it loads. --}}
    <iframe
        src="{{ route('demo.emails.html', $email->slug) }}"
        title="The email: {{ $email->label }}"
        class="app-email-frame"
        sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
        loading="lazy"
        onload="this.style.height = this.contentDocument.documentElement.scrollHeight + 'px'"
    ></iframe>

    <nav class="govuk-pagination govuk-pagination--block" aria-label="Emails">
        @if ($previous)
            <div class="govuk-pagination__prev">
                <a class="govuk-link govuk-pagination__link" href="{{ route('demo.emails.show', $previous->slug) }}" rel="prev">
                    <svg class="govuk-pagination__icon govuk-pagination__icon--prev" xmlns="http://www.w3.org/2000/svg" height="13" width="15" aria-hidden="true" focusable="false" viewBox="0 0 15 13">
                        <path d="m6.5938-0.0078125-6.7266 6.7266 6.7441 6.4062 1.377-1.449-4.1856-3.9768h12.896v-2h-12.984l4.2931-4.293-1.414-1.414z"></path>
                    </svg>
                    <span class="govuk-pagination__link-title">Previous<span class="govuk-visually-hidden"> email</span></span>
                    <span class="govuk-visually-hidden">:</span>
                    <span class="govuk-pagination__link-label">{{ $previous->label }}</span>
                </a>
            </div>
        @endif
        @if ($next)
            <div class="govuk-pagination__next">
                <a class="govuk-link govuk-pagination__link" href="{{ route('demo.emails.show', $next->slug) }}" rel="next">
                    <svg class="govuk-pagination__icon govuk-pagination__icon--next" xmlns="http://www.w3.org/2000/svg" height="13" width="15" aria-hidden="true" focusable="false" viewBox="0 0 15 13">
                        <path d="m8.107-0.0078125-1.4136 1.414 4.2926 4.293h-12.986v2h12.896l-4.1855 3.9766 1.377 1.4492 6.7441-6.4062-6.7246-6.7266z"></path>
                    </svg>
                    <span class="govuk-pagination__link-title">Next<span class="govuk-visually-hidden"> email</span></span>
                    <span class="govuk-visually-hidden">:</span>
                    <span class="govuk-pagination__link-label">{{ $next->label }}</span>
                </a>
            </div>
        @endif
    </nav>
</x-layouts.app>
