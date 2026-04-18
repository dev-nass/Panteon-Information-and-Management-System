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
