<span class="govuk-caption-l">Admin</span>
<h1 class="govuk-heading-l">{{ $title }}</h1>
<nav class="app-sub-navigation" aria-label="Admin">
    <ul class="app-sub-navigation__list">
        @foreach (['Unions' => ['admin.unions.index', 'admin.unions.*'], 'Clean up' => ['admin.cleanup.index', 'admin.cleanup.*'], 'Printables' => ['admin.printables', 'admin.printables']] as $text => [$route, $pattern])
            <li>
                <a href="{{ route($route) }}" class="app-sub-navigation__link" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $text }}</a>
            </li>
        @endforeach
    </ul>
</nav>
