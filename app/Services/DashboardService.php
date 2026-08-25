<?php

namespace App\Services;

use App\Models\BurialRecord;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public const AGE_BUCKETS = [
        '0-12' => [0, 12],
        '13-19' => [13, 19],
        '20-39' => [20, 39],
        '40-59' => [40, 59],
        '60-74' => [60, 74],
        '75+' => [75, PHP_INT_MAX],
    ];

    public function getTotalStats(array $filters = []): array
    {
        $totalLots = Lot::count();
        $occupiedLots = Lot::has('burialRecords')->count();

        $burialQuery = BurialRecord::query();
        $this->applyDeceasedJoinAndFilters($burialQuery, $filters);

        return [
            'total_burial_records' => $burialQuery->count(),
            'total_lots' => $totalLots,
            'occupied_lots' => $occupiedLots,
            'available_lots' => $totalLots - $occupiedLots,
        ];
    }

    public function getDisposalStats(array $filters = []): array
    {
        $query = DeceasedRecord::query();
        $this->applyDeceasedTableFilters($query, $filters);

        $disposalStats = $query->select('corpse_disposal', DB::raw('count(*) as count'))
            ->groupBy('corpse_disposal')
            ->get()
            ->pluck('count', 'corpse_disposal')
            ->toArray();

        return [
            'burial' => $disposalStats['burial'] ?? 0,
            'cremation' => $disposalStats['cremation'] ?? 0,
        ];
    }

    public function getActivityData(string $filter, int $year, array $filters = []): array
    {
        $now = Carbon::now();

        if ($filter === 'today') {
            $query = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('HOUR(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereDate('deceased_records.date_of_depository', $now->toDateString())
                ->groupBy('period')
                ->orderBy('period');

            $this->applyDeceasedTableFilters($query, $filters, 'deceased_records');

            $data = $query->get()->pluck('count', 'period')->toArray();

            $labels = range(0, 23);
            $values = array_map(fn ($hour) => $data[$hour] ?? 0, $labels);
            $labels = array_map(fn ($hour) => sprintf('%02d:00', $hour), $labels);
        } elseif ($filter === 'weekly') {
            $query = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('DATE(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereBetween('deceased_records.date_of_depository', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                ->groupBy('period')
                ->orderBy('period');

            $this->applyDeceasedTableFilters($query, $filters, 'deceased_records');

            $data = $query->get()->pluck('count', 'period')->toArray();

            $labels = [];
            $values = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $now->copy()->startOfWeek()->addDays($i);
                $dateStr = $date->toDateString();
                $labels[] = $date->format('D');
                $values[] = $data[$dateStr] ?? 0;
            }
        } elseif ($filter === 'yearly') {
            $query = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('MONTH(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereYear('deceased_records.date_of_depository', $year)
                ->groupBy('period')
                ->orderBy('period');

            $this->applyDeceasedTableFilters($query, $filters, 'deceased_records');

            $data = $query->get()->pluck('count', 'period')->toArray();

            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $values = array_map(fn ($month) => $data[$month] ?? 0, range(1, 12));
        } else {
            $query = BurialRecord::join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
                ->select(
                    DB::raw('DAY(deceased_records.date_of_depository) as period'),
                    DB::raw('count(*) as count')
                )
                ->whereYear('deceased_records.date_of_depository', $now->year)
                ->whereMonth('deceased_records.date_of_depository', $now->month)
                ->groupBy('period')
                ->orderBy('period');

            $this->applyDeceasedTableFilters($query, $filters, 'deceased_records');

            $data = $query->get()->pluck('count', 'period')->toArray();

            $labels = range(1, $now->daysInMonth);
            $values = array_map(fn ($day) => $data[$day] ?? 0, $labels);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    public function getAgeDistribution(array $filters = []): array
    {
        $ageExpr = $this->computedAgeExpression('deceased_records');

        $buckets = collect(self::AGE_BUCKETS)->map(fn ($range, $label) => [
            'label' => $label,
            'min' => $range[0],
            'max' => $range[1],
        ])->values();

        $selects = [];
        foreach ($buckets as $bucket) {
            if ($bucket['max'] === PHP_INT_MAX) {
                $selects[] = "SUM(CASE WHEN {$ageExpr} >= {$bucket['min']} THEN 1 ELSE 0 END) as `{$bucket['label']}`";
            } else {
                $selects[] = "SUM(CASE WHEN {$ageExpr} BETWEEN {$bucket['min']} AND {$bucket['max']} THEN 1 ELSE 0 END) as `{$bucket['label']}`";
            }
        }
        $selects[] = "SUM(CASE WHEN {$ageExpr} IS NULL THEN 1 ELSE 0 END) as `Unknown`";

        $query = BurialRecord::query();
        $this->applyDeceasedJoinAndFilters($query, $filters);

        $row = $query->selectRaw(implode(', ', $selects))->first();

        $labels = [...array_column($buckets->toArray(), 'label'), 'Unknown'];
        $values = array_map(fn ($label) => (int) ($row->$label ?? 0), $labels);

        return ['labels' => $labels, 'values' => $values];
    }

    public function getGeographicDistribution(array $filters = [], int $limit = 10): array
    {
        $query = DeceasedRecord::query();
        $this->applyDeceasedTableFilters($query, $filters);

        $rows = $query->select('address', DB::raw('count(*) as cnt'))
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->where('address', '!=', 'None')
            ->groupBy('address')
            ->get();

        $normalized = $rows->groupBy(fn ($row) => $this->normalizeBarangay($row->address))
            ->map(fn ($group) => [
                'count' => $group->sum('cnt'),
                'display' => $this->mostCommonAddress($group->pluck('address')),
            ])
            ->sortByDesc('count')
            ->values();

        $top = $normalized->take($limit);
        $othersCount = $normalized->slice($limit)->sum('count');

        $labels = $top->pluck('display')->toArray();
        $values = $top->pluck('count')->toArray();

        if ($othersCount > 0) {
            $labels[] = 'Others';
            $values[] = $othersCount;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getFilterOptions(): array
    {
        $barangays = DeceasedRecord::whereNotNull('address')
            ->where('address', '!=', '')
            ->where('address', '!=', 'None')
            ->pluck('address')
            ->groupBy(fn ($addr) => $this->normalizeBarangay($addr))
            ->map(fn ($group) => $this->mostCommonAddress($group))
            ->values()
            ->sort()
            ->values()
            ->toArray();

        return ['barangays' => $barangays];
    }

    public function normalizeBarangay(string $address): string
    {
        $result = strtolower(trim($address));

        $result = preg_replace('/^\s*(brgy\.?\s*|barangay\s+)/i', '', $result);

        $romans = [
            'xii' => '12', 'xiii' => '13', 'xiv' => '14', 'xv' => '15',
            'xi' => '11', 'ix' => '9', 'x' => '10',
            'viii' => '8', 'vii' => '7', 'vi' => '6',
            'iv' => '4', 'iii' => '3', 'ii' => '2', 'v' => '5', 'i' => '1',
        ];

        foreach ($romans as $roman => $digit) {
            $result = preg_replace("/\b{$roman}\b/i", $digit, $result);
        }

        $result = preg_replace('/\s+/', ' ', $result);

        return trim($result);
    }

    private function computedAgeExpression(string $table = 'deceased_records'): string
    {
        return "COALESCE({$table}.age, TIMESTAMPDIFF(YEAR, {$table}.date_of_birth, {$table}.date_of_death))";
    }

    /**
     * Apply filters to a query that already has `deceased_records` accessible
     * (either via join or direct model query).
     */
    private function applyDeceasedTableFilters(Builder $query, array $filters, string $table = 'deceased_records'): void
    {
        if (! empty($filters['age_range']) && isset(self::AGE_BUCKETS[$filters['age_range']])) {
            $range = self::AGE_BUCKETS[$filters['age_range']];
            $ageExpr = $this->computedAgeExpression($table);

            if ($range[1] === PHP_INT_MAX) {
                $query->whereRaw("{$ageExpr} >= ?", [$range[0]]);
            } else {
                $query->whereRaw("{$ageExpr} BETWEEN ? AND ?", [$range[0], $range[1]]);
            }
        }

        if (! empty($filters['barangay'])) {
            $matchingAddresses = $this->getRawAddressesForBarangay($filters['barangay']);

            if ($matchingAddresses->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn("{$table}.address", $matchingAddresses->values()->toArray());
            }
        }
    }

    /**
     * Join deceased_records to a BurialRecord query and apply filters.
     */
    private function applyDeceasedJoinAndFilters(Builder $query, array $filters): void
    {
        $query->join('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id');

        if (! empty($filters['year'])) {
            $query->whereYear('deceased_records.date_of_depository', (int) $filters['year']);
        }

        $this->applyDeceasedTableFilters($query, $filters, 'deceased_records');
    }

    private function getRawAddressesForBarangay(string $normalizedBarangay): Collection
    {
        return DeceasedRecord::whereNotNull('address')
            ->where('address', '!=', '')
            ->where('address', '!=', 'None')
            ->get()
            ->filter(fn ($record) => $this->normalizeBarangay($record->address) === strtolower($normalizedBarangay))
            ->pluck('address')
            ->unique();
    }

    private function mostCommonAddress(Collection $addresses): string
    {
        return $addresses->groupBy(fn ($addr) => strtolower(trim($addr)))
            ->map(fn ($group) => ['address' => $group->first(), 'count' => $group->count()])
            ->sortByDesc('count')
            ->first()['address'] ?? '';
    }
}
