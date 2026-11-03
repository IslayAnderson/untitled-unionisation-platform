<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\ReferendumAnswer;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Landlord;
use App\Models\LandlordType;
use App\Models\MailingList;
use App\Models\Policy;
use App\Models\Poll;
use App\Models\Referendum;
use App\Models\Role;
use App\Models\Thread;
use App\Models\UnionMail;
use App\Models\Unit;
use App\Models\User;
use App\Support\BoilerplateConstitution;
use App\Support\ConstitutionRules;
use App\Support\RichText;
use App\Support\VouchingBulletin;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Builds the public demo union, Larkspur House: 252 flats in sections A to D,
 * each with 7 floors of 9 flats (C503 is section C, floor 5, flat 3).
 *
 * Its history is played through the real services with the clock wound back,
 * so votes, approvals and amendments are all consistent. No email is sent.
 * Everything is dated relative to when it's built, so open votes stay open;
 * `demo:reset` rebuilds it daily. See docs/architecture/demo.md.
 */
class DemoUnion
{
    private Building $building;

    private Carbon $now;

    /** @var array<string, Contact> Tenants by flat */
    private array $tenants = [];

    /** @var array<string, mixed> Things later events refer back to */
    private array $refs = [];

    /** @var list<array{Carbon, int, Closure}> When, order added, what */
    private array $events = [];

    private int $sequence = 0;

    public function __construct(
        private Cleanup $cleanup,
        private Vouching $vouching,
        private Referendums $referendums,
        private PollVoting $polls,
        private MailApprovals $mail,
    ) {}

    /**
     * Remove any demo union.
     */
    public function remove(): int
    {
        $buildings = Building::where('is_demo', true)->get();
        $buildings->each(fn (Building $building) => $this->cleanup->purge($building));

        return $buildings->count();
    }

    public function build(): Building
    {
        $this->remove();

        // Nothing the demo's history does should reach anyone.
        Notification::fake();
        Mail::fake();
        mt_srand(503);

        $this->now = Carbon::now();

        try {
            $this->schedule();
            $this->play();
        } finally {
            Carbon::setTestNow();
        }

        $this->referendums->closeDue($this->building);

        return $this->building;
    }

