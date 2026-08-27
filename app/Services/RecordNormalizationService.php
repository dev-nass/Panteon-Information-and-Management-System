<?php

namespace App\Services;

use Carbon\Carbon;

class RecordNormalizationService
{
    /**
     * Normalize an address: trim, title-case, collapse whitespace.
     */
    public function normalizeAddress(?string $address): ?string
    {
        if ($address === null || trim($address) === '') {
            return null;
        }

        $normalized = strtolower(trim($address));
        $normalized = ucwords($normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Normalize a name: trim, title-case, collapse whitespace.
     */
    public function normalizeName(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $normalized = strtolower(trim($name));
        $normalized = ucwords($normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Split a full name string into first, middle, and last name components.
     *
     * @return array{first_name: ?string, middle_name: ?string, last_name: ?string}
     */
    public function parseFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $parts = array_map(fn ($part) => $this->normalizeName($part) ?? '', $parts);
        $count = count($parts);

        if ($count === 0) {
            return ['first_name' => null, 'middle_name' => null, 'last_name' => null];
        }

        if ($count === 1) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => null];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = ! empty($parts) ? implode(' ', $parts) : null;

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
        ];
    }

    /**
     * Parse a date value (Excel serial or string) into Y-m-d format.
     */
    public function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            if (is_numeric($date)) {
                $unixDate = ($date - 25569) * 86400;

                return date('Y-m-d', $unixDate);
            }

            $timestamp = strtotime($date);

            return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Compute age from date of birth and date of death.
     * Falls back to explicitAge when dates are unavailable.
     */
    public function computeAge(?string $dateOfBirth, ?string $dateOfDeath, ?int $explicitAge = null): ?int
    {
        if ($dateOfBirth !== null && $dateOfDeath !== null) {
            $birth = Carbon::parse($dateOfBirth);
            $death = Carbon::parse($dateOfDeath);

            return $birth->diffInYears($death);
        }

        return $explicitAge;
    }
}
