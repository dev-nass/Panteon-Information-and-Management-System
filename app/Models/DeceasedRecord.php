<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeceasedRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
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
        'time_of_depository',
        'company_address',
        'company_supervisor_name',
        'father_name',
        'mother_maiden_name',
        'burial_place',
        'part_of_LGBTQ',
        'precinct_num',
    ];

    protected static function booted(): void
    {
        static::saving(function (DeceasedRecord $record) {
            $record->computeAge();
        });
    }

    public function burialRecords(): HasOne
    {
        return $this->hasOne(BurialRecord::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    private function computeAge(): void
    {
        $birth = $this->date_of_birth ? Carbon::parse($this->date_of_birth) : null;
        $death = $this->date_of_death ? Carbon::parse($this->date_of_death) : null;

        if ($birth && $death) {
            $this->age = $birth->diffInYears($death);
        } elseif ($birth) {
            $this->age = $birth->diffInYears(Carbon::now());
        } else {
            $this->age = null;
        }
    }
}
