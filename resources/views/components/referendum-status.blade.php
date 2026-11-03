@props(['referendum'])

@php($outcome = $referendum->outcome)
<span @class([
    'rounded px-1.5 py-0.5 text-xs font-medium',
    'bg-sky-100 text-sky-800' => ! $outcome,
    'bg-emerald-100 text-emerald-800' => $outcome === App\Enums\ReferendumOutcome::Passed,
    'bg-red-100 text-red-800' => $outcome === App\Enums\ReferendumOutcome::Rejected,
    'bg-stone-100 text-stone-700' => in_array($outcome, [App\Enums\ReferendumOutcome::NoQuorum, App\Enums\ReferendumOutcome::Withdrawn], true),
])>{{ $outcome?->label() ?? ($referendum->isOpen() ? 'Voting open' : 'Being counted') }}</span>
