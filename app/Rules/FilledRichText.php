<?php

namespace App\Rules;

use App\Support\RichText;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Lang;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator;

/**
 * Rich text with some visible text once cleaned, not just empty paragraphs. Fails with the field's
 * "required" message, since to the person filling it in the box is empty.
 */
class FilledRichText implements ValidationRule, ValidatorAwareRule
{
    private Validator $validator;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! RichText::isBlank(RichText::clean($value))) {
            return;
        }

        $custom = "validation.custom.{$attribute}.required";

        $fail($this->validator->customMessages["{$attribute}.required"] ?? (Lang::has($custom) ? $custom : 'validation.required'))->translate();
    }
}
