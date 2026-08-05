<?php

declare(strict_types=1);

namespace App\Traits\DTO;

trait NormalizesLocalizedPayload
{
    /**
     * @return list<array<string, string>>
     */
    protected static function normalizeTranslationRows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $rawRow) {
            if (! is_array($rawRow)) {
                continue;
            }

            $locale = $rawRow['locale'] ?? $rawRow['language_code'] ?? null;
            if (! is_scalar($locale) || trim((string) $locale) === '') {
                continue;
            }

            $row = ['locale' => trim((string) $locale)];
            $fields = $rawRow['fields'] ?? null;
            if (is_array($fields)) {
                $rawRow = $fields;
            }

            foreach ($rawRow as $field => $fieldValue) {
                if (! is_string($field) || in_array($field, ['locale', 'language_code', 'fields'], true)) {
                    continue;
                }
                if (is_scalar($fieldValue)) {
                    $row[$field] = (string) $fieldValue;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    protected static function normalizeLocalized(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $localized = [];
        foreach ($value as $field => $fieldValue) {
            if (is_string($field) && is_scalar($fieldValue)) {
                $localized[$field] = (string) $fieldValue;
            }
        }

        return $localized;
    }
}
