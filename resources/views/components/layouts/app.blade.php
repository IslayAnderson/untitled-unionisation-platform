@props(['title' => null, 'backLink' => null, 'mainClass' => null])

@php
    $user = auth()->user();
    $isMember = $user?->contact?->isEnrolled();

    // Text => [route name, route pattern for the current item]. Kept flat, as the Service navigation component asks.
    $navigation = match (true) {
        $isMember => [
            'Your union' => ['dashboard', 'dashboard'],
            'Bulletins' => ['bulletins.index', 'bulletins.*'],
            'Discussions' => ['threads.index', 'threads.*'],
            'Referendums' => ['referendums.index', 'referendums.*'],
            'Policies' => ['policies.index', 'policies.*'],
            'Roles' => ['roles.index', 'roles.*'],
            'Constitution' => ['constitution.show', 'constitution.*'],
            'Mail' => ['mail.index', 'mail.*'],
            'Contacts' => ['contacts.index', 'contacts.*'],
            'Mailing lists' => ['mailing-lists.index', 'mailing-lists.*'],
        ],
        $user?->contact !== null => ['Your union' => ['dashboard', 'dashboard']],
        default => [],
    };

    if ($user?->is_admin) {
        $navigation['Admin'] = ['admin.unions.index', 'admin.*'];
    }
@endphp