    /**
     * Run every event in time order, counting referendums and expiring mail as
     * the clock passes their deadlines. Events can schedule later events.
     */
    private function play(): void
    {
        while ($this->events) {
            usort($this->events, fn (array $a, array $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
            [$at, , $event] = array_shift($this->events);

            Carbon::setTestNow($at);

            if (isset($this->building)) {
                $this->referendums->closeDue($this->building);
                $this->mail->expireOverdue();
            }

            $event();
        }
    }

    /**
     * Schedule an event $daysAgo days before now, at a time of day ("19:30").
     */
    private function on(int $daysAgo, string $time, Closure $event): void
    {
        $this->events[] = [$this->now->copy()->subDays($daysAgo)->setTimeFromTimeString($time), ++$this->sequence, $event];
    }

    private function schedule(): void
    {
        $this->founding();
        $this->residents();
        $this->landlords();
        $this->bulletins();
        $this->discussions();
        $this->governance();
        $this->correspondence();
        $this->stillOpen();
    }

    private function founding(): void
    {
        $this->on(150, '20:10', function () {
            $this->building = Building::forceCreate([
                'name' => 'Larkspur House Tenants\' Union',
                'address_line_1' => 'Larkspur House, 1 Example Road',
                'city' => 'London',
                'postcode' => 'EX1 2MP',
                'address_provider' => 'demo',
                'address_provider_id' => 'larkspur-house',
                'signup_token' => Str::random(48),
                'is_demo' => true,
            ]);

            BoilerplateConstitution::createFor($this->building);
            VouchingBulletin::createFor($this->building);

            foreach (['A', 'B', 'C', 'D'] as $section) {
                foreach (range(1, 7) as $floor) {
                    foreach (range(1, 9) as $flat) {
                        Unit::findOrCreateInBuilding($this->building, "{$section}{$floor}0{$flat}");
                    }
                }
            }

            $this->join('Priya Raman', 'A204');
        });

        $this->on(150, '21:05', fn () => $this->join('Marcus Bell', 'B601'));
        $this->on(149, '08:40', fn () => $this->join('Joanna Kowalska', 'D307'));

        // The first three vouch for each other while the union is starting.
        $this->on(148, '19:00', function () {
            $this->vouch('A204', 'B601');
            $this->vouch('D307', 'B601');
            $this->vouch('B601', 'A204');
            $this->vouch('D307', 'A204');
            $this->vouch('A204', 'D307');
            $this->vouch('B601', 'D307');
        });
    }

    private function residents(): void
    {
        // Name, flat, days ago they signed up. Members are vouched for by two
        // neighbours a few days later; the last few are still waiting.
        $members = [
            ['Sam Taylor', 'C503', 146], ['Hannah Brooks', 'A401', 145], ['Kwame Mensah', 'B402', 144],
            ['Ama Boateng', 'A706', 143], ['Tom Fitzgerald', 'D502', 141], ['Yusuf Ahmed', 'C209', 140],
            ['Oliver Grant', 'C705', 139], ['Leah Goldberg', 'B108', 138], ['Aisha Khan', 'A309', 137],
            ['Ben Carter', 'B205', 136], ['Carmen Diaz', 'C102', 135], ['David Chen', 'D603', 134],
            ['Elena Petrova', 'A507', 133], ['Femi Adeyemi', 'B704', 131], ['Grace O\'Neill', 'C401', 130],
            ['Harry Walsh', 'D105', 128], ['Isabel Santos', 'A608', 127], ['Jamal Wright', 'B306', 126],
            ['Katie Murphy', 'C608', 124], ['Liam Byrne', 'D409', 122], ['Maria Rossi', 'A103', 121],
            ['Nadia Hussain', 'B503', 119], ['Owen Lloyd', 'C306', 118], ['Phoebe Clarke', 'D701', 116],
            ['Rahul Mehta', 'D204', 115], ['Sophie Turner', 'A305', 113], ['Tariq Aziz', 'B607', 111],
            ['Ursula Novak', 'C704', 110], ['Victor Silva', 'D406', 108], ['Wendy Ng', 'A702', 106],
            ['Xavier Dubois', 'B209', 104], ['Yasmin Ali', 'C507', 102], ['Zainab Bello', 'D608', 100],
            ['Adam Price', 'A503', 98], ['Bethany Cole', 'B101', 96], ['Chidi Okafor', 'C603', 93],
            ['Dominika Nowak', 'D302', 91], ['Ethan Hughes', 'A408', 88], ['Farah Siddiqui', 'B509', 86],
            ['George Evans', 'C208', 83], ['Hana Sato', 'D505', 81], ['Ibrahim Diallo', 'A601', 78],
            ['Jess Morgan', 'B702', 76], ['Kofi Asante', 'C109', 73], ['Laura Bianchi', 'D108', 71],
            ['Mohammed Rahman', 'A209', 68], ['Niamh Kelly', 'B405', 66], ['Oscar Lindqvist', 'C405', 63],
            ['Patrick Doyle', 'D601', 61], ['Rosa Moreno', 'A106', 58], ['Sanjay Patel', 'B303', 55],
            ['Tess Robinson', 'C702', 52], ['Uche Nwosu', 'D403', 49], ['Vanessa Reid', 'A705', 46],
            ['Will Foster', 'B608', 43], ['Diana Popescu', 'C506', 40], ['Eilidh Fraser', 'D706', 37],
            ['Finn O\'Brien', 'A402', 34], ['Gabriela Costa', 'B505', 31], ['Hiroshi Tanaka', 'C303', 28],
            ['Imogen Shaw', 'D209', 25], ['Jakub Wiśniewski', 'A604', 22], ['Kemi Adebayo', 'B706', 19],
            ['Lucy Hall', 'C107', 16], ['Mateo García', 'D504', 14], ['Nina Johansson', 'A307', 12],
        ];

        foreach ($members as $index => [$name, $flat, $daysAgo]) {
            $this->on($daysAgo, sprintf('%02d:%02d', 18 + $index % 4, ($index * 17) % 60), fn () => $this->join($name, $flat));
            $this->on($daysAgo - 1 - $index % 3, '19:45', function () use ($flat) {
                foreach ($this->members()->reject(fn (Contact $member) => $member->unit->name === $flat)->shuffle()->take(2) as $voucher) {
                    $this->vouch($voucher->unit->name, $flat);
                }
            });
        }

        $waiting = [
            ['Mei Lin', 'D704', 9, 1], ['Rhys Jenkins', 'A102', 6, 1], ['Fatima Noor', 'C301', 4, 0],
            ['Daniel Okoro', 'B501', 3, 0], ['Chloé Martin', 'D201', 1, 0],
        ];

        foreach ($waiting as [$name, $flat, $daysAgo, $vouches]) {
            $this->on($daysAgo, '20:15', fn () => $this->join($name, $flat));

            if ($vouches) {
                $this->on($daysAgo - 2, '18:30', fn () => $this->vouch('C503', $flat));
            }
        }

        $this->on(8, '21:00', fn () => $this->reply($this->building->vouchingBulletin, 'D704', 'Hi, I\'m in D704, moved in last month. I\'m free most evenings after 7. Happy to meet in the D lobby.'));
        $this->on(7, '08:10', fn () => $this->reply($this->building->vouchingBulletin, 'C503', 'Welcome! I\'ve vouched for you already. Can someone else in D meet Mei this week?'));
        $this->on(5, '19:20', fn () => $this->reply($this->building->vouchingBulletin, 'A102', 'A102 here. I work shifts, but weekends are good. I\'ll be at the courtyard get-together.'));
        $this->on(3, '22:05', fn () => $this->reply($this->building->vouchingBulletin, 'C301', 'Just signed up. Is anyone in C around on Sunday morning?'));
        $this->on(2, '09:30', fn () => $this->reply($this->building->vouchingBulletin, 'C507', 'Fatima, I\'m on floor 5 of C. Knock on C507 on Sunday any time before noon.'));
    }

    private function landlords(): void
    {
        $this->on(135, '21:30', function () {
            $type = fn (string $name) => ContactType::where('type', $name)->value('id');

            $freeholder = $this->landlord('Larkspur Freehold Ltd', 'Company', '22 Example Square, London, EX1 9ZZ', '12345678');
            $agent = $this->landlord('Brightwell Property Management', 'Company', '4 Sample Street, London, EX2 4AB', '87654321');
            $lettings = $this->landlord('Keystone Lettings', 'Company', '118 Example High Street, London, EX1 3CD', null);
            $this->building->update(['building_owner_id' => $freeholder->id]);

            $contacts = [
                'daniel' => [$freeholder, 'Daniel Hart', 'Landlord', 'daniel.hart@larkspur-freehold.example', '020 7946 0101'],
                'rachel' => [$agent, 'Rachel Moore', 'Building management', 'rachel.moore@brightwell.example', '020 7946 0202'],
                'repairs' => [$agent, 'Brightwell Repairs Desk', 'Building management', 'repairs@brightwell.example', '020 7946 0203'],
                'owen' => [$lettings, 'Owen Price', 'Letting agent', 'owen@keystone-lettings.example', '020 7946 0303'],
            ];

            foreach ($contacts as $key => [$landlord, $name, $contactType, $email, $telephone]) {
                $this->refs["contact.{$key}"] = $this->building->contacts()->create([
                    'name' => $name,
                    'type_id' => $type($contactType),
                    'landlord_id' => $landlord->id,
                    'email' => $email,
                    'telephone' => $telephone,
                ]);
            }

            $freeholder->update(['primary_contact_id' => $this->refs['contact.daniel']->id]);
            $agent->update(['primary_contact_id' => $this->refs['contact.rachel']->id]);
            $lettings->update(['primary_contact_id' => $this->refs['contact.owen']->id]);

            $lists = [
                'repairs' => ['Managing agent: repairs', $agent, ['rachel', 'repairs']],
                'freeholder' => ['Freeholder', $freeholder, ['daniel']],
                'all' => ['Everyone we deal with', null, ['daniel', 'rachel', 'repairs', 'owen']],
                'lettings' => ['Letting agent', $lettings, ['owen']],
            ];

            foreach ($lists as $key => [$name, $landlord, $members]) {
                $list = MailingList::create(['name' => $name, 'building_id' => $this->building->id, 'landlord_id' => $landlord?->id]);
                $list->contacts()->attach(array_map(fn (string $contact) => $this->refs["contact.{$contact}"]->id, $members));
                $this->refs["list.{$key}"] = $list;
            }
        });
    }

    private function bulletins(): void
    {
        $this->on(147, '09:15', function () {
            $bulletin = $this->bulletin('A204', 'Welcome to the Larkspur House Tenants\' Union', <<<'HTML'
                <p>Thank you for signing up. This is our union's private noticeboard: only members can read it.</p>
                <p>We're tenants of all four sections, A to D, and we've come together because the same problems keep coming back: the lifts, the heating, repairs that take months, and rent rises we have no say in.</p>
                <h2>How it works</h2>
                <ul><li>Neighbours become members once two members have met them and vouched for them in person.</li><li>Anything we send to Brightwell or the freeholder is approved by members first and signed by the union, never by one of us.</li><li>Big decisions are made by secret ballot.</li></ul>
                <p>If you know a neighbour who'd want to join, print a flyer from the dashboard and put it through their door.</p>
                HTML);
            $bulletin->update(['pinned_at' => now()]);
        });

        $this->on(146, '20:40', function () {
            $bulletin = $this->bulletin('B601', 'Heating and hot water: log every outage', <<<'HTML'
                <p>Brightwell keep telling us outages are "isolated incidents". Let's prove they aren't.</p>
                <p>Every time your heating or hot water goes off, reply here with your flat, the date and how long it lasted. Even if it's back after an hour.</p>
                HTML);
            $poll = $this->poll($bulletin, 'How often has your heating or hot water failed this winter?', ['Never', 'Once or twice', '3 to 5 times', 'More than 5 times'], 14);
            $this->on(133, '19:00', fn () => $this->pollVotes($poll, [2, 9, 6, 4]));
            $this->on(145, '07:30', fn () => $this->reply($bulletin, 'D307', 'D307: no hot water Monday morning until about 2pm.'));
            $this->on(142, '22:10', fn () => $this->reply($bulletin, 'A401', 'A401: radiators cold all weekend. Third time since October.'));
            $this->on(143, '08:05', fn () => $this->reply($bulletin, 'C503', 'C503: same as A401, Saturday to Monday.'));
        });

        $this->on(133, '21:20', fn () => $this->bulletin('A204', 'Notes from our first meeting', <<<'HTML'
            <p>Thanks to the 19 of you who came to the courtyard on Thursday. What we agreed:</p>
            <ol><li><strong>Repairs first.</strong> Marcus will keep a log of outstanding repairs, and we'll write to Brightwell with the worst ones.</li><li><strong>The section C lift</strong> has been out for most of the month. We'll email Brightwell about it this week.</li><li><strong>Rent.</strong> Several of us have heard increases are coming. Joanna will find out what notice they have to give.</li><li><strong>Roles.</strong> We'll propose a repairs coordinator and a representative for each section, so every floor has someone to talk to.</li></ol>
            <p>Next meeting in about a month. Bring a neighbour.</p>
            HTML));

        $this->on(118, '18:50', function () {
            $bulletin = $this->bulletin('D307', 'Rent increase letters: don\'t sign anything yet', <<<'HTML'
                <p>Keystone Lettings have started sending letters asking tenants to sign a new tenancy at a higher rent.</p>
                <p><strong>You don't have to sign.</strong> If you're on a periodic tenancy, they have to give proper notice of an increase, and you can challenge it. Talk to us before you reply.</p>
                HTML);
            $poll = $this->poll($bulletin, 'Have you had a rent increase letter?', ['Yes', 'No', 'Not sure'], 10);
            $this->on(109, '20:00', fn () => $this->pollVotes($poll, [17, 9, 3]));
            $this->on(117, '07:55', fn () => $this->reply($bulletin, 'B205', 'Got mine yesterday: 12%. They want it back within 14 days.'));
            $this->on(117, '12:40', fn () => $this->reply($bulletin, 'D603', 'Same here, 11.5%. Not signing.'));
        });

        $this->on(103, '21:00', function () {
            $this->refs['bulletin.rent'] = $this->bulletin('D307', 'Our response to the proposed rent increases', <<<'HTML'
                <p>To Keystone Lettings, Brightwell Property Management and Larkspur Freehold Ltd,</p>
                <p>Many tenants of Larkspur House have received letters proposing rent increases of 11 to 12%, with a request to sign new tenancy agreements within 14 days.</p>
                <p>We don't accept increases of this size while basic repairs remain outstanding: the section C lift has failed repeatedly, and tenants across the building have recorded heating and hot water outages all winter.</p>
                <p>We ask you to withdraw the letters and meet the union to discuss rents and a timetable for repairs.</p>
                HTML);
        });

        $this->on(90, '19:30', function () {
            $bulletin = $this->bulletin('A401', 'Bin store and recycling', <<<'HTML'
                <p>The council now collects our recycling on Tuesdays instead of Thursdays. Please don't leave bags outside the bin store on the weekend: the foxes get to them.</p>
                HTML);
            $this->on(89, '08:20', fn () => $this->reply($bulletin, 'D105', 'Can we ask Brightwell for a second bin store key for D? Ours has been broken for weeks.'));
        });

        $this->on(70, '20:05', fn () => $this->bulletin('A204', 'Know your rights: rent increases', <<<'HTML'
            <p>A short guide, after lots of questions at the last meeting. This isn't legal advice: if you're unsure, talk to Shelter or Citizens Advice.</p>
            <ul><li>If your fixed term has ended, your landlord usually has to use a formal notice to raise the rent, with at least a month's notice.</li><li>You can challenge a notice at a tribunal before it takes effect.</li><li>Signing a new tenancy agreement means agreeing to the new rent.</li></ul>
            <p>Our policy on rent increases is on the Policies page.</p>
            HTML));

        $this->on(58, '18:15', fn () => $this->bulletin('B402', 'Stepping down as section B rep', <<<'HTML'
            <p>I'm moving in with my partner across town at the end of next month, so I've stepped down as section B representative. Leah has offered to stand, and Priya has proposed her.</p>
            <p>Thank you all. Keep going!</p>
            HTML));

        $this->on(45, '21:40', fn () => $this->bulletin('B601', 'Repairs log: what\'s fixed and what isn\'t', <<<'HTML'
            <p><strong>Fixed:</strong> section C lift (after 7 weeks), D bin store lock, B lobby lights.</p>
            <p><strong>Still waiting:</strong> damp in D-section bathrooms (reported 3 months ago), A-section intercom, communal heating pump in the basement (temporary fix only), fire door closers on floors 4 to 6 of C.</p>
            <p>Every fix on this list happened after we wrote to Brightwell as a union. Keep reporting.</p>
            HTML));

        $this->on(33, '19:10', function () {
            $bulletin = $this->bulletin('A706', 'Damp and mould survey', <<<'HTML'
                <p>We want to show Brightwell how widespread damp is before the winter. Please answer the poll, even if you have no damp at all, so we know the true picture.</p>
                HTML);
            $poll = $this->poll($bulletin, 'Where do you have damp or mould?', ['Bathroom', 'Bedroom', 'Kitchen', 'Living room', 'Nowhere'], 14, true);
            $this->on(20, '20:00', fn () => $this->pollVotes($poll, [24, 15, 9, 6, 14]));
            $this->on(32, '09:00', fn () => $this->reply($bulletin, 'D108', 'Black mould behind the wardrobe in the bedroom. Photos sent to Brightwell twice.'));
        });

        $this->on(28, '20:30', function () {
            $bulletin = $this->bulletin('D502', 'Parcels going missing from the lobby', <<<'HTML'
                <p>Three parcels have gone from the D lobby this month. If you can, have things delivered to a locker or a neighbour who's in.</p>
                HTML);
            $this->on(27, '18:45', fn () => $this->reply($bulletin, 'D701', 'Mine too, a pair of shoes. The front door doesn\'t close properly, so anyone can walk in.'));
        });

        $this->on(15, '21:15', fn () => $this->bulletin('A204', 'Major works notice: roof and windows', <<<'HTML'
            <p>The freeholder has sent a notice of intention to carry out major works to the roof and windows. Leaseholders will be charged, and tenants will live through the works.</p>
            <p>We have 30 days to send observations. Join the discussion on what to ask for, and look out for a draft letter to approve.</p>
            HTML));

        $this->on(9, '19:00', function () {
            $bulletin = $this->bulletin('A401', 'Summer get-together in the courtyard', <<<'HTML'
                <p>Food, music and a chance to meet the neighbours you only see in the lift. Bring something to share if you can. Everyone in the building is welcome, members or not.</p>
                HTML);
            $poll = $this->poll($bulletin, 'Which day works for the get-together?', ['Saturday afternoon', 'Sunday afternoon', 'Friday evening'], -5);
            $this->on(7, '20:00', fn () => $this->pollVotes($poll, [14, 21, 6], vote: [1]));
        });

        $this->on(4, '20:20', function () {
            $bulletin = $this->bulletin('A204', 'General meeting: which evening?', <<<'HTML'
                <p>We'll hold a general meeting before we reply to the major works notice. Which evening suits you best?</p>
                HTML);
            $poll = $this->poll($bulletin, 'Which evening should we meet?', ['Tuesday', 'Wednesday', 'Thursday'], -6);
            $this->on(2, '21:00', fn () => $this->pollVotes($poll, [9, 13, 11]));
        });
    }

    private function discussions(): void
    {
        $threads = [
            [131, 'C503', 'Section C lift: keep a log here', 'The C lift was out again from Friday to Tuesday. Please add every time it breaks, so we have dates when we write to Brightwell.', [
                [130, 'C705', 'Stuck between floors 3 and 4 on Sunday for 20 minutes. The alarm went to a call centre that didn\'t know where we were.'],
                [127, 'C401', 'Out this morning too. I carried my buggy down five floors.'],
                [126, 'C503', 'Thanks both. I\'ve put the dates in our email to Brightwell. Their replies are on its bulletin.'],
            ]],
            [125, 'C209', 'Window cleaning: are we paying for it?', 'Our service charge breakdown has £4,200 a year for window cleaning. Has anyone ever seen the windows cleaned?', [
                [124, 'A507', 'Not once in three years.'],
                [123, 'B601', 'Let\'s add it to the list of questions for Brightwell. They have to show us invoices if we ask.'],
            ]],
            [110, 'B108', 'Noise from the ground floor gym', 'The new gym under section B plays music from 6am. Is it just me?', [
                [109, 'B205', 'Not just you. The floor shakes when they drop weights.'],
                [108, 'B205', 'The lease says commercial units can\'t cause a nuisance to residents. Worth raising with the freeholder.'],
            ]],
            [80, 'D502', 'Should we contact the local councillor?', 'A councillor could put pressure on Brightwell about the damp. Thoughts?', [
                [79, 'A204', 'Good idea, but let\'s agree what we\'re asking for first, so it comes from the union.'],
                [78, 'D307', 'Agreed. Maybe after the damp survey?'],
            ]],
            [35, 'C503', 'Is your heating working again?', 'Brightwell say the basement pump was replaced last week. Is everyone\'s heating back?', [
                [34, 'C102', 'Yes, finally.'],
                [34, 'D409', 'Still lukewarm on floor 4 of D.'],
                [33, 'B601', 'I\'ll add D4 to the repairs log.'],
            ]],
            [12, 'B601', 'Major works: what should we ask for?', 'Here\'s a start for our observations on the roof and windows works. Add yours.', [
                [11, 'A706', 'Ask for the windows in D to be done first: that\'s where the damp is worst.'],
                [11, 'C209', 'Three quotes, and one from a contractor we nominate. We\'re allowed to.'],
                [10, 'A204', 'Thanks all. I\'ll put a draft letter up for approval this week.'],
            ]],
            [5, 'D204', 'Intercom in section D', 'The D intercom has been down for a fortnight. Delivery drivers can\'t get in. Has anyone reported it?', [
                [5, 'D701', 'I reported it to the repairs desk last Monday. Reference number BW-20931.'],
                [4, 'B601', 'Added to the log. If it isn\'t fixed by Friday we\'ll chase it as the union.'],
            ]],
        ];

        foreach ($threads as [$daysAgo, $author, $title, $text, $replies]) {
            $this->on($daysAgo, '20:50', function () use ($author, $title, $text, $replies) {
                $thread = Thread::create([
                    'building_id' => $this->building->id,
                    'author_id' => $this->tenant($author)->id,
                    'title' => $title,
                    'rich_text' => RichText::fromPlainText($text),
                    'published_at' => now(),
                ]);

                foreach ($replies as $index => [$replyDaysAgo, $replyAuthor, $reply]) {
                    $this->on($replyDaysAgo, sprintf('%02d:%02d', 8 + $index * 5, 10 + $index * 13), fn () => $this->reply($thread, $replyAuthor, $reply));
                }
            });
        }
    }

    private function governance(): void
    {
        $this->on(130, '21:10', function () {
            $this->refs['referendum.rota'] = $this->referendums->proposePolicy($this->tenant('A401'), 'Bin store volunteer rota', 'The bin store is a mess every weekend. A rota of volunteers would keep it tidy until we can get Brightwell to do their job.', <<<'HTML'
                <p>Members can volunteer for a week on the bin store rota. The volunteer checks the bin store on Saturday and Sunday evening, and reports overflowing bins and fly-tipping to the repairs desk.</p>
                HTML);
            $this->on(117, '20:00', fn () => $this->votes($this->refs['referendum.rota'], 0.7, 0.8, 0.15));
        });

        $this->on(122, '20:30', function () {
            $referendum = $this->referendums->proposePolicy($this->tenant('A204'), 'Repairs we expect', 'So we have something to hold Brightwell to, and so every member knows when to escalate.', <<<'HTML'
                <p>The union expects Brightwell Property Management to:</p>
                <ul><li>attend emergencies (no heating or hot water, a lift out of service, a leak, a broken front door) within <strong>48 hours</strong>;</li><li>fix other repairs within <strong>28 days</strong>;</li><li>give every repair a reference number and tell tenants when it's done.</li></ul>
                <p>When a repair misses these times, the repairs coordinator raises it with Brightwell on behalf of the union.</p>
                HTML);
            $this->refs['referendum.repairs'] = $referendum;
            $this->on(109, '19:00', fn () => $this->votes($referendum, 0.75, 0.92, 0.05));
            $this->on(121, '08:30', fn () => $this->reply($referendum->thread, 'C209', 'Should "lift out of service" be 24 hours? Some of us can\'t manage the stairs.'));
            $this->on(121, '12:15', fn () => $this->reply($referendum->thread, 'A204', 'Good point. Let\'s start with 48 and tighten it once Brightwell are used to being held to something.'));
        });

        $this->on(116, '21:00', function () {
            $referendum = $this->referendums->proposeRole($this->tenant('B601'), 'Repairs coordinator', 'Keeps the union\'s repairs log and chases Brightwell when repairs are late.', [
                'Keep the repairs log up to date',
                'Chase late repairs with Brightwell on behalf of the union',
                'Report on repairs at general meetings',
            ], 'Someone needs to own the repairs log so nothing slips through.');
            $this->on(103, '19:30', fn () => $this->votes($referendum, 0.72, 0.95, 0.03));
        });

        $this->on(112, '20:45', function () {
            $referendum = $this->referendums->proposeRole($this->tenant('A204'), 'Section representative', 'The first point of contact for tenants in their section.', [
                'Welcome new tenants in the section and tell them about the union',
                'Meet neighbours to vouch for them',
                'Pass on problems in the section to the right member',
            ], 'With 252 flats, every section needs someone people know.');
            $this->on(99, '19:30', fn () => $this->votes($referendum, 0.68, 0.9, 0.08));
        });

        $this->on(100, '21:30', function () {
            $coordinator = Role::where('building_id', $this->building->id)->where('title', 'Repairs coordinator')->sole();
            $referendum = $this->referendums->proposeElection($this->tenant('A204'), $coordinator, $this->tenant('B601'), 'Marcus has kept the repairs log since our first meeting.');
            $this->on(87, '19:00', fn () => $this->votes($referendum, 0.7, 0.97, 0.03));
        });

        $this->on(96, '21:00', function () {
            $rep = Role::where('building_id', $this->building->id)->where('title', 'Section representative')->sole();

            foreach (['A204' => 'D307', 'B402' => 'B601', 'C503' => 'A204', 'D307' => 'C503'] as $candidate => $proposer) {
                $referendum = $this->referendums->proposeElection($this->tenant($proposer), $rep, $this->tenant($candidate), "{$this->tenant($candidate)->name} has offered to represent section {$candidate[0]}.");
                $this->on(83, '19:00', fn () => $this->votes($referendum, 0.66, 0.94, 0.04));
            }
        });

        $this->on(95, '20:15', function () {
            $current = $this->building->fresh()->currentConstitution;
            $rules = ConstitutionRules::fromArray([...$current->rules, 'referendum_days' => 10, 'instant_mail_expiry_hours' => 72]);
            $text = str_replace('<h2>8. Amendments</h2>', <<<'HTML'
                <h2>8. General meetings</h2>
                <p>The Union holds a general meeting of members at least every three months. Any member may ask for one, and members are told of it at least seven days before.</p>
                <h2>9. Amendments</h2>
                HTML, $current->rich_text);

            $referendum = $this->referendums->proposeConstitution($this->tenant('D307'), 'Shorter referendums, longer instant mail, and general meetings', 'Two weeks is too long when Brightwell give us 14 days to respond, and 48 hours isn\'t long enough for people on night shifts to approve urgent mail. This also commits us to regular general meetings.', $text, $rules);
            $this->on(82, '20:00', fn () => $this->votes($referendum, 0.71, 0.88, 0.06));
            $this->on(94, '09:40', fn () => $this->reply($referendum->thread, 'C705', 'Makes sense. Ten days still gives everyone a weekend to vote.'));
        });

        $this->on(92, '19:45', function () {
            $referendum = $this->referendums->proposePolicy($this->tenant('D307'), 'Rent increases: our position', 'So everyone knows what to do when a rent increase letter arrives.', <<<'HTML'
                <p>The union opposes any rent increase above inflation while repairs are outstanding.</p>
                <ul><li>Members who receive a rent increase letter or a new tenancy to sign should share it with the union before responding.</li><li>The union will challenge increases together where members agree.</li><li>No member is expected to sign a new agreement under pressure of a deadline set by the letting agent.</li></ul>
                HTML);
            $this->refs['policy.rent'] = $referendum->referendable;
            $this->on(79, '20:30', fn () => $this->votes($referendum, 0.74, 0.93, 0.02));
        });

        $this->on(84, '21:15', function () {
            $referendum = $this->referendums->proposePolicy($this->tenant('D502'), 'No parcels left in the lobby', 'Parcels keep being stolen. If couriers can\'t leave them in the lobby, they can\'t be taken.', <<<'HTML'
                <p>The union asks Brightwell to put up signs telling couriers not to leave parcels in the lobbies, and members agree not to ask for lobby deliveries.</p>
                HTML);
            $this->on(71, '19:50', fn () => $this->votes($referendum, 0.62, 0.3, 0.1));
            $this->on(83, '10:20', fn () => $this->reply($referendum->thread, 'A608', 'I work days and there\'s nowhere else for parcels to go. Fix the front door instead?'));
            $this->on(83, '19:05', fn () => $this->reply($referendum->thread, 'D701', 'Agree with Isabel. The door is the real problem.'));
        });

        $this->on(78, '20:40', function () {
            $referendum = $this->referendums->proposeRole($this->tenant('A401'), 'Social secretary', 'Organises social events for the building.', ['Organise at least two social events a year'], 'We\'re a union, but we\'re also neighbours.');
            $this->on(69, '20:00', fn () => $this->votes($referendum, 0.21, 0.8, 0.1, you: false));
        });

        $this->on(74, '20:00', function () {
            $original = Policy::where('building_id', $this->building->id)->where('title', 'Repairs we expect')->sole();
            $referendum = $this->referendums->proposePolicy($this->tenant('B601'), 'Repairs we expect', 'Brightwell are now mostly meeting 48 hours. Lifts and heating should be 24 hours, as Yusuf suggested.', <<<'HTML'
                <p>The union expects Brightwell Property Management to:</p>
                <ul><li>attend to a lift out of service, or no heating or hot water, within <strong>24 hours</strong>;</li><li>attend other emergencies (a leak, a broken front door) within <strong>48 hours</strong>;</li><li>fix other repairs within <strong>28 days</strong>;</li><li>give every repair a reference number and tell tenants when it's done.</li></ul>
                <p>When a repair misses these times, the repairs coordinator raises it with Brightwell on behalf of the union.</p>
                HTML, $original);
            $this->on(65, '19:40', fn () => $this->votes($referendum, 0.69, 0.91, 0.04));
        });

        $this->on(66, '21:20', function () {
            $referendum = $this->referendums->proposeRepeal($this->tenant('C209'), $this->refs['policy.rent']->fresh(), 'Keystone have withdrawn the letters, so we may not need this any more.');
            $this->on(65, '18:30', fn () => $this->reply($referendum->thread, 'D307', 'They withdrew them for now. They\'ll be back next year, and the policy costs us nothing.'));
            $this->on(63, '09:10', fn () => $this->referendums->withdraw($this->tenant('C209'), $referendum));
            $this->on(63, '09:12', fn () => $this->reply($referendum->thread, 'C209', 'Fair enough, I\'ve withdrawn it.'));
        });

        $this->on(58, '18:00', fn () => $this->referendums->stepDown($this->tenant('B402'), Role::where('building_id', $this->building->id)->where('title', 'Section representative')->sole()));

        $this->on(56, '20:30', function () {
            $rep = Role::where('building_id', $this->building->id)->where('title', 'Section representative')->sole();
            $referendum = $this->referendums->proposeElection($this->tenant('A204'), $rep, $this->tenant('B108'), 'Leah has offered to take over from Kwame as section B representative.');
            $this->on(47, '19:00', fn () => $this->votes($referendum, 0.64, 0.96, 0.02));
        });

        $this->on(52, '19:55', function () {
            $rota = Policy::where('building_id', $this->building->id)->where('title', 'Bin store volunteer rota')->sole();
            $referendum = $this->referendums->proposeRepeal($this->tenant('A401'), $rota, 'Brightwell now pay for a weekend clean of the bin store, after our email. We don\'t need the rota any more.');
            $this->on(43, '19:30', fn () => $this->votes($referendum, 0.6, 0.94, 0.04));
        });
    }

    private function correspondence(): void
    {
        $this->on(128, '20:15', function () {
            $mail = $this->mail->createInstant($this->tenant('C503'), $this->refs['list.repairs'], 'Section C lift out of service again', <<<'HTML'
                <p>Dear Brightwell,</p>
                <p>The lift in section C of Larkspur House has been out of service five times in the last month, most recently from Friday to Tuesday. A resident was trapped between floors for 20 minutes on Sunday.</p>
                <p>Tenants on the upper floors, including people with young children and limited mobility, can't leave their homes when the lift is down. Please tell us by Friday what is wrong with it and when it will be permanently fixed.</p>
                HTML);
            $this->approve($mail);
            $this->on(127, '11:20', fn () => $this->emailReply($mail, 'repairs', 'Thank you for your email. An engineer has been booked for Thursday morning to inspect the section C lift. We will update you once we have their report. Ref BW-18842.'));
            $this->on(120, '16:05', fn () => $this->emailReply($mail, 'rachel', 'Further to our earlier reply, the engineer found a worn door operator, which is the cause of the repeated faults. A replacement part is on order and will be fitted in the next three weeks. We apologise for the disruption.'));
        });

        $this->on(102, '20:00', function () {
            $mail = $this->mail->createFromBulletin($this->tenant('D307'), $this->refs['bulletin.rent'], $this->refs['list.all']);
            $this->approve($mail, rejections: 3);
            $this->on(99, '10:45', fn () => $this->emailReply($mail, 'owen', 'Thank you for your letter. The proposed rents reflect current market rates in the area. However, we have passed your concerns to the landlord and will not take any further action on the letters until we hear back.'));
            $this->on(96, '15:30', fn () => $this->emailReply($mail, 'daniel', 'Dear Larkspur House Tenants\' Union, we have asked Keystone to withdraw the rent increase letters while we review the repairs programme with Brightwell. We would be happy to meet two representatives of the union next month.'));
        });

        $this->on(62, '21:30', function () {
            $mail = $this->mail->createInstant($this->tenant('C705'), $this->refs['list.repairs'], 'Concierge hours', '<p>Dear Brightwell, we demand that the concierge is on duty 24 hours a day, starting next week, or tenants will stop paying the service charge.</p>');
            $this->reject($mail);
        });

        $this->on(55, '19:10', fn () => $this->mail->createInstant($this->tenant('A401'), $this->refs['list.repairs'], 'Mice in the section D bin store', '<p>Dear Brightwell, tenants have seen mice in the section D bin store several times this week. Please arrange pest control.</p>'));

        $this->on(40, '20:25', function () {
            $mail = $this->mail->createInstant($this->tenant('B601'), $this->refs['list.repairs'], 'Fire door closers, section C', '<p>Dear Brightwell, the fire door closers on floors 4 to 6 of section C are broken, so the doors don\'t close on their own. Please repair them as a matter of urgency.</p>');
            $this->on(40, '21:05', fn () => $this->mail->withdraw($this->tenant('B601'), $mail));
            $this->on(40, '21:10', function () {
                $mail = $this->mail->createInstant($this->tenant('B601'), $this->refs['list.repairs'], 'Fire door closers, section C (floors 4 to 6)', '<p>Dear Brightwell,</p><p>The fire door closers on floors 4, 5 and 6 of section C are broken, so the fire doors don\'t close on their own. As these doors protect the escape route, please repair them within 48 hours, as set out in our repairs policy.</p>');
                $this->approve($mail);
                $this->on(39, '09:30', fn () => $this->emailReply($mail, 'repairs', 'Thank you. A contractor will attend tomorrow to replace the closers on floors 4 to 6 of section C. Ref BW-20117.'));
            });
        });

        $this->on(21, '20:40', function () {
            $mail = $this->mail->createInstant($this->tenant('A706'), $this->refs['list.repairs'], 'Damp and mould in section D', <<<'HTML'
                <p>Dear Brightwell,</p>
                <p>Our survey of members found damp or mould in the bathrooms of 24 flats, and in the bedrooms of 15. It is worst in section D, where several tenants first reported it more than three months ago.</p>
                <p>Please arrange a damp survey of the whole building, starting with section D, and tell us when it will happen.</p>
                HTML);
            $this->approve($mail);
            $this->on(19, '14:10', fn () => $this->emailReply($mail, 'rachel', 'Thank you for sharing the results of your survey. We have instructed a surveyor to inspect all section D flats over the next fortnight, and will contact tenants directly to arrange access.'));
        });
    }

    /**
     * What's still going on: open referendums and polls, and mail waiting for
     * approval, some of it waiting for the demo visitor.
     */
    private function stillOpen(): void
    {
        $this->on(6, '20:10', function () {
            $referendum = $this->referendums->proposePolicy($this->tenant('A706'), 'Damp and mould: what we expect', 'After the survey, we should be clear about what we expect from Brightwell before winter.', <<<'HTML'
                <p>When a tenant reports damp or mould, the union expects Brightwell to:</p>
                <ul><li>inspect within <strong>7 days</strong>, and within 24 hours if a child, an older person or someone with a health condition lives there;</li><li>treat the mould and fix the cause, not just paint over it;</li><li>write to the tenant with what they found and what they'll do.</li></ul>
                HTML);
            $this->on(4, '19:00', fn () => $this->votes($referendum, 0.38, 0.9, 0.05, you: false));
            $this->on(5, '08:45', fn () => $this->reply($referendum->thread, 'D108', 'Yes please. My daughter has asthma and we\'ve waited months.'));
        });

        $this->on(3, '21:00', function () {
            $coordinator = Role::where('building_id', $this->building->id)->where('title', 'Repairs coordinator')->sole();
            $referendum = $this->referendums->proposeElection($this->tenant('A204'), $coordinator, $this->tenant('A706'), 'Marcus has asked for help: there are now more repairs than one person can chase. Ama ran the damp survey.');
            $this->on(2, '20:00', fn () => $this->votes($referendum, 0.31, 0.95, 0.03, you: false));
            $this->on(2, '20:05', fn () => $this->referendums->vote($this->tenant('C503'), $referendum, ReferendumAnswer::For));
        });

        $this->on(2, '19:40', function () {
            $current = $this->building->fresh()->currentConstitution;
            $rules = ConstitutionRules::fromArray([...$current->rules, 'referendum_quorum_percent' => 40]);
            $referendum = $this->referendums->proposeConstitution($this->tenant('B601'), 'Lower the referendum quorum to 40%', 'With over 70 members, getting half of us to vote in 10 days is hard. The social secretary vote failed on turnout, not support.', $current->rich_text, $rules);
            $this->on(1, '21:00', fn () => $this->votes($referendum, 0.22, 0.75, 0.1, you: false));
        });

        $this->on(2, '22:15', function () {
            $bulletin = $this->bulletin('A204', 'Major works: our observations', <<<'HTML'
                <p>Dear Larkspur Freehold Ltd,</p>
                <p>These are the observations of Larkspur House Tenants' Union on your notice of intention to carry out major works to the roof and windows.</p>
                <ol><li>Please replace the windows in section D first: our survey found damp in most section D flats.</li><li>Please obtain an estimate from a contractor we nominate, as well as your own.</li><li>Please tell us how tenants will be kept safe and warm while windows are replaced, and give each tenant at least two weeks' notice of work in their flat.</li></ol>
                HTML);
            $mail = $this->mail->createFromBulletin($this->tenant('A204'), $bulletin, $this->refs['list.freeholder']);
            $this->on(1, '20:00', fn () => $this->decideSome($mail, 18, 1));
        });

        $this->on(1, '19:30', fn () => $this->mail->createInstant($this->tenant('D204'), $this->refs['list.repairs'], 'Intercom in section D', '<p>Dear Brightwell, the section D intercom has been out of order for over two weeks (ref BW-20931). Delivery drivers and visitors can\'t get in. Please repair it this week.</p>'));
    }

    private function join(string $name, string $flat): Contact
    {
        $contact = Contact::create([
            'name' => $name,
            'building_id' => $this->building->id,
            'unit_id' => Unit::findOrCreateInBuilding($this->building, $flat)->id,
            'type_id' => ContactType::where('type', 'Tenant')->value('id'),
        ]);

        $email = $flat === 'C503' ? config('demo.email') : Str::slug($name, '.').'@demo.invalid';

        User::forceCreate([
            'contact_id' => $contact->id,
            'email' => $email,
            // Nobody knows it: the demo is entered from /demo.
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => now(),
        ]);

        return $this->tenants[$flat] = $contact;
    }

    private function vouch(string $voucher, string $vouchee): void
    {
        $this->vouching->vouch($this->tenant($voucher), $this->tenant($vouchee));
    }

    /**
     * The tenant of a flat as they are now in the story, e.g. since being vouched for.
     */
    private function tenant(string $flat): Contact
    {
        return $this->tenants[$flat]->fresh();
    }

    /**
     * Members at the current point in the story.
     *
     * @return Collection<int, Contact>
     */
    private function members(): Collection
    {
        return $this->building->contacts()->whereNotNull('enrolled_at')->with('unit')->get();
    }

    private function landlord(string $name, string $type, string $address, ?string $companyNumber): Landlord
    {
        return $this->building->landlords()->create([
            'name' => $name,
            'type_id' => LandlordType::where('type', $type)->value('id'),
            'address' => $address,
            'company_number' => $companyNumber,
        ]);
    }

    private function bulletin(string $author, string $title, string $html): Bulletin
    {
        return $this->building->bulletins()->create([
            'author_id' => $this->tenant($author)->id,
            'title' => $title,
            'rich_text' => preg_replace('/>\s+</', '><', trim($html)),
            'published_at' => now(),
        ]);
    }

    private function reply(Bulletin|Thread $on, string $author, string $text): void
    {
        $on->replies()->create([
            'author_id' => $this->tenant($author)->id,
            'rich_text' => RichText::fromPlainText($text),
            'published_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $answers
     * @param  int  $closesInDays  Negative for a poll that's still open
     */
    private function poll(Bulletin $bulletin, string $title, array $answers, int $closesInDays, bool $multiple = false): Poll
    {
        return $this->polls->create(
            $bulletin->author,
            $bulletin,
            $title,
            null,
            $answers,
            $multiple,
            $closesInDays > 0 ? now()->addDays($closesInDays) : $this->now->copy()->addDays(-$closesInDays),
        );
    }

    /**
     * Cast ballots for each answer, in order. Multiple-choice polls take
     * several answers from the same voters. $vote is the demo visitor's
     * answers, or null to leave the poll for them.
     *
     * @param  list<int>  $counts
     * @param  list<int>|null  $vote
     */
    private function pollVotes(Poll $poll, array $counts, ?array $vote = null): void
    {
        $answers = $poll->answers()->orderBy('position')->pluck('id')->all();
        $voters = $this->members()->reject(fn (Contact $member) => $member->unit->name === 'C503')->shuffle()->values();

        // Early on there were fewer members than the story's numbers.
        $most = $poll->allows_multiple ? max($counts) : array_sum($counts);
        $scale = min(1, $voters->count() * 0.85 / $most);
        $counts = array_map(fn (int $count) => (int) round($count * $scale), $counts);

        if ($poll->allows_multiple) {
            $ballots = $voters->take(max($counts))->map(fn () => []);

            foreach ($counts as $index => $count) {
                foreach ($ballots->keys()->shuffle()->take($count) as $key) {
                    $ballots[$key] = [...$ballots[$key], $answers[$index]];
                }
            }

            $ballots->filter()->each(fn (array $choices, int $key) => $this->polls->vote($voters[$key], $poll, $choices));
        } else {
            $voter = 0;

            foreach ($counts as $index => $count) {
                for ($cast = 0; $cast < $count; $cast++) {
                    $this->polls->vote($voters[$voter++], $poll, [$answers[$index]]);
                }
            }
        }

        if ($vote) {
            $this->polls->vote($this->tenant('C503'), $poll, array_map(fn (int $index) => $answers[$index], $vote));
        }
    }

    /**
     * Ballots from $turnout of members: $for of the rest for, $abstain
     * abstaining, and the remainder against. Unless $you is false, the demo
     * visitor votes with the majority.
     */
    private function votes(Referendum $referendum, float $turnout, float $for, float $abstain, bool $you = true): void
    {
        $members = $this->members()->reject(fn (Contact $member) => $member->unit->name === 'C503');
        $voters = $members->shuffle()->take((int) round(($members->count() + 1) * $turnout) - ($you ? 1 : 0))->values();
        $abstaining = (int) round($voters->count() * $abstain);
        $forCount = (int) round(($voters->count() - $abstaining) * $for);

        foreach ($voters as $index => $voter) {
            $answer = match (true) {
                $index < $forCount => ReferendumAnswer::For,
                $index < $forCount + $abstaining => ReferendumAnswer::Abstain,
                default => ReferendumAnswer::Against,
            };

            $this->referendums->vote($voter, $referendum, $answer);
        }

        if ($you) {
            $this->referendums->vote($this->tenant('C503'), $referendum, $for >= 0.5 ? ReferendumAnswer::For : ReferendumAnswer::Against);
        }
    }

    /**
     * Approve until it's sent, with some approvers rejecting first.
     */
    private function approve(UnionMail $mail, int $rejections = 0): void
    {
        foreach ($mail->approvers()->with('contact')->get()->shuffle() as $approver) {
            $mail = $mail->fresh();

            if (! $mail->isPending()) {
                return;
            }

            $decision = $rejections-- > 0 ? ApprovalDecision::Reject : ApprovalDecision::Approve;
            $this->mail->decide($approver->contact, $mail, $decision);
        }
    }

    private function reject(UnionMail $mail): void
    {
        foreach ($mail->approvers()->with('contact')->get() as $approver) {
            if (! $mail->fresh()->isPending()) {
                return;
            }

            $this->mail->decide($approver->contact, $mail, ApprovalDecision::Reject);
        }
    }

    /**
     * Some approvals on mail that stays pending, leaving the demo visitor's
     * decision open.
     */
    private function decideSome(UnionMail $mail, int $approvals, int $rejections): void
    {
        $approvers = $mail->approvers()->with('contact.unit')->get()
            ->reject(fn ($approver) => $approver->contact->unit?->name === 'C503')
            ->shuffle()
            ->values();

        foreach ($approvers->take($approvals + $rejections) as $index => $approver) {
            $this->mail->decide($approver->contact, $mail, $index < $rejections ? ApprovalDecision::Reject : ApprovalDecision::Approve);
        }
    }

    private function emailReply(UnionMail $mail, string $from, string $text): void
    {
        $contact = $this->refs["contact.{$from}"];

        $mail->fresh()->bulletin->replies()->create([
            'union_mail_id' => $mail->id,
            'contact_id' => $contact->id,
            'from_name' => $contact->name,
            'from_email' => $contact->email,
            'message_id' => '<'.Str::uuid().'@demo.invalid>',
            'title' => 'Re: '.$mail->subject,
            'rich_text' => RichText::fromPlainText($text),
            'published_at' => now(),
        ]);
    }
}
