<?php

namespace App\Helpers;

final class ProgressHelper
{
    /**
     * @param array<string, mixed> $body
     */
    public static function getProgressFromNoteBody(array $body): ?float
    {
        $progress = null;
        if (!empty($body['blocks'])) {
            $totalCheckboxes = 0;
            $checkedCheckboxes = 0;
            foreach ($body['blocks'] as $block) {
                if (isset($block['type']) && $block['type'] === 'checklist') {
                    if (isset($block['data']['items']) && is_array($block['data']['items'])) {
                        foreach ($block['data']['items'] as $note) {
                            $totalCheckboxes++;
                            if (isset($note['checked']) && $note['checked'] === true) {
                                $checkedCheckboxes++;
                            }
                        }
                    }
                }
            }
            if ($totalCheckboxes > 0) {
                $progress = round(($checkedCheckboxes / $totalCheckboxes) * 100);
            }
        }
        return $progress;
    }
}
