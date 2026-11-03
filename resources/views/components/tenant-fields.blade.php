{{-- Fields shared by the new building and join building signup forms. --}}
<x-field name="name" label="Your name" required autocomplete="name" spellcheck="false" />
<x-field name="email" label="Email address" type="email" required autocomplete="email" spellcheck="false" />
<x-field name="telephone" label="Telephone number (optional)" type="tel" autocomplete="tel" width="20" />
<x-field name="unit_name" label="Your flat or room" required width="10" hint="For example, Flat 4. Tenants who share a flat or room should enter the same name." />
<x-field name="password" label="Create a password" type="password" required autocomplete="new-password" width="20" />
<x-field name="password_confirmation" label="Confirm your password" type="password" required autocomplete="new-password" width="20" />
