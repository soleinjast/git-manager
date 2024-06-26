<?php

namespace App\Services;

class GitService
{
    public function isMeaningfulPatch(string $patch): bool
    {
        // Split the patch into lines
        $lines = explode("\n", $patch);

        $addedLines = [];
        $removedLines = [];

        foreach ($lines as $line) {
            // Check for lines that are added
            if (str_starts_with($line, '+')) {
                $addedLines[] = substr($line, 1);
            }
            // Check for lines that are removed
            if (str_starts_with($line, '-')) {
                $removedLines[] = substr($line, 1);
            }
        }
        // Remove whitespace for comparison
        $normalize = function($lines) {
            return array_map(function($line) {
                return preg_replace('/\s+/', '', $line);
            }, $lines);
        };

        $normalizedAdded = $normalize($addedLines);
        $normalizedRemoved = $normalize($removedLines);

        // If there are no added or removed lines after filtering, return false
        if (empty($normalizedAdded) && empty($normalizedRemoved)) {
            return false;
        }

        // Combine the added and removed lines into single strings for comparison
        $combinedAdded = implode("", $normalizedAdded);
        $combinedRemoved = implode("", $normalizedRemoved);

        // If the combined strings are different, the change is meaningful
        return $combinedAdded !== $combinedRemoved;
    }

}
