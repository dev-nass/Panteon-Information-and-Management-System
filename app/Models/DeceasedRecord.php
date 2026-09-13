<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeceasedRecord extends Model
{
    use HasFactory;

    protected $appends = ['age'];

    protected $fillable = [
        'applicant_id',
        'first_name',
        'middle_name',
        'last_name',
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

    protected function age(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if (! $this->date_of_birth) {
                    return null;
                }

                $birth = Carbon::parse($this->date_of_birth);

                if ($this->date_of_death) {
                    return (int) $birth->diffInYears(Carbon::parse($this->date_of_death));
                }

                return (int) $birth->diffInYears(Carbon::now());
            },
        );
    }

    public function burialRecords(): HasOne
    {
        return $this->hasOne(BurialRecord::class);
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
