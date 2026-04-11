<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedLog extends Model
{
    protected $fillable = ['file_name', 'status'];

    public function users()
    {
        return $this->belongsTo(User::class);
    }
}
