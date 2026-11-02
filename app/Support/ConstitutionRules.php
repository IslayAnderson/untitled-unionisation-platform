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
    ];

    public function __construct(
        public int $minSignups,
        public int $vouchesRequired,
        public int $referendumQuorumPercent,
        public int $referendumPassPercent,
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
        ];
    }
}
