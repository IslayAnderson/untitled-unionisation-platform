{{-- Numbers are placeholders filled in by App\Support\ConstitutionText, so amending a number updates the text. --}}
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
<p>Any tenant living in the Building may join. A tenant becomes a member once {vouches_required} existing members have confirmed in person that they live in the Building.</p>
<p>Vouching begins once {min_signups} tenants have signed up. Until the Union has more than {vouches_required} members, any signed-up tenant may vouch for another.</p>
<p>Membership ends when a member leaves the Building or resigns.</p>

<h2>4. Decisions</h2>
<p>Everyday decisions are made by polls of members. Policies, roles and changes to this constitution are made by referendum.</p>
<p>Any member may propose a policy, a change to a policy, a role, an election to a role, or a change to this constitution. A referendum is open for {referendum_days} days, and members' votes are secret.</p>
<p>A referendum is valid if at least {referendum_quorum_percent}% of members vote, and passes if more than {referendum_pass_percent}% of the votes cast for or against are in favour. Abstentions count towards the quorum but not towards the result.</p>

<h2>5. Mail</h2>
<p>No member may email anyone on the Union's behalf alone. Mail is signed by the Union, never by the member who wrote it, and must never include personal or private information.</p>
<ul>
    <li><strong>Instant mail</strong> is approved by members picked at random: {instant_mail_approver_percent}% of members are asked, and it is sent once {instant_mail_approval_percent}% of members have approved. It expires after {instant_mail_expiry_hours} hours. Once sent, it is posted as a bulletin.</li>
    <li><strong>Bulletin mail</strong> begins as a bulletin. Its author may ask to send it, and it is sent once {bulletin_mail_approval_percent}% of members have approved. It expires after {bulletin_mail_expiry_hours} hours.</li>
</ul>
<p>The author of a mail cannot approve it. Percentages are rounded up.</p>

<h2>6. Roles</h2>
<p>Members may create roles (such as secretary or repairs coordinator) and elect members to them by referendum. A member may step down from a role at any time, and members may remove them by referendum. Roles carry only the responsibilities members give them.</p>

<h2>7. Conduct</h2>
<p>Members treat each other with respect. Members do not share other members' personal details outside the Union without consent.</p>

<h2>8. Amendments</h2>
<p>This constitution, including the numbers in sections 3, 4 and 5, may be changed by referendum.</p>
