<?php

namespace App\Models;

use Database\Factories\JunctionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Junction extends Model
{
    /** @use HasFactory<JunctionFactory> */
    use HasFactory;

    protected $fillable = [
        'junction_number',
        'type',
        'coordinates',
        'label',
    ];

    protected $hidden = [
        'coordinates',
    ];
}
