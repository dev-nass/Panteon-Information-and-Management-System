<?php

namespace App\Repositories;

use App\Models\BurialRecord;
use Illuminate\Database\Eloquent\Model;

class BurialRecordRepository extends Repository
{
    public function __construct(BurialRecord $model)
    {
        return parent::__construct($model);
    }

    public function getBurialRecordsWithFilters(
        string $sortField,
        string $sortDirection,
        string|null $search,
        string $filter
    ) {
        return $this->query()->with(['deceasedRecord', 'lot', 'user'])

            ->leftJoin('deceased_records', 'burial_records.deceased_record_id', '=', 'deceased_records.id')
            ->select('burial_records.*')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->whereRaw("CONCAT(deceased_records.first_name, ' ', deceased_records.last_name) like ?", ["%{$search}%"])
                        ->orWhereRaw("CONCAT(deceased_records.first_name, ' ', deceased_records.middle_name, ' ', deceased_records.last_name) like ?", ["%{$search}%"])
                        ->orWhere('deceased_records.first_name', 'like', "%{$search}%")
                        ->orWhere('deceased_records.middle_name', 'like', "%{$search}%")
                        ->orWhere('deceased_records.last_name', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'buried', function ($q) {
                $q->whereNotNull('deceased_records.date_of_depository');
            })
            ->when($filter === 'pending', function ($q) {
                $q->whereNull('deceased_records.date_of_depository');
            })
            ->when($filter === 'assigned', function ($q) {
                $q->whereNotNull('burial_records.lot_id');
            })
            ->when($filter === 'unassigned', function ($q) {
                $q->whereNull('burial_records.lot_id');
            })
            ->orderBy(
                str_starts_with($sortField, 'deceased_')
                ? 'deceased_records.' . str_replace('deceased_', '', $sortField)
                : "burial_records.$sortField",
                $sortDirection
            );
    }

    public function createBurialRecord(int $deceasedId, int $lotId, int $createdBy): Model
    {
        return $this->create([
            'deceased_record_id' => $deceasedId,
            'lot_id' => $lotId,
            'user_id' => $createdBy,
        ]);
    }


    public function updateBurialRecord(Model $burialRecord, int $lotId, int $updatedBy): bool
    {
        return $this->update($burialRecord, [
            'lot_id' => $lotId,
            'user_id' => $updatedBy,
        ]);
    }
}
