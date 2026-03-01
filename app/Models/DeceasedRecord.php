<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeceasedRecord extends Model
{
    use HasFactory;

    //
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'age',
        'date_of_birth',
        'date_of_death',
        'cause_of_death',
        'place_of_death',
        'civil_status',
        'religion',
        'nationality',
        'address',
        'occupation',
        'corpse_disposal',
        'cremation_place',
        'cremation_date',
        'date_of_depository',
        'company_address',
        'company_supervisor_name',
        'father_name',
        'mother_maiden_name',
        'burial_place',
        'part_of_LGBTQ',
        'precinct_num',
    ];

    public function burial_records(): HasMany
    {
        return $this->hasMany(BurialRecord::class);
    }
}
