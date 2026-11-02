{{-- Fields shared by the new building and join building signup forms. --}}
<x-field name="name" label="Your name" required autocomplete="name" />
<x-field name="email" label="Email" type="email" required autocomplete="email" />
<x-field name="telephone" label="Telephone (optional)" type="tel" autocomplete="tel" />
<x-field name="unit_name" label="Your flat or room" required placeholder="e.g. Flat 4" hint="Tenants who share a flat or room should enter the same name." />
<x-field name="password" label="Password" type="password" required autocomplete="new-password" />
<x-field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
