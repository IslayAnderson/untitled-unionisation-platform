<?php

namespace App\Support;

/**
 * A paragraph-by-paragraph comparison of two rich texts, so members can see
 * what an amendment changes before they vote.
 */
final class TextDiff
{
    /**
     * @return list<array{type: 'same'|'added'|'removed', text: string}>
     */
    public static function paragraphs(string $oldHtml, string $newHtml): array
    {
        $old = self::split($oldHtml);
        $new = self::split($newHtml);
        $oldCount = count($old);
        $newCount = count($new);

        // Longest common subsequence table, filled from the end.
        $lengths = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));
        for ($i = $oldCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $old[$i] === $new[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $diff = [];
        $i = $j = 0;
        while ($i < $oldCount || $j < $newCount) {
            if ($i < $oldCount && $j < $newCount && $old[$i] === $new[$j]) {
                $diff[] = ['type' => 'same', 'text' => $old[$i++]];
                $j++;
            } elseif ($j < $newCount && ($i === $oldCount || $lengths[$i][$j + 1] >= $lengths[$i + 1][$j])) {
                $diff[] = ['type' => 'added', 'text' => $new[$j++]];
            } else {
                $diff[] = ['type' => 'removed', 'text' => $old[$i++]];
            }
        }

        return $diff;
    }

    /**
     * @return list<string>
     */
    private static function split(string $html): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\n{2,}/', RichText::toPlainText($html)) ?: []),
            fn (string $paragraph) => $paragraph !== '',
        ));
    }
}
