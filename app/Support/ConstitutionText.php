<?php

namespace App\Support;

/**
 * Constitution text refers to its numbers with placeholders such as
 * {vouches_required}, so the text always matches the rules the platform
 * follows. Unknown placeholders are left as they are.
 */
final class ConstitutionText
{
    public static function render(string $html, ConstitutionRules $rules): string
    {
        $values = $rules->toArray();

        return preg_replace_callback(
            '/\{([a-z_]+)\}/',
            fn (array $match) => array_key_exists($match[1], $values) ? (string) $values[$match[1]] : $match[0],
            $html,
        );
    }

    /**
     * Each placeholder and what it shows, for the amendment form.
     *
     * @return array<string, string>
     */
    public static function placeholders(): array
    {
        return collect(ConstitutionRules::FIELDS)
            ->mapWithKeys(fn (array $field, string $name) => ["{{$name}}" => $field['label'].($field['unit'] === '%' ? ' (%)' : " ({$field['unit']})")])
            ->all();
    }
}
