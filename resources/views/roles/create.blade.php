<x-layouts.app title="Propose a role" :back-link="route('roles.index')">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-l">Propose a role</h1>
            <p class="govuk-body">
                Creating a role goes to a referendum. Once it's created, members can propose electing someone to it.
            </p>

            <form method="POST" action="{{ route('roles.store') }}" novalidate>
                @csrf
                <x-field name="title" label="Role" required maxlength="100" hint="For example, repairs coordinator." />
                <x-textarea name="description" label="What it's for (optional)" rows="2" maxlength="2000" />
                <x-textarea name="responsibilities" label="Responsibilities" rows="5"
                    hint="One per line, up to 15. For example: Keep the repairs log up to date." />
                <x-textarea name="reason" label="Why? (optional)" rows="3" maxlength="5000" hint="Starts the discussion. Members see it next to the vote." />
                <x-button>Put it to a referendum</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
