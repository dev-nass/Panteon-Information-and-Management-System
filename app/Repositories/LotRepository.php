<?php

namespace App\Repositories;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Model;

class LotRepository extends Repository
{
    public function __construct(Lot $model)
    {
        return parent::__construct($model);
    }

    /**
     * Description: Used before the createBurialRecord on BurialRecordService.
     * A lot may be shared by multiple burial records, so this only resolves it.
     */
    public function findLotForAssignment(int $lotId): Model
    {
        $lot = $this->find($lotId);

        if (! $lot) {
            throw new \Exception('Lot not found');
        }

        return $lot;

    }
}
