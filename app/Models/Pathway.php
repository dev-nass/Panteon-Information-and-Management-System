<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pathway extends Model
{
    /** @use HasFactory<\Database\Factories\PathwayFactory> */
    use HasFactory;

    protected $fillable = [
        'from_junction_id',
        'to_junction_id',
        'distance_meters',
        'coordinates',
    ];

    public function fromJunction()
    {
        return $this->belongsTo(Junction::class, 'from_junction_id');
    }

    public function toJunction()
    {
        return $this->belongsTo(Junction::class, 'to_junction_id');
    }
}
