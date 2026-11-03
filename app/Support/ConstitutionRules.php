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
        'instant_mail_approval_percent' => 10,
        'instant_mail_approver_percent' => 20,
        'instant_mail_expiry_hours' => 48,
        'bulletin_mail_approval_percent' => 50,
        'bulletin_mail_expiry_hours' => 168,
    ];

    public function __construct(
        public int $minSignups,
        public int $vouchesRequired,
        public int $referendumQuorumPercent,
        public int $referendumPassPercent,
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
            'instant_mail_approval_percent' => $this->instantMailApprovalPercent,
            'instant_mail_approver_percent' => $this->instantMailApproverPercent,
            'instant_mail_expiry_hours' => $this->instantMailExpiryHours,
            'bulletin_mail_approval_percent' => $this->bulletinMailApprovalPercent,
            'bulletin_mail_expiry_hours' => $this->bulletinMailExpiryHours,
        ];
    }
}