<!DOCTYPE html>
<html lang="en" class="govuk-template">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0c0c">
    <title>{{ $errors->any() ? 'Error: ' : '' }}{{ $title ? $title.' – ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="govuk-template__body">
    <script>document.body.className += ' js-enabled' + ('noModule' in HTMLScriptElement.prototype ? ' govuk-frontend-supported' : '');</script>

    <a href="#main-content" class="govuk-skip-link" data-module="govuk-skip-link">Skip to main content</a>

    {{-- Not on GOV.UK, so the Generic header with the service's own name, not the GOV.UK header. --}}
    <header class="govuk-generic-header govuk-!-display-none-print">
        <div class="govuk-generic-header__container govuk-width-container">
            <div class="govuk-generic-header__logo">
                <a href="{{ route(match (true) { $user?->contact !== null => 'dashboard', (bool) $user?->is_admin => 'admin.unions.index', default => 'home' }) }}" class="govuk-generic-header__homepage-link">{{ config('app.name') }}</a>
            </div>
        </div>
    </header>

    <section aria-label="Service information" class="govuk-service-navigation govuk-!-display-none-print" data-module="govuk-service-navigation">
        <div class="govuk-width-container">
            <div class="govuk-service-navigation__container">
                <nav aria-label="Menu" class="govuk-service-navigation__wrapper">
                    <button type="button" class="govuk-service-navigation__toggle govuk-js-service-navigation-toggle" aria-controls="navigation" hidden aria-hidden="true">
                        Menu
                    </button>
                    <ul class="govuk-service-navigation__list" id="navigation">
                        @foreach ($navigation as $text => [$route, $pattern])
                            @php($current = request()->routeIs($pattern))
                            <li @class(['govuk-service-navigation__item', 'govuk-service-navigation__item--active' => $current])>
                                <a class="govuk-service-navigation__link" href="{{ route($route) }}" @if ($current) aria-current="page" @endif>
                                    @if ($current)
                                        <strong class="govuk-service-navigation__active-fallback">{{ $text }}</strong>
                                    @else
                                        {{ $text }}
                                    @endif
                                </a>
                            </li>
                        @endforeach
                        @auth
                            <li class="govuk-service-navigation__item">
                                <form method="POST" action="{{ route('logout') }}" class="app-inline-form">
                                    @csrf
                                    <button type="submit" class="govuk-service-navigation__link app-link-button">Log out</button>
                                </form>
                            </li>
                        @else
                            <li class="govuk-service-navigation__item">
                                <a class="govuk-service-navigation__link" href="{{ route('login') }}">Log in</a>
                            </li>
                        @endauth
                    </ul>
                </nav>
            </div>
        </div>
    </section>

    <div class="govuk-width-container">
        @if ($backLink)
            <a href="{{ $backLink }}" class="govuk-back-link govuk-!-display-none-print">Back</a>
        @endif

        <main @class(['govuk-main-wrapper', $mainClass]) id="main-content">
            @if ($user?->isDemo())
                <div class="govuk-notification-banner govuk-!-display-none-print" role="region" aria-labelledby="demo-banner-title" data-module="govuk-notification-banner">
                    <div class="govuk-notification-banner__header">
                        <h2 class="govuk-notification-banner__title" id="demo-banner-title">Demo</h2>
                    </div>
                    <div class="govuk-notification-banner__content">
                        <p class="govuk-notification-banner__heading">You're looking around a demo union. Nothing you do here is saved.</p>
                        {{-- A div, not a p: a form can't sit inside a paragraph. --}}
                        <div class="govuk-body govuk-!-margin-bottom-0">
                            <a class="govuk-notification-banner__link" href="{{ route('demo.show') }}">About the demo</a>
                            or
                            <form method="POST" action="{{ route('logout') }}" class="app-inline-form">
                                @csrf
                                <button type="submit" class="app-link-button govuk-notification-banner__link">leave the demo</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="govuk-error-summary" data-module="govuk-error-summary">
                    <div role="alert">
                        <h2 class="govuk-error-summary__title">There is a problem</h2>
                        <div class="govuk-error-summary__body">
                            <ul class="govuk-list govuk-error-summary__list">
                                @foreach ($errors->getMessages() as $field => $messages)
                                    {{-- One item of an array field ("answers.2") links to the field as a whole. --}}
                                    <li><a href="#{{ str_replace('.', '_', preg_replace('/\.\d+$/', '', $field)) }}">{{ $messages[0] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('status') && session('status') !== 'verification-link-sent')
                <div class="govuk-notification-banner govuk-notification-banner--success govuk-!-display-none-print" role="alert" aria-labelledby="status-banner-title" data-module="govuk-notification-banner">
                    <div class="govuk-notification-banner__header">
                        <h2 class="govuk-notification-banner__title" id="status-banner-title">Success</h2>
                    </div>
                    <div class="govuk-notification-banner__content">
                        <p class="govuk-notification-banner__heading">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            @if (session('notice'))
                <div class="govuk-notification-banner govuk-!-display-none-print" role="region" aria-labelledby="notice-banner-title" data-module="govuk-notification-banner">
                    <div class="govuk-notification-banner__header">
                        <h2 class="govuk-notification-banner__title" id="notice-banner-title">Important</h2>
                    </div>
                    <div class="govuk-notification-banner__content">
                        <p class="govuk-notification-banner__heading">{{ session('notice') }}</p>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <footer class="govuk-footer govuk-!-display-none-print">
        <div class="govuk-width-container">
            <div class="govuk-footer__meta">
                <div class="govuk-footer__meta-item govuk-footer__meta-item--grow">
                    <h2 class="govuk-visually-hidden">Support links</h2>
                    <ul class="govuk-footer__inline-list">
                        @if (config('demo.enabled'))
                            <li class="govuk-footer__inline-list-item">
                                <a class="govuk-footer__link" href="{{ route('demo.show') }}">Demo union</a>
                            </li>
                        @endif
                        @if ($user?->contact)
                            <li class="govuk-footer__inline-list-item">
                                <a class="govuk-footer__link" href="{{ route('membership.show') }}">Your membership</a>
                            </li>
                        @endif
                    </ul>
                    <p class="govuk-body-s govuk-!-margin-bottom-0">{{ config('app.name') }} is run by tenants, for tenants. It isn't a government service.</p>
                    <p class="govuk-body-s govuk-!-margin-top-2 govuk-!-margin-bottom-0">{{ config('app.name') }} is developed and maintained by <a class="govuk-footer__link" href="https://islayanderson.co.uk">Islay Anderson</a>.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
