@props(['mail'])

{{-- The Tag component, coloured by status. --}}
<strong {{ $attributes->class([
    'govuk-tag',
    'govuk-tag--green' => $mail->status === App\Enums\UnionMailStatus::Sent,
    'govuk-tag--red' => $mail->status === App\Enums\UnionMailStatus::Rejected,
    'govuk-tag--grey' => in_array($mail->status, [App\Enums\UnionMailStatus::Expired, App\Enums\UnionMailStatus::Withdrawn], true),
]) }}>{{ $mail->status->label() }}</strong>
