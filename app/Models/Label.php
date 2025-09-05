<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'warna',
    ];

    /**
     * Get the mitras that belong to this label.
     */
    public function mitras()
    {
        return $this->hasMany(Mitra::class);
    }
}
