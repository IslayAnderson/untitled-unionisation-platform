@props(['referendum'])

{{-- The Tag component, coloured by outcome. --}}
@php($outcome = $referendum->outcome)
<strong {{ $attributes->class([
    'govuk-tag',
    'govuk-tag--green' => $outcome === App\Enums\ReferendumOutcome::Passed,
    'govuk-tag--red' => $outcome === App\Enums\ReferendumOutcome::Rejected,
    'govuk-tag--grey' => in_array($outcome, [App\Enums\ReferendumOutcome::NoQuorum, App\Enums\ReferendumOutcome::Withdrawn], true),
]) }}>{{ $outcome?->label() ?? ($referendum->isOpen() ? 'Voting open' : 'Being counted') }}</strong>
