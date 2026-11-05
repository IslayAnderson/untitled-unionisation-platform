@php
    $app = config('app.name');
    $email = config('privacy.email');
@endphp

<x-layouts.app title="Privacy policy">
    <div class="govuk-grid-row">
        <div class="govuk-grid-column-two-thirds">
            <h1 class="govuk-heading-xl">Privacy policy</h1>
            <p class="govuk-body-s">Last updated 3 November 2026</p>

            <p class="govuk-body-l">
                This explains what personal data {{ $app }} collects, why, who can see it and how long it's kept, and your
                rights under UK data protection law (the UK GDPR and the Data Protection Act 2018).
            </p>

            <h2 class="govuk-heading-l">Who we are</h2>
            <p class="govuk-body">
                {{ $app }} is developed and run by {{ config('privacy.controller') }}, who is the data controller: the person
                responsible for your personal data on it.
                @if (config('privacy.ico_registration'))
                    We're registered with the Information Commissioner's Office, number {{ config('privacy.ico_registration') }}.
                @endif
            </p>
            <p class="govuk-body">
                For anything about your data, email <a href="mailto:{{ $email }}" class="govuk-link">{{ $email }}</a>.
            </p>
            <p class="govuk-body">
                Each tenants' union on the platform is run by its own members. They decide what the union posts and sends,
                but not how the platform stores or protects your data.
            </p>

            <h2 class="govuk-heading-l">What we collect</h2>

            <h3 class="govuk-heading-m">When you sign up</h3>
            <ul class="govuk-list govuk-list--bullet">
                <li>your name and email address</li>
                <li>your password, which is stored scrambled (hashed) so nobody can read it, including us</li>
                <li>your phone number, if you choose to give it</li>
                <li>your building's address and the name of your flat or room</li>
            </ul>

            <h3 class="govuk-heading-m">When you join and take part</h3>
            <ul class="govuk-list govuk-list--bullet">
                <li>who vouched for you and who you vouched for, and when you became a member</li>
                <li>
                    whether you voted in each referendum and poll, so you can't vote twice, but never how you voted. Votes are
                    added to the totals without any link to you.
                </li>
                <li>roles you're elected to, and whether you left or were removed from the union</li>
                <li>
                    what you write: bulletins, replies, discussions, polls, emails to landlords and your approvals of them,
                    with the images and files you add. Images are compressed and their hidden details, such as the location
                    a photo was taken, are removed.
                </li>
                <li>
                    details of landlords, letting agents and other contacts that members add, and the replies they send to the
                    union's emails
                </li>
            </ul>

            <h3 class="govuk-heading-m">When you use the site</h3>
            <ul class="govuk-list govuk-list--bullet">
                <li>
                    while you're logged in, your IP address and the type of browser you use, kept with your session to keep
                    your account secure
                </li>
                <li>when you last used the site, to the nearest hour, so we don't delete accounts that are still in use</li>
                <li>error logs, which can include the address of the page you were on</li>
                <li>feedback you send us, with your name and email address if you give them</li>
                <li>
                    how many times each of our QR codes is scanned. We record which code and when, never who scanned it.
                </li>
                <li>when someone enters the demo union, so we know how many people look around it. We record the time only, never who.</li>
            </ul>

            <h2 class="govuk-heading-l">Why we use it</h2>
            <p class="govuk-body">We only use your data to run the platform and your union. We never sell it or use it for advertising.</p>
            <table class="govuk-table">
                <caption class="govuk-table__caption govuk-table__caption--s">What we use your data for, and our lawful basis</caption>
                <thead class="govuk-table__head">
                    <tr class="govuk-table__row">
                        <th scope="col" class="govuk-table__header">What for</th>
                        <th scope="col" class="govuk-table__header">Lawful basis</th>
                    </tr>
                </thead>
                <tbody class="govuk-table__body">
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Your account, your union and everything it does: vouching, posts, votes and emails</td>
                        <td class="govuk-table__cell">Providing the service you signed up for (contract)</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Emails about your account and your union, such as votes and approvals</td>
                        <td class="govuk-table__cell">Providing the service you signed up for (contract)</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Keeping accounts secure, preventing abuse and fixing problems</td>
                        <td class="govuk-table__cell">Our legitimate interest in a safe, working platform</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Checking uploaded images against known child abuse images</td>
                        <td class="govuk-table__cell">Our legitimate interest in keeping illegal material off the platform</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Details of landlords and agents that members add</td>
                        <td class="govuk-table__cell">The union's legitimate interest in contacting them about the building</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Reading and replying to feedback you send</td>
                        <td class="govuk-table__cell">Our legitimate interest in improving the platform</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell">Answering requests and complaints about your data</td>
                        <td class="govuk-table__cell">Legal obligation</td>
                    </tr>
                </tbody>
            </table>
            <p class="govuk-body">
                Being in a tenants' union, and how you take part, could say something about your views. We treat it as
                sensitive: it's only shown to members of your own union, and never to your landlord.
            </p>

            <h2 class="govuk-heading-l">Who can see it</h2>
            <ul class="govuk-list govuk-list--bullet">
                <li>
                    <strong>Members of your union</strong> see your name, your flat and what you write. They don't see your email
                    address or phone number.
                </li>
                <li>
                    <strong>Tenants waiting to be vouched for</strong> can only see the vouching bulletin, including what you
                    write there.
                </li>
                <li>
                    <strong>Landlords and agents</strong> receive the union's emails, which are signed by the union and never name
                    the member who wrote them. Images in those emails can be seen by anyone who has the email.
                </li>
                <li>
                    <strong>The platform's administrators</strong> see only counts in the admin area, not names or what anyone
                    wrote. To keep the platform running, they can access the database, and only do so when they need to.
                </li>
                <li>
                    <strong>Service providers</strong> who host and run parts of the platform for us, and only use your data to
                    do that:
                    <ul class="govuk-list govuk-list--bullet">
                        <li>IONOS, which hosts our server and its database</li>
                        <li>Cloudflare, which protects the site, stores and delivers uploaded images and files, and scans images for known child abuse material</li>
                        <li>Geoapify, which looks up addresses when a building is registered. It's sent what you type in the address search, never your name or email.</li>
                    </ul>
                </li>
            </ul>
            <p class="govuk-body">
                Some providers may handle data outside the UK. When they do, they must protect it as UK law requires, for
                example with the UK's International Data Transfer Agreement or Addendum.
            </p>
            <p class="govuk-body">
                We'll only share your data with anyone else if the law requires it, such as a court order, or to report
                child abuse material.
            </p>

            <h2 class="govuk-heading-l" id="cookies">Cookies</h2>
            <p class="govuk-body">
                We only use cookies the site needs to work, which the law allows without asking for your consent. There are
                no analytics, advertising or tracking cookies, and nothing from other websites.
            </p>
            <table class="govuk-table">
                <caption class="govuk-table__caption govuk-table__caption--s">Cookies we use</caption>
                <thead class="govuk-table__head">
                    <tr class="govuk-table__row">
                        <th scope="col" class="govuk-table__header">Name</th>
                        <th scope="col" class="govuk-table__header">What it's for</th>
                        <th scope="col" class="govuk-table__header">Expires</th>
                    </tr>
                </thead>
                <tbody class="govuk-table__body">
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell app-url">{{ config('session.cookie') }}</td>
                        <td class="govuk-table__cell">Keeps you logged in as you move between pages</td>
                        <td class="govuk-table__cell">After {{ config('session.lifetime') / 60 }} hours without activity</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell app-url">XSRF-TOKEN</td>
                        <td class="govuk-table__cell">Stops other websites sending forms as you</td>
                        <td class="govuk-table__cell">After {{ config('session.lifetime') / 60 }} hours without activity</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell app-url">remember_web_…</td>
                        <td class="govuk-table__cell">Keeps you logged in on this device, only if you tick "Remember me" when logging in</td>
                        <td class="govuk-table__cell">When you log out, or after 400 days</td>
                    </tr>
                    <tr class="govuk-table__row">
                        <td class="govuk-table__cell app-url">{{ \App\Http\Controllers\CookieBannerController::COOKIE }}</td>
                        <td class="govuk-table__cell">Remembers that you've hidden the cookie message</td>
                        <td class="govuk-table__cell">1 year</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="govuk-heading-l">How long we keep it</h2>
            <ul class="govuk-list govuk-list--bullet">
                <li>Your account is kept until you delete it, or until we delete it because it's not in use.</li>
                <li>We delete accounts that don't confirm their email within 7 days.</li>
                <li>We may delete accounts that haven't chosen a building and flat after 30 days, or haven't been vouched for after 90 days.</li>
                <li>We may delete unions with no members where nobody has signed up for 90 days.</li>
                <li>We always email you a week before deleting your account or union, and logging in stops it.</li>
                <li>
                    When you delete your account, we delete your details, sessions, vouches, voting records and roles. What you
                    wrote stays in the union's records, shown as written by a former member, because other members rely on it.
                </li>
                <li>
                    Copies of a union's records that we email to members who leave, are removed, or whose union is deleted are
                    kept for 30 days for them to download, then deleted.
                </li>
                <li>Sessions end after 2 hours without activity.</li>
            </ul>

            <h2 class="govuk-heading-l" id="your-rights">Your rights</h2>
            <p class="govuk-body">You have the right to:</p>
            <ul class="govuk-list govuk-list--bullet">
                <li>get a copy of the personal data we hold about you</li>
                <li>have it corrected if it's wrong</li>
                <li>have it deleted. You can delete your account yourself from your membership page.</li>
                <li>have us limit how we use it, or object to us using it</li>
                <li>get your data in a format you can take elsewhere</li>
            </ul>
            <p class="govuk-body">
                To use any of these rights, email <a href="mailto:{{ $email }}" class="govuk-link">{{ $email }}</a>. We'll
                reply within one month. It's free.
            </p>

            <h2 class="govuk-heading-l" id="complaints">Complaints</h2>
            <p class="govuk-body">
                If you think we've mishandled your data, please tell us first so we can put it right.
            </p>
            <p class="govuk-body">
                Email <a href="mailto:{{ $email }}" class="govuk-link">{{ $email }}</a>. We'll reply within one month.
            </p>
            <p class="govuk-body">
                You can also complain to the Information Commissioner's Office (ICO), the UK's data protection regulator, at
                <a href="https://ico.org.uk/make-a-complaint/" class="govuk-link" rel="noreferrer noopener" target="_blank">ico.org.uk/make-a-complaint (opens in new tab)</a>
                or by phone on 0303 123 1113.
            </p>

            <h2 class="govuk-heading-l">Changes to this policy</h2>
            <p class="govuk-body">
                If we change how we use your data, we'll update this page and the date at the top. If a change affects you
                significantly, we'll email you first.
            </p>
        </div>
    </div>
</x-layouts.app>
