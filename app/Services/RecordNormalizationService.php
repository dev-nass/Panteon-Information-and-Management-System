<?php

namespace App\Services;

use App\Models\DeceasedRecord;
use App\Models\Lot;
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
     */
    public function computeAge(?string $dateOfBirth, ?string $dateOfDeath): ?int
    {
        if ($dateOfBirth !== null && $dateOfDeath !== null) {
            $birth = Carbon::parse($dateOfBirth);
            $death = Carbon::parse($dateOfDeath);

            return (int) $birth->diffInYears($death);
        }

        if ($dateOfBirth !== null) {
            return (int) Carbon::parse($dateOfBirth)->diffInYears(Carbon::now());
        }

        return null;
    }

    /**
     * Check if a deceased record with the same name and dates already exists.
     */
    public function findDuplicateDeceased(
        string $firstName,
        string $lastName,
        ?string $dateOfBirth,
        ?string $dateOfDeath,
        ?string $dateOfDepository = null,
        ?int $excludeId = null
    ): ?DeceasedRecord {
        $query = DeceasedRecord::where('first_name', $firstName)
            ->where('last_name', $lastName);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $hasDateMatch = false;

        $query->where(function ($q) use ($dateOfBirth, $dateOfDeath, $dateOfDepository, &$hasDateMatch) {
            if ($dateOfBirth !== null) {
                $q->where('date_of_birth', $dateOfBirth);
                $hasDateMatch = true;
            }

            if ($dateOfDeath !== null) {
                if ($hasDateMatch) {
                    $q->orWhere('date_of_death', $dateOfDeath);
                } else {
                    $q->where('date_of_death', $dateOfDeath);
                    $hasDateMatch = true;
                }
            }

            if ($dateOfDepository !== null) {
                if ($hasDateMatch) {
                    $q->orWhere('date_of_depository', $dateOfDepository);
                } else {
                    $q->where('date_of_depository', $dateOfDepository);
                    $hasDateMatch = true;
                }
            }
        });

        return $query->first();
    }

    /**
     * Parse APT. number into column and row.
     * Handles Phase 1A underground compound rows E1/E2.
     * e.g. "1e1" => ["1","E1"], "10E2" => ["10","E2"], "5E" => ["5","E"], "12D" => ["12","D"]
     *
     * @return array{0: string, 1: string} [column, row]
     */
    public function parseAptNumber(string $aptNumber): array
    {
        $aptNumber = trim($aptNumber);

        if ($aptNumber === '') {
            return ['', ''];
        }

        if (preg_match('/^(\d+)([A-Za-z]+\d*)$/', $aptNumber, $matches)) {
            return [$matches[1], strtoupper($matches[2])];
        }

        $column = preg_replace('/\D/', '', $aptNumber);
        $rowLetter = strtoupper(preg_replace('/\d/', '', $aptNumber));

        return [$column, $rowLetter];
    }

    /**
     * Normalize Excel cluster encoding. Underground clusters are prefixed with UG (e.g. UG8S, UG 3N, UG-3N, ug8s)
     * and should map to cluster_name without prefix with type hint underground. Plain "8S" maps to apartment.
     *
     * @return array{0: string, 1: string|null} [normalizedClusterName, clusterTypeHint]
     */
    public function normalizeClusterName(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['', null];
        }

        if (preg_match('/^ULT\s*[-_]?\s*(.+)$/i', $raw, $m)) {
            $name = trim($m[1]);
            $name = preg_replace('/[\s\-_]+/', '', $name);
            $name = strtoupper($name);
            $name = preg_replace('/^I(?=[0-9NS])/', '1', $name);
            $name = preg_replace('/^LT/', '1', $name);
            $name = preg_replace('/^0+(\d)/', '$1', $name);

            return [$name, 'underground'];
        }

        if (preg_match('/^UG\s*[-_]?\s*(.+)$/i', $raw, $m)) {
            $name = trim($m[1]);
            $name = preg_replace('/[\s\-_]+/', '', $name);
            $name = strtoupper($name);
            $name = preg_replace('/^I(?=[0-9NS])/', '1', $name);
            $name = preg_replace('/^LT/', '1', $name);
            $name = preg_replace('/^0+(\d)/', '$1', $name);

            return [$name, 'underground'];
        }

        $name = strtoupper($raw);
        $name = preg_replace('/[\s\-_]+/', '', $name);
        $name = preg_replace('/^0+(\d)/', '$1', $name);

        return [$name, null];
    }

    /**
     * Normalize phase encoding from Excel. Handles variants like PH3, Ph1b, PH-2, P1A, 1-A, H3.
     */
    public function normalizePhaseName(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $n = strtoupper($raw);
        $n = preg_replace('/[\s\-_\.]+/', '', $n);
        $n = preg_replace('/^(PH|P|H)+/', '', $n);

        return $n;
    }

    /**
     * Build typed and generic lot maps for fast lookup.
     *
     * @param  iterable<Lot>  $lots
     * @return array{0: array<string, Lot>, 1: array<string, Lot>} [typedMap, genericMap]
     */
    public function buildLotMaps(iterable $lots): array
    {
        $lotsMap = [];
        $lotsMapGeneric = [];

        foreach ($lots as $lot) {
            $phaseKey = strtoupper($lot->cluster->phase->phase_name);
            $clusterKey = strtoupper($lot->cluster->cluster_name);
            $typeKey = strtolower($lot->cluster->cluster_type ?? '');
            $rowKey = strtoupper($lot->row);
            $colKey = $lot->column;
            $typedKey = $phaseKey.'|'.$clusterKey.'|'.$typeKey.'|'.$rowKey.'|'.$colKey;
            $lotsMap[$typedKey] = $lot;

            $genericKey = $phaseKey.'|'.$clusterKey.'|'.$rowKey.'|'.$colKey;
            if (! isset($lotsMapGeneric[$genericKey])) {
                $lotsMapGeneric[$genericKey] = $lot;
            }
        }

        return [$lotsMap, $lotsMapGeneric];
    }

    /**
     * Find lot using typed map; respects UG hint and falls back gracefully.
     *
     * @param  array<string, Lot>  $lotsMap
     * @param  array<string, Lot>  $lotsMapGeneric
     * @param  array<int, bool>  $usedLotIds
     */
    public function findLotByClusterAndApt(array $lotsMap, array $lotsMapGeneric, array $usedLotIds, string $phaseKey, string $clusterName, ?string $typeHint, string $rowLetter, string $column): ?Lot
    {
        $phaseCandidates = [$phaseKey];
        if ($phaseKey === '1') {
            $phaseCandidates = ['1A', '1B'];
        }

        foreach ($phaseCandidates as $pk) {
            if ($typeHint !== null) {
                $key = $pk.'|'.$clusterName.'|'.$typeHint.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMap[$key] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }

                $genericKey = $pk.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMapGeneric[$genericKey] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }

                continue;
            }

            foreach (['apartment', 'underground'] as $type) {
                $key = $pk.'|'.$clusterName.'|'.$type.'|'.$rowLetter.'|'.$column;
                $candidate = $lotsMap[$key] ?? null;
                if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                    return $candidate;
                }
            }

            $genericKey = $pk.'|'.$clusterName.'|'.$rowLetter.'|'.$column;
            $candidate = $lotsMapGeneric[$genericKey] ?? null;
            if ($candidate && ! isset($usedLotIds[$candidate->id])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Build dedup key for file-level duplicate detection.
     */
    public function buildDedupKey(string $firstName, string $lastName, ?string $dateOfBirth, ?string $dateOfDeath, ?string $dateOfDepository): string
    {
        return strtolower(trim($firstName)).'|'.strtolower(trim($lastName)).'|'.($dateOfBirth ?? '').'|'.($dateOfDeath ?? '').'|'.($dateOfDepository ?? '');
    }

    /**
     * Check if current row duplicates a previously seen entry in the same file.
     * Mirrors findDuplicateDeceased OR logic for file-level duplicates.
     *
     * @param  array<string, int>  $seenKeys  [dedupKey => rowNumber]
     * @return int|null duplicate row number if found
     */
    public function findFileDuplicate(array $seenKeys, string $firstName, string $lastName, ?string $dateOfBirth, ?string $dateOfDeath, ?string $dateOfDepository): ?int
    {
        $firstKey = strtolower(trim($firstName));
        $lastKey = strtolower(trim($lastName));

        if ($firstKey === '' || $lastKey === '') {
            return null;
        }

        $dedupKey = $this->buildDedupKey($firstName, $lastName, $dateOfBirth, $dateOfDeath, $dateOfDepository);

        if (isset($seenKeys[$dedupKey])) {
            return $seenKeys[$dedupKey];
        }

        foreach ($seenKeys as $seenKey => $seenRow) {
            [$sFirst, $sLast, $sBirth, $sDeath, $sDep] = array_pad(explode('|', $seenKey, 5), 5, '');
            if ($sFirst !== $firstKey || $sLast !== $lastKey) {
                continue;
            }

            $birthMatch = $dateOfBirth && $dateOfBirth === $sBirth;
            $deathMatch = $dateOfDeath && $dateOfDeath === $sDeath;
            $depMatch = $dateOfDepository && $dateOfDepository === $sDep;

            if ($birthMatch || $deathMatch || $depMatch) {
                return $seenRow;
            }
        }

        return null;
    }
}
