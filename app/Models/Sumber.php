<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'warna',
    ];

    /**
     * Get the transaksis that belong to this sumber.
     */
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}
