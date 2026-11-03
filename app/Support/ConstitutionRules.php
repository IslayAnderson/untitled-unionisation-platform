<?php

namespace App\Support;

/**
 * The machine-readable rules stored on a constitution (constitutions.rules).
 */
final readonly class ConstitutionRules
{
    public const DEFAULTS = [
        'min_signups' => 3,
        'vouches_required' => 2,
        'referendum_quorum_percent' => 50,
        'referendum_pass_percent' => 50,
        'referendum_days' => 14,
        'removal_pass_percent' => 50,
        'instant_mail_approval_percent' => 10,
        'instant_mail_approver_percent' => 20,
        'instant_mail_expiry_hours' => 48,
        'bulletin_mail_approval_percent' => 50,
        'bulletin_mail_expiry_hours' => 168,
    ];

    /**
     * How each rule is described and the range members can set it to by amendment.
     *
     * @var array<string, array{label: string, unit: string, min: int, max: int}>
     */
    public const FIELDS = [
        'min_signups' => ['label' => 'Tenants needed before vouching starts', 'unit' => 'tenants', 'min' => 2, 'max' => 50],
        'vouches_required' => ['label' => 'Vouches needed to become a member', 'unit' => 'vouches', 'min' => 1, 'max' => 10],
        'referendum_quorum_percent' => ['label' => 'Referendum quorum: members who must vote', 'unit' => '%', 'min' => 1, 'max' => 100],
        'referendum_pass_percent' => ['label' => 'Referendum passes with more than this share of for and against votes', 'unit' => '%', 'min' => 50, 'max' => 99],
        'referendum_days' => ['label' => 'Referendums are open for', 'unit' => 'days', 'min' => 1, 'max' => 60],
        'removal_pass_percent' => ['label' => 'Removing a member passes with more than this share of for and against votes', 'unit' => '%', 'min' => 50, 'max' => 99],
        'instant_mail_approval_percent' => ['label' => 'Instant mail: members who must approve', 'unit' => '%', 'min' => 1, 'max' => 100],
        'instant_mail_approver_percent' => ['label' => 'Instant mail: members asked to approve', 'unit' => '%', 'min' => 1, 'max' => 100],
        'instant_mail_expiry_hours' => ['label' => 'Instant mail expires after', 'unit' => 'hours', 'min' => 1, 'max' => 336],
        'bulletin_mail_approval_percent' => ['label' => 'Bulletin mail: members who must approve', 'unit' => '%', 'min' => 1, 'max' => 100],
        'bulletin_mail_expiry_hours' => ['label' => 'Bulletin mail expires after', 'unit' => 'hours', 'min' => 24, 'max' => 720],
    ];

    public function __construct(
        public int $minSignups,
        public int $vouchesRequired,
        public int $referendumQuorumPercent,
        public int $referendumPassPercent,
        public int $referendumDays,
        public int $removalPassPercent,
        public int $instantMailApprovalPercent,
        public int $instantMailApproverPercent,
        public int $instantMailExpiryHours,
        public int $bulletinMailApprovalPercent,
        public int $bulletinMailExpiryHours,
    ) {}

    /**
     * @param  array<string, mixed>  $rules
     */
    public static function fromArray(array $rules): self
    {
        $rules = array_merge(self::DEFAULTS, $rules);

        return new self(
            minSignups: (int) $rules['min_signups'],
            vouchesRequired: (int) $rules['vouches_required'],
            referendumQuorumPercent: (int) $rules['referendum_quorum_percent'],
            referendumPassPercent: (int) $rules['referendum_pass_percent'],
            referendumDays: (int) $rules['referendum_days'],
            removalPassPercent: (int) $rules['removal_pass_percent'],
            instantMailApprovalPercent: (int) $rules['instant_mail_approval_percent'],
            instantMailApproverPercent: (int) $rules['instant_mail_approver_percent'],
            instantMailExpiryHours: (int) $rules['instant_mail_expiry_hours'],
            bulletinMailApprovalPercent: (int) $rules['bulletin_mail_approval_percent'],
            bulletinMailExpiryHours: (int) $rules['bulletin_mail_expiry_hours'],
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'min_signups' => $this->minSignups,
            'vouches_required' => $this->vouchesRequired,
            'referendum_quorum_percent' => $this->referendumQuorumPercent,
            'referendum_pass_percent' => $this->referendumPassPercent,
            'referendum_days' => $this->referendumDays,
            'removal_pass_percent' => $this->removalPassPercent,
            'instant_mail_approval_percent' => $this->instantMailApprovalPercent,
            'instant_mail_approver_percent' => $this->instantMailApproverPercent,
            'instant_mail_expiry_hours' => $this->instantMailExpiryHours,
            'bulletin_mail_approval_percent' => $this->bulletinMailApprovalPercent,
            'bulletin_mail_expiry_hours' => $this->bulletinMailExpiryHours,
        ];
    }

    /**
     * Validation rules for amending these, under the given input key.
     *
     * @return array<string, list<string>>
     */
    public static function validationRules(string $key = 'rules'): array
    {
        $rules = [$key => ['required', 'array']];

        foreach (self::FIELDS as $name => $field) {
            $rules["{$key}.{$name}"] = ['required', 'integer', "min:{$field['min']}", "max:{$field['max']}"];
        }

        return $rules;
    }
}
