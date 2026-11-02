<h2>1. Name</h2>
<p>The union is called {{ $building->name }} (“the Union”). It represents the tenants of {{ $building->formattedAddress() }} (“the Building”).</p>

<h2>2. Purpose</h2>
<p>The Union exists to:</p>
<ul>
    <li>bring tenants of the Building together to act collectively;</li>
    <li>make demands of the building owner, landlords and their agents;</li>
    <li>hold them to account on repairs, safety, rents and conditions;</li>
    <li>support members who face problems with their tenancy.</li>
</ul>

<h2>3. Membership</h2>
<p>Any tenant living in the Building may join. A tenant becomes a member once {{ $rules->vouchesRequired }} existing members have confirmed in person that they live in the Building.</p>
<p>Vouching begins once {{ $rules->minSignups }} tenants have signed up. Until the Union has {{ $rules->vouchesRequired + 1 }} members, any signed-up tenant may vouch for another.</p>
<p>Membership ends when a member leaves the Building or resigns.</p>

<h2>4. Decisions</h2>
<p>Everyday decisions are made by polls of members. Policies and changes to this constitution are made by referendum.</p>
<p>A referendum is valid if at least {{ $rules->referendumQuorumPercent }}% of members vote, and passes if more than {{ $rules->referendumPassPercent }}% of the votes cast for or against are in favour. Abstentions count towards the quorum but not towards the result.</p>

<h2>5. Roles</h2>
<p>Members may create roles (such as secretary or repairs coordinator) and elect members to them. Roles carry only the responsibilities members give them.</p>

<h2>6. Conduct</h2>
<p>Members treat each other with respect. Members do not share other members' personal details outside the Union without consent.</p>

<h2>7. Amendments</h2>
<p>This constitution, including the numbers in sections 3 and 4, may be changed by referendum.</p>
