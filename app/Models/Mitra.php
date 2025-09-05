<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'no_telp',
        'brand_id',
        'chat',
        'kota',
        'provinsi',
        'transaksi',
        'komentar',
    ];

    protected $casts = [
        'transaksi' => 'decimal:2',
    ];

    /**
     * Get the brand that owns the mitra.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
