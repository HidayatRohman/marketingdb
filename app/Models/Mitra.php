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
        'tanggal_lead',
        'brand_id',
        'label_id',
        'chat',
        'kota',
        'provinsi',
        'komentar',
    ];

    protected $casts = [
        'tanggal_lead' => 'date',
        // Removed transaksi cast since we're removing the field
    ];

    /**
     * Get the brand that owns the mitra.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the label that belongs to the mitra.
     */
    public function label()
    {
        return $this->belongsTo(Label::class);
    }
}
