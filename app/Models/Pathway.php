<?php

namespace App\Models;

use Database\Factories\PathwayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pathway extends Model
{
    /** @use HasFactory<PathwayFactory> */
    use HasFactory;

    protected $fillable = [
        'from_junction_id',
        'to_junction_id',
        'distance_meters',
        'coordinates',
    ];

    protected $hidden = [
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
